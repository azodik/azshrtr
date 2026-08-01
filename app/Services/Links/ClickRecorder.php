<?php

namespace App\Services\Links;

use App\Jobs\RecordLinkClickJob;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class ClickRecorder
{
    public function record(Link $link, Request $request): void
    {
        $ua = (string) $request->userAgent();
        $clientIp = $this->clientIp($request);

        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'link_id' => $link->id,
            'organization_id' => $link->organization_id,
            'referrer' => Str::limit((string) $request->headers->get('referer'), 512, ''),
            'user_agent' => Str::limit($ua, 512, ''),
            'device_bucket' => $this->deviceBucket($ua),
            'browser' => $this->browser($ua),
            'country' => $this->country($request),
            'region' => $this->header($request, 'CF-Region', 64),
            'city' => $this->header($request, 'CF-IPCity', 120),
            'ip_hash' => hash('sha256', $clientIp.'|'.(string) config('app.key')),
            'clicked_at' => now()->toIso8601String(),
        ];

        try {
            if (config('queue.default') === 'sync') {
                RecordLinkClickJob::dispatchSync($payload);
            } else {
                Queue::push(new RecordLinkClickJob($payload));
            }
        } catch (\Throwable) {
            RecordLinkClickJob::dispatchSync($payload);
        }
    }

    private function clientIp(Request $request): string
    {
        $cf = trim((string) $request->headers->get('CF-Connecting-IP', ''));
        if ($cf !== '') {
            return $cf;
        }

        return (string) $request->ip();
    }

    private function country(Request $request): ?string
    {
        $code = strtoupper(trim((string) $request->headers->get('CF-IPCountry', '')));
        if ($code === '' || $code === 'XX' || $code === 'T1') {
            return null;
        }

        return Str::limit($code, 2, '');
    }

    private function header(Request $request, string $name, int $max): ?string
    {
        $value = trim((string) $request->headers->get($name, ''));
        if ($value === '') {
            return null;
        }

        return Str::limit($value, $max, '');
    }

    private function deviceBucket(string $ua): string
    {
        $ua = strtolower($ua);

        if ($ua === '') {
            return 'unknown';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function browser(string $ua): string
    {
        $uaLower = strtolower($ua);
        if ($uaLower === '') {
            return 'unknown';
        }

        return match (true) {
            str_contains($uaLower, 'edg/') => 'Edge',
            str_contains($uaLower, 'opr/') || str_contains($uaLower, 'opera') => 'Opera',
            str_contains($uaLower, 'chrome/') && ! str_contains($uaLower, 'edg/') => 'Chrome',
            str_contains($uaLower, 'firefox/') => 'Firefox',
            str_contains($uaLower, 'safari/') && ! str_contains($uaLower, 'chrome/') => 'Safari',
            str_contains($uaLower, 'msie') || str_contains($uaLower, 'trident/') => 'IE',
            default => 'Other',
        };
    }
}
