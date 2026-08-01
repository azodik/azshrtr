<?php

namespace App\Services\Auth;

use App\Enums\EmailOtpPurpose;
use App\Mail\EmailOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\Mail\LocalizedMailer;
use App\Support\SupportedLocale;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailOtpService
{
    public function __construct(private readonly LocalizedMailer $mailer) {}

    public function send(
        string $email,
        EmailOtpPurpose $purpose = EmailOtpPurpose::Login,
        ?string $locale = null,
    ): void {
        $email = Str::lower(trim($email));
        $user = User::query()->where('email', $email)->first();

        // Always behave the same to avoid account enumeration.
        $code = (string) random_int(100000, 999999);

        EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        EmailOtp::query()->create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose->value,
            'user_id' => $user?->id,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        if ($user !== null && $user->is_active) {
            if ($locale !== null) {
                $user->forceFill([
                    'preferred_locale' => SupportedLocale::normalize($locale),
                ])->save();
            }

            $this->mailer->sendToUser(
                $user->fresh() ?? $user,
                new EmailOtpMail($code, $user->name),
                $locale,
            );
        }
    }

    public function verify(string $email, string $code, EmailOtpPurpose $purpose = EmailOtpPurpose::Login): User
    {
        $email = Str::lower(trim($email));

        $otp = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->orderByDesc('created_at')
            ->first();

        if ($otp === null || $otp->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['Verification code expired or not found. Request a new one.'],
            ]);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => ['Too many attempts. Request a new code.'],
            ]);
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        $user = User::query()->where('email', $email)->where('is_active', true)->first();
        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['No active account found for that email.'],
            ]);
        }

        return $user;
    }
}
