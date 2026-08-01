<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Services\Billing\PlanEntitlements;
use App\Services\Ops\BatchDeleter;
use Illuminate\Console\Command;

class AuditPruneCommand extends Command
{
    protected $signature = 'audit:prune {--chunk=1000 : Rows to delete per batch}';

    protected $description = 'Delete audit log rows older than each organization\'s plan retention';

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
            $days = max(1, $entitlements->auditRetentionDays($organization));
            $cutoff = now()->subDays($days);

            $deleted += $deleter->deleteEloquent(
                AuditLog::query()
                    ->where('organization_id', $organization->id)
                    ->where('created_at', '<', $cutoff),
                chunk: $chunk,
            );
        });

        $this->components->info("Pruned {$deleted} audit log row(s).");

        return self::SUCCESS;
    }
}
