<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\DestroysMany;
use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Concerns\ResolvesListQuery;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\Domains\DomainService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DomainController extends Controller
{
    use DestroysMany;
    use EnsuresOrganizationMembership;
    use ResolvesListQuery;

    public function __construct(private readonly DomainService $domains) {}

    public function index(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters($request, ['created_at', 'hostname', 'status', 'verified_at']);

        $paginator = $this->filteredQuery($organization->id, $filters)
            ->with('dnsRecords')
            ->paginate($this->perPage($request));

        return response()->json(array_merge($paginator->toArray(), [
            'cname_target' => config('azshrtr.domains.cname_target'),
        ]));
    }

    public function export(Request $request, string $organizationId): StreamedResponse|JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters($request, ['created_at', 'hostname', 'status', 'verified_at']);
        $format = $request->validate(['format' => ['sometimes', 'in:csv,json']])['format'] ?? 'csv';

        $rows = $this->filteredQuery($organization->id, $filters)->limit(5000)->get();

        if ($format === 'json') {
            return response()->json(['data' => $rows])->withHeaders([
                'Content-Disposition' => 'attachment; filename="domains.json"',
            ]);
        }

        return $this->streamCsv(
            'domains.csv',
            ['id', 'hostname', 'status', 'verified_at', 'created_at'],
            $rows->map(fn (Domain $domain) => [
                $domain->id,
                $domain->hostname,
                $domain->status,
                optional($domain->verified_at)?->toIso8601String(),
                optional($domain->created_at)?->toIso8601String(),
            ]),
        );
    }

    public function show(Request $request, string $organizationId, string $domainId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $domain = Domain::query()
            ->with('dnsRecords')
            ->where('organization_id', $organization->id)
            ->whereKey($domainId)
            ->firstOrFail();

        return response()->json([
            'domain' => $domain,
            'cname_target' => config('azshrtr.domains.cname_target'),
        ]);
    }

    public function store(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $data = $request->validate(['hostname' => ['required', 'string', 'max:255']]);
        $domain = $this->domains->add($organization, $request->user(), $data['hostname']);

        return response()->json(['domain' => $domain], 201);
    }

    public function verify(Request $request, string $organizationId, string $domainId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $domain = Domain::query()->where('organization_id', $organization->id)->whereKey($domainId)->firstOrFail();
        $domain = $this->domains->verify($domain, $organization, $request->user());

        return response()->json(['domain' => $domain]);
    }

    public function destroy(Request $request, string $organizationId, string $domainId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $domain = Domain::query()->where('organization_id', $organization->id)->whereKey($domainId)->firstOrFail();
        $this->domains->delete($domain, $organization, $request->user());

        return response()->json(['ok' => true]);
    }

    public function destroyMany(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $ids = $this->bulkIds($request);

        $domains = Domain::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $ids)
            ->get();

        foreach ($domains as $domain) {
            $this->domains->delete($domain, $organization, $request->user());
        }

        return response()->json(['ok' => true, 'deleted' => $domains->count()]);
    }

    /**
     * @param  array{sort: string, direction: string, q: string|null, from: string|null, to: string|null}  $filters
     * @return Builder<Domain>
     */
    private function filteredQuery(string $organizationId, array $filters): Builder
    {
        $query = Domain::query()->where('organization_id', $organizationId);

        if ($filters['q'] !== null) {
            $q = $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('hostname', 'like', '%'.$q.'%')
                    ->orWhere('status', 'like', '%'.$q.'%');
            });
        }

        return $this->applySortAndDates($query, $filters);
    }
}
