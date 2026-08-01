<?php

namespace Tests\Feature;

use App\Enums\BillingPaymentEventKind;
use App\Enums\SubscriptionStatus;
use App\Jobs\ProcessDodoWebhookJob;
use App\Mail\BillingPaymentMail;
use App\Mail\SubscriptionChangeMail;
use App\Models\AuditLog;
use App\Models\BillingCustomer;
use App\Models\BillingPaymentEvent;
use App\Models\BillingPlan;
use App\Models\DodoWebhook;
use App\Models\OrganizationSubscription;
use App\Models\User;
use App\Services\Billing\BillingService;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function scheduling_downgrade_sets_cancel_at_and_emails_owners(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Pro workspace');
        $proPlan = BillingPlan::query()->where('slug', 'pro')->firstOrFail();
        $periodEnd = now()->addMonth();

        OrganizationSubscription::query()->where('organization_id', $org->id)->update([
            'billing_plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'current_period_start' => now()->subMonth(),
            'current_period_end' => $periodEnd,
            'cancel_at' => null,
            'cancelled_at' => null,
        ]);
        $org->unsetRelation('subscription');

        app(BillingService::class)->scheduleCancel($org->fresh(), $owner);

        $subscription = $org->fresh()->subscription;
        $this->assertNotNull($subscription?->cancel_at);
        $this->assertTrue(
            $subscription->cancel_at->equalTo($periodEnd->copy()->micro(0))
                || $subscription->cancel_at->diffInSeconds($periodEnd) < 2,
        );

        Mail::assertSent(SubscriptionChangeMail::class, function (SubscriptionChangeMail $mail) use ($org): bool {
            return $mail->kind === 'downgrade_scheduled'
                && $mail->organizationName === $org->name;
        });
    }

    #[Test]
    public function applying_scheduled_cancellations_moves_to_free_and_emails(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Pro workspace');
        $proPlan = BillingPlan::query()->where('slug', 'pro')->firstOrFail();

        OrganizationSubscription::query()->where('organization_id', $org->id)->update([
            'billing_plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'current_period_end' => now()->subDay(),
            'cancel_at' => now()->subMinute(),
            'cancelled_at' => now()->subDay(),
        ]);

        $count = app(BillingService::class)->applyScheduledCancellations();
        $this->assertSame(1, $count);

        $subscription = $org->fresh()->subscription;
        $this->assertSame('free', $subscription?->plan?->slug);
        $this->assertSame(SubscriptionStatus::Cancelled, $subscription?->status);

        Mail::assertSent(SubscriptionChangeMail::class, function (SubscriptionChangeMail $mail) use ($org): bool {
            return $mail->kind === 'downgraded'
                && $mail->organizationName === $org->name;
        });
    }

    #[Test]
    public function resume_clears_scheduled_downgrade(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Pro workspace');
        $proPlan = BillingPlan::query()->where('slug', 'pro')->firstOrFail();

        OrganizationSubscription::query()->where('organization_id', $org->id)->update([
            'billing_plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'current_period_end' => now()->addMonth(),
            'cancel_at' => now()->addMonth(),
            'cancelled_at' => now(),
        ]);
        $org->unsetRelation('subscription');

        app(BillingService::class)->resumePro($org->fresh(), $owner);

        $subscription = $org->fresh()->subscription;
        $this->assertNull($subscription?->cancel_at);
        $this->assertNull($subscription?->cancelled_at);
        $this->assertSame('pro', $subscription?->plan?->slug);
    }

    #[Test]
    public function subscription_cancelled_webhook_downgrades_to_free(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Pro workspace');
        $proPlan = BillingPlan::query()->where('slug', 'pro')->firstOrFail();

        OrganizationSubscription::query()->where('organization_id', $org->id)->update([
            'billing_plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'provider_subscription_id' => 'sub_dodo_cancel_1',
            'current_period_end' => now()->addMonth(),
            'cancel_at' => null,
            'cancelled_at' => null,
        ]);

        $webhook = DodoWebhook::query()->create([
            'event_id' => 'evt_cancel_1',
            'event_type' => 'subscription.cancelled',
            'payload' => [
                'type' => 'subscription.cancelled',
                'data' => [
                    'id' => 'sub_dodo_cancel_1',
                    'metadata' => [
                        'organization_id' => $org->id,
                    ],
                ],
            ],
            'status' => 'pending',
        ]);

        app()->call([new ProcessDodoWebhookJob($webhook->id), 'handle']);

        $subscription = $org->fresh()->subscription;
        $this->assertSame('free', $subscription?->plan?->slug);
        $this->assertSame(SubscriptionStatus::Cancelled, $subscription?->status);
        $this->assertSame('processed', $webhook->fresh()->status);

        Mail::assertSent(SubscriptionChangeMail::class, function (SubscriptionChangeMail $mail) use ($org): bool {
            return $mail->kind === 'downgraded'
                && $mail->organizationName === $org->name;
        });
    }

    #[Test]
    public function invoices_endpoint_returns_paginated_dodo_payments(): void
    {
        config([
            'billing.enabled' => true,
            'billing.dodo.api_key' => 'test_key',
            'billing.dodo.base_url' => 'https://test.dodopayments.com',
        ]);
        $this->seedCore();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Billed workspace');

        BillingCustomer::query()->create([
            'organization_id' => $org->id,
            'provider' => 'dodo',
            'provider_customer_id' => 'cus_test_1',
        ]);

        Http::fake([
            'test.dodopayments.com/payments*' => Http::response([
                'items' => [
                    [
                        'payment_id' => 'pay_1',
                        'status' => 'succeeded',
                        'currency' => 'USD',
                        'total_amount' => 2000,
                        'created_at' => '2026-07-01T12:00:00Z',
                        'invoice_url' => 'https://example.com/invoice/1',
                        'subscription_id' => 'sub_1',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/organizations/{$org->id}/billing/invoices?page=1&per_page=10")
            ->assertOk()
            ->assertJsonPath('data.0.id', 'pay_1')
            ->assertJsonPath('data.0.amount', 2000)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total', 1);
    }

    #[Test]
    public function invoices_endpoint_returns_empty_when_no_customer(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Free workspace');

        $this->actingAs($owner)
            ->getJson("/api/v1/organizations/{$org->id}/billing/invoices")
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('total', 0);
    }

    #[Test]
    public function payment_succeeded_webhook_stores_event_audits_and_emails(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Pay workspace');

        $webhook = DodoWebhook::query()->create([
            'event_id' => 'evt_pay_ok_1',
            'event_type' => 'payment.succeeded',
            'payload' => [
                'type' => 'payment.succeeded',
                'data' => [
                    'payment_id' => 'pay_ok_1',
                    'subscription_id' => 'sub_ok_1',
                    'status' => 'succeeded',
                    'currency' => 'USD',
                    'total_amount' => 2000,
                    'customer_id' => 'cus_pay_1',
                    'metadata' => [
                        'organization_id' => $org->id,
                    ],
                ],
            ],
            'status' => 'received',
        ]);

        app()->call([new ProcessDodoWebhookJob($webhook->id), 'handle']);

        $this->assertSame('pro', $org->fresh()->subscription?->plan?->slug);

        $event = BillingPaymentEvent::query()
            ->where('provider_event_id', 'evt_pay_ok_1')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame(BillingPaymentEventKind::PaymentSucceeded, $event->kind);
        $this->assertTrue($event->email_sent);
        $this->assertSame('processed', $webhook->fresh()->status);

        $this->assertTrue(
            AuditLog::query()
                ->where('organization_id', $org->id)
                ->where('action', 'billing.payment_succeeded')
                ->exists(),
        );

        Mail::assertSent(BillingPaymentMail::class, function (BillingPaymentMail $mail) use ($org): bool {
            return $mail->kind === 'payment_succeeded'
                && $mail->organizationName === $org->name;
        });
        Mail::assertNotSent(SubscriptionChangeMail::class);
    }

    #[Test]
    public function related_success_webhooks_send_only_one_email_and_process_once(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Once workspace');

        $paymentWebhook = DodoWebhook::query()->create([
            'event_id' => 'evt_once_pay',
            'event_type' => 'payment.succeeded',
            'payload' => [
                'type' => 'payment.succeeded',
                'data' => [
                    'payment_id' => 'pay_once_1',
                    'subscription_id' => 'sub_once_1',
                    'status' => 'succeeded',
                    'currency' => 'USD',
                    'total_amount' => 2000,
                    'metadata' => ['organization_id' => $org->id],
                ],
            ],
            'status' => 'received',
        ]);

        $subscriptionWebhook = DodoWebhook::query()->create([
            'event_id' => 'evt_once_sub',
            'event_type' => 'subscription.active',
            'payload' => [
                'type' => 'subscription.active',
                'data' => [
                    'id' => 'sub_once_1',
                    'subscription_id' => 'sub_once_1',
                    'status' => 'active',
                    'metadata' => ['organization_id' => $org->id],
                ],
            ],
            'status' => 'received',
        ]);

        app()->call([new ProcessDodoWebhookJob($paymentWebhook->id), 'handle']);
        app()->call([new ProcessDodoWebhookJob($subscriptionWebhook->id), 'handle']);
        // Replay must be a no-op.
        app()->call([new ProcessDodoWebhookJob($paymentWebhook->id), 'handle']);

        Mail::assertSent(BillingPaymentMail::class, 1);
        Mail::assertNotSent(SubscriptionChangeMail::class);
        $this->assertSame('processed', $paymentWebhook->fresh()->status);
        $this->assertSame('processed', $subscriptionWebhook->fresh()->status);
        $this->assertSame(
            1,
            BillingPaymentEvent::query()
                ->where('organization_id', $org->id)
                ->where('kind', BillingPaymentEventKind::PaymentSucceeded)
                ->where('email_sent', true)
                ->count(),
        );
    }

    #[Test]
    public function payment_failed_webhook_stores_event_audits_and_emails(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Fail workspace');

        OrganizationSubscription::query()->where('organization_id', $org->id)->update([
            'provider_checkout_session_id' => 'cks_fail_1',
        ]);

        $webhook = DodoWebhook::query()->create([
            'event_id' => 'evt_pay_fail_1',
            'event_type' => 'payment.failed',
            'payload' => [
                'type' => 'payment.failed',
                'data' => [
                    'payment_id' => 'pay_fail_1',
                    'status' => 'failed',
                    'currency' => 'USD',
                    'total_amount' => 2000,
                    'error_code' => 'CARD_DECLINED',
                    'error_message' => 'Card declined',
                    'checkout_session_id' => 'cks_fail_1',
                    'metadata' => [
                        'organization_id' => $org->id,
                    ],
                ],
            ],
            'status' => 'received',
        ]);

        app()->call([new ProcessDodoWebhookJob($webhook->id), 'handle']);

        $this->assertSame('free', $org->fresh()->subscription?->plan?->slug);

        $event = BillingPaymentEvent::query()
            ->where('provider_event_id', 'evt_pay_fail_1')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame(BillingPaymentEventKind::PaymentFailed, $event->kind);
        $this->assertSame('CARD_DECLINED', $event->error_code);
        $this->assertTrue($event->email_sent);

        $this->assertTrue(
            AuditLog::query()
                ->where('organization_id', $org->id)
                ->where('action', 'billing.payment_failed')
                ->exists(),
        );

        Mail::assertSent(BillingPaymentMail::class, function (BillingPaymentMail $mail) use ($org): bool {
            return $mail->kind === 'payment_failed'
                && $mail->organizationName === $org->name;
        });
    }

    #[Test]
    public function checkout_return_failed_records_event_and_emails_without_granting_pro(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Return fail workspace');

        $this->actingAs($owner)
            ->postJson("/api/v1/organizations/{$org->id}/billing/checkout-return", [
                'status' => 'failed',
                'subscription_id' => 'sub_return_fail_1',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame('free', $org->fresh()->subscription?->plan?->slug);

        $event = BillingPaymentEvent::query()
            ->where('provider_event_id', 'checkout_return_failed:sub_return_fail_1')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame(BillingPaymentEventKind::PaymentFailed, $event->kind);
        $this->assertTrue($event->email_sent);

        Mail::assertSent(BillingPaymentMail::class, function (BillingPaymentMail $mail) use ($org): bool {
            return $mail->kind === 'payment_failed'
                && $mail->organizationName === $org->name;
        });
    }

    #[Test]
    public function abandoned_checkout_webhook_stores_event_audits_and_emails(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Abandon workspace');

        OrganizationSubscription::query()->where('organization_id', $org->id)->update([
            'provider_checkout_session_id' => 'cks_abandon_1',
        ]);

        $webhook = DodoWebhook::query()->create([
            'event_id' => 'evt_abandon_1',
            'event_type' => 'abandoned_checkout.detected',
            'payload' => [
                'type' => 'abandoned_checkout.detected',
                'data' => [
                    'checkout_id' => 'cks_abandon_1',
                    'abandonment_reason' => 'checkout_incomplete',
                    'status' => 'abandoned',
                    'metadata' => [
                        'organization_id' => $org->id,
                    ],
                ],
            ],
            'status' => 'received',
        ]);

        app()->call([new ProcessDodoWebhookJob($webhook->id), 'handle']);

        $this->assertSame('free', $org->fresh()->subscription?->plan?->slug);

        $event = BillingPaymentEvent::query()
            ->where('provider_event_id', 'evt_abandon_1')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame(BillingPaymentEventKind::CheckoutAbandoned, $event->kind);
        $this->assertTrue($event->email_sent);

        $this->assertTrue(
            AuditLog::query()
                ->where('organization_id', $org->id)
                ->where('action', 'billing.checkout_abandoned')
                ->exists(),
        );

        Mail::assertSent(BillingPaymentMail::class, function (BillingPaymentMail $mail) use ($org): bool {
            return $mail->kind === 'checkout_abandoned'
                && $mail->organizationName === $org->name;
        });
    }

    #[Test]
    public function refund_succeeded_webhook_stores_events_audits_and_emails(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Refund workspace');

        $webhook = DodoWebhook::query()->create([
            'event_id' => 'evt_refund_ok_1',
            'event_type' => 'refund.succeeded',
            'payload' => [
                'type' => 'refund.succeeded',
                'data' => [
                    'refund_id' => 'ref_ok_1',
                    'payment_id' => 'pay_refund_1',
                    'status' => 'succeeded',
                    'currency' => 'USD',
                    'amount' => 2000,
                    'metadata' => [
                        'organization_id' => $org->id,
                    ],
                ],
            ],
            'status' => 'received',
        ]);

        app()->call([new ProcessDodoWebhookJob($webhook->id), 'handle']);

        $this->assertSame('processed', $webhook->fresh()->status);

        $initiated = BillingPaymentEvent::query()
            ->where('provider_event_id', 'evt_refund_ok_1:refund_initiated')
            ->first();
        $succeeded = BillingPaymentEvent::query()
            ->where('provider_event_id', 'evt_refund_ok_1:refund_succeeded')
            ->first();

        $this->assertNotNull($initiated);
        $this->assertSame(BillingPaymentEventKind::RefundInitiated, $initiated->kind);
        $this->assertTrue($initiated->email_sent);

        $this->assertNotNull($succeeded);
        $this->assertSame(BillingPaymentEventKind::RefundSucceeded, $succeeded->kind);
        $this->assertTrue($succeeded->email_sent);
        $this->assertSame(2000, $succeeded->amount_cents);

        $this->assertTrue(
            AuditLog::query()
                ->where('organization_id', $org->id)
                ->where('action', 'billing.refund_initiated')
                ->exists(),
        );
        $this->assertTrue(
            AuditLog::query()
                ->where('organization_id', $org->id)
                ->where('action', 'billing.refund_succeeded')
                ->exists(),
        );

        Mail::assertSent(BillingPaymentMail::class, 2);
        Mail::assertSent(BillingPaymentMail::class, function (BillingPaymentMail $mail) use ($org): bool {
            return $mail->kind === 'refund_initiated'
                && $mail->organizationName === $org->name
                && $mail->amountLabel === '20.00 USD';
        });
        Mail::assertSent(BillingPaymentMail::class, function (BillingPaymentMail $mail) use ($org): bool {
            return $mail->kind === 'refund_succeeded'
                && $mail->organizationName === $org->name
                && $mail->amountLabel === '20.00 USD';
        });
    }

    #[Test]
    public function refund_pending_webhook_sends_initiated_email_only(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();
        Mail::fake();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Refund pending workspace');

        $pending = DodoWebhook::query()->create([
            'event_id' => 'evt_refund_pending_1',
            'event_type' => 'refund.succeeded',
            'payload' => [
                'type' => 'refund.succeeded',
                'data' => [
                    'refund_id' => 'ref_pending_1',
                    'payment_id' => 'pay_refund_pending_1',
                    'status' => 'pending',
                    'currency' => 'USD',
                    'amount' => 1500,
                    'metadata' => [
                        'organization_id' => $org->id,
                    ],
                ],
            ],
            'status' => 'received',
        ]);

        app()->call([new ProcessDodoWebhookJob($pending->id), 'handle']);

        $this->assertSame(
            1,
            BillingPaymentEvent::query()
                ->where('organization_id', $org->id)
                ->where('kind', BillingPaymentEventKind::RefundInitiated)
                ->count(),
        );
        $this->assertSame(
            0,
            BillingPaymentEvent::query()
                ->where('organization_id', $org->id)
                ->where('kind', BillingPaymentEventKind::RefundSucceeded)
                ->count(),
        );
        Mail::assertSent(BillingPaymentMail::class, 1);
        Mail::assertSent(BillingPaymentMail::class, fn (BillingPaymentMail $mail): bool => $mail->kind === 'refund_initiated');

        $succeeded = DodoWebhook::query()->create([
            'event_id' => 'evt_refund_pending_done_1',
            'event_type' => 'refund.succeeded',
            'payload' => [
                'type' => 'refund.succeeded',
                'data' => [
                    'refund_id' => 'ref_pending_1',
                    'payment_id' => 'pay_refund_pending_1',
                    'status' => 'succeeded',
                    'currency' => 'USD',
                    'amount' => 1500,
                    'metadata' => [
                        'organization_id' => $org->id,
                    ],
                ],
            ],
            'status' => 'received',
        ]);

        app()->call([new ProcessDodoWebhookJob($succeeded->id), 'handle']);

        Mail::assertSent(BillingPaymentMail::class, 2);
        Mail::assertSent(BillingPaymentMail::class, fn (BillingPaymentMail $mail): bool => $mail->kind === 'refund_succeeded');
        $this->assertSame(
            1,
            BillingPaymentEvent::query()
                ->where('organization_id', $org->id)
                ->where('kind', BillingPaymentEventKind::RefundInitiated)
                ->where('email_sent', true)
                ->count(),
        );
    }
}
