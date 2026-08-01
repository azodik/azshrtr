<?php

namespace App\Console\Commands;

use App\Models\BillingPlan;
use App\Services\Billing\DodoPaymentsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SetupDodoCommand extends Command
{
    protected $signature = 'setup:dodo
                            {--force : Recreate the Pro product even when DODO_PRODUCT_PRO is already set}
                            {--skip-seed : Do not re-seed / update billing_plans rows}
                            {--webhook= : Public HTTPS URL for Dodo webhooks (e.g. https://xxx.ngrok.app/api/v1/webhooks/dodo)}
                            {--env-file= : Path to .env file (defaults to project .env)}';

    protected $description = 'Create the yearly Pro product in Dodo Payments, write DODO_PRODUCT_PRO to .env, and optionally register a webhook';

    /**
     * @var array{name: string, description: string, price_cents: int, env_key: string, slug: string}
     */
    private array $pro = [
        'slug' => 'pro',
        'name' => 'Azshrtr Pro',
        'description' => 'Unlimited links & QR, custom domains, password links — $20/year',
        'price_cents' => 2000,
        'env_key' => 'DODO_PRODUCT_PRO',
    ];

    public function handle(DodoPaymentsClient $dodo): int
    {
        $this->components->info('Dodo Payments setup');

        $envPath = $this->resolveEnvPath();
        if (! File::exists($envPath)) {
            $this->components->error('.env is missing. Run php artisan azshrtr:setup first.');

            return self::FAILURE;
        }

        $apiKey = (string) config('billing.dodo.api_key');
        if ($apiKey === '') {
            $this->components->error('Set DODO_PAYMENTS_API_KEY in .env, then re-run setup:dodo.');

            return self::FAILURE;
        }

        $environment = (string) (config('billing.dodo.environment') ?: 'test_mode');
        if (! in_array($environment, ['test_mode', 'live_mode'], true)) {
            $environment = 'test_mode';
        }

        $expectedBase = $environment === 'live_mode'
            ? 'https://live.dodopayments.com'
            : 'https://test.dodopayments.com';

        $currentBase = rtrim((string) config('billing.dodo.base_url'), '/');
        if ($currentBase === '' || str_contains($currentBase, 'api.dodopayments.com')) {
            $this->writeEnv($envPath, 'DODO_PAYMENTS_BASE_URL', $expectedBase);
            config(['billing.dodo.base_url' => $expectedBase]);
            $this->components->twoColumnDetail('DODO_PAYMENTS_BASE_URL', $expectedBase);
        }

        $this->writeEnv($envPath, 'DODO_PAYMENTS_ENVIRONMENT', $environment);
        $this->writeEnv(
            $envPath,
            'DODO_PAYMENTS_RETURN_URL',
            rtrim((string) config('app.url'), '/').'/console/{organization_id}/billing',
        );

        $this->components->twoColumnDetail('Environment', $environment);
        $this->components->twoColumnDetail('API', $dodo->baseUrl());

        try {
            $existing = $dodo->listProducts();
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $configured = trim((string) (config('billing.dodo.product_pro') ?: $this->envValue($envPath, $this->pro['env_key']) ?? ''));
        $productId = null;

        if ($configured !== '' && ! $this->option('force')) {
            try {
                $this->syncProduct($dodo, $configured);
            } catch (\Throwable $exception) {
                $this->components->error($this->pro['name'].': '.$exception->getMessage());

                return self::FAILURE;
            }

            $productId = $configured;
            $this->components->twoColumnDetail($this->pro['name'], 'updated '.$configured);
        } else {
            $matched = $this->findExistingProduct($existing);
            if ($matched !== null && ! $this->option('force')) {
                try {
                    $this->syncProduct($dodo, $matched);
                } catch (\Throwable $exception) {
                    $this->components->error($this->pro['name'].': '.$exception->getMessage());

                    return self::FAILURE;
                }

                $productId = $matched;
                $this->writeEnv($envPath, $this->pro['env_key'], $matched);
                config(['billing.dodo.product_pro' => $matched]);
                $this->components->twoColumnDetail($this->pro['name'], 'updated '.$matched);
            } else {
                try {
                    $created = $dodo->createProduct($this->productPayload());
                } catch (\Throwable $exception) {
                    $this->components->error($this->pro['name'].': '.$exception->getMessage());

                    return self::FAILURE;
                }

                $productId = $created['product_id'] ?? $created['id'] ?? null;
                if (! is_string($productId) || $productId === '') {
                    $this->components->error($this->pro['name'].': unexpected create response.');

                    return self::FAILURE;
                }

                $this->writeEnv($envPath, $this->pro['env_key'], $productId);
                config(['billing.dodo.product_pro' => $productId]);
                $this->components->twoColumnDetail($this->pro['name'], 'created '.$productId);
            }
        }

        if (! is_string($productId) || $productId === '') {
            $this->components->error('Could not resolve Dodo product id.');

            return self::FAILURE;
        }

        if (! $this->option('skip-seed')) {
            $this->components->task('Syncing billing_plans.dodo_product_id', function () use ($productId): void {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\BillingPlanSeeder',
                    '--force' => true,
                ]);

                BillingPlan::query()->where('slug', 'pro')->update([
                    'dodo_product_id' => $productId,
                ]);
            });
        }

        $webhookOption = $this->option('webhook');
        if (is_string($webhookOption) && $webhookOption !== '') {
            $this->configureWebhook($dodo, $envPath, $webhookOption);
        } else {
            $this->newLine();
            $this->components->warn('Webhook not configured by this command.');
            $default = rtrim((string) config('app.url'), '/').'/api/v1/webhooks/dodo';
            $this->line('  For a public tunnel or production host:');
            $this->line('    php artisan setup:dodo --webhook='.$default);
            $this->line('  Or create the endpoint in the Dodo dashboard and set DODO_PAYMENTS_WEBHOOK_SECRET.');
        }

        $this->newLine();
        $this->components->info('Done. Product ID (copied to .env as DODO_PRODUCT_PRO):');
        $this->components->twoColumnDetail('PRO', $productId);
        $this->line('  '.$productId);

        $this->newLine();
        $this->line('Enable billing with AZSHRTR_BILLING_ENABLED=true, then upgrade an org from Console → Billing.');

        return self::SUCCESS;
    }

    private function syncProduct(DodoPaymentsClient $dodo, string $productId): void
    {
        $dodo->updateProduct($productId, [
            'name' => $this->pro['name'],
            'description' => $this->pro['description'],
            'price' => [
                'type' => 'recurring_price',
                'price' => $this->pro['price_cents'],
                'currency' => strtoupper((string) config('billing.currency', 'USD')),
                'discount' => 0,
                'purchasing_power_parity' => false,
                'payment_frequency_count' => 1,
                'payment_frequency_interval' => 'Year',
                'subscription_period_count' => 1,
                'subscription_period_interval' => 'Year',
                'trial_period_days' => 0,
            ],
            'metadata' => [
                'azshrtr_plan_slug' => 'pro',
                'azshrtr_product' => 'true',
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $products
     */
    private function findExistingProduct(array $products): ?string
    {
        foreach ($products as $product) {
            $metadata = $product['metadata'] ?? [];
            $metaSlug = is_array($metadata) ? ($metadata['azshrtr_plan_slug'] ?? null) : null;
            $productName = $product['name'] ?? null;
            $id = $product['product_id'] ?? $product['id'] ?? null;

            if (! is_string($id) || $id === '') {
                continue;
            }

            if ($metaSlug === 'pro' || $productName === $this->pro['name']) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(): array
    {
        $currency = strtoupper((string) config('billing.currency', 'USD'));

        return [
            'name' => $this->pro['name'],
            'description' => $this->pro['description'],
            'tax_category' => 'saas',
            'price' => [
                'type' => 'recurring_price',
                'price' => $this->pro['price_cents'],
                'currency' => $currency,
                'discount' => 0,
                'purchasing_power_parity' => false,
                'payment_frequency_count' => 1,
                'payment_frequency_interval' => 'Year',
                'subscription_period_count' => 1,
                'subscription_period_interval' => 'Year',
                'trial_period_days' => 0,
            ],
            'metadata' => [
                'azshrtr_plan_slug' => 'pro',
                'azshrtr_product' => 'true',
            ],
        ];
    }

    private function configureWebhook(DodoPaymentsClient $dodo, string $envPath, string $url): void
    {
        $url = trim($url);
        if (! Str::startsWith($url, 'https://')) {
            $this->components->error('Webhook URL must be HTTPS.');

            return;
        }

        $filterTypes = [
            'subscription.active',
            'subscription.renewed',
            'subscription.on_hold',
            'subscription.failed',
            'subscription.cancelled',
            'subscription.expired',
            'payment.succeeded',
            'payment.failed',
            'payment.cancelled',
            'refund.succeeded',
            'refund.failed',
            'abandoned_checkout.detected',
            'abandoned_checkout.recovered',
        ];

        $payload = [
            'url' => $url,
            'description' => 'Azshrtr billing subscriptions',
            'filter_types' => $filterTypes,
            'disabled' => false,
            'metadata' => [
                'azshrtr' => 'true',
            ],
        ];

        try {
            $existingId = $this->resolveExistingWebhookId($dodo, $envPath, $url);

            if ($existingId !== null) {
                $webhook = $dodo->updateWebhook($existingId, $payload);
                $webhookId = $webhook['id'] ?? $webhook['webhook_id'] ?? $existingId;
                $action = 'updated';
            } else {
                $webhook = $dodo->createWebhook($payload);
                $webhookId = $webhook['id'] ?? $webhook['webhook_id'] ?? null;
                $action = 'created';
            }
        } catch (\Throwable $exception) {
            $this->components->error('Webhook: '.$exception->getMessage());

            return;
        }

        if (! is_string($webhookId) || $webhookId === '') {
            $this->components->error('Webhook: unexpected API response (missing id).');

            return;
        }

        $this->writeEnv($envPath, 'DODO_PAYMENTS_WEBHOOK_ID', $webhookId);
        config(['billing.dodo.webhook_id' => $webhookId]);

        $this->components->twoColumnDetail('Webhook', $action.' '.$webhookId);
        $this->components->twoColumnDetail('Webhook URL', $url);

        $existingSecret = trim((string) (
            config('billing.dodo.webhook_secret')
            ?: $this->envValue($envPath, 'DODO_PAYMENTS_WEBHOOK_SECRET')
            ?: ''
        ));

        $secret = $dodo->retrieveWebhookSecret($webhookId)
            ?? (is_string($webhook['secret'] ?? null) ? $webhook['secret'] : null)
            ?? (is_string($webhook['webhook_secret'] ?? null) ? $webhook['webhook_secret'] : null);

        if (is_string($secret) && $secret !== '') {
            $this->writeEnv($envPath, 'DODO_PAYMENTS_WEBHOOK_SECRET', $secret);
            config(['billing.dodo.webhook_secret' => $secret]);
            $this->components->twoColumnDetail('DODO_PAYMENTS_WEBHOOK_SECRET', 'saved to .env');
        } elseif ($existingSecret !== '') {
            $this->components->twoColumnDetail('DODO_PAYMENTS_WEBHOOK_SECRET', 'kept existing .env value');
        } else {
            $this->components->warn(
                'Webhook saved without a secret in the API response. Copy the signing secret from the Dodo dashboard into DODO_PAYMENTS_WEBHOOK_SECRET.',
            );
        }
    }

    private function resolveExistingWebhookId(DodoPaymentsClient $dodo, string $envPath, string $url): ?string
    {
        $configuredId = trim((string) (
            config('billing.dodo.webhook_id')
            ?: $this->envValue($envPath, 'DODO_PAYMENTS_WEBHOOK_ID')
            ?: ''
        ));

        $webhooks = $dodo->listWebhooks();

        if ($configuredId !== '') {
            foreach ($webhooks as $webhook) {
                $id = $webhook['id'] ?? $webhook['webhook_id'] ?? null;
                if (is_string($id) && $id === $configuredId) {
                    return $id;
                }
            }
        }

        $normalizedUrl = rtrim($url, '/');
        $urlMatches = [];
        $azshrtrMatches = [];

        foreach ($webhooks as $webhook) {
            $id = $webhook['id'] ?? $webhook['webhook_id'] ?? null;
            if (! is_string($id) || $id === '') {
                continue;
            }

            $webhookUrl = is_string($webhook['url'] ?? null) ? rtrim($webhook['url'], '/') : '';
            if ($webhookUrl !== '' && strcasecmp($webhookUrl, $normalizedUrl) === 0) {
                $urlMatches[] = $id;
            }

            $metadata = is_array($webhook['metadata'] ?? null) ? $webhook['metadata'] : [];
            if (($metadata['azshrtr'] ?? null) === 'true') {
                $azshrtrMatches[] = $id;
            }
        }

        if ($urlMatches !== []) {
            if (count($urlMatches) > 1) {
                $this->components->warn(
                    'Multiple Dodo webhooks share this URL. Updating '.$urlMatches[0].' — disable duplicates in the Dodo dashboard.',
                );
            }

            return $urlMatches[0];
        }

        if ($azshrtrMatches !== []) {
            if (count($azshrtrMatches) > 1) {
                $this->components->warn(
                    'Multiple Azshrtr webhooks found. Updating '.$azshrtrMatches[0].' — disable extras in the Dodo dashboard.',
                );
            }

            return $azshrtrMatches[0];
        }

        return null;
    }

    private function resolveEnvPath(): string
    {
        $custom = $this->option('env-file');
        if (is_string($custom) && $custom !== '') {
            return $custom;
        }

        return base_path('.env');
    }

    private function envValue(string $envPath, string $key): ?string
    {
        $contents = File::get($envPath);
        if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches)) {
            return null;
        }

        $value = trim($matches[1]);
        if (Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            $value = substr($value, 1, -1);
        }

        return $value;
    }

    private function writeEnv(string $envPath, string $key, string $value): void
    {
        $contents = File::get($envPath);
        $needsQuotes = str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '"');
        $encoded = $needsQuotes ? '"'.str_replace('"', '\\"', $value).'"' : $value;
        $line = $key.'='.$encoded;

        if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $contents) === 1) {
            $contents = preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $contents) ?? $contents;
        } else {
            $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        File::put($envPath, $contents);
    }
}
