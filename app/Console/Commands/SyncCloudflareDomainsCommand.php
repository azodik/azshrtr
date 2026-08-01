<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\Cloudflare\CustomDomainCloudflareService;
use Illuminate\Console\Command;

class SyncCloudflareDomainsCommand extends Command
{
    protected $signature = 'domains:sync-cloudflare';

    protected $description = 'Refresh pending Cloudflare custom hostname statuses';

    public function handle(CustomDomainCloudflareService $cloudflare): int
    {
        if (! $cloudflare->enabled()) {
            $this->components->warn('Cloudflare custom hostnames disabled.');

            return self::SUCCESS;
        }

        $synced = 0;
        Domain::query()
            ->whereNull('verified_at')
            ->whereNotNull('cloudflare_hostname_id')
            ->orderBy('updated_at')
            ->limit(100)
            ->each(function (Domain $domain) use ($cloudflare, &$synced): void {
                try {
                    $cloudflare->refreshAndVerify($domain);
                    $synced++;
                } catch (\Throwable) {
                    // leave pending
                }
            });

        $this->components->info("Synced {$synced} domain(s).");

        return self::SUCCESS;
    }
}
