<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Link extends Model
{
    use HasUuids;
    use SoftDeletes;

    /** @var list<string> */
    protected $appends = [
        'short_url',
    ];

    protected $fillable = [
        'organization_id',
        'user_id',
        'domain_id',
        'code',
        'destination_url',
        'title',
        'password_hash',
        'expires_at',
        'claim_token',
        'claim_token_expires_at',
        'is_anonymous',
        'is_disabled',
        'click_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'claim_token_expires_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'is_disabled' => 'boolean',
            'click_count' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(LinkClick::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPasswordProtected(): bool
    {
        return filled($this->password_hash);
    }

    public function shortUrl(?string $root = null): string
    {
        if ($this->domain?->hostname) {
            return 'https://'.$this->domain->hostname.'/'.$this->code;
        }

        $base = rtrim($root ?? (string) config('app.url'), '/');

        return $base.'/'.$this->code;
    }

    public function getShortUrlAttribute(): string
    {
        return $this->shortUrl();
    }
}
