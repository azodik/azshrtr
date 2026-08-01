<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

/**
 * Stress-tests hardened org import/export (chunked export, bounded import).
 */
class ImportExportStressTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    /**
     * @return list<array{destination_url: string, title: string}>
     */
    private function makeRows(int $count): array
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'destination_url' => 'https://example.com/stress/'.$i.'/'.Str::lower(Str::random(8)),
                'title' => 'Stress '.$i,
            ];
        }

        return $rows;
    }

    private function report(string $label, float $startedAt, int $memBefore): void
    {
        $elapsedMs = (int) round((hrtime(true) - $startedAt) / 1e6);
        $memDelta = memory_get_usage(true) - $memBefore;
        $peak = memory_get_peak_usage(true);
        fwrite(STDERR, sprintf(
            "[stress] %s | %dms | Δmem=%s | peak=%s | limit=%s\n",
            $label,
            $elapsedMs,
            $this->bytes($memDelta),
            $this->bytes($peak),
            (string) ini_get('memory_limit'),
        ));
    }

    private function bytes(int $bytes): string
    {
        $sign = $bytes < 0 ? '-' : '';
        $bytes = abs($bytes);
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024 || $unit === 'GB') {
                return sprintf('%s%.1f%s', $sign, $bytes, $unit);
            }
            $bytes /= 1024;
        }

        return $sign.(string) $bytes.'B';
    }

    private function platformDomainId(): string
    {
        return Domain::query()->whereNull('organization_id')->value('id')
            ?? Domain::query()->value('id');
    }

    private function seedLinks(int $count): void
    {
        $domainId = $this->platformDomainId();
        $now = now()->toDateTimeString();
        $batch = [];
        for ($i = 0; $i < $count; $i++) {
            $batch[] = [
                'id' => (string) Str::ulid(),
                'organization_id' => $this->apiOrganization->id,
                'user_id' => $this->apiUser->id,
                'domain_id' => $domainId,
                'code' => Str::lower(Str::random(8)).dechex($i),
                'destination_url' => 'https://example.com/export-seed/'.$i,
                'title' => 'Export seed '.$i,
                'is_anonymous' => 0,
                'is_disabled' => 0,
                'click_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($batch) >= 500) {
                DB::table('links')->insert($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            DB::table('links')->insert($batch);
        }
    }

    #[Test]
    public function import_handles_increasing_json_row_counts(): void
    {
        $this->createOrganizationContext();
        $this->actingAsApiOwner();

        foreach ([100, 500, 1500] as $count) {
            $rows = $this->makeRows($count);
            $payload = json_encode($rows, JSON_THROW_ON_ERROR);
            $payloadBytes = strlen($payload);

            $memBefore = memory_get_usage(true);
            $started = hrtime(true);

            $response = $this->postJson($this->orgPath('import'), [
                'format' => 'json',
                'payload' => $payload,
            ]);

            $this->report(sprintf('import json rows=%d payload=%s status=%d', $count, $this->bytes($payloadBytes), $response->status()), $started, $memBefore);

            $response->assertOk();
            $this->assertSame($count, (int) $response->json('import.total_rows'));
            $this->assertSame($count, (int) $response->json('import.success_rows'));
            $this->assertSame(0, (int) $response->json('import.error_rows'));
        }
    }

    #[Test]
    public function import_rejects_payload_over_configured_max_bytes(): void
    {
        config(['azshrtr.import_export.max_payload_bytes' => 2048]);

        $this->createOrganizationContext();
        $this->actingAsApiOwner();

        $rows = $this->makeRows(50);
        $payload = json_encode($rows, JSON_THROW_ON_ERROR);
        $this->assertGreaterThan(2048, strlen($payload));

        $this->postJson($this->orgPath('import'), [
            'format' => 'json',
            'payload' => $payload,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payload']);
    }

    #[Test]
    public function import_rejects_more_than_max_rows(): void
    {
        config(['azshrtr.import_export.max_rows' => 25]);

        $this->createOrganizationContext();
        $this->actingAsApiOwner();

        $this->postJson($this->orgPath('import'), [
            'format' => 'json',
            'payload' => json_encode($this->makeRows(26), JSON_THROW_ON_ERROR),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payload']);
    }

    #[Test]
    public function export_handles_large_link_sets_json_and_csv(): void
    {
        $this->createOrganizationContext();
        $this->actingAsApiOwner();

        $seedCount = 5000;
        $memBefore = memory_get_usage(true);
        $started = hrtime(true);
        $this->seedLinks($seedCount);
        $this->report(sprintf('seed links=%d', $seedCount), $started, $memBefore);
        $this->assertSame($seedCount, Link::query()->where('organization_id', $this->apiOrganization->id)->count());

        foreach (['json', 'csv'] as $format) {
            $memBefore = memory_get_usage(true);
            $started = hrtime(true);

            $response = $this->postJson($this->orgPath('export'), [
                'format' => $format,
            ]);

            $this->report(sprintf('export format=%s status=%d rows=%s', $format, $response->status(), (string) $response->json('export.row_count')), $started, $memBefore);

            $response->assertOk();
            $this->assertSame($seedCount, (int) $response->json('export.row_count'));
            $this->assertSame('completed', $response->json('export.status'));

            $exportId = (string) $response->json('export.id');
            $download = $this->get($this->orgPath('exports/'.$exportId.'/download'));
            $download->assertOk();
            $bodyLen = strlen((string) $download->streamedContent());
            if ($bodyLen === 0) {
                $bodyLen = strlen($download->getContent());
            }
            fwrite(STDERR, sprintf("[stress] download format=%s body=%s\n", $format, $this->bytes($bodyLen)));
            $this->assertGreaterThan(1000, $bodyLen);
        }
    }

    #[Test]
    public function export_json_survives_20k_rows_under_128m(): void
    {
        $this->createOrganizationContext();
        $this->actingAsApiOwner();

        $seedCount = 20_000;
        $memBefore = memory_get_usage(true);
        $started = hrtime(true);
        $this->seedLinks($seedCount);
        $this->report(sprintf('seed links=%d', $seedCount), $started, $memBefore);

        $memBefore = memory_get_usage(true);
        $started = hrtime(true);
        $response = $this->postJson($this->orgPath('export'), ['format' => 'json']);
        $this->report(sprintf('export 20k status=%d rows=%s', $response->status(), (string) $response->json('export.row_count')), $started, $memBefore);

        $response->assertOk();
        $this->assertSame($seedCount, (int) $response->json('export.row_count'));
        $this->assertLessThan(100 * 1024 * 1024, memory_get_peak_usage(true));
    }
}
