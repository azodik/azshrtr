<?php

namespace App\Services\Mail;

use App\Models\User;
use App\Support\SupportedLocale;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class LocalizedMailer
{
    public function localeFor(?User $user, ?string $fallback = null): string
    {
        if ($user !== null && filled($user->preferred_locale)) {
            return SupportedLocale::normalize($user->preferred_locale);
        }

        if ($fallback !== null) {
            return SupportedLocale::normalize($fallback);
        }

        return SupportedLocale::fromRequest();
    }

    public function send(string $to, MailableContract $mailable, string $locale): void
    {
        $previous = App::getLocale();
        App::setLocale(SupportedLocale::normalize($locale));

        try {
            Mail::to($to)->send($mailable);
        } finally {
            App::setLocale($previous);
        }
    }

    public function sendToUser(User $user, MailableContract $mailable, ?string $fallbackLocale = null): void
    {
        $this->send(
            $user->email,
            $mailable,
            $this->localeFor($user, $fallbackLocale),
        );
    }
}
