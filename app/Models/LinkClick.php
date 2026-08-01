<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'idempotency_key',
        'link_id',
        'organization_id',
        'clicked_at',
        'referrer',
        'user_agent',
        'device_bucket',
        'browser',
        'country',
        'region',
        'city',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
