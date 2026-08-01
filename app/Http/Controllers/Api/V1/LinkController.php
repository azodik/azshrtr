<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\DestroysMany;
use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Concerns\ResolvesListQuery;
use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Services\Links\ClaimService;
use App\Services\Links\LinkService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LinkController extends Controller
{
    use DestroysMany;
    use EnsuresOrganizationMembership;
    use ResolvesListQuery;

    public function __construct(
        private readonly LinkService $links,
        private readonly ClaimService $claims,
    ) {}

    public function index(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters($request, ['created_at', 'code', 'title', 'click_count', 'destination_url']);

        return response()->json(
            $this->filteredQuery($organization->id, $filters)
                ->paginate($this->perPage($request)),
        );
    }

    public function export(Request $request, string $organizationId): StreamedResponse|JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters($request, ['created_at', 'code', 'title', 'click_count', 'destination_url']);
        $format = $request->validate(['format' => ['sometimes', 'in:csv,json']])['format'] ?? 'csv';

        $rows = $this->filteredQuery($organization->id, $filters)->limit(5000)->get();

        if ($format === 'json') {
            return response()->json(['data' => $rows])->withHeaders([
                'Content-Disposition' => 'attachment; filename="links.json"',
            ]);
        }

        return $this->streamCsv(
            'links.csv',
            ['id', 'code', 'title', 'destination_url', 'click_count', 'created_at'],
            $rows->map(fn (Link $link) => [
                $link->id,
                $link->code,
                $link->title,
                $link->destination_url,
                $link->click_count,
                optional($link->created_at)?->toIso8601String(),
            ]),
        );
    }

    public function store(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $data = $request->validate([
            'destination_url' => ['required', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'min:4', 'max:128'],
            'domain_id' => ['nullable', 'uuid'],
        ]);

        $link = $this->links->createOwned($organization, $request->user(), $data);

        return response()->json(['link' => $link], 201);
    }

    public function show(Request $request, string $organizationId, string $linkId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $link = Link::query()->where('organization_id', $organization->id)->whereKey($linkId)->firstOrFail();

        return response()->json(['link' => $link]);
    }

    public function update(Request $request, string $organizationId, string $linkId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $link = Link::query()->where('organization_id', $organization->id)->whereKey($linkId)->firstOrFail();
        $data = $request->validate([
            'destination_url' => ['sometimes', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'max:128'],
            'is_disabled' => ['sometimes', 'boolean'],
        ]);

        $link = $this->links->update($link, $organization, $request->user(), $data);

        return response()->json(['link' => $link]);
    }

    public function destroy(Request $request, string $organizationId, string $linkId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $link = Link::query()->where('organization_id', $organization->id)->whereKey($linkId)->firstOrFail();
        $this->links->delete($link, $organization, $request->user());

        return response()->json(['ok' => true]);
    }

    public function destroyMany(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $ids = $this->bulkIds($request);

        $links = Link::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $ids)
            ->get();

        foreach ($links as $link) {
            $this->links->delete($link, $organization, $request->user());
        }

        return response()->json(['ok' => true, 'deleted' => $links->count()]);
    }

    public function claim(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'organization_id' => ['required', 'uuid'],
        ]);

        $organization = $this->organization($request, $data['organization_id']);
        $link = $this->claims->claim($data['token'], $organization, $request->user());

        return response()->json(['link' => $link]);
    }

    /**
     * @param  array{sort: string, direction: string, q: string|null, from: string|null, to: string|null}  $filters
     * @return Builder<Link>
     */
    private function filteredQuery(string $organizationId, array $filters): Builder
    {
        $query = Link::query()->where('organization_id', $organizationId);

        if ($filters['q'] !== null) {
            $q = $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('code', 'like', '%'.$q.'%')
                    ->orWhere('title', 'like', '%'.$q.'%')
                    ->orWhere('destination_url', 'like', '%'.$q.'%');
            });
        }

        return $this->applySortAndDates($query, $filters);
    }
}
