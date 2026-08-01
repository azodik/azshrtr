<?php

namespace App\Services\Billing;

use App\Enums\AuditAction;
use App\Enums\BillingPaymentEventKind;
use App\Enums\SubscriptionStatus;
use App\Models\BillingCustomer;
use App\Models\BillingPaymentEvent;
use App\Models\BillingPlan;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BillingService
{
    public function __construct(
        private readonly DodoPaymentsClient $dodo,
        private readonly PlanEntitlements $entitlements,
        private readonly AuditLogger $audit,
        private readonly BillingNotifier $notifier,
        private readonly BillingPaymentEventRecorder $paymentEvents,
    ) {}

    /**
     * @return array{checkout_url: string, session_id: string}
     */
    public function startProCheckout(Organization $organization, User $user): array
    {
        if (! $this->entitlements->billingEnabled()) {
            throw ValidationException::withMessages([
                'billing' => ['Billing isn’t available right now. Please try again later.'],
            ]);
        }

        if (! $this->dodo->isConfigured()) {
            throw ValidationException::withMessages([
                'billing' => ['Payments aren’t set up on this workspace yet. Please try again later.'],
            ]);
        }

        $organization->loadMissing('subscription');

        // Never open a second paid checkout while already on Pro (avoids double charges).
        if ($this->entitlements->isPro($organization)) {
            if ($organization->subscription?->cancel_at !== null) {
                throw ValidationException::withMessages([
                    'billing' => ['You’re already on Pro. Choose Keep Pro on the Billing page if you want to stay subscribed.'],
                ]);
            }

            throw ValidationException::withMessages([
                'billing' => ['You’re already on Pro — no need to pay again.'],
            ]);
        }

        if ($this->hasOpenCheckout($organization)) {
            throw ValidationException::withMessages([
                'billing' => ['You already have a checkout in progress. Finish that payment, or wait a few minutes before starting again.'],
            ]);
        }

        $pro = BillingPlan::query()->where('slug', 'pro')->firstOrFail();

        try {
            $session = $this->dodo->createCheckoutSession($organization, $pro, $user);
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'billing' => ['We couldn’t start checkout right now. Please try again in a moment.'],
            ]);
        }

        $subscription = $organization->subscription;
        if ($subscription === null) {
            OrganizationSubscription::query()->create([
                'organization_id' => $organization->id,
                'billing_plan_id' => $pro->id,
                'status' => SubscriptionStatus::Incomplete,
                'provider_checkout_session_id' => $session['session_id'],
            ]);
        } else {
            $subscription->forceFill([
                'provider_checkout_session_id' => $session['session_id'],
            ])->save();
        }

        $this->paymentEvents->record(
            BillingPaymentEventKind::CheckoutStarted,
            $organization,
            [
                'provider_event_id' => 'checkout_started:'.$session['session_id'],
                'provider_checkout_session_id' => $session['session_id'],
                'status' => 'started',
                'metadata' => [
                    'billing_plan_slug' => 'pro',
                ],
                'send_email' => false,
                'actor' => $user,
            ],
        );

        return $session;
    }

    public function applyPro(
        Organization $organization,
        ?string $dodoSubscriptionId = null,
        ?string $dodoCustomerId = null,
        bool $notify = true,
    ): void {
        $wasPro = $this->entitlements->isPro($organization);
        $pro = BillingPlan::query()->where('slug', 'pro')->firstOrFail();
        $subscription = $organization->subscription;

        if ($dodoCustomerId !== null && $dodoCustomerId !== '') {
            BillingCustomer::query()->updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'provider' => 'dodo',
                    'provider_customer_id' => $dodoCustomerId,
                ],
            );
        }

        if ($subscription === null) {
            OrganizationSubscription::query()->create([
                'organization_id' => $organization->id,
                'billing_plan_id' => $pro->id,
                'status' => SubscriptionStatus::Active,
                'provider_subscription_id' => $dodoSubscriptionId,
                'current_period_start' => now(),
                'current_period_end' => now()->addYear(),
            ]);
        } else {
            $subscription->forceFill([
                'billing_plan_id' => $pro->id,
                'status' => SubscriptionStatus::Active,
                'provider_subscription_id' => $dodoSubscriptionId ?? $subscription->provider_subscription_id,
                'cancelled_at' => null,
                'cancel_at' => null,
                'current_period_start' => now(),
                'current_period_end' => now()->addYear(),
            ])->save();
        }

        $organization->unsetRelation('subscription');
        $this->audit->log(AuditAction::BillingUpgraded, null, $organization, 'subscription', $organization->id);

        if ($notify && ! $wasPro) {
            $this->notifier->notifyUpgraded($organization->fresh() ?? $organization);
        }
    }

    public function scheduleCancel(Organization $organization, User $user): void
    {
        $organization->loadMissing('subscription.plan');
        $subscription = $organization->subscription;
        if ($subscription === null || $subscription->plan?->isFree()) {
            throw ValidationException::withMessages([
                'billing' => ['No active Pro subscription to cancel.'],
            ]);
        }

        if ($subscription->cancel_at !== null) {
            throw ValidationException::withMessages([
                'billing' => ['A downgrade is already scheduled for the end of this billing period.'],
            ]);
        }

        if (filled($subscription->provider_subscription_id) && $this->dodo->isConfigured()) {
            try {
                $this->dodo->cancelSubscriptionAtPeriodEnd($subscription->provider_subscription_id);
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages([
                    'billing' => [$e->getMessage()],
                ]);
            }
        }

        $cancelAt = $subscription->current_period_end ?? now();

        $subscription->forceFill([
            'cancel_at' => $cancelAt,
            'cancelled_at' => now(),
        ])->save();

        $this->audit->log(AuditAction::BillingCancelled, $user, $organization, 'subscription', $subscription->id);
        $this->notifier->notifyDowngradeScheduled($organization, $cancelAt);
    }

    public function resumePro(Organization $organization, User $user): void
    {
        $organization->loadMissing('subscription.plan');
        $subscription = $organization->subscription;
        if (
            $subscription === null
            || $subscription->plan?->isFree()
            || $subscription->cancel_at === null
        ) {
            throw ValidationException::withMessages([
                'billing' => ['There is no scheduled downgrade to cancel.'],
            ]);
        }

        if (filled($subscription->provider_subscription_id) && $this->dodo->isConfigured()) {
            try {
                $this->dodo->resumeSubscription($subscription->provider_subscription_id);
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages([
                    'billing' => [$e->getMessage()],
                ]);
            }
        }

        $subscription->forceFill([
            'cancel_at' => null,
            'cancelled_at' => null,
        ])->save();

        $this->audit->log(AuditAction::BillingUpgraded, $user, $organization, 'subscription', $subscription->id);
    }

    public function applyScheduledCancellations(): int
    {
        $count = 0;
        OrganizationSubscription::query()
            ->with('organization')
            ->whereNotNull('cancel_at')
            ->where('cancel_at', '<=', now())
            ->where('status', SubscriptionStatus::Active)
            ->each(function (OrganizationSubscription $subscription) use (&$count): void {
                $organization = $subscription->organization;
                if ($organization !== null) {
                    $this->downgradeToFree($organization, notify: true);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Trust provider webhook: subscription cancelled / expired → Free immediately.
     */
    public function applyProviderCancellation(Organization $organization): void
    {
        $organization->loadMissing('subscription.plan');
        $subscription = $organization->subscription;
        if ($subscription === null || $subscription->plan?->isFree()) {
            return;
        }

        $subscriptionId = $subscription->id;
        $this->downgradeToFree($organization, notify: true);
        $this->audit->log(
            AuditAction::BillingCancelled,
            null,
            $organization,
            'subscription',
            $subscriptionId,
        );
    }

    /**
     * Open checkout without a later failure/success — blocks starting another paid session.
     */
    private function hasOpenCheckout(Organization $organization): bool
    {
        $started = BillingPaymentEvent::query()
            ->where('organization_id', $organization->id)
            ->where('kind', BillingPaymentEventKind::CheckoutStarted)
            ->where('created_at', '>=', now()->subMinutes(20))
            ->latest('created_at')
            ->first();

        if ($started === null) {
            return false;
        }

        $resolved = BillingPaymentEvent::query()
            ->where('organization_id', $organization->id)
            ->whereIn('kind', [
                BillingPaymentEventKind::PaymentSucceeded,
                BillingPaymentEventKind::PaymentFailed,
                BillingPaymentEventKind::CheckoutAbandoned,
            ])
            ->where('created_at', '>=', $started->created_at)
            ->exists();

        return ! $resolved;
    }

    /**
     * Checkout return URL reported a failed/cancelled attempt.
     * Does not change plan — only records + emails when webhooks are delayed/missing.
     *
     * @param  array{status: string, subscription_id?: string|null}  $payload
     */
    public function recordCheckoutReturnFailure(
        Organization $organization,
        User $user,
        array $payload,
    ): void {
        $status = strtolower($payload['status']);
        if (! in_array($status, ['failed', 'cancelled', 'canceled'], true)) {
            return;
        }

        $subscriptionId = is_string($payload['subscription_id'] ?? null)
            ? $payload['subscription_id']
            : null;

        $subscription = $organization->subscription;
        if ($subscription !== null && filled($subscriptionId) && ! filled($subscription->provider_subscription_id)) {
            $subscription->forceFill([
                'provider_subscription_id' => $subscriptionId,
            ])->save();
        }

        $checkoutSessionId = $organization->subscription?->provider_checkout_session_id;
        $idempotencyKey = filled($subscriptionId)
            ? 'checkout_return_failed:'.$subscriptionId
            : 'checkout_return_failed:'.$organization->id.':'.now()->format('YmdHi');

        $this->paymentEvents->record(
            BillingPaymentEventKind::PaymentFailed,
            $organization,
            [
                'provider_event_id' => $idempotencyKey,
                'provider_subscription_id' => $subscriptionId,
                'provider_checkout_session_id' => $checkoutSessionId,
                'status' => $status,
                'metadata' => [
                    'source' => 'checkout_return',
                    'reported_by' => $user->id,
                ],
                'send_email' => ! $this->paymentEvents->alreadyEmailed(
                    BillingPaymentEventKind::PaymentFailed,
                    $organization,
                    null,
                    $subscriptionId,
                    $checkoutSessionId,
                ),
                'actor' => $user,
            ],
        );
    }

    /**
     * @return array{
     *     data: list<array{
     *         id: string,
     *         status: string|null,
     *         currency: string|null,
     *         amount: int|null,
     *         created_at: string|null,
     *         invoice_url: string|null,
     *         subscription_id: string|null
     *     }>,
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int,
     *     from: int|null,
     *     to: int|null
     * }
     */
    public function listInvoices(Organization $organization, int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));

        $empty = [
            'data' => [],
            'current_page' => $page,
            'last_page' => 1,
            'per_page' => $perPage,
            'total' => 0,
            'from' => null,
            'to' => null,
        ];

        if (! $this->entitlements->billingEnabled() || ! $this->dodo->isConfigured()) {
            return $empty;
        }

        $organization->loadMissing(['billingCustomer', 'subscription']);
        $customerId = $organization->billingCustomer?->provider_customer_id;
        $subscriptionId = $organization->subscription?->provider_subscription_id;

        if (! filled($customerId) && ! filled($subscriptionId)) {
            return $empty;
        }

        try {
            $result = $this->dodo->listPayments(
                filled($customerId) ? $customerId : null,
                filled($customerId) ? null : $subscriptionId,
                $perPage,
                $page - 1,
            );
        } catch (RuntimeException) {
            return $empty;
        }

        $rows = [];
        foreach ($result['items'] as $payment) {
            $paymentId = $payment['payment_id'] ?? $payment['id'] ?? null;
            if (! is_string($paymentId) || $paymentId === '') {
                continue;
            }

            $amount = $payment['total_amount'] ?? null;
            $createdAt = $payment['created_at'] ?? null;
            $invoiceUrl = $payment['invoice_url'] ?? null;
            $currency = $payment['currency'] ?? null;
            $status = $payment['status'] ?? 'succeeded';
            $subId = $payment['subscription_id'] ?? null;

            $rows[] = [
                'id' => $paymentId,
                'status' => is_string($status) ? $status : null,
                'currency' => is_string($currency) ? $currency : null,
                'amount' => is_numeric($amount) ? (int) $amount : null,
                'created_at' => is_string($createdAt) ? $createdAt : null,
                'invoice_url' => is_string($invoiceUrl) ? $invoiceUrl : null,
                'subscription_id' => is_string($subId) ? $subId : null,
            ];
        }

        $count = count($rows);
        $hasMore = $result['has_more'];
        $lastPage = $hasMore ? $page + 1 : $page;
        $total = $hasMore
            ? ($page * $perPage) + 1
            : (($page - 1) * $perPage) + $count;

        return [
            'data' => $rows,
            'current_page' => $page,
            'last_page' => max(1, $lastPage),
            'per_page' => $perPage,
            'total' => $total,
            'from' => $count > 0 ? (($page - 1) * $perPage) + 1 : null,
            'to' => $count > 0 ? (($page - 1) * $perPage) + $count : null,
        ];
    }

    private function downgradeToFree(Organization $organization, bool $notify): void
    {
        $free = BillingPlan::query()->where('slug', 'free')->first();
        if ($free === null) {
            return;
        }

        $subscription = $organization->subscription;
        if ($subscription === null) {
            return;
        }

        $subscription->forceFill([
            'billing_plan_id' => $free->id,
            'status' => SubscriptionStatus::Cancelled,
            'cancel_at' => null,
            'cancelled_at' => $subscription->cancelled_at ?? now(),
        ])->save();

        $organization->unsetRelation('subscription');

        if ($notify) {
            $this->notifier->notifyDowngraded($organization);
        }
    }
}
