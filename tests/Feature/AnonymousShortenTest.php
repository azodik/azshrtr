<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Services\Domains\PlatformDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnonymousShortenTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shorten_creates_anonymous_link(): void
    {
        $this->seedCore();

        $response = $this->from('/')->post(route('shorten'), [
            'url' => 'https://azshrtr.com/path',
        ]);

        $response->assertRedirect(route('home'))
            ->assertSessionHas('shorten.short_url')
            ->assertSessionHas('shorten.claim_url')
            ->assertSessionHas('shorten.qr_svg');

        $this->assertDatabaseHas('links', [
            'is_anonymous' => true,
            'destination_url' => 'https://azshrtr.com/path',
        ]);
    }

    public function test_homepage_shorten_returns_json_without_redirect(): void
    {
        $this->seedCore();

        $response = $this->postJson(route('shorten'), [
            'url' => 'https://azshrtr.com/ajax',
        ]);

        $response->assertOk()
            ->assertJsonPath('shorten.destination_url', 'https://azshrtr.com/ajax')
            ->assertJsonStructure([
                'shorten' => ['short_url', 'destination_url', 'expires_at', 'claim_url', 'qr_svg'],
            ]);

        $this->assertDatabaseHas('links', [
            'is_anonymous' => true,
            'destination_url' => 'https://azshrtr.com/ajax',
        ]);
    }

    public function test_redirect_works_and_records_click(): void
    {
        $this->seedCore();

        $domain = app(PlatformDomain::class)->resolve();

        $link = Link::query()->create([
            'domain_id' => $domain->id,
            'code' => 'abc1234',
            'destination_url' => 'https://azshrtr.com/dest',
            'is_anonymous' => true,
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->get('/'.$link->code)
            ->assertRedirect('https://azshrtr.com/dest');

        $this->assertSame(1, $link->fresh()->click_count);
        $this->assertDatabaseHas('link_clicks', [
            'link_id' => $link->id,
        ]);
    }

    public function test_purge_expired_anonymous_links(): void
    {
        $this->seedCore();

        $domain = app(PlatformDomain::class)->resolve();

        $expired = Link::query()->create([
            'domain_id' => $domain->id,
            'code' => 'expired1',
            'destination_url' => 'https://azshrtr.com/a',
            'is_anonymous' => true,
            'expires_at' => now()->subMinute(),
        ]);

        $active = Link::query()->create([
            'domain_id' => $domain->id,
            'code' => 'active1',
            'destination_url' => 'https://azshrtr.com/b',
            'is_anonymous' => true,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->artisan('links:purge-expired')->assertSuccessful();

        $this->assertDatabaseMissing('links', ['id' => $expired->id]);
        $this->assertDatabaseHas('links', ['id' => $active->id]);
    }

    public function test_rejects_non_http_schemes(): void
    {
        $this->from('/')->post(route('shorten'), [
            'url' => 'javascript:alert(1)',
        ])->assertRedirect('/')
            ->assertSessionHasErrors('url');
    }
}
