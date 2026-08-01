<?php

namespace App\Services\Links;

use App\Enums\AuditAction;
use App\Models\Link;
use App\Models\LinkExport;
use App\Models\LinkImport;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Domains\PlatformDomain;
use App\Services\Usage\UsageTracker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LinkImportExportService
{
    public function __construct(
        private readonly LinkService $links,
        private readonly AuditLogger $audit,
        private readonly UsageTracker $usage,
        private readonly PlatformDomain $platformDomain,
    ) {}

    /**
     * @return array{export: LinkExport, download_url: string}
     */
    public function export(Organization $organization, User $user, string $format): array
    {
        $export = LinkExport::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'processing',
            'format' => $format,
        ]);

        $path = 'exports/'.$export->id.'.'.$format;
        $absolute = Storage::disk('local')->path($path);
        $directory = dirname($absolute);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create export directory.');
        }

        $handle = fopen($absolute, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open export file for writing.');
        }

        $rowCount = 0;

        try {
            $query = Link::query()
                ->where('organization_id', $organization->id)
                ->select([
                    'id',
                    'code',
                    'destination_url',
                    'title',
                    'expires_at',
                    'is_disabled',
                    'click_count',
                    'created_at',
                ])
                ->orderBy('id');

            if ($format === 'csv') {
                fputcsv($handle, ['code', 'destination_url', 'title', 'click_count', 'created_at']);
                foreach ($query->lazyById(500) as $link) {
                    fputcsv($handle, [
                        $link->code,
                        $link->destination_url,
                        (string) $link->title,
                        (int) $link->click_count,
                        $link->created_at?->toIso8601String() ?? '',
                    ]);
                    $rowCount++;
                }
            } else {
                fwrite($handle, '[');
                $first = true;
                foreach ($query->lazyById(500) as $link) {
                    $row = [
                        'id' => $link->id,
                        'code' => $link->code,
                        'destination_url' => $link->destination_url,
                        'title' => $link->title,
                        'expires_at' => $link->expires_at?->toIso8601String(),
                        'is_disabled' => (bool) $link->is_disabled,
                        'click_count' => (int) $link->click_count,
                        'created_at' => $link->created_at?->toIso8601String(),
                    ];
                    if (! $first) {
                        fwrite($handle, ',');
                    }
                    $first = false;
                    fwrite($handle, json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
                    $rowCount++;
                }
                fwrite($handle, ']');
            }
        } finally {
            fclose($handle);
        }

        $export->forceFill([
            'status' => 'completed',
            'path' => $path,
            'row_count' => $rowCount,
        ])->save();

        $this->audit->log(AuditAction::ExportCompleted, $user, $organization, 'export', $export->id);

        return [
            'export' => $export,
            'download_url' => url('/api/v1/organizations/'.$organization->id.'/exports/'.$export->id.'/download'),
        ];
    }

    public function import(Organization $organization, User $user, string $format, string $payload): LinkImport
    {
        $maxBytes = (int) config('azshrtr.import_export.max_payload_bytes', 5_242_880);
        $maxRows = (int) config('azshrtr.import_export.max_rows', 10_000);

        if (strlen($payload) > $maxBytes) {
            throw ValidationException::withMessages([
                'payload' => [sprintf(
                    'Import payload exceeds the maximum size of %s.',
                    $this->humanBytes($maxBytes),
                )],
            ]);
        }

        $import = LinkImport::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'processing',
            'format' => $format,
        ]);

        $errors = [];
        $success = 0;
        $total = 0;
        $usageBatch = 0;
        $domain = $this->platformDomain->resolve();

        try {
            if ($format === 'json') {
                $decoded = json_decode($payload, true);
                if (! is_array($decoded)) {
                    throw ValidationException::withMessages([
                        'payload' => ['JSON payload must be an array of link objects.'],
                    ]);
                }

                if (count($decoded) > $maxRows) {
                    throw ValidationException::withMessages([
                        'payload' => ["Import is limited to {$maxRows} rows per request."],
                    ]);
                }

                foreach ($decoded as $index => $item) {
                    $total++;
                    try {
                        $url = is_array($item) ? ($item['destination_url'] ?? $item['url'] ?? null) : null;
                        if (! is_string($url)) {
                            throw new \InvalidArgumentException('Missing destination_url');
                        }
                        $title = is_array($item) ? ($item['title'] ?? null) : null;
                        $this->links->createOwnedForImport($organization, $user, [
                            'destination_url' => $url,
                            'title' => is_string($title) ? $title : null,
                        ], $domain);
                        $success++;
                        $usageBatch++;
                    } catch (ValidationException $e) {
                        $errors[] = ['row' => $index, 'error' => collect($e->errors())->flatten()->first() ?? $e->getMessage()];
                    } catch (\Throwable $e) {
                        $errors[] = ['row' => $index, 'error' => $e->getMessage()];
                    }

                    if ($usageBatch >= 50) {
                        $this->usage->incrementLinksCreatedBy($organization, $usageBatch);
                        $usageBatch = 0;
                    }
                }
            } else {
                $lines = preg_split('/\r\n|\r|\n/', $payload) ?: [];
                $dataLines = 0;
                foreach ($lines as $index => $line) {
                    if ($index === 0 && str_contains(strtolower($line), 'destination')) {
                        continue;
                    }
                    if (trim($line) === '') {
                        continue;
                    }
                    $dataLines++;
                }

                if ($dataLines > $maxRows) {
                    throw ValidationException::withMessages([
                        'payload' => ["Import is limited to {$maxRows} rows per request."],
                    ]);
                }

                foreach ($lines as $index => $line) {
                    if ($index === 0 && str_contains(strtolower($line), 'destination')) {
                        continue;
                    }
                    if (trim($line) === '') {
                        continue;
                    }
                    $total++;
                    $parts = str_getcsv($line);
                    [$url, $title] = $this->csvUrlAndTitle($parts);
                    try {
                        if (! is_string($url) || $url === '') {
                            throw new \InvalidArgumentException('Missing URL');
                        }
                        $this->links->createOwnedForImport($organization, $user, [
                            'destination_url' => $url,
                            'title' => is_string($title) && $title !== '' ? $title : null,
                        ], $domain);
                        $success++;
                        $usageBatch++;
                    } catch (ValidationException $e) {
                        $errors[] = ['row' => $index, 'error' => collect($e->errors())->flatten()->first() ?? $e->getMessage()];
                    } catch (\Throwable $e) {
                        $errors[] = ['row' => $index, 'error' => $e->getMessage()];
                    }

                    if ($usageBatch >= 50) {
                        $this->usage->incrementLinksCreatedBy($organization, $usageBatch);
                        $usageBatch = 0;
                    }
                }
            }

            if ($usageBatch > 0) {
                $this->usage->incrementLinksCreatedBy($organization, $usageBatch);
            }

            $import->forceFill([
                'status' => 'completed',
                'total_rows' => $total,
                'success_rows' => $success,
                'error_rows' => count($errors),
                'errors' => $errors === [] ? null : array_slice($errors, 0, 100),
            ])->save();
        } catch (ValidationException $e) {
            $import->forceFill([
                'status' => 'failed',
                'total_rows' => $total,
                'success_rows' => $success,
                'error_rows' => count($errors),
                'errors' => [['row' => null, 'error' => collect($e->errors())->flatten()->first() ?? $e->getMessage()]],
            ])->save();
            throw $e;
        }

        $this->audit->log(AuditAction::ImportCompleted, $user, $organization, 'import', $import->id);

        return $import;
    }

    /**
     * Support both `destination_url,title` and export-shaped `code,destination_url,title,...` rows.
     *
     * @param  list<string|null>  $parts
     * @return array{0: ?string, 1: ?string}
     */
    private function csvUrlAndTitle(array $parts): array
    {
        $candidate0 = isset($parts[0]) ? trim((string) $parts[0]) : '';
        $candidate1 = isset($parts[1]) ? trim((string) $parts[1]) : '';

        if ($candidate0 !== '' && preg_match('#^https?://#i', $candidate0) === 1) {
            return [$candidate0, $parts[1] ?? null];
        }

        if ($candidate1 !== '' && preg_match('#^https?://#i', $candidate1) === 1) {
            return [$candidate1, $parts[2] ?? null];
        }

        return [$candidate0 !== '' ? $candidate0 : null, $parts[1] ?? null];
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return rtrim(rtrim(number_format($bytes / 1_048_576, 1, '.', ''), '0'), '.').'MB';
        }

        return (string) max(1, (int) round($bytes / 1024)).'KB';
    }
}
