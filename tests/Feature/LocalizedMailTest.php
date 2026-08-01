<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Mail\SignInActivityMail;
use App\Mail\UsageAlertMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WelcomeMail;
use App\Models\OrganizationUsageMonth;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use App\Services\Billing\UsageAlertNotifier;
use App\Services\OrganizationService;
use App\Services\Usage\UsageTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalizedMailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function register_persists_preferred_locale_and_localizes_verify_mail(): void
    {
        Mail::fake();
        $this->seedCore();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ana',
            'email' => 'ana@azshrtr.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accepted_terms' => true,
            'preferred_locale' => 'es',
        ])
            ->assertCreated()
            ->assertJsonPath('user.preferred_locale', 'es');

        Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail): bool {
            App::setLocale('es');

            return $mail->hasTo('ana@azshrtr.com')
                && $mail->envelope()->subject === __('mail.verify_email.subject');
        });
    }

    #[Test]
    public function verifying_email_sends_welcome_mail(): void
    {
        Mail::fake();
        $this->seedCore();

        $user = User::factory()->unverified()->create([
            'email' => 'welcome@azshrtr.com',
            'preferred_locale' => 'en',
        ]);

        $issued = app(EmailVerificationService::class)->issue($user);
        Mail::fake();

        $this->postJson('/api/v1/auth/email/verify', [
            'token' => $issued['token'],
        ])->assertOk();

        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });
    }

    #[Test]
    public function second_login_sends_sign_in_activity_mail(): void
    {
        Mail::fake();
        $this->seedCore();

        $user = User::factory()->create([
            'email' => 'signin@azshrtr.com',
            'password' => 'Password123!',
            'last_login_at' => now()->subDay(),
            'last_login_ip' => '1.2.3.4',
            'preferred_locale' => 'en',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk();

        Mail::assertSent(SignInActivityMail::class, function (SignInActivityMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });
    }

    #[Test]
    public function password_reset_uses_localized_mailable(): void
    {
        Mail::fake();
        $this->seedCore();

        $user = User::factory()->create([
            'email' => 'reset@azshrtr.com',
            'preferred_locale' => 'de',
        ]);

        Password::sendResetLink(['email' => $user->email]);

        Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $mail): bool {
            App::setLocale('de');

            return $mail->hasTo('reset@azshrtr.com')
                && $mail->envelope()->subject === __('mail.password_reset.subject');
        });
    }

    #[Test]
    public function usage_alerts_fire_at_configured_thresholds(): void
    {
        Mail::fake();
        $this->seedCore();
        Config::set('billing.enabled', true);
        Config::set('azshrtr.usage_alerts.thresholds', [89, 90, 100]);

        $user = User::factory()->create([
            'email' => 'owner@azshrtr.com',
            'preferred_locale' => 'en',
        ]);
        $org = app(OrganizationService::class)->createForUser($user, 'Usage Org');

        $period = app(UsageTracker::class)->currentPeriod();
        OrganizationUsageMonth::query()->updateOrCreate(
            [
                'organization_id' => $org->id,
                'period' => $period,
            ],
            [
                'links_created' => 2670,
                'qr_generated' => 0,
                'api_calls' => 0,
                'alerts' => null,
            ],
        );

        app(UsageAlertNotifier::class)->checkAfterIncrement($org->fresh());

        Mail::assertSent(UsageAlertMail::class, function (UsageAlertMail $mail) use ($user): bool {
            return $mail->hasTo($user->email) && $mail->kind === 'warning' && $mail->threshold === 89;
        });
    }
}
