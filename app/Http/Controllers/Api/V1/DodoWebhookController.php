<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDodoWebhookJob;
use App\Models\DodoWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DodoWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('billing.dodo.webhook_secret');
        $payload = $request->getContent();

        if ($secret !== '' && ! $this->validDodoSignature($request, $payload, $secret)) {
            Log::warning('Dodo webhook signature rejected', [
                'has_webhook_id' => $request->header('webhook-id') !== null,
                'has_webhook_timestamp' => $request->header('webhook-timestamp') !== null,
                'has_webhook_signature' => $request->header('webhook-signature') !== null
                    || $request->header('X-Dodo-Signature') !== null,
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $data = $request->all();
        $eventId = $request->header('webhook-id')
            ?? $data['event_id']
            ?? $data['id']
            ?? null;
        $eventType = $data['type'] ?? $data['event_type'] ?? 'unknown';

        if (is_string($eventId) && $eventId !== '') {
            $existing = DodoWebhook::query()->where('event_id', $eventId)->first();
            if ($existing !== null) {
                if (! in_array($existing->status, ['processed', 'processing'], true)) {
                    ProcessDodoWebhookJob::dispatch($existing->id);
                }

                return response()->json(['ok' => true, 'duplicate' => true]);
            }
        }

        $webhook = DodoWebhook::query()->create([
            'event_id' => is_string($eventId) ? $eventId : null,
            'event_type' => is_string($eventType) ? $eventType : 'unknown',
            'payload' => $data,
            'status' => 'received',
        ]);

        ProcessDodoWebhookJob::dispatch($webhook->id);

        return response()->json(['ok' => true]);
    }

    private function validDodoSignature(Request $request, string $payload, string $secret): bool
    {
        $signatureHeader = (string) $request->header(
            'webhook-signature',
            (string) $request->header('X-Dodo-Signature', ''),
        );

        if ($signatureHeader === '') {
            return false;
        }

        $webhookId = (string) $request->header('webhook-id', '');
        $timestamp = (string) $request->header('webhook-timestamp', '');

        // Standard Webhooks (Dodo): signed as "{id}.{timestamp}.{body}" with whsec_ secret.
        if ($webhookId !== '' && $timestamp !== '') {
            if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 60 * 5) {
                return false;
            }

            $secretKey = $this->decodeWebhookSecret($secret);
            $signedContent = $webhookId.'.'.$timestamp.'.'.$payload;
            $expected = base64_encode(hash_hmac('sha256', $signedContent, $secretKey, true));

            foreach (preg_split('/\s+/', trim($signatureHeader)) ?: [] as $versioned) {
                $parts = explode(',', $versioned, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                [$version, $signature] = $parts;
                if ($version === 'v1' && hash_equals($expected, $signature)) {
                    return true;
                }
            }
        }

        // Legacy fallbacks kept for older test payloads / dashboards.
        $legacyHex = hash_hmac('sha256', $payload, $secret);
        if (hash_equals($legacyHex, $signatureHeader)) {
            return true;
        }

        if (str_contains($signatureHeader, 'signature=')) {
            parse_str(str_replace(',', '&', $signatureHeader), $parts);
            $sig = $parts['signature'] ?? '';
            $ts = $parts['timestamp'] ?? '';
            if (! is_string($sig) || ! is_string($ts) || $sig === '' || $ts === '') {
                return false;
            }
            $expected = hash_hmac('sha256', $ts.'.'.$payload, $secret);

            return hash_equals($expected, $sig);
        }

        return false;
    }

    private function decodeWebhookSecret(string $secret): string
    {
        if (str_starts_with($secret, 'whsec_')) {
            $decoded = base64_decode(substr($secret, strlen('whsec_')), true);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $secret;
    }
}
