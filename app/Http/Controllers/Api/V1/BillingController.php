<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Controller;
use App\Services\Billing\BillingService;
use App\Services\Billing\PlanEntitlements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    use EnsuresOrganizationMembership;

    public function __construct(
        private readonly BillingService $billing,
        private readonly PlanEntitlements $entitlements,
    ) {}

    public function show(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $organization->load('subscription.plan');

        return response()->json([
            'billing_enabled' => $this->entitlements->billingEnabled(),
            'plan' => $this->entitlements->planFor($organization),
            'subscription' => $organization->subscription,
            'can_manage_billing' => $this->memberRole($request)->canManageBilling(),
        ]);
    }

    public function checkout(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageBilling(), 403);

        $session = $this->billing->startProCheckout($organization, $request->user());

        return response()->json($session);
    }

    public function cancel(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageBilling(), 403);

        $this->billing->scheduleCancel($organization, $request->user());
        $organization->load('subscription.plan');

        return response()->json([
            'ok' => true,
            'subscription' => $organization->subscription,
            'plan' => $this->entitlements->planFor($organization),
        ]);
    }

    public function resume(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageBilling(), 403);

        $this->billing->resumePro($organization, $request->user());
        $organization->load('subscription.plan');

        return response()->json([
            'ok' => true,
            'subscription' => $organization->subscription,
            'plan' => $this->entitlements->planFor($organization),
        ]);
    }

    public function invoices(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 10)));

        return response()->json(
            $this->billing->listInvoices($organization, $page, $perPage),
        );
    }

    /**
     * Browser return from Dodo checkout. Never grants Pro — only surfaces
     * failed/cancelled attempts for audit + customer email when webhooks lag.
     */
    public function checkoutReturn(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $data = $request->validate([
            'status' => ['required', 'string', 'max:64'],
            'subscription_id' => ['sometimes', 'nullable', 'string', 'max:191'],
        ]);

        $status = strtolower($data['status']);
        if (in_array($status, ['failed', 'cancelled', 'canceled'], true)) {
            $this->billing->recordCheckoutReturnFailure(
                $organization,
                $request->user(),
                [
                    'status' => $status,
                    'subscription_id' => $data['subscription_id'] ?? null,
                ],
            );
        }

        $organization->load('subscription.plan');

        return response()->json([
            'ok' => true,
            'status' => $status,
            'plan' => $this->entitlements->planFor($organization),
            'subscription' => $organization->subscription,
        ]);
    }
}
