<?php

namespace App\Services\Auth;

use App\Mail\VerifyEmailMail;
use App\Mail\WelcomeMail;
use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Services\Mail\LocalizedMailer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public function __construct(private readonly LocalizedMailer $mailer) {}

    /**
     * @return array{token: string, code: string}
     */
    public function issue(User $user): array
    {
        $token = Str::random(64);
        $code = (string) random_int(100000, 999999);

        EmailVerificationToken::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        EmailVerificationToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addHours(24),
        ]);

        $verifyUrl = rtrim((string) config('app.url'), '/').'/console/verify-email?token='.$token;

        $this->mailer->sendToUser(
            $user,
            new VerifyEmailMail($user, $verifyUrl, $code),
        );

        return ['token' => $token, 'code' => $code];
    }

    public function verifyToken(string $token): User
    {
        $record = EmailVerificationToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->latest()
            ->first();

        if ($record === null || ! $record->isValid()) {
            throw ValidationException::withMessages([
                'token' => ['This verification link is invalid or has expired.'],
            ]);
        }

        return $this->consume($record);
    }

    public function verifyCode(User $user, string $code): User
    {
        $record = EmailVerificationToken::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if ($record === null || ! $record->isValid() || $record->code_hash === null) {
            throw ValidationException::withMessages([
                'code' => ['This verification code is invalid or has expired.'],
            ]);
        }

        if (! Hash::check($code, $record->code_hash)) {
            throw ValidationException::withMessages([
                'code' => ['Incorrect verification code.'],
            ]);
        }

        return $this->consume($record);
    }

    private function consume(EmailVerificationToken $record): User
    {
        $wasUnverified = $record->user->email_verified_at === null;
        $record->update(['consumed_at' => now()]);
        $user = $record->user;
        $user->forceFill(['email_verified_at' => now()])->save();
        $user = $user->fresh() ?? $user;

        if ($wasUnverified) {
            $this->mailer->sendToUser(
                $user,
                new WelcomeMail(
                    $user,
                    rtrim((string) config('app.url'), '/').'/console',
                ),
            );
        }

        return $user;
    }
}
