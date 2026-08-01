<?php

namespace Tests\Feature;

use App\Enums\ApiKeyScope;
use App\Enums\MemberRole;
use App\Models\Link;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\ApiKeys\ApiKeyService;
use App\Services\Domains\PlatformDomain;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function console_user_from_org_a_cannot_read_org_b_resources(): void
    {
        $this->seedCore();

        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $orgA = app(OrganizationService::class)->createForUser($ownerA, 'Org A');
        $orgB = app(OrganizationService::class)->createForUser($ownerB, 'Org B');

        $domain = app(PlatformDomain::class)->resolve();
        $linkB = Link::query()->create([
            'organization_id' => $orgB->id,
            'user_id' => $ownerB->id,
            'domain_id' => $domain->id,
            'code' => 'orgblink',
            'destination_url' => 'https://azshrtr.com/b',
            'title' => 'B only',
        ]);

        $this->actingAs($ownerA)
            ->getJson("/api/v1/organizations/{$orgB->id}/links")
            ->assertNotFound();

        $this->actingAs($ownerA)
            ->getJson("/api/v1/organizations/{$orgB->id}/links/{$linkB->id}")
            ->assertNotFound();

        $this->actingAs($ownerA)
            ->getJson("/api/v1/organizations/{$orgA->id}/links/{$linkB->id}")
            ->assertNotFound();

        $this->actingAs($ownerA)
            ->getJson("/api/v1/organizations/{$orgB->id}/billing")
            ->assertNotFound();

        $this->actingAs($ownerA)
            ->getJson("/api/v1/organizations/{$orgB->id}/api-keys")
            ->assertNotFound();
    }

    #[Test]
    public function product_api_key_from_org_a_cannot_access_org_b_links(): void
    {
        $this->seedCore();

        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $orgA = app(OrganizationService::class)->createForUser($ownerA, 'Org A');
        $orgB = app(OrganizationService::class)->createForUser($ownerB, 'Org B');

        $keyA = app(ApiKeyService::class)->create($orgA, $ownerA, 'A key', ApiKeyScope::allValues());

        $domain = app(PlatformDomain::class)->resolve();
        $linkB = Link::query()->create([
            'organization_id' => $orgB->id,
            'user_id' => $ownerB->id,
            'domain_id' => $domain->id,
            'code' => 'secretb',
            'destination_url' => 'https://azshrtr.com/secret-b',
            'title' => 'Secret B',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$keyA['plain_text'])
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('organization.id', $orgA->id);

        $list = $this->withHeader('Authorization', 'Bearer '.$keyA['plain_text'])
            ->getJson('/api/v1/links')
            ->assertOk();
        $ids = collect($list->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($linkB->id));

        $this->withHeader('Authorization', 'Bearer '.$keyA['plain_text'])
            ->getJson('/api/v1/links/'.$linkB->id)
            ->assertNotFound();

        $this->withHeader('Authorization', 'Bearer '.$keyA['plain_text'])
            ->patchJson('/api/v1/links/'.$linkB->id, ['title' => 'Hijacked'])
            ->assertNotFound();

        $this->withHeader('Authorization', 'Bearer '.$keyA['plain_text'])
            ->deleteJson('/api/v1/links/'.$linkB->id)
            ->assertNotFound();

        $this->assertDatabaseHas('links', [
            'id' => $linkB->id,
            'organization_id' => $orgB->id,
            'title' => 'Secret B',
        ]);
    }

    #[Test]
    public function member_cannot_invite_manage_billing_or_create_api_keys(): void
    {
        $this->seedCore();
        config(['billing.enabled' => true]);

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Team');

        OrganizationMember::query()->create([
            'organization_id' => $org->id,
            'user_id' => $member->id,
            'role' => MemberRole::Member,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($owner)
            ->postJson("/api/v1/organizations/{$org->id}/members/invite", [
                'email' => 'pending@azshrtr.com',
                'role' => 'member',
            ])
            ->assertCreated();

        $this->actingAs($member)
            ->postJson("/api/v1/organizations/{$org->id}/members/invite", [
                'email' => 'another@azshrtr.com',
                'role' => 'member',
            ])
            ->assertForbidden();

        $this->actingAs($member)
            ->postJson("/api/v1/organizations/{$org->id}/billing/checkout")
            ->assertForbidden();

        $this->actingAs($member)
            ->postJson("/api/v1/organizations/{$org->id}/billing/cancel")
            ->assertForbidden();

        $this->actingAs($member)
            ->postJson("/api/v1/organizations/{$org->id}/api-keys", [
                'name' => 'Should fail',
            ])
            ->assertForbidden();

        $members = $this->actingAs($member)
            ->getJson("/api/v1/organizations/{$org->id}/members")
            ->assertOk()
            ->assertJsonPath('can_manage', false);

        $invitations = $members->json('invitations');
        $this->assertNotEmpty($invitations);
        $this->assertArrayNotHasKey('invite_url', $invitations[0]);

        $this->actingAs($member)
            ->getJson("/api/v1/organizations/{$org->id}/billing")
            ->assertOk()
            ->assertJsonPath('can_manage_billing', false);

        $this->actingAs($member)
            ->postJson("/api/v1/organizations/{$org->id}/links", [
                'destination_url' => 'https://azshrtr.com/member-ok',
            ])
            ->assertCreated();
    }

    #[Test]
    public function admin_can_manage_members_and_keys_but_not_billing(): void
    {
        $this->seedCore();
        config(['billing.enabled' => true]);

        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Team');

        OrganizationMember::query()->create([
            'organization_id' => $org->id,
            'user_id' => $admin->id,
            'role' => MemberRole::Admin,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->postJson("/api/v1/organizations/{$org->id}/members/invite", [
                'email' => 'hire@azshrtr.com',
                'role' => 'member',
            ])
            ->assertCreated()
            ->assertJsonStructure(['invitation' => ['invite_url']]);

        $this->actingAs($admin)
            ->postJson("/api/v1/organizations/{$org->id}/api-keys", [
                'name' => 'Admin key',
            ])
            ->assertCreated();

        $this->actingAs($admin)
            ->postJson("/api/v1/organizations/{$org->id}/billing/checkout")
            ->assertForbidden();

        $this->actingAs($admin)
            ->getJson("/api/v1/organizations/{$org->id}/billing")
            ->assertOk()
            ->assertJsonPath('can_manage_billing', false);

        $list = $this->actingAs($admin)
            ->getJson("/api/v1/organizations/{$org->id}/members")
            ->assertOk()
            ->assertJsonPath('can_manage', true);

        $this->assertArrayHasKey('invite_url', $list->json('invitations.0'));
    }

    #[Test]
    public function owner_sees_billing_manage_flag_and_invite_urls(): void
    {
        $this->seedCore();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Owned');

        OrganizationInvitation::query()->create([
            'organization_id' => $org->id,
            'email' => 'wait@azshrtr.com',
            'role' => MemberRole::Member,
            'token' => 'test-token-'.uniqid(),
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($owner)
            ->getJson("/api/v1/organizations/{$org->id}/billing")
            ->assertOk()
            ->assertJsonPath('can_manage_billing', true);

        $this->actingAs($owner)
            ->getJson("/api/v1/organizations/{$org->id}/members")
            ->assertOk()
            ->assertJsonPath('can_manage', true)
            ->assertJsonStructure(['invitations' => [['invite_url']]]);
    }
}
