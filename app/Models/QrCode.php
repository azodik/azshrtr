<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'link_id',
        'content',
        'size',
        'margin',
        'format',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
