<?php

namespace App\Services\ApiKeys;

use App\Enums\ApiKeyScope;
use App\Enums\AuditAction;
use App\Models\ApiKey;
use App\Models\ApiKeyScope as ApiKeyScopeRow;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Usage\UsageTracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiKeyService
{
    public function __construct(
        private readonly UsageTracker $usage,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<string>|null  $scopes
     * @return array{api_key: ApiKey, plain_text: string}
     */
    public function create(Organization $organization, User $user, string $name, ?array $scopes = null): array
    {
        $this->usage->assertCanCreateApiKey($organization);

        $prefix = app()->environment('production') ? 'az_live_' : 'az_test_';
        $secret = $prefix.Str::random(40);
        $scopes = $scopes ?: ApiKeyScope::allValues();

        $apiKey = DB::transaction(function () use ($organization, $user, $name, $prefix, $secret, $scopes): ApiKey {
            $apiKey = ApiKey::query()->create([
                'organization_id' => $organization->id,
                'created_by' => $user->id,
                'name' => $name,
                'prefix' => $prefix,
                'key_hash' => hash('sha256', $secret),
                'last_four' => substr($secret, -4),
            ]);

            foreach ($scopes as $scope) {
                ApiKeyScopeRow::query()->create([
                    'api_key_id' => $apiKey->id,
                    'scope' => $scope,
                ]);
            }

            return $apiKey->load('scopeRows');
        });

        $this->audit->log(AuditAction::ApiKeyCreated, $user, $organization, 'api_key', $apiKey->id);

        return ['api_key' => $apiKey, 'plain_text' => $secret];
    }

    public function revoke(ApiKey $apiKey, Organization $organization, User $user): void
    {
        $apiKey->forceFill(['revoked_at' => now()])->save();
        $this->audit->log(AuditAction::ApiKeyRevoked, $user, $organization, 'api_key', $apiKey->id);
    }

    public function findByPlainText(string $plain): ?ApiKey
    {
        return ApiKey::query()
            ->with('scopeRows')
            ->where('key_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->first();
    }
}
