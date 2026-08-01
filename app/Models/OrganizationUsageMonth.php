<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationUsageMonth extends Model
{
    use HasUuids;

    protected $table = 'organization_usage_months';

    protected $fillable = [
        'organization_id',
        'period',
        'links_created',
        'qr_generated',
        'api_calls',
        'alerts',
    ];

    protected function casts(): array
    {
        return [
            'alerts' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
