<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationService $organizations) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizations = $user->organizations()
            ->orderBy('name')
            ->get()
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'role' => $org->pivot->role,
            ])
            ->values();

        return response()->json(['organizations' => $organizations]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $organization = $this->organizations->createForUser($request->user(), $data['name']);
        $request->user()->load(['organizations' => fn ($q) => $q->orderBy('name')]);

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'role' => 'owner',
            ],
            'organizations' => $request->user()->organizations->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'role' => $org->pivot->role,
            ])->values(),
        ], 201);
    }
}
