<?php

namespace App\Services\Auth;

use App\Mail\SignInActivityMail;
use App\Models\User;
use App\Services\Mail\LocalizedMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SignInNotifier
{
    public function __construct(private readonly LocalizedMailer $mailer) {}

    public function recordSuccessfulLogin(User $user, Request $request): void
    {
        $previousLoginAt = $user->last_login_at;

        $ip = $request->ip() ?? '';
        $userAgent = (string) $request->userAgent();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip !== '' ? $ip : null,
        ])->save();

        // Skip the very first recorded login (welcome covers account creation).
        if ($previousLoginAt === null) {
            return;
        }

        $locale = $this->mailer->localeFor($user);
        $device = $this->summarizeDevice($userAgent, $locale);
        $ipLabel = $ip !== ''
            ? $ip
            : (string) __('mail.sign_in_activity.unknown_ip');

        $this->mailer->sendToUser(
            $user,
            new SignInActivityMail(
                user: $user,
                signedInAt: Carbon::now()->timezone(config('app.timezone'))->toDayDateTimeString(),
                ipAddress: $ipLabel,
                device: $device,
                secureUrl: rtrim((string) config('app.url'), '/').'/console/settings',
            ),
        );
    }

    private function summarizeDevice(string $userAgent, string $locale): string
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            if ($userAgent === '') {
                return (string) __('mail.sign_in_activity.unknown_device');
            }

            $ua = strtolower($userAgent);
            $browser = match (true) {
                str_contains($ua, 'edg/') => 'Edge',
                str_contains($ua, 'chrome/') && ! str_contains($ua, 'edg/') => 'Chrome',
                str_contains($ua, 'firefox/') => 'Firefox',
                str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/') => 'Safari',
                default => 'Browser',
            };
            $os = match (true) {
                str_contains($ua, 'windows') => 'Windows',
                str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
                str_contains($ua, 'android') => 'Android',
                str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
                str_contains($ua, 'linux') => 'Linux',
                default => 'Unknown OS',
            };

            return "{$browser} · {$os}";
        } finally {
            app()->setLocale($previous);
        }
    }
}
