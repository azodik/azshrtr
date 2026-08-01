<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use Database\Seeders\BillingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetupDodoCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.currency' => 'USD',
            'billing.dodo.api_key' => 'test_api_key',
            'billing.dodo.base_url' => 'https://test.dodopayments.com',
            'billing.dodo.environment' => 'test_mode',
            'billing.dodo.product_pro' => null,
            'billing.dodo.webhook_id' => null,
            'billing.dodo.webhook_secret' => null,
            'app.url' => 'https://azshrtr.test',
        ]);
    }

    #[Test]
    public function it_fails_without_api_key(): void
    {
        config(['billing.dodo.api_key' => '']);
        $envPath = $this->makeTempEnv();

        $this->artisan('setup:dodo', ['--env-file' => $envPath, '--skip-seed' => true])
            ->assertFailed();

        File::delete($envPath);
    }

    #[Test]
    public function it_creates_pro_product_writes_env_and_syncs_plan(): void
    {
        $this->dodoHttpFake();
        $envPath = $this->makeTempEnv("APP_KEY=base64:test\n");

        $this->artisan('setup:dodo', ['--env-file' => $envPath])
            ->assertSuccessful();

        $env = File::get($envPath);
        $this->assertStringContainsString('DODO_PRODUCT_PRO=pdt_test_pro', $env);
        $this->assertStringContainsString(
            'DODO_PAYMENTS_RETURN_URL=https://azshrtr.test/console/{organization_id}/billing',
            $env,
        );

        $this->assertSame('pdt_test_pro', BillingPlan::query()->where('slug', 'pro')->value('dodo_product_id'));

        Http::assertSent(function (Request $request): bool {
            if ($request->method() !== 'POST' || ! str_ends_with($request->url(), '/products')) {
                return false;
            }

            $body = $request->data();

            return ($body['tax_category'] ?? null) === 'saas'
                && ($body['price']['type'] ?? null) === 'recurring_price'
                && ($body['price']['payment_frequency_interval'] ?? null) === 'Year'
                && ($body['price']['price'] ?? null) === 2000
                && ($body['metadata']['azshrtr_plan_slug'] ?? null) === 'pro';
        });

        File::delete($envPath);
    }

    #[Test]
    public function it_registers_webhook_and_saves_secret(): void
    {
        $this->dodoHttpFake();
        $envPath = $this->makeTempEnv("APP_KEY=base64:test\n");

        $this->artisan('setup:dodo', [
            '--env-file' => $envPath,
            '--webhook' => 'https://example.ngrok.app/api/v1/webhooks/dodo',
        ])->assertSuccessful();

        $env = File::get($envPath);
        $this->assertStringContainsString('DODO_PAYMENTS_WEBHOOK_SECRET=whsec_dGVzdF9zZWNyZXQ=', $env);
        $this->assertStringContainsString('DODO_PAYMENTS_WEBHOOK_ID=ep_test_webhook', $env);

        Http::assertSent(function (Request $request): bool {
            if (
                $request->method() !== 'POST'
                || ! str_ends_with($request->url(), '/webhooks')
                || ($request['url'] ?? null) !== 'https://example.ngrok.app/api/v1/webhooks/dodo'
            ) {
                return false;
            }

            $filters = $request['filter_types'] ?? [];

            return in_array('refund.succeeded', $filters, true)
                && in_array('refund.failed', $filters, true);
        });

        File::delete($envPath);
    }

    #[Test]
    public function it_updates_existing_webhook_instead_of_creating(): void
    {
        Http::fake([
            'test.dodopayments.com/products*' => function (Request $request) {
                if ($request->method() === 'GET') {
                    return Http::response(['items' => []], 200);
                }

                return Http::response([
                    'product_id' => 'pdt_test_pro',
                    'name' => $request['name'] ?? 'Azshrtr Pro',
                    'metadata' => $request['metadata'] ?? [],
                ], 200);
            },
            'test.dodopayments.com/webhooks*' => function (Request $request) {
                $path = (string) parse_url($request->url(), PHP_URL_PATH);

                if ($request->method() === 'GET' && $path === '/webhooks') {
                    return Http::response([
                        'data' => [
                            [
                                'id' => 'ep_existing',
                                'url' => 'https://example.ngrok.app/api/v1/webhooks/dodo',
                                'description' => 'Azshrtr billing subscriptions',
                                'metadata' => ['azshrtr' => 'true'],
                                'created_at' => '2026-01-01T00:00:00Z',
                                'updated_at' => '2026-01-01T00:00:00Z',
                            ],
                        ],
                        'done' => true,
                    ], 200);
                }

                if ($request->method() === 'PATCH' && str_contains($path, '/webhooks/ep_existing')) {
                    return Http::response([
                        'id' => 'ep_existing',
                        'url' => $request['url'] ?? null,
                        'filter_types' => $request['filter_types'] ?? [],
                        'metadata' => $request['metadata'] ?? [],
                    ], 200);
                }

                if ($request->method() === 'POST' && $path === '/webhooks') {
                    return Http::response([
                        'id' => 'ep_should_not_create',
                        'url' => $request['url'] ?? null,
                    ], 200);
                }

                if (str_ends_with($request->url(), '/secret')) {
                    return Http::response([
                        'secret' => 'whsec_dGVzdF9zZWNyZXQ=',
                    ], 200);
                }

                return Http::response([], 404);
            },
        ]);

        $this->seed(BillingPlanSeeder::class);
        $envPath = $this->makeTempEnv("APP_KEY=base64:test\n");

        $this->artisan('setup:dodo', [
            '--env-file' => $envPath,
            '--webhook' => 'https://example.ngrok.app/api/v1/webhooks/dodo',
        ])->assertSuccessful();

        $env = File::get($envPath);
        $this->assertStringContainsString('DODO_PAYMENTS_WEBHOOK_ID=ep_existing', $env);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PATCH'
                && str_contains($request->url(), '/webhooks/ep_existing')
                && ($request['url'] ?? null) === 'https://example.ngrok.app/api/v1/webhooks/dodo'
                && in_array('abandoned_checkout.detected', $request['filter_types'] ?? [], true);
        });

        Http::assertNotSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && (string) parse_url($request->url(), PHP_URL_PATH) === '/webhooks';
        });

        File::delete($envPath);
    }

    #[Test]
    public function it_rejects_non_https_webhook_urls(): void
    {
        $this->dodoHttpFake();
        $envPath = $this->makeTempEnv("APP_KEY=base64:test\n");

        $this->artisan('setup:dodo', [
            '--env-file' => $envPath,
            '--webhook' => 'http://insecure.example/api/v1/webhooks/dodo',
        ])->assertSuccessful();

        $this->assertStringNotContainsString('DODO_PAYMENTS_WEBHOOK_SECRET=', File::get($envPath));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/webhooks'));

        File::delete($envPath);
    }

    private function makeTempEnv(string $contents = "APP_KEY=\n"): string
    {
        $path = storage_path('framework/testing/dodo-env-'.uniqid('', true));
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);

        return $path;
    }

    private function dodoHttpFake(): void
    {
        Http::fake([
            'test.dodopayments.com/products*' => function (Request $request) {
                if ($request->method() === 'GET') {
                    return Http::response(['items' => []], 200);
                }

                if ($request->method() === 'PATCH') {
                    return Http::response([
                        'product_id' => basename(parse_url($request->url(), PHP_URL_PATH) ?: ''),
                    ], 200);
                }

                return Http::response([
                    'product_id' => 'pdt_test_pro',
                    'name' => $request['name'] ?? 'Azshrtr Pro',
                    'metadata' => $request['metadata'] ?? [],
                ], 200);
            },
            'test.dodopayments.com/webhooks*' => function (Request $request) {
                $path = (string) parse_url($request->url(), PHP_URL_PATH);

                if ($request->method() === 'GET' && $path === '/webhooks') {
                    return Http::response([
                        'data' => [],
                        'done' => true,
                    ], 200);
                }

                if ($request->method() === 'POST' && $path === '/webhooks') {
                    return Http::response([
                        'id' => 'ep_test_webhook',
                        'url' => $request['url'] ?? null,
                    ], 200);
                }

                if (str_ends_with($request->url(), '/secret')) {
                    return Http::response([
                        'secret' => 'whsec_dGVzdF9zZWNyZXQ=',
                    ], 200);
                }

                return Http::response([], 404);
            },
        ]);

        $this->seed(BillingPlanSeeder::class);
    }
}
