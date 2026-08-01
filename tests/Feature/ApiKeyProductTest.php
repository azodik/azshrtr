<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ApiKeys\ApiKeyService;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_bearer_api_key_can_create_link(): void
    {
        $this->seedCore();

        $user = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($user);
        $created = app(ApiKeyService::class)->create($org, $user, 'Test key');

        $response = $this->withHeader('Authorization', 'Bearer '.$created['plain_text'])
            ->postJson('/api/v1/links', [
                'destination_url' => 'https://azshrtr.com/api',
            ]);

        $response->assertCreated()
            ->assertJsonPath('link.destination_url', 'https://azshrtr.com/api');

        $this->withHeader('Authorization', 'Bearer '.$created['plain_text'])
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('organization.id', $org->id);
    }
}
