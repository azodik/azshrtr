<?php

namespace App\Console\Commands;

use App\Models\LinkClick;
use App\Models\Organization;
use App\Services\Billing\PlanEntitlements;
use App\Services\Ops\BatchDeleter;
use Illuminate\Console\Command;

class ClicksPruneCommand extends Command
{
    protected $signature = 'clicks:prune {--chunk=1000 : Rows to delete per batch}';

    protected $description = 'Delete link click rows older than each organization\'s plan retention';

    public function handle(PlanEntitlements $entitlements, BatchDeleter $deleter): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $deleted = 0;

        Organization::query()->orderBy('id')->each(function (Organization $organization) use (
            $entitlements,
            $deleter,
            $chunk,
            &$deleted,
        ): void {
            $days = max(1, $entitlements->clickRetentionDays($organization));
            $cutoff = now()->subDays($days);

            $deleted += $deleter->deleteEloquent(
                LinkClick::query()
                    ->where('organization_id', $organization->id)
                    ->where('clicked_at', '<', $cutoff),
                chunk: $chunk,
            );
        });

        $this->components->info("Pruned {$deleted} click row(s).");

        return self::SUCCESS;
    }
}
