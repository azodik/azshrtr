<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Concerns\ResolvesListQuery;
use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiRequestLogController extends Controller
{
    use EnsuresOrganizationMembership;
    use ResolvesListQuery;

    public function index(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $logs = $this->filteredQuery($request, $organization->id)
            ->with('apiKey:id,name,prefix,last_four')
            ->paginate($this->perPage($request, 50));

        return response()->json($logs);
    }

    public function show(Request $request, string $organizationId, string $logId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $log = ApiRequestLog::query()
            ->with('apiKey:id,name,prefix,last_four')
            ->where('organization_id', $organization->id)
            ->whereKey($logId)
            ->firstOrFail();

        return response()->json(['log' => $log]);
    }

    public function export(Request $request, string $organizationId): StreamedResponse|JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $format = $request->validate([
            'format' => ['sometimes', 'in:csv,json'],
        ])['format'] ?? 'csv';

        $rows = $this->filteredQuery($request, $organization->id)
            ->with('apiKey:id,name,prefix,last_four')
            ->limit(5000)
            ->get();

        if ($format === 'json') {
            return response()->json(['data' => $rows])->withHeaders([
                'Content-Disposition' => 'attachment; filename="api-request-logs.json"',
            ]);
        }

        return $this->streamCsv(
            'api-request-logs.csv',
            ['id', 'method', 'path', 'status', 'latency_ms', 'api_key', 'created_at'],
            $rows->map(function (ApiRequestLog $row): array {
                $key = $row->apiKey
                    ? $row->apiKey->name.' ('.$row->apiKey->prefix.'…'.$row->apiKey->last_four.')'
                    : '';

                return [
                    $row->id,
                    $row->method,
                    $row->path,
                    $row->status,
                    $row->latency_ms,
                    $key,
                    optional($row->created_at)?->toIso8601String(),
                ];
            }),
        );
    }

    /**
     * @return Builder<ApiRequestLog>
     */
    private function filteredQuery(Request $request, string $organizationId): Builder
    {
        $list = $this->listFilters($request, ['created_at', 'method', 'path', 'status', 'latency_ms']);
        $filters = $request->validate([
            'api_key_id' => ['sometimes', 'uuid'],
            'status' => ['sometimes', 'integer'],
            'statuses' => ['sometimes', 'array'],
            'statuses.*' => ['integer'],
            'method' => ['sometimes', 'string', 'max:16'],
            'methods' => ['sometimes', 'array'],
            'methods.*' => ['string', 'max:16'],
            'path' => ['sometimes', 'string', 'max:255'],
        ]);

        $query = ApiRequestLog::query()->where('organization_id', $organizationId);

        if (! empty($filters['api_key_id'])) {
            $query->where('api_key_id', $filters['api_key_id']);
        }

        $statuses = $filters['statuses'] ?? [];
        if ($statuses === [] && isset($filters['status'])) {
            $statuses = [$filters['status']];
        }
        if ($statuses !== []) {
            $query->whereIn('status', array_map('intval', $statuses));
        }

        $methods = $filters['methods'] ?? [];
        if ($methods === [] && ! empty($filters['method'])) {
            $methods = [$filters['method']];
        }
        if ($methods !== []) {
            $query->whereIn('method', array_map(
                static fn (string $method): string => strtoupper($method),
                $methods,
            ));
        }

        $search = $list['q'] ?? ($filters['path'] ?? null);
        if (! empty($search)) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('path', 'like', '%'.$search.'%')
                    ->orWhere('method', 'like', '%'.$search.'%');
            });
        }

        return $this->applySortAndDates($query, $list);
    }
}
