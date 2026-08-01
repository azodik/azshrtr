<?php

namespace App\Console\Commands;

use App\Models\ApiRequestLog;
use App\Models\Organization;
use App\Services\Billing\PlanEntitlements;
use App\Services\Ops\BatchDeleter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApiLogsPruneCommand extends Command
{
    protected $signature = 'api-logs:prune {--chunk=1000 : Rows to delete per batch}';

    protected $description = 'Delete raw API request logs and hourly aggregates older than plan retention';

    public function handle(PlanEntitlements $entitlements, BatchDeleter $deleter): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $rawDeleted = 0;
        $aggregateDeleted = 0;

        Organization::query()->orderBy('id')->each(function (Organization $organization) use (
            $entitlements,
            $deleter,
            $chunk,
            &$rawDeleted,
            &$aggregateDeleted,
        ): void {
            $days = max(1, $entitlements->apiLogRetentionDays($organization));
            $cutoff = now()->subDays($days);

            $rawDeleted += $deleter->deleteEloquent(
                ApiRequestLog::query()
                    ->where('organization_id', $organization->id)
                    ->where('created_at', '<', $cutoff),
                chunk: $chunk,
            );

            $aggregateDeleted += $deleter->deleteQuery(
                DB::table('api_request_aggregates')
                    ->where('organization_id', $organization->id)
                    ->where('period_start', '<', $cutoff),
                'api_request_aggregates',
                chunk: $chunk,
            );
        });

        $this->components->info(
            "Pruned {$rawDeleted} raw API log row(s) and {$aggregateDeleted} aggregate row(s).",
        );

        return self::SUCCESS;
    }
}
