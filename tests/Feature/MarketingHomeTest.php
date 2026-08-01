<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingHomeTest extends TestCase
{
    public function test_home_page_renders_brand_and_shortener(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('azshrtr', false);
        $response->assertSee('Shorten', false);
        $response->assertSee('destination', false);
    }

    public function test_pricing_page_renders(): void
    {
        $this->get('/pricing')->assertOk()->assertSee('Pro', false);
    }

    public function test_docs_install_page_renders(): void
    {
        $this->get('/docs/install')->assertOk()->assertSee('Install', false);
    }

    public function test_console_shell_renders(): void
    {
        $this->get('/console')->assertOk()->assertSee('console-root', false);
    }

    public function test_api_health_endpoint(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('service', 'azshrtr')
            ->assertJsonPath('checks.database.ok', true);
    }
}
