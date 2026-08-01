<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ApiKey extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'created_by',
        'name',
        'prefix',
        'key_hash',
        'last_four',
        'last_used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeRows(): HasMany
    {
        return $this->hasMany(ApiKeyScope::class);
    }

    /**
     * @return list<string>
     */
    public function scopeValues(): array
    {
        /** @var Collection<int, ApiKeyScope> $rows */
        $rows = $this->relationLoaded('scopeRows')
            ? $this->scopeRows
            : $this->scopeRows()->get();

        return $rows->pluck('scope')->values()->all();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopeValues(), true);
    }
}
