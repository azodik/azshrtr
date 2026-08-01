<?php

namespace App\Services\Domains;

use App\Models\Domain;
use RuntimeException;

class PlatformDomain
{
    public function hostname(): string
    {
        $configured = strtolower(trim((string) config('azshrtr.domains.root', '')));
        if ($configured !== '') {
            return $configured;
        }

        $fromApp = parse_url((string) config('app.url'), PHP_URL_HOST);

        return strtolower((string) ($fromApp ?: 'localhost'));
    }

    public function resolve(): Domain
    {
        $hostname = $this->hostname();

        $domain = Domain::query()
            ->where('is_system', true)
            ->where('hostname', $hostname)
            ->first();

        if ($domain !== null) {
            return $domain;
        }

        // Local/dev fallbacks when APP_URL host differs slightly.
        $domain = Domain::query()
            ->where('is_system', true)
            ->orderBy('created_at')
            ->first();

        if ($domain !== null) {
            return $domain;
        }

        throw new RuntimeException(
            'Platform domain is not seeded. Run migrations + seeders (BillingPlanSeeder / DatabaseSeeder).',
        );
    }

    public function findByHostname(string $hostname): ?Domain
    {
        return Domain::query()
            ->where('hostname', strtolower($hostname))
            ->first();
    }
}
