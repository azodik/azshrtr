<?php

namespace Tests\Feature;

use App\Models\DodoWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dodo_webhook_accepts_unsigned_payload_when_secret_unset(): void
    {
        $this->seedCore();
        Queue::fake();

        config(['billing.dodo.webhook_secret' => '']);

        $this->postJson('/api/v1/webhooks/dodo', [
            'id' => 'evt_test_1',
            'type' => 'subscription.active',
            'data' => ['status' => 'active'],
        ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(1, DodoWebhook::query()->count());
    }

    #[Test]
    public function dodo_webhook_rejects_invalid_signature_when_secret_set(): void
    {
        $this->seedCore();
        config(['billing.dodo.webhook_secret' => 'whsec_'.base64_encode('super-secret-bytes')]);

        $this->postJson('/api/v1/webhooks/dodo', [
            'id' => 'evt_test_2',
            'type' => 'subscription.active',
        ], [
            'webhook-id' => 'msg_1',
            'webhook-timestamp' => (string) time(),
            'webhook-signature' => 'v1,invalid',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid signature');

        $this->assertSame(0, DodoWebhook::query()->count());
    }

    #[Test]
    public function dodo_webhook_accepts_standard_webhooks_signature(): void
    {
        $this->seedCore();
        Queue::fake();

        $secretBytes = 'super-secret-bytes';
        config(['billing.dodo.webhook_secret' => 'whsec_'.base64_encode($secretBytes)]);

        $body = json_encode([
            'id' => 'evt_test_3',
            'type' => 'payment.failed',
            'data' => ['status' => 'failed'],
        ], JSON_THROW_ON_ERROR);

        $webhookId = 'msg_test_3';
        $timestamp = (string) time();
        $signature = base64_encode(hash_hmac(
            'sha256',
            $webhookId.'.'.$timestamp.'.'.$body,
            $secretBytes,
            true,
        ));

        $this->call(
            'POST',
            '/api/v1/webhooks/dodo',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_WEBHOOK_ID' => $webhookId,
                'HTTP_WEBHOOK_TIMESTAMP' => $timestamp,
                'HTTP_WEBHOOK_SIGNATURE' => 'v1,'.$signature,
            ],
            $body,
        )
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(1, DodoWebhook::query()->count());
        $this->assertSame($webhookId, DodoWebhook::query()->value('event_id'));
        $this->assertSame('payment.failed', DodoWebhook::query()->value('event_type'));
    }
}
