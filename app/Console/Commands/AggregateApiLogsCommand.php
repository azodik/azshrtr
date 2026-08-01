<?php

namespace App\Console\Commands;

use App\Models\ApiRequestLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateApiLogsCommand extends Command
{
    protected $signature = 'api-logs:aggregate {--chunk=5000 : Raw rows to process per run}';

    protected $description = 'Roll raw API hits older than 24h into hourly aggregates, then delete those raw rows';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $cutoff = now()->subDay();
        $deleted = 0;
        $passes = 0;

        do {
            $logs = ApiRequestLog::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit($chunk)
                ->get();

            if ($logs->isEmpty()) {
                break;
            }

            $passes++;

            $logs->groupBy(function (ApiRequestLog $log): string {
                return implode('|', [
                    (string) $log->organization_id,
                    (string) ($log->api_key_id ?? ''),
                    $log->created_at?->format('Y-m-d H:00:00') ?? '',
                ]);
            })->each(function ($group, string $key) use (&$deleted): void {
                [$orgId, $keyId, $periodStart] = explode('|', $key, 3);

                $existing = DB::table('api_request_aggregates')
                    ->where('organization_id', $orgId)
                    ->where('period', 'hour')
                    ->where('period_start', $periodStart)
                    ->when(
                        $keyId === '',
                        fn ($q) => $q->whereNull('api_key_id'),
                        fn ($q) => $q->where('api_key_id', $keyId),
                    )
                    ->first();

                $requestCount = $group->count();
                $errorCount = $group->where('status', '>=', 400)->count();

                if ($existing) {
                    DB::table('api_request_aggregates')->where('id', $existing->id)->update([
                        'request_count' => $existing->request_count + $requestCount,
                        'error_count' => $existing->error_count + $errorCount,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('api_request_aggregates')->insert([
                        'organization_id' => $orgId,
                        'api_key_id' => $keyId === '' ? null : $keyId,
                        'period' => 'hour',
                        'period_start' => $periodStart,
                        'request_count' => $requestCount,
                        'error_count' => $errorCount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $ids = $group->pluck('id')->all();
                $deleted += ApiRequestLog::query()->whereIn('id', $ids)->delete();
            });
        } while ($logs->count() === $chunk && $passes < 20);

        $this->components->info("Aggregated; deleted {$deleted} raw log row(s) across {$passes} pass(es).");

        return self::SUCCESS;
    }
}
