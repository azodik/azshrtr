<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (filter_var(env('APP_FORCE_URL', false), FILTER_VALIDATE_BOOLEAN)) {
            URL::forceRootUrl((string) config('app.url'));

            $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME);
            if (is_string($scheme) && $scheme !== '') {
                URL::forceScheme($scheme);
            }
        }

        ResetPassword::createUrlUsing(function (mixed $notifiable, string $token): string {
            $email = $notifiable instanceof User ? $notifiable->email : '';

            return rtrim((string) config('app.url'), '/').'/console/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $email,
            ]);
        });
    }
}
