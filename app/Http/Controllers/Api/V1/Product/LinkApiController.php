<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Concerns\ResolvesPagination;
use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\Organization;
use App\Models\User;
use App\Services\Links\LinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkApiController extends Controller
{
    use ResolvesPagination;

    public function __construct(private readonly LinkService $links) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('api_organization');

        return response()->json(
            Link::query()
                ->where('organization_id', $organization->id)
                ->latest()
                ->paginate($this->perPage($request, 50)),
        );
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('api_organization');
        $data = $request->validate([
            'destination_url' => ['required', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'max:128'],
            'domain_id' => ['nullable', 'uuid'],
        ]);

        $actor = User::query()->find(
            $request->attributes->get('api_key')->created_by,
        ) ?? $organization->members()->first()?->user;

        if ($actor === null) {
            return response()->json(['message' => 'No actor available for this key.'], 422);
        }

        $link = $this->links->createOwned($organization, $actor, $data);

        return response()->json(['link' => $link], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('api_organization');
        $link = Link::query()->where('organization_id', $organization->id)->whereKey($id)->firstOrFail();

        return response()->json(['link' => $link]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('api_organization');
        $link = Link::query()->where('organization_id', $organization->id)->whereKey($id)->firstOrFail();
        $data = $request->validate([
            'destination_url' => ['sometimes', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'max:128'],
            'is_disabled' => ['sometimes', 'boolean'],
        ]);

        $actor = User::query()->find($request->attributes->get('api_key')->created_by)
            ?? $organization->members()->first()?->user;

        $link = $this->links->update($link, $organization, $actor, $data);

        return response()->json(['link' => $link]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('api_organization');
        $link = Link::query()->where('organization_id', $organization->id)->whereKey($id)->firstOrFail();
        $actor = User::query()->find($request->attributes->get('api_key')->created_by)
            ?? $organization->members()->first()?->user;
        $this->links->delete($link, $organization, $actor);

        return response()->json(['ok' => true]);
    }

    public function clicks(Request $request, string $id): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('api_organization');
        $link = Link::query()->where('organization_id', $organization->id)->whereKey($id)->firstOrFail();

        return response()->json(
            $link->clicks()->latest('clicked_at')->paginate($this->perPage($request, 100)),
        );
    }
}
