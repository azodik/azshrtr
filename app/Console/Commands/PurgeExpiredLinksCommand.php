<?php

namespace App\Console\Commands;

use App\Models\Link;
use Illuminate\Console\Command;

class PurgeExpiredLinksCommand extends Command
{
    protected $signature = 'links:purge-expired {--chunk=500 : Rows to delete per run}';

    protected $description = 'Hard-delete anonymous unclaimed links past expires_at';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $deleted = 0;

        Link::query()
            ->where('is_anonymous', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->limit($chunk)
            ->get()
            ->each(function (Link $link) use (&$deleted): void {
                $link->forceDelete();
                $deleted++;
            });

        $this->components->info("Purged {$deleted} expired anonymous link(s).");

        return self::SUCCESS;
    }
}
