<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuditAction;
use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Concerns\ResolvesListQuery;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    use EnsuresOrganizationMembership;
    use ResolvesListQuery;

    public function index(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $logs = $this->filteredQuery($request, $organization->id)
            ->with('actor:id,name,email')
            ->paginate($this->perPage($request, 50));

        return response()->json($logs);
    }

    public function show(Request $request, string $organizationId, string $logId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $log = AuditLog::query()
            ->with('actor:id,name,email')
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
            ->with('actor:id,name,email')
            ->limit(5000)
            ->get();

        if ($format === 'json') {
            return response()->json(['data' => $rows])->withHeaders([
                'Content-Disposition' => 'attachment; filename="audit-logs.json"',
            ]);
        }

        return $this->streamCsv(
            'audit-logs.csv',
            ['id', 'action', 'actor_email', 'resource_type', 'resource_id', 'ip_address', 'created_at'],
            $rows->map(fn (AuditLog $row) => [
                $row->id,
                $row->action,
                $row->actor?->email,
                $row->resource_type,
                $row->resource_id,
                $row->ip_address,
                optional($row->created_at)?->toIso8601String(),
            ]),
        );
    }

    /**
     * @return Builder<AuditLog>
     */
    private function filteredQuery(Request $request, string $organizationId): Builder
    {
        $list = $this->listFilters($request, ['created_at', 'action', 'resource_type']);
        $filters = $request->validate([
            'action' => ['sometimes', 'string', 'max:120'],
            'actions' => ['sometimes', 'array'],
            'actions.*' => ['string', Rule::in(array_column(AuditAction::cases(), 'value'))],
        ]);

        $query = AuditLog::query()->where('organization_id', $organizationId);

        $actions = $filters['actions'] ?? [];
        if ($actions === [] && ! empty($filters['action'])) {
            $actions = [$filters['action']];
        }
        if ($actions !== []) {
            $query->whereIn('action', $actions);
        }

        if ($list['q'] !== null) {
            $q = $list['q'];
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('action', 'like', '%'.$q.'%')
                    ->orWhere('resource_type', 'like', '%'.$q.'%')
                    ->orWhere('resource_id', 'like', '%'.$q.'%')
                    ->orWhereHas('actor', function (Builder $actorQuery) use ($q): void {
                        $actorQuery->where('email', 'like', '%'.$q.'%')
                            ->orWhere('name', 'like', '%'.$q.'%');
                    });
            });
        }

        return $this->applySortAndDates($query, $list);
    }
}
