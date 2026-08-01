<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LinkClick;
use App\Services\Billing\PlanEntitlements;
use App\Services\Usage\UsageTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    use EnsuresOrganizationMembership;

    public function __construct(
        private readonly UsageTracker $usage,
        private readonly PlanEntitlements $entitlements,
    ) {}

    public function __invoke(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $counter = $this->usage->counter($organization);
        $plan = $this->entitlements->planFor($organization);
        $isFree = $this->entitlements->isFree($organization);

        $clicks7d = LinkClick::query()
            ->where('organization_id', $organization->id)
            ->where('clicked_at', '>=', now()->subDays(7))
            ->count();

        $clicks30d = LinkClick::query()
            ->where('organization_id', $organization->id)
            ->where('clicked_at', '>=', now()->subDays(30))
            ->count();

        $topCountries = LinkClick::query()
            ->where('organization_id', $organization->id)
            ->where('clicked_at', '>=', now()->subDays(30))
            ->whereNotNull('country')
            ->selectRaw('country, count(*) as total')
            ->groupBy('country')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'country' => $row->country,
                'total' => (int) $row->total,
            ]);

        $recentAudit = AuditLog::query()
            ->where('organization_id', $organization->id)
            ->latest('created_at')
            ->limit(10)
            ->get();

        $freePool = null;
        if ($isFree && $this->entitlements->billingEnabled()) {
            $owner = $this->entitlements->owner($organization);
            $free = $this->entitlements->freePlan();
            $pool = $owner !== null
                ? $this->entitlements->freePoolUsage($owner)
                : ['links_created' => 0, 'qr_generated' => 0, 'api_keys' => 0, 'organization_ids' => []];

            $freePool = [
                'links_created' => $pool['links_created'],
                'qr_generated' => $pool['qr_generated'],
                'api_keys' => $pool['api_keys'],
                'links_per_month' => $free->links_per_month,
                'qr_per_month' => $free->qr_per_month,
                'api_keys_limit' => (int) $free->api_keys_limit,
                'organization_count' => count($pool['organization_ids']),
            ];
        }

        return response()->json([
            'plan' => [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'links_per_month' => $this->entitlements->linksPerMonth($organization),
                'qr_per_month' => $this->entitlements->qrPerMonth($organization),
            ],
            'usage' => [
                'period' => $counter->period,
                'links_created' => $counter->links_created,
                'qr_generated' => $counter->qr_generated,
                'api_calls' => $counter->api_calls,
                'scope' => $isFree ? 'free_pool' : 'organization',
            ],
            'free_pool' => $freePool,
            'clicks' => [
                'last_7_days' => $clicks7d,
                'last_30_days' => $clicks30d,
            ],
            'top_countries' => $topCountries,
            'recent_audit' => $recentAudit,
            'billing_enabled' => $this->entitlements->billingEnabled(),
        ]);
    }
}
