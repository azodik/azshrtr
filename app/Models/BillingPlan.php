<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'links_per_month',
        'qr_per_month',
        'api_keys_limit',
        'audit_retention_days',
        'click_retention_days',
        'api_log_retention_days',
        'price_cents_yearly',
        'currency',
        'dodo_product_id',
        'is_public',
        'allows_custom_domains',
        'allows_password_links',
        'features',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'allows_custom_domains' => 'boolean',
            'allows_password_links' => 'boolean',
            'features' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }

    public function isFree(): bool
    {
        return $this->slug === 'free';
    }

    public function isPro(): bool
    {
        return $this->slug === 'pro';
    }
}
