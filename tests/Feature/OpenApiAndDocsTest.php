<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpenApiAndDocsTest extends TestCase
{
    #[Test]
    public function openapi_spec_is_published(): void
    {
        $path = public_path('openapi.yaml');
        $this->assertFileExists($path);

        $contents = (string) file_get_contents($path);
        $this->assertStringContainsString('openapi: 3.1.0', $contents);
        $this->assertStringContainsString('/api/v1/links', $contents);
        $this->assertStringContainsString('ApiKeyAuth', $contents);
        $this->assertStringContainsString('https://azshrtr.test', $contents);
        $this->assertStringContainsString('http://localhost:8080', $contents);
        $this->assertStringContainsString('https://azshrtr.com', $contents);
        $this->assertStringContainsString('operationId: getHealth', $contents);
        $this->assertStringContainsString('x-tagGroups', $contents);
        $this->assertStringContainsString('Auth MFA', $contents);
        $this->assertStringContainsString('Product Links', $contents);

        $response = $this->get('/openapi.yaml')->assertOk();
        $body = (string) $response->getContent();
        $this->assertStringContainsString('openapi: 3.1.0', $body);
        $this->assertStringContainsString('https://azshrtr.test', $body);
        $this->assertStringContainsString('https://azshrtr.com', $body);
    }

    #[Test]
    public function api_docs_and_explorer_pages_render(): void
    {
        $this->get('/docs/api')
            ->assertOk()
            ->assertSee('OpenAPI', false)
            ->assertSee('/docs/api-explorer', false);

        $this->get('/docs/api-explorer')
            ->assertOk()
            ->assertSee('elements-api', false)
            ->assertSee('/openapi.yaml', false)
            ->assertSee('@stoplight/elements', false)
            ->assertDontSee('mkt-footer-dark', false)
            ->assertDontSee('aria-label="Primary"', false);
    }

    #[Test]
    public function faq_documents_roles_and_api_isolation(): void
    {
        $this->get('/docs/faq')
            ->assertOk()
            ->assertSee('Can one organization’s API access another', false)
            ->assertSee('What can Owner, Admin, and Member do?', false)
            ->assertSee('Checkout, cancel, or resume Pro billing', false)
            ->assertSee('How long are audit, API, and click logs kept?', false);
    }
}
