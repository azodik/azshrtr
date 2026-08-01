<?php

namespace Tests\Concerns;

use App\Enums\ApiKeyScope;
use App\Models\Organization;
use App\Models\User;
use App\Services\ApiKeys\ApiKeyService;
use App\Services\OrganizationService;

trait InteractsWithApi
{
    protected User $apiUser;

    protected Organization $apiOrganization;

    protected string $apiKeyPlainText;

    /**
     * @param  list<string>|null  $scopes
     * @return array{user: User, organization: Organization, plain_text: string}
     */
    protected function createOrganizationContext(?array $scopes = null, ?string $orgName = null): array
    {
        $this->seedCore();

        $user = User::factory()->create();
        $organization = app(OrganizationService::class)->createForUser($user, $orgName ?? 'Test Org');
        $created = app(ApiKeyService::class)->create(
            $organization,
            $user,
            'Integration key',
            $scopes ?? ApiKeyScope::allValues(),
        );

        $this->apiUser = $user;
        $this->apiOrganization = $organization;
        $this->apiKeyPlainText = $created['plain_text'];

        return [
            'user' => $user,
            'organization' => $organization,
            'plain_text' => $created['plain_text'],
        ];
    }

    protected function withApiKey(?string $plainText = null): static
    {
        return $this->withHeader('Authorization', 'Bearer '.($plainText ?? $this->apiKeyPlainText));
    }

    protected function actingAsApiOwner(): static
    {
        return $this->actingAs($this->apiUser);
    }

    protected function orgPath(string $suffix = ''): string
    {
        $base = '/api/v1/organizations/'.$this->apiOrganization->id;

        return $suffix === '' ? $base : $base.'/'.ltrim($suffix, '/');
    }
}
