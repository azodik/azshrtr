<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class AzshrtrSetupCommand extends Command
{
    protected $signature = 'azshrtr:setup
                            {--force : Overwrite empty or placeholder .env keys with defaults}
                            {--skip-migrate : Skip running migrations}
                            {--with-demo : Seed demo data (Phase 1+)}';

    protected $description = 'Create/update .env, migrate, and print a setup summary';

    public function handle(): int
    {
        $this->components->info('Azshrtr setup');

        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (! File::exists($envPath)) {
            if (! File::exists($examplePath)) {
                $this->components->error('.env.example is missing.');

                return self::FAILURE;
            }

            File::copy($examplePath, $envPath);
            $this->components->twoColumnDetail('.env', 'created from .env.example');
        } else {
            $this->components->twoColumnDetail('.env', 'already present');
        }

        $this->ensureEnvKeys($envPath);

        if (blank(env('APP_KEY')) || $this->envValue($envPath, 'APP_KEY') === '') {
            Artisan::call('key:generate', ['--force' => true]);
            $this->components->twoColumnDetail('APP_KEY', 'generated');
        } else {
            $this->components->twoColumnDetail('APP_KEY', 'ok');
        }

        if (! $this->option('skip-migrate')) {
            $this->components->task('Running migrations', function (): void {
                Artisan::call('migrate', ['--force' => true]);
            });
        }

        $this->components->task('Seeding plans and platform domains', function (): void {
            Artisan::call('db:seed', [
                '--force' => true,
            ]);
        });

        if ($this->option('with-demo')) {
            $this->components->warn('Demo account seeding is optional — create a user via /console/register.');
        }

        $this->newLine();
        $this->printSummary($envPath);

        $this->newLine();
        $this->components->info('Next steps');
        $this->line('  npm install && npm run build');
        $this->line('  php artisan serve   # or open your Herd site');
        $this->line('  Console: '.rtrim((string) $this->envValue($envPath, 'APP_URL'), '/').'/console');

        return self::SUCCESS;
    }

    private function ensureEnvKeys(string $envPath): void
    {
        $defaults = [
            'APP_NAME' => 'Azshrtr',
            'APP_URL' => 'https://azshrtr.test',
            'APP_LOCALE' => 'en',
            'APP_FALLBACK_LOCALE' => 'en',
            'DB_CONNECTION' => 'mariadb',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'azshrtr',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'SESSION_DRIVER' => 'database',
            'QUEUE_CONNECTION' => 'database',
            'CACHE_STORE' => 'database',
            'MAIL_MAILER' => 'log',
            'MAIL_FROM_ADDRESS' => 'hello@azshrtr.test',
            'MAIL_FROM_NAME' => '${APP_NAME}',
            'SANCTUM_STATEFUL_DOMAINS' => 'azshrtr.test,localhost,127.0.0.1',
            'AZSHRTR_BILLING_ENABLED' => 'false',
            'AZSHRTR_BILLING_CURRENCY' => 'USD',
            'AZSHRTR_USAGE_TIMEZONE' => 'UTC',
            'AZSHRTR_GUEST_LINK_TTL_MINUTES' => '30',
            'AZSHRTR_CRON_QUEUE' => 'true',
            'AZSHRTR_DOMAIN_ROOT' => 'azshrtr.test',
            'AZSHRTR_DOMAIN_DNS_VERIFY' => 'true',
            'AZSHRTR_CUSTOM_DOMAIN_CNAME_TARGET' => 'customers.azshrtr.com',
            'DODO_PAYMENTS_BASE_URL' => 'https://test.dodopayments.com',
            'DODO_PAYMENTS_ENVIRONMENT' => 'test_mode',
            'DODO_PAYMENTS_RETURN_URL' => '${APP_URL}/console/{organization_id}/billing',
            'CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED' => 'false',
        ];

        foreach ($defaults as $key => $value) {
            $current = $this->envValue($envPath, $key);

            if ($current === null) {
                File::append($envPath, PHP_EOL.$key.'='.$value.PHP_EOL);
                $this->components->twoColumnDetail($key, 'added');

                continue;
            }

            if ($this->option('force') && ($current === '' || $this->isPlaceholder($key, $current))) {
                $this->setEnvValue($envPath, $key, $value);
                $this->components->twoColumnDetail($key, 'updated');
            }
        }
    }

    private function isPlaceholder(string $key, string $value): bool
    {
        if (in_array($value, ['null', 'changeme', 'secret', 'password'], true)) {
            return true;
        }

        return str_contains(strtolower($key), 'authzio');
    }

    private function envValue(string $envPath, string $key): ?string
    {
        $contents = File::get($envPath);

        if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches)) {
            return null;
        }

        return trim($matches[1], " \t\"'");
    }

    private function setEnvValue(string $envPath, string $key, string $value): void
    {
        $contents = File::get($envPath);
        $line = $key.'='.$value;

        if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $contents)) {
            $contents = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $line, $contents) ?? $contents;
        } else {
            $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        File::put($envPath, $contents);
    }

    private function printSummary(string $envPath): void
    {
        $this->components->info('Summary');
        $this->components->twoColumnDetail('APP_URL', (string) $this->envValue($envPath, 'APP_URL'));
        $this->components->twoColumnDetail('DB_CONNECTION', (string) $this->envValue($envPath, 'DB_CONNECTION'));
        $this->components->twoColumnDetail('CACHE_STORE', (string) $this->envValue($envPath, 'CACHE_STORE'));
        $this->components->twoColumnDetail('QUEUE_CONNECTION', (string) $this->envValue($envPath, 'QUEUE_CONNECTION'));
        $this->components->twoColumnDetail(
            'Billing',
            ($this->envValue($envPath, 'AZSHRTR_BILLING_ENABLED') === 'true') ? 'enabled' : 'disabled (self-host)',
        );
    }
}
