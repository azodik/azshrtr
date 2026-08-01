<?php

namespace App\Services\Auth;

use App\Enums\AuditAction;
use App\Models\Passkey;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;

class PasskeyService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function webAuthn(string $rpId, string $rpName = 'azshrtr'): WebAuthn
    {
        return new WebAuthn($rpName, $rpId, null, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function registrationOptions(User $user, string $rpId): array
    {
        $webAuthn = $this->webAuthn($rpId, (string) config('app.name', 'azshrtr'));
        $userId = substr(hash('sha256', (string) $user->id, true), 0, 32);

        $createArgs = $webAuthn->getCreateArgs(
            $userId,
            $user->email,
            $user->name,
            60,
            false,
            'preferred',
        );

        // ByteBuffer::__toString() returns hex — never cast the challenge to string.
        $this->putChallenge('webauthn_register_challenge', $webAuthn->getChallenge());
        session(['webauthn_register_user_id' => $user->id]);

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) json_encode($createArgs), true);

        return $payload;
    }

    /**
     * @param  array{id: string, rawId: string, type: string, response: array{clientDataJSON: string, attestationObject: string}}  $credential
     */
    public function register(User $user, array $credential, string $rpId, ?string $name = null): Passkey
    {
        $webAuthn = $this->webAuthn($rpId, (string) config('app.name', 'azshrtr'));
        $challenge = $this->pullChallenge('webauthn_register_challenge');

        try {
            $data = $webAuthn->processCreate(
                $this->decode($credential['response']['clientDataJSON']),
                $this->decode($credential['response']['attestationObject']),
                $challenge,
                false,
                true,
                false,
            );
        } catch (WebAuthnException $exception) {
            throw ValidationException::withMessages([
                'passkey' => [$exception->getMessage()],
            ]);
        }

        session()->forget(['webauthn_register_user_id']);

        $passkey = Passkey::query()->create([
            'user_id' => $user->id,
            'name' => $name ?: 'Passkey',
            'credential_id' => $credential['id'],
            'public_key' => $data->credentialPublicKey,
            // Platform authenticators (Touch ID, etc.) often omit the counter.
            'sign_count' => (int) ($data->signatureCounter ?? 0),
            'attestation_format' => $data->attestationFormat ?? null,
            'aaguid' => isset($data->AAGUID) ? bin2hex((string) $data->AAGUID) : null,
        ]);

        $this->audit->log(AuditAction::PasskeyRegistered, $user, null, 'passkey', $passkey->id);

        return $passkey;
    }

    /**
     * @return array<string, mixed>
     */
    public function authenticationOptions(string $rpId, ?string $email = null): array
    {
        $webAuthn = $this->webAuthn($rpId, (string) config('app.name', 'azshrtr'));
        $ids = [];

        if ($email !== null && $email !== '') {
            $user = User::query()->where('email', Str::lower($email))->first();
            if ($user !== null) {
                foreach ($user->passkeys as $passkey) {
                    $ids[] = $this->decode($passkey->credential_id);
                }
            }
        }

        $getArgs = $webAuthn->getGetArgs($ids, 60, true, true, true, true, true, 'preferred');

        $this->putChallenge('webauthn_login_challenge', $webAuthn->getChallenge());

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) json_encode($getArgs), true);

        return $payload;
    }

    /**
     * @param  array{id: string, rawId: string, type: string, response: array{clientDataJSON: string, authenticatorData: string, signature: string, userHandle?: string|null}}  $credential
     */
    public function authenticate(array $credential, string $rpId): User
    {
        $webAuthn = $this->webAuthn($rpId, (string) config('app.name', 'azshrtr'));
        $challenge = $this->pullChallenge('webauthn_login_challenge');

        $passkey = Passkey::query()->where('credential_id', $credential['id'])->first();

        if ($passkey === null) {
            throw ValidationException::withMessages([
                'passkey' => ['Unknown passkey.'],
            ]);
        }

        try {
            $webAuthn->processGet(
                $this->decode($credential['response']['clientDataJSON']),
                $this->decode($credential['response']['authenticatorData']),
                $this->decode($credential['response']['signature']),
                $passkey->public_key,
                $challenge,
                $passkey->sign_count,
                false,
                true,
            );
        } catch (WebAuthnException $exception) {
            throw ValidationException::withMessages([
                'passkey' => [$exception->getMessage()],
            ]);
        }

        $passkey->update([
            'sign_count' => $passkey->sign_count + 1,
            'last_used_at' => now(),
        ]);

        $user = $passkey->user;
        if ($user === null || ! $user->is_active) {
            throw ValidationException::withMessages([
                'passkey' => ['Passkey account is inactive.'],
            ]);
        }

        Auth::login($user, false);
        request()->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->save();

        return $user;
    }

    public function delete(User $user, string $passkeyId): void
    {
        $passkey = Passkey::query()
            ->where('user_id', $user->id)
            ->whereKey($passkeyId)
            ->firstOrFail();

        $passkey->delete();
        $this->audit->log(AuditAction::PasskeyDeleted, $user, null, 'passkey', $passkeyId);
    }

    private function decode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw ValidationException::withMessages([
                'passkey' => ['Invalid passkey payload encoding.'],
            ]);
        }

        return $decoded;
    }

    /**
     * Persist the raw challenge bytes (base64). Casting ByteBuffer to string yields hex via
     * __toString() and breaks processCreate/processGet ("invalid challenge").
     */
    private function putChallenge(string $key, ByteBuffer $challenge): void
    {
        session([$key => base64_encode($challenge->getBinaryString())]);
    }

    private function pullChallenge(string $key): string
    {
        $encoded = session()->pull($key);

        if (! is_string($encoded) || $encoded === '') {
            throw ValidationException::withMessages([
                'passkey' => ['Passkey challenge expired. Try again.'],
            ]);
        }

        $binary = base64_decode($encoded, true);

        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages([
                'passkey' => ['Passkey challenge expired. Try again.'],
            ]);
        }

        return $binary;
    }
}
