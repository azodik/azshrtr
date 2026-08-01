<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkExport extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'user_id',
        'status',
        'format',
        'path',
        'row_count',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
