<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\LinkClick;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordLinkClickJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 3600;

    /**
     * @param  array{
     *     idempotency_key: string,
     *     link_id: string,
     *     organization_id?: string|null,
     *     referrer?: string|null,
     *     user_agent?: string|null,
     *     device_bucket?: string|null,
     *     browser?: string|null,
     *     country?: string|null,
     *     region?: string|null,
     *     city?: string|null,
     *     ip_hash?: string|null,
     *     clicked_at?: string
     * }  $payload
     */
    public function __construct(public array $payload) {}

    public function uniqueId(): string
    {
        return (string) ($this->payload['idempotency_key'] ?? '');
    }

    public function handle(): void
    {
        $key = $this->payload['idempotency_key'] ?? null;
        if (! is_string($key) || $key === '') {
            return;
        }

        $click = LinkClick::query()->firstOrCreate(
            ['idempotency_key' => $key],
            [
                'link_id' => $this->payload['link_id'],
                'organization_id' => $this->payload['organization_id'] ?? null,
                'clicked_at' => $this->payload['clicked_at'] ?? now(),
                'referrer' => $this->payload['referrer'] ?? null,
                'user_agent' => $this->payload['user_agent'] ?? null,
                'device_bucket' => $this->payload['device_bucket'] ?? null,
                'browser' => $this->payload['browser'] ?? null,
                'country' => $this->payload['country'] ?? null,
                'region' => $this->payload['region'] ?? null,
                'city' => $this->payload['city'] ?? null,
                'ip_hash' => $this->payload['ip_hash'] ?? null,
            ],
        );

        if ($click->wasRecentlyCreated) {
            Link::query()->whereKey($this->payload['link_id'])->increment('click_count');
        }
    }
}
