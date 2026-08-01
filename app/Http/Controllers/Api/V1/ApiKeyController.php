<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\DestroysMany;
use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Concerns\ResolvesListQuery;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\ApiKeys\ApiKeyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiKeyController extends Controller
{
    use DestroysMany;
    use EnsuresOrganizationMembership;
    use ResolvesListQuery;

    public function __construct(private readonly ApiKeyService $keys) {}

    public function index(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters($request, ['created_at', 'name', 'last_used_at', 'revoked_at']);

        $keys = $this->filteredQuery($organization->id, $filters)
            ->with('scopeRows')
            ->paginate($this->perPage($request))
            ->through(fn (ApiKey $key) => $this->serialize($key));

        return response()->json(array_merge($keys->toArray(), [
            'can_manage' => $this->memberRole($request)->canManageMembers(),
        ]));
    }

    public function export(Request $request, string $organizationId): StreamedResponse|JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters($request, ['created_at', 'name', 'last_used_at', 'revoked_at']);
        $format = $request->validate(['format' => ['sometimes', 'in:csv,json']])['format'] ?? 'csv';

        $rows = $this->filteredQuery($organization->id, $filters)->with('scopeRows')->limit(5000)->get();
        $payload = $rows->map(fn (ApiKey $key) => $this->serialize($key));

        if ($format === 'json') {
            return response()->json(['data' => $payload])->withHeaders([
                'Content-Disposition' => 'attachment; filename="api-keys.json"',
            ]);
        }

        return $this->streamCsv(
            'api-keys.csv',
            ['id', 'name', 'prefix', 'last_four', 'scopes', 'last_used_at', 'revoked_at', 'created_at'],
            $payload->map(fn (array $key) => [
                $key['id'],
                $key['name'],
                $key['prefix'],
                $key['last_four'],
                implode(',', $key['scopes']),
                $key['last_used_at'],
                $key['revoked_at'],
                $key['created_at'],
            ]),
        );
    }

    public function show(Request $request, string $organizationId, string $apiKeyId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $apiKey = ApiKey::query()
            ->with('scopeRows')
            ->where('organization_id', $organization->id)
            ->whereKey($apiKeyId)
            ->firstOrFail();

        return response()->json(['api_key' => $this->serialize($apiKey)]);
    }

    public function store(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageMembers(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => ['string'],
        ]);

        $result = $this->keys->create($organization, $request->user(), $data['name'], $data['scopes'] ?? null);
        $apiKey = $result['api_key'];

        return response()->json([
            'api_key' => $this->serialize($apiKey),
            'plain_text' => $result['plain_text'],
        ], 201);
    }

    public function destroy(Request $request, string $organizationId, string $apiKeyId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageMembers(), 403);

        $apiKey = ApiKey::query()->where('organization_id', $organization->id)->whereKey($apiKeyId)->firstOrFail();
        $this->keys->revoke($apiKey, $organization, $request->user());

        return response()->json(['ok' => true]);
    }

    public function destroyMany(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageMembers(), 403);

        $ids = $this->bulkIds($request);

        $keys = ApiKey::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $ids)
            ->whereNull('revoked_at')
            ->get();

        foreach ($keys as $apiKey) {
            $this->keys->revoke($apiKey, $organization, $request->user());
        }

        return response()->json(['ok' => true, 'deleted' => $keys->count()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ApiKey $key): array
    {
        return [
            'id' => $key->id,
            'name' => $key->name,
            'prefix' => $key->prefix,
            'last_four' => $key->last_four,
            'scopes' => $key->scopeValues(),
            'last_used_at' => $key->last_used_at?->toIso8601String(),
            'revoked_at' => $key->revoked_at?->toIso8601String(),
            'created_at' => $key->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array{sort: string, direction: string, q: string|null, from: string|null, to: string|null}  $filters
     * @return Builder<ApiKey>
     */
    private function filteredQuery(string $organizationId, array $filters): Builder
    {
        $query = ApiKey::query()->where('organization_id', $organizationId);

        if ($filters['q'] !== null) {
            $q = $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('prefix', 'like', '%'.$q.'%')
                    ->orWhere('last_four', 'like', '%'.$q.'%');
            });
        }

        return $this->applySortAndDates($query, $filters);
    }
}
