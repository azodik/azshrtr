<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Services\Domains\PlatformDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_default_organization(): void
    {
        $this->seedCore();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jai',
            'email' => 'jai@azshrtr.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accepted_terms' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'jai@azshrtr.com')
            ->assertJsonPath('user.email_verified_at', null);

        $this->assertNotEmpty($response->json('user.organizations'));
        $this->assertAuthenticated();
    }

    public function test_register_requires_accepted_terms(): void
    {
        $this->seedCore();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jai',
            'email' => 'terms@azshrtr.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accepted_terms' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['accepted_terms']);
    }

    public function test_claim_assigns_anonymous_link_to_org(): void
    {
        $this->seedCore();

        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jai',
            'email' => 'claim@azshrtr.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accepted_terms' => true,
        ])->assertCreated();

        $orgId = $register->json('user.organizations.0.id');
        $domain = app(PlatformDomain::class)->resolve();

        $link = Link::query()->create([
            'domain_id' => $domain->id,
            'code' => 'claimme',
            'destination_url' => 'https://azshrtr.com/claim',
            'is_anonymous' => true,
            'expires_at' => now()->addMinutes(20),
            'claim_token' => 'claim-token-123',
            'claim_token_expires_at' => now()->addMinutes(20),
        ]);

        $this->postJson('/api/v1/links/claim', [
            'token' => 'claim-token-123',
            'organization_id' => $orgId,
        ])->assertOk();

        $link->refresh();
        $this->assertFalse($link->is_anonymous);
        $this->assertSame($orgId, $link->organization_id);
        $this->assertNull($link->claim_token);
        $this->assertNull($link->expires_at);
    }
}
