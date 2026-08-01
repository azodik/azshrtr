<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function marketing_404_renders_branded_page(): void
    {
        $this->get('/docs/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found', false)
            ->assertSee('azshrtr', false)
            ->assertSee('Error 404', false)
            ->assertSee('Back to azshrtr', false);
    }

    #[Test]
    public function console_path_404_view_points_back_to_console(): void
    {
        $this->app->instance('request', Request::create('/console/missing-page', 'GET'));

        $html = view('errors.404')->render();

        $this->assertStringContainsString('Back to console', $html);
        $this->assertStringContainsString('Error 404', $html);
    }

    #[Test]
    public function api_unknown_route_returns_json_404(): void
    {
        $this->getJson('/api/v1/this-route-does-not-exist-'.uniqid())
            ->assertNotFound()
            ->assertHeader('content-type', 'application/json')
            ->assertJson([
                'message' => 'The requested resource was not found.',
            ])
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('trace');
    }
}
