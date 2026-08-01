<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Billing\PlanEntitlements;
use App\Services\Usage\UsageTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(
        private readonly UsageTracker $usage,
        private readonly PlanEntitlements $entitlements,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('api_organization');
        $counter = $this->usage->counter($organization);
        $plan = $this->entitlements->planFor($organization);

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'plan' => [
                'slug' => $plan->slug,
                'name' => $plan->name,
            ],
            'usage' => [
                'period' => $counter->period,
                'links_created' => $counter->links_created,
                'qr_generated' => $counter->qr_generated,
                'api_calls' => $counter->api_calls,
            ],
        ]);
    }
}
