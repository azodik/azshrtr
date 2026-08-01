<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthSessionApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_me_profile_password_and_logout_work(): void
    {
        $this->seedCore();

        $user = User::factory()->create([
            'email' => 'session@azshrtr.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'session@azshrtr.com',
            'password' => 'Password123!',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'session@azshrtr.com')
            ->assertJsonStructure(['user', 'csrf_token']);

        $this->assertAuthenticatedAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->patchJson('/api/v1/auth/profile', [
            'name' => 'Updated Name',
            'theme_preference' => 'dark',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.theme_preference', 'dark');

        $this->putJson('/api/v1/auth/password', [
            'current_password' => 'Password123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertOk();

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('ok', true);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }

    #[Test]
    public function forgot_and_reset_password_flow(): void
    {
        $this->seedCore();
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset@azshrtr.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@azshrtr.com',
        ])->assertOk();

        Mail::assertSent(PasswordResetMail::class);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@azshrtr.com',
            'password' => 'ResetPassword123!',
            'password_confirmation' => 'ResetPassword123!',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'reset@azshrtr.com',
            'password' => 'ResetPassword123!',
        ])->assertOk();
    }

    #[Test]
    public function mfa_and_passkey_status_endpoints_require_auth(): void
    {
        $this->seedCore();

        $this->getJson('/api/v1/auth/mfa')->assertUnauthorized();
        $this->getJson('/api/v1/auth/passkeys')->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->getJson('/api/v1/auth/mfa')
            ->assertOk()
            ->assertJsonStructure(['mfa_enabled', 'recovery_codes_remaining']);

        $this->getJson('/api/v1/auth/passkeys')
            ->assertOk()
            ->assertJsonStructure(['passkeys']);
    }

    #[Test]
    public function passkey_registration_options_store_binary_challenge_not_hex_cast(): void
    {
        $this->seedCore();

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/v1/auth/passkeys/register/options');
        $response->assertOk()->assertJsonStructure(['publicKey' => ['challenge']]);

        $challengeB64Url = $response->json('publicKey.challenge');
        $this->assertIsString($challengeB64Url);

        $fromOptions = base64_decode(
            strtr($challengeB64Url, '-_', '+/').str_repeat('=', (4 - strlen($challengeB64Url) % 4) % 4),
            true,
        );
        $this->assertNotFalse($fromOptions);

        $stored = session('webauthn_register_challenge');
        $this->assertIsString($stored);

        $fromSession = base64_decode($stored, true);
        $this->assertNotFalse($fromSession);
        $this->assertSame($fromOptions, $fromSession);
        // Regression: casting ByteBuffer to string yields hex and broke processCreate.
        $this->assertNotSame(bin2hex($fromOptions), $stored);
    }
}
