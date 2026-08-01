<?php

namespace App\Services\Auth;

use App\Enums\AuditAction;
use App\Models\MfaRecoveryCode;
use App\Models\User;
use App\Models\UserMfaSettings;
use App\Services\Audit\AuditLogger;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class MfaService
{
    private const RECOVERY_CODE_COUNT = 10;

    public function __construct(
        private readonly Google2FA $google2fa,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{secret: string, otpauth_url: string, qr_svg: string}
     */
    public function beginSetup(User $user): array
    {
        $settings = $user->mfaSettings;
        if ($settings?->enabled) {
            throw ValidationException::withMessages([
                'mfa' => ['Authenticator MFA is already enabled.'],
            ]);
        }

        $secret = $this->google2fa->generateSecretKey(32);

        DB::transaction(function () use ($user, $secret): void {
            MfaRecoveryCode::query()->where('user_id', $user->id)->delete();
            UserMfaSettings::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'secret' => $secret,
                    'enabled' => false,
                    'confirmed_at' => null,
                ],
            );
        });

        $otpauthUrl = $this->google2fa->getQRCodeUrl(
            (string) config('app.name', 'azshrtr'),
            $user->email,
            $secret,
        );

        return [
            'secret' => $secret,
            'otpauth_url' => $otpauthUrl,
            'qr_svg' => $this->qrSvg($otpauthUrl),
        ];
    }

    /**
     * @return list<string>
     */
    public function confirmSetup(User $user, string $code): array
    {
        $settings = UserMfaSettings::query()->where('user_id', $user->id)->first();
        if ($settings?->enabled) {
            throw ValidationException::withMessages([
                'mfa' => ['Authenticator MFA is already enabled.'],
            ]);
        }

        $secret = $settings?->secret;
        if (! is_string($secret) || $secret === '') {
            throw ValidationException::withMessages([
                'mfa' => ['Start authenticator setup before confirming.'],
            ]);
        }

        if (! $this->verifyTotp($secret, $code)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid authenticator code.'],
            ]);
        }

        $plainCodes = $this->generateRecoveryCodes();

        DB::transaction(function () use ($user, $settings, $plainCodes): void {
            $this->replaceRecoveryCodes($user, $plainCodes);
            $settings->forceFill([
                'enabled' => true,
                'confirmed_at' => now(),
            ])->save();
        });

        $this->audit->log(AuditAction::MfaEnabled, $user, null, 'user', (string) $user->id);

        return $plainCodes;
    }

    public function verify(User $user, string $code): bool
    {
        $settings = $user->mfaSettings ?? UserMfaSettings::query()->where('user_id', $user->id)->first();
        if ($settings === null || ! $settings->enabled) {
            return true;
        }

        $normalized = trim($code);
        $secret = $settings->secret;

        if (is_string($secret) && $secret !== '' && preg_match('/^\d{6}$/', $normalized) === 1) {
            if ($this->verifyTotp($secret, $normalized)) {
                return true;
            }
        }

        return $this->consumeRecoveryCode($user, $normalized);
    }

    /**
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user, string $code): array
    {
        $this->assertMfaEnabled($user);

        if (! $this->verify($user, $code)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid authenticator or recovery code.'],
            ]);
        }

        $plainCodes = $this->generateRecoveryCodes();
        $this->replaceRecoveryCodes($user, $plainCodes);

        return $plainCodes;
    }

    public function disable(User $user, string $code): void
    {
        $this->assertMfaEnabled($user);

        if (! $this->verify($user, $code)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid authenticator or recovery code.'],
            ]);
        }

        DB::transaction(function () use ($user): void {
            MfaRecoveryCode::query()->where('user_id', $user->id)->delete();
            UserMfaSettings::query()->where('user_id', $user->id)->delete();
        });

        $this->audit->log(AuditAction::MfaDisabled, $user, null, 'user', (string) $user->id);
    }

    public function remainingRecoveryCodeCount(User $user): int
    {
        return MfaRecoveryCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->count();
    }

    private function verifyTotp(string $secret, string $code): bool
    {
        try {
            return $this->google2fa->verifyKey($secret, $code, 1);
        } catch (\Throwable) {
            return false;
        }
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $candidate = strtoupper(str_replace([' ', '-'], '', $code));
        if (strlen($candidate) < 8) {
            return false;
        }

        $codes = MfaRecoveryCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->get();

        foreach ($codes as $recoveryCode) {
            if (Hash::check($candidate, $recoveryCode->code_hash)) {
                $recoveryCode->forceFill(['used_at' => now()])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $raw = strtoupper(Str::random(8));
            $codes[] = substr($raw, 0, 4).'-'.substr($raw, 4, 4);
        }

        return $codes;
    }

    /**
     * @param  list<string>  $plainCodes
     */
    private function replaceRecoveryCodes(User $user, array $plainCodes): void
    {
        MfaRecoveryCode::query()->where('user_id', $user->id)->delete();

        foreach ($plainCodes as $plain) {
            MfaRecoveryCode::query()->create([
                'user_id' => $user->id,
                'code_hash' => Hash::make(strtoupper(str_replace([' ', '-'], '', $plain))),
            ]);
        }
    }

    private function qrSvg(string $otpauthUrl): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($otpauthUrl);
    }

    private function assertMfaEnabled(User $user): void
    {
        $settings = $user->mfaSettings ?? UserMfaSettings::query()->where('user_id', $user->id)->first();
        if ($settings === null || ! $settings->enabled) {
            throw ValidationException::withMessages([
                'mfa' => ['Authenticator MFA is not enabled.'],
            ]);
        }
    }
}
