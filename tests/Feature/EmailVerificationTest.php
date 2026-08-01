<?php

namespace Tests\Feature;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function register_sends_verification_email_and_leaves_unverified(): void
    {
        Mail::fake();
        $this->seedCore();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jai',
            'email' => 'jai@azshrtr.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accepted_terms' => true,
        ])->assertCreated()
            ->assertJsonPath('user.email', 'jai@azshrtr.com')
            ->assertJsonPath('user.email_verified_at', null);

        Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail): bool {
            return $mail->hasTo('jai@azshrtr.com');
        });

        $this->assertNull(User::query()->where('email', 'jai@azshrtr.com')->value('email_verified_at'));
    }

    #[Test]
    public function guest_can_verify_with_email_link_token(): void
    {
        Mail::fake();
        $this->seedCore();

        $user = User::factory()->unverified()->create([
            'email' => 'verify-link@azshrtr.com',
        ]);

        $issued = app(EmailVerificationService::class)->issue($user);

        $this->postJson('/api/v1/auth/email/verify', [
            'token' => $issued['token'],
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'verify-link@azshrtr.com')
            ->assertJsonPath('message', 'Email verified.');

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotNull($user->fresh()?->email_verified_at);
    }

    #[Test]
    public function authenticated_user_can_verify_with_code(): void
    {
        Mail::fake();
        $this->seedCore();

        $user = User::factory()->unverified()->create([
            'email' => 'code@azshrtr.com',
        ]);

        $issued = app(EmailVerificationService::class)->issue($user);

        $this->actingAs($user)
            ->postJson('/api/v1/auth/email/verify', [
                'code' => $issued['code'],
            ])
            ->assertOk();

        $this->assertNotNull($user->fresh()?->email_verified_at);
    }

    #[Test]
    public function resend_confirmation_issues_new_mail(): void
    {
        Mail::fake();
        $this->seedCore();

        $user = User::factory()->unverified()->create([
            'email' => 'resend@azshrtr.com',
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/auth/email/resend-confirmation')
            ->assertOk()
            ->assertJsonPath('message', 'Confirmation email sent.')
            ->assertJsonStructure(['csrf_token']);

        Mail::assertSent(VerifyEmailMail::class);
    }

    #[Test]
    public function login_resends_verification_when_unverified(): void
    {
        Mail::fake();
        $this->seedCore();

        $user = User::factory()->unverified()->create([
            'email' => 'login-verify@azshrtr.com',
            'password' => 'Password123!',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk()
            ->assertJsonPath('user.email_verified_at', null);

        Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });
    }
}
