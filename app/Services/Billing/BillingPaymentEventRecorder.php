<?php

namespace App\Services\Billing;

use App\Enums\AuditAction;
use App\Enums\BillingPaymentEventKind;
use App\Models\BillingPaymentEvent;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Log;

class BillingPaymentEventRecorder
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly BillingNotifier $notifier,
    ) {}

    /**
     * @param  array{
     *     provider_event_id?: string|null,
     *     provider_payment_id?: string|null,
     *     provider_checkout_session_id?: string|null,
     *     provider_subscription_id?: string|null,
     *     provider_customer_id?: string|null,
     *     status?: string|null,
     *     currency?: string|null,
     *     amount_cents?: int|null,
     *     error_code?: string|null,
     *     error_message?: string|null,
     *     payload?: array<string, mixed>|null,
     *     metadata?: array<string, mixed>|null,
     *     send_email?: bool,
     *     actor?: User|null
     * }  $attributes
     */
    public function alreadyEmailed(
        BillingPaymentEventKind $kind,
        Organization $organization,
        ?string $paymentId = null,
        ?string $subscriptionId = null,
        ?string $checkoutSessionId = null,
        ?string $refundId = null,
    ): bool {
        if (
            $paymentId === null
            && $subscriptionId === null
            && $checkoutSessionId === null
            && $refundId === null
        ) {
            return false;
        }

        return BillingPaymentEvent::query()
            ->where('organization_id', $organization->id)
            ->where('kind', $kind)
            ->where('email_sent', true)
            ->where(function ($query) use ($paymentId, $subscriptionId, $checkoutSessionId, $refundId): void {
                if ($paymentId !== null) {
                    $query->orWhere('provider_payment_id', $paymentId);
                }
                if ($subscriptionId !== null) {
                    $query->orWhere('provider_subscription_id', $subscriptionId);
                }
                if ($checkoutSessionId !== null) {
                    $query->orWhere('provider_checkout_session_id', $checkoutSessionId);
                }
                if ($refundId !== null) {
                    $query->orWhere('metadata->refund_id', $refundId);
                }
            })
            ->exists();
    }

    public function record(
        BillingPaymentEventKind $kind,
        ?Organization $organization,
        array $attributes = [],
    ): BillingPaymentEvent {
        $providerEventId = $this->nullableString($attributes['provider_event_id'] ?? null);

        if ($providerEventId !== null) {
            $existing = BillingPaymentEvent::query()
                ->where('provider_event_id', $providerEventId)
                ->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        $event = BillingPaymentEvent::query()->create([
            'organization_id' => $organization?->id,
            'kind' => $kind,
            'provider' => 'dodo',
            'provider_event_id' => $providerEventId,
            'provider_payment_id' => $this->nullableString($attributes['provider_payment_id'] ?? null),
            'provider_checkout_session_id' => $this->nullableString($attributes['provider_checkout_session_id'] ?? null),
            'provider_subscription_id' => $this->nullableString($attributes['provider_subscription_id'] ?? null),
            'provider_customer_id' => $this->nullableString($attributes['provider_customer_id'] ?? null),
            'status' => $this->nullableString($attributes['status'] ?? null),
            'currency' => $this->nullableString($attributes['currency'] ?? null),
            'amount_cents' => isset($attributes['amount_cents']) && is_numeric($attributes['amount_cents'])
                ? (int) $attributes['amount_cents']
                : null,
            'error_code' => $this->nullableString($attributes['error_code'] ?? null),
            'error_message' => $this->nullableString($attributes['error_message'] ?? null),
            'payload' => is_array($attributes['payload'] ?? null) ? $attributes['payload'] : null,
            'metadata' => is_array($attributes['metadata'] ?? null) ? $attributes['metadata'] : null,
            'email_sent' => false,
        ]);

        $this->audit->log(
            $this->auditActionFor($kind),
            $attributes['actor'] ?? null,
            $organization,
            'billing_payment_event',
            $event->id,
            [
                'kind' => $kind->value,
                'provider_payment_id' => $event->provider_payment_id,
                'provider_checkout_session_id' => $event->provider_checkout_session_id,
                'amount_cents' => $event->amount_cents,
                'currency' => $event->currency,
                'error_code' => $event->error_code,
            ],
        );

        $shouldEmail = (bool) ($attributes['send_email'] ?? true);
        if ($shouldEmail && $organization !== null && $this->isCustomerFacingKind($kind)) {
            $this->sendEmail($organization, $kind, $event);
        }

        return $event;
    }

    private function sendEmail(
        Organization $organization,
        BillingPaymentEventKind $kind,
        BillingPaymentEvent $event,
    ): void {
        $mailKind = match ($kind) {
            BillingPaymentEventKind::PaymentSucceeded => 'payment_succeeded',
            BillingPaymentEventKind::PaymentFailed => 'payment_failed',
            BillingPaymentEventKind::CheckoutAbandoned => 'checkout_abandoned',
            BillingPaymentEventKind::RefundInitiated => 'refund_initiated',
            BillingPaymentEventKind::RefundSucceeded => 'refund_succeeded',
            default => null,
        };

        if ($mailKind === null) {
            return;
        }

        try {
            $this->notifier->notifyPaymentOutcome(
                $organization,
                $mailKind,
                $this->formatAmount($event->amount_cents, $event->currency),
            );

            $event->forceFill([
                'email_sent' => true,
                'emailed_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('Billing payment outcome email failed', [
                'kind' => $kind->value,
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function auditActionFor(BillingPaymentEventKind $kind): AuditAction
    {
        return match ($kind) {
            BillingPaymentEventKind::CheckoutStarted => AuditAction::BillingCheckoutStarted,
            BillingPaymentEventKind::PaymentSucceeded => AuditAction::BillingPaymentSucceeded,
            BillingPaymentEventKind::PaymentFailed => AuditAction::BillingPaymentFailed,
            BillingPaymentEventKind::CheckoutAbandoned => AuditAction::BillingCheckoutAbandoned,
            BillingPaymentEventKind::RefundInitiated => AuditAction::BillingRefundInitiated,
            BillingPaymentEventKind::RefundSucceeded => AuditAction::BillingRefundSucceeded,
        };
    }

    private function isCustomerFacingKind(BillingPaymentEventKind $kind): bool
    {
        return in_array($kind, [
            BillingPaymentEventKind::PaymentSucceeded,
            BillingPaymentEventKind::PaymentFailed,
            BillingPaymentEventKind::CheckoutAbandoned,
            BillingPaymentEventKind::RefundInitiated,
            BillingPaymentEventKind::RefundSucceeded,
        ], true);
    }

    private function formatAmount(?int $amountCents, ?string $currency): ?string
    {
        if ($amountCents === null) {
            return null;
        }

        $code = strtoupper($currency ?? 'USD');
        $value = number_format($amountCents / 100, 2);

        return $value.' '.$code;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
