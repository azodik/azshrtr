<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'billing_email',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(OrganizationSubscription::class);
    }

    public function billingCustomer(): HasOne
    {
        return $this->hasOne(BillingCustomer::class);
    }

    public function usageMonths(): HasMany
    {
        return $this->hasMany(OrganizationUsageMonth::class);
    }
}
