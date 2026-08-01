<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkImport extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'user_id',
        'status',
        'format',
        'total_rows',
        'success_rows',
        'error_rows',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
