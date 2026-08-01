<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'hostname',
        'is_system',
        'is_primary',
        'status',
        'cloudflare_hostname_id',
        'cloudflare_status',
        'cloudflare_ssl_status',
        'verification_token',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DomainDnsRecord::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null && $this->status === 'verified';
    }
}
