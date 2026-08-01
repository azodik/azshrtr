<?php

namespace Tests\Feature;

use App\Enums\ApiKeyScope;
use App\Models\LinkClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    #[Test]
    public function health_is_public(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('service', 'azshrtr')
            ->assertJsonPath('checks.database.ok', true)
            ->assertJsonStructure([
                'ok',
                'service',
                'checks' => [
                    'database' => ['ok'],
                ],
            ]);
    }

    #[Test]
    public function missing_or_invalid_api_key_is_rejected(): void
    {
        $this->seedCore();

        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Missing API key.');

        $this->withHeader('Authorization', 'Bearer az_test_invalid')
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid API key.');
    }

    #[Test]
    public function product_api_supports_full_link_lifecycle_and_clicks(): void
    {
        $this->createOrganizationContext();

        $this->withApiKey()
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('organization.id', $this->apiOrganization->id)
            ->assertJsonStructure([
                'organization' => ['id', 'name', 'slug'],
                'plan' => ['slug', 'name'],
                'usage' => ['period', 'links_created', 'qr_generated', 'api_calls'],
            ]);

        $create = $this->withApiKey()
            ->postJson('/api/v1/links', [
                'destination_url' => 'https://azshrtr.com/product',
                'title' => 'Product link',
            ])
            ->assertCreated()
            ->assertJsonPath('link.destination_url', 'https://azshrtr.com/product')
            ->assertJsonPath('link.title', 'Product link');

        $linkId = (string) $create->json('link.id');

        $this->withApiKey()
            ->getJson('/api/v1/links')
            ->assertOk()
            ->assertJsonPath('data.0.id', $linkId);

        $this->withApiKey()
            ->getJson('/api/v1/links/'.$linkId)
            ->assertOk()
            ->assertJsonPath('link.id', $linkId);

        $this->withApiKey()
            ->patchJson('/api/v1/links/'.$linkId, [
                'title' => 'Updated title',
                'is_disabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('link.title', 'Updated title')
            ->assertJsonPath('link.is_disabled', true);

        LinkClick::query()->create([
            'link_id' => $linkId,
            'organization_id' => $this->apiOrganization->id,
            'clicked_at' => now(),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'phpunit',
            'country' => 'IN',
            'referrer' => null,
        ]);

        $this->withApiKey()
            ->getJson('/api/v1/links/'.$linkId.'/clicks')
            ->assertOk()
            ->assertJsonPath('data.0.link_id', $linkId);

        $this->withApiKey()
            ->deleteJson('/api/v1/links/'.$linkId)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->withApiKey()
            ->getJson('/api/v1/links/'.$linkId)
            ->assertNotFound();
    }

    #[Test]
    public function product_api_enforces_scopes(): void
    {
        $this->createOrganizationContext([ApiKeyScope::LinksRead->value]);

        $this->withApiKey()
            ->getJson('/api/v1/me')
            ->assertOk();

        $this->withApiKey()
            ->postJson('/api/v1/links', [
                'destination_url' => 'https://azshrtr.com/denied',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Insufficient API key scope.');

        $writeOnly = $this->createOrganizationContext(
            [ApiKeyScope::LinksWrite->value],
            'Write Org',
        );

        $created = $this->withApiKey($writeOnly['plain_text'])
            ->postJson('/api/v1/links', [
                'destination_url' => 'https://azshrtr.com/write-only',
            ])
            ->assertCreated();

        $this->withApiKey($writeOnly['plain_text'])
            ->getJson('/api/v1/links/'.$created->json('link.id').'/clicks')
            ->assertForbidden();
    }
}
