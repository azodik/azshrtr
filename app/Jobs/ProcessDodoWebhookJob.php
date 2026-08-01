<?php

namespace App\Jobs;

use App\Enums\BillingPaymentEventKind;
use App\Models\BillingCustomer;
use App\Models\BillingPaymentEvent;
use App\Models\DodoWebhook;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Services\Billing\BillingPaymentEventRecorder;
use App\Services\Billing\BillingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDodoWebhookJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 3600;

    public function __construct(public string $webhookId) {}

    public function uniqueId(): string
    {
        return $this->webhookId;
    }

    public function handle(
        BillingService $billing,
        BillingPaymentEventRecorder $paymentEvents,
    ): void {
        $claimed = DB::transaction(function () {
            $webhook = DodoWebhook::query()
                ->whereKey($this->webhookId)
                ->lockForUpdate()
                ->first();

            if ($webhook === null) {
                return null;
            }

            if (in_array($webhook->status, ['processed', 'processing'], true)) {
                return null;
            }

            $webhook->forceFill([
                'status' => 'processing',
                'error' => null,
            ])->save();

            return $webhook;
        });

        if ($claimed === null) {
            return;
        }

        try {
            $payload = $claimed->payload ?? [];
            $type = strtolower((string) ($claimed->event_type ?? ''));
            /** @var array<string, mixed> $data */
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
            /** @var array<string, mixed> $metadata */
            $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];

            $organization = $this->resolveOrganization($data, $metadata, $type);
            $eventId = $this->stringOrNull($claimed->event_id)
                ?? $this->stringOrNull($payload['id'] ?? null)
                ?? ($claimed->id.':'.$type);

            if ($organization !== null && $this->isUpgradeEvent($type)) {
                $paymentId = $this->paymentIdFrom($data, $type);
                $subscriptionId = $this->stringOrNull($data['subscription_id'] ?? null)
                    ?? ($this->isSubscriptionPayload($type)
                        ? $this->stringOrNull($data['id'] ?? null)
                        : null);

                // Plan upgrades are applied only from webhooks (not checkout return).
                // Never send the welcome/upgraded mail here — one payment receipt email only.
                $billing->applyPro(
                    $organization,
                    $subscriptionId,
                    $this->customerIdFrom($data),
                    notify: false,
                );

                $paymentEvents->record(
                    BillingPaymentEventKind::PaymentSucceeded,
                    $organization,
                    [
                        'provider_event_id' => $eventId,
                        'provider_payment_id' => $paymentId,
                        'provider_checkout_session_id' => $this->checkoutSessionIdFrom($data),
                        'provider_subscription_id' => $subscriptionId,
                        'provider_customer_id' => $this->customerIdFrom($data),
                        'status' => $this->stringOrNull($data['status'] ?? null) ?? 'succeeded',
                        'currency' => $this->stringOrNull($data['currency'] ?? null),
                        'amount_cents' => is_numeric($data['total_amount'] ?? null)
                            ? (int) $data['total_amount']
                            : null,
                        'payload' => $data,
                        'metadata' => $metadata,
                        'send_email' => ! $paymentEvents->alreadyEmailed(
                            BillingPaymentEventKind::PaymentSucceeded,
                            $organization,
                            $paymentId,
                            $subscriptionId,
                            $this->checkoutSessionIdFrom($data),
                        ),
                    ],
                );
            } elseif ($organization !== null && $this->isCancelEvent($type)) {
                $billing->applyProviderCancellation($organization);
            } elseif ($this->isPaymentFailedEvent($type)) {
                $paymentId = $this->paymentIdFrom($data, $type);
                $subscriptionId = $this->stringOrNull($data['subscription_id'] ?? null);
                $checkoutSessionId = $this->checkoutSessionIdFrom($data);

                $paymentEvents->record(
                    BillingPaymentEventKind::PaymentFailed,
                    $organization,
                    [
                        'provider_event_id' => $eventId,
                        'provider_payment_id' => $paymentId,
                        'provider_checkout_session_id' => $checkoutSessionId,
                        'provider_subscription_id' => $subscriptionId,
                        'provider_customer_id' => $this->customerIdFrom($data),
                        'status' => $this->stringOrNull($data['status'] ?? null) ?? 'failed',
                        'currency' => $this->stringOrNull($data['currency'] ?? null),
                        'amount_cents' => is_numeric($data['total_amount'] ?? null)
                            ? (int) $data['total_amount']
                            : null,
                        'error_code' => $this->stringOrNull($data['error_code'] ?? null),
                        'error_message' => $this->stringOrNull($data['error_message'] ?? null),
                        'payload' => $data,
                        'metadata' => $metadata,
                        'send_email' => $organization !== null && ! $paymentEvents->alreadyEmailed(
                            BillingPaymentEventKind::PaymentFailed,
                            $organization,
                            $paymentId,
                            $subscriptionId,
                            $checkoutSessionId,
                        ),
                    ],
                );
            } elseif ($this->isAbandonedCheckoutEvent($type)) {
                $checkoutSessionId = $this->checkoutSessionIdFrom($data)
                    ?? $this->stringOrNull($data['checkout_id'] ?? null)
                    ?? $this->stringOrNull($data['id'] ?? null);

                $paymentEvents->record(
                    BillingPaymentEventKind::CheckoutAbandoned,
                    $organization,
                    [
                        'provider_event_id' => $eventId,
                        'provider_payment_id' => $this->stringOrNull($data['payment_id'] ?? null),
                        'provider_checkout_session_id' => $checkoutSessionId,
                        'provider_customer_id' => $this->customerIdFrom($data),
                        'status' => $this->stringOrNull($data['status'] ?? null)
                            ?? $this->stringOrNull($data['abandonment_reason'] ?? null)
                            ?? 'abandoned',
                        'currency' => $this->stringOrNull($data['currency'] ?? null),
                        'amount_cents' => is_numeric($data['total_amount'] ?? null)
                            ? (int) $data['total_amount']
                            : null,
                        'payload' => $data,
                        'metadata' => $metadata,
                        'send_email' => $organization !== null && ! $paymentEvents->alreadyEmailed(
                            BillingPaymentEventKind::CheckoutAbandoned,
                            $organization,
                            null,
                            null,
                            $checkoutSessionId,
                        ),
                    ],
                );
            } elseif ($this->isRefundEvent($type)) {
                $refundId = $this->refundIdFrom($data);
                $paymentId = $this->stringOrNull($data['payment_id'] ?? null);
                $status = strtolower((string) ($this->stringOrNull($data['status'] ?? null) ?? ''));
                $kinds = $this->refundEventKinds(
                    $type,
                    $status,
                    $organization !== null
                        && ! $paymentEvents->alreadyEmailed(
                            BillingPaymentEventKind::RefundInitiated,
                            $organization,
                            $paymentId,
                            null,
                            null,
                            $refundId,
                        ),
                );

                $refundMetadata = $metadata;
                if ($refundId !== null) {
                    $refundMetadata['refund_id'] = $refundId;
                }

                $amountCents = null;
                if (is_numeric($data['amount'] ?? null)) {
                    $amountCents = (int) $data['amount'];
                } elseif (is_numeric($data['total_amount'] ?? null)) {
                    $amountCents = (int) $data['total_amount'];
                }

                foreach ($kinds as $kind) {
                    $paymentEvents->record(
                        $kind,
                        $organization,
                        [
                            // Distinct ids so initiated+succeeded from one webhook both persist.
                            'provider_event_id' => count($kinds) > 1
                                ? $eventId.':'.$kind->value
                                : $eventId,
                            'provider_payment_id' => $paymentId,
                            'provider_subscription_id' => $this->stringOrNull($data['subscription_id'] ?? null),
                            'provider_customer_id' => $this->customerIdFrom($data),
                            'status' => $status !== '' ? $status : $kind->value,
                            'currency' => $this->stringOrNull($data['currency'] ?? null),
                            'amount_cents' => $amountCents,
                            'error_code' => $this->stringOrNull($data['error_code'] ?? null),
                            'error_message' => $this->stringOrNull($data['error_message'] ?? null)
                                ?? $this->stringOrNull($data['reason'] ?? null),
                            'payload' => $data,
                            'metadata' => $refundMetadata,
                            'send_email' => $organization !== null
                                && ! $paymentEvents->alreadyEmailed(
                                    $kind,
                                    $organization,
                                    $paymentId,
                                    null,
                                    null,
                                    $refundId,
                                ),
                        ],
                    );
                }
            }

            $claimed->forceFill([
                'status' => 'processed',
                'processed_at' => now(),
                'error' => null,
            ])->save();
        } catch (\Throwable $e) {
            Log::error('Dodo webhook processing failed', ['error' => $e->getMessage()]);
            $claimed->forceFill([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     */
    private function resolveOrganization(array $data, array $metadata, string $type): ?Organization
    {
        $organizationId = $metadata['organization_id'] ?? null;
        if (is_string($organizationId) && $organizationId !== '') {
            return Organization::query()->find($organizationId);
        }

        $checkoutSessionId = $this->checkoutSessionIdFrom($data)
            ?? ($this->isAbandonedCheckoutEvent($type)
                ? ($this->stringOrNull($data['checkout_id'] ?? null) ?? $this->stringOrNull($data['id'] ?? null))
                : null);

        if ($checkoutSessionId !== null) {
            $subscription = OrganizationSubscription::query()
                ->with('organization')
                ->where('provider_checkout_session_id', $checkoutSessionId)
                ->first();
            if ($subscription?->organization !== null) {
                return $subscription->organization;
            }
        }

        $subscriptionId = $this->stringOrNull($data['subscription_id'] ?? null);
        if ($subscriptionId === null && $this->isSubscriptionPayload($type)) {
            $subscriptionId = $this->stringOrNull($data['id'] ?? null);
        }

        if ($subscriptionId !== null) {
            $subscription = OrganizationSubscription::query()
                ->with('organization')
                ->where('provider_subscription_id', $subscriptionId)
                ->first();
            if ($subscription?->organization !== null) {
                return $subscription->organization;
            }
        }

        $customerId = $this->customerIdFrom($data);
        if ($customerId !== null) {
            return BillingCustomer::query()
                ->with('organization')
                ->where('provider_customer_id', $customerId)
                ->first()
                ?->organization;
        }

        $paymentId = $this->stringOrNull($data['payment_id'] ?? null);
        if ($paymentId !== null && $this->isRefundEvent($type)) {
            $prior = BillingPaymentEvent::query()
                ->with('organization')
                ->where('provider_payment_id', $paymentId)
                ->whereNotNull('organization_id')
                ->latest('created_at')
                ->first();
            if ($prior?->organization !== null) {
                return $prior->organization;
            }

            $subscription = OrganizationSubscription::query()
                ->with('organization')
                ->where('provider_subscription_id', $paymentId)
                ->first();
            if ($subscription?->organization !== null) {
                return $subscription->organization;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function customerIdFrom(array $data): ?string
    {
        return $this->stringOrNull($data['customer_id'] ?? null)
            ?? $this->stringOrNull(
                is_array($data['customer'] ?? null)
                    ? ($data['customer']['customer_id'] ?? null)
                    : null,
            );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function paymentIdFrom(array $data, string $type): ?string
    {
        return $this->stringOrNull($data['payment_id'] ?? null)
            ?? (str_contains($type, 'payment.')
                ? $this->stringOrNull($data['id'] ?? null)
                : null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function checkoutSessionIdFrom(array $data): ?string
    {
        return $this->stringOrNull($data['checkout_session_id'] ?? null)
            ?? $this->stringOrNull($data['session_id'] ?? null)
            ?? $this->stringOrNull($data['checkout_id'] ?? null);
    }

    private function isUpgradeEvent(string $type): bool
    {
        return str_contains($type, 'subscription.active')
            || str_contains($type, 'payment.succeeded')
            || str_contains($type, 'checkout.completed')
            || str_contains($type, 'abandoned_checkout.recovered');
    }

    private function isPaymentFailedEvent(string $type): bool
    {
        return str_contains($type, 'payment.failed')
            || str_contains($type, 'subscription.failed')
            || str_contains($type, 'payment.cancelled')
            || str_contains($type, 'payment.canceled');
    }

    private function isAbandonedCheckoutEvent(string $type): bool
    {
        return str_contains($type, 'abandoned_checkout.detected');
    }

    private function isRefundEvent(string $type): bool
    {
        return str_contains($type, 'refund.');
    }

    private function isCancelEvent(string $type): bool
    {
        return str_contains($type, 'subscription.cancelled')
            || str_contains($type, 'subscription.canceled')
            || str_contains($type, 'subscription.expired');
    }

    private function isSubscriptionPayload(string $type): bool
    {
        return str_contains($type, 'subscription.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function refundIdFrom(array $data): ?string
    {
        return $this->stringOrNull($data['refund_id'] ?? null)
            ?? $this->stringOrNull($data['id'] ?? null);
    }

    /**
     * Dodo publicly documents refund.succeeded / refund.failed. Pending/review
     * statuses (and any future refund.pending|created|initiated types) map to
     * "initiated". On succeeded, also emit initiated when it was never emailed —
     * create→pending often has no separate webhook.
     *
     * @return list<BillingPaymentEventKind>
     */
    private function refundEventKinds(string $type, string $status, bool $includeInitiatedOnSuccess): array
    {
        if (
            str_contains($type, 'refund.failed')
            || $status === 'failed'
        ) {
            return [];
        }

        if (
            str_contains($type, 'refund.pending')
            || str_contains($type, 'refund.created')
            || str_contains($type, 'refund.initiated')
            || in_array($status, ['pending', 'review'], true)
        ) {
            return [BillingPaymentEventKind::RefundInitiated];
        }

        if (
            str_contains($type, 'refund.succeeded')
            || $status === 'succeeded'
        ) {
            $kinds = [];
            if ($includeInitiatedOnSuccess) {
                $kinds[] = BillingPaymentEventKind::RefundInitiated;
            }
            $kinds[] = BillingPaymentEventKind::RefundSucceeded;

            return $kinds;
        }

        if ($status !== '') {
            return [BillingPaymentEventKind::RefundInitiated];
        }

        return [];
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
