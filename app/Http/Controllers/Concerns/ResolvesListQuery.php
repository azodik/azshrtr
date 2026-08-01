<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ResolvesListQuery
{
    use ResolvesPagination;

    /**
     * @param  list<string>  $allowedSorts
     * @return array{sort: string, direction: string, q: string|null, from: string|null, to: string|null}
     */
    protected function listFilters(Request $request, array $allowedSorts, string $defaultSort = 'created_at', string $defaultDirection = 'desc'): array
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', Rule::in($allowedSorts)],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'format' => ['sometimes', 'string', Rule::in(['csv', 'json'])],
        ]);

        return [
            'sort' => $validated['sort'] ?? $defaultSort,
            'direction' => $validated['direction'] ?? $defaultDirection,
            'q' => isset($validated['q']) && $validated['q'] !== '' ? $validated['q'] : null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array{sort: string, direction: string, from: string|null, to: string|null}  $filters
     * @return Builder<Model>
     */
    protected function applySortAndDates(Builder $query, array $filters, string $dateColumn = 'created_at'): Builder
    {
        if (! empty($filters['from'])) {
            $query->where($dateColumn, '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where($dateColumn, '<=', $filters['to'].' 23:59:59');
        }

        return $query->orderBy($filters['sort'], $filters['direction']);
    }

    /**
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     * @param  list<string>  $headers
     */
    protected function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
