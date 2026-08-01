<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Enums\SubscriptionStatus;
use App\Models\BillingPlan;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\OrganizationSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

/**
 * End-to-end coverage of console (session) API resources under /api/v1.
 */
class ConsoleApiIntegrationTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    #[Test]
    public function console_api_covers_organizations_links_qr_domains_keys_logs_billing_and_import_export(): void
    {
        $this->createOrganizationContext();
        $this->actingAsApiOwner();

        $this->getJson('/api/v1/organizations')
            ->assertOk()
            ->assertJsonPath('organizations.0.id', $this->apiOrganization->id);

        $second = $this->postJson('/api/v1/organizations', [
            'name' => 'Second workspace',
        ])
            ->assertCreated()
            ->json('organization.id');

        $this->assertNotNull($second);

        $this->getJson($this->orgPath('overview'))
            ->assertOk()
            ->assertJsonStructure(['plan', 'usage', 'clicks']);

        $this->getJson($this->orgPath('analytics'))
            ->assertOk();

        $linkId = $this->postJson($this->orgPath('links'), [
            'destination_url' => 'https://azshrtr.com/console-link',
            'title' => 'Console link',
        ])
            ->assertCreated()
            ->json('link.id');

        $this->getJson($this->orgPath('links'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $linkId);

        $this->getJson($this->orgPath('links/'.$linkId))
            ->assertOk()
            ->assertJsonPath('link.id', $linkId);

        $this->patchJson($this->orgPath('links/'.$linkId), [
            'title' => 'Renamed link',
        ])
            ->assertOk()
            ->assertJsonPath('link.title', 'Renamed link');

        $this->getJson($this->orgPath('links/export?format=json'))
            ->assertOk()
            ->assertJsonStructure(['data']);

        $extraLinkId = $this->postJson($this->orgPath('links'), [
            'destination_url' => 'https://azshrtr.com/bulk-me',
        ])->assertCreated()->json('link.id');

        $this->postJson($this->orgPath('links/bulk-delete'), [
            'ids' => [$extraLinkId],
        ])
            ->assertOk()
            ->assertJsonPath('deleted', 1);

        $this->getJson($this->orgPath('links/'.$linkId.'/qr.svg'))
            ->assertOk();

        $qrId = $this->postJson($this->orgPath('qr'), [
            'link_id' => $linkId,
            'size' => 256,
        ])
            ->assertCreated()
            ->json('qr.id');

        $this->getJson($this->orgPath('qr'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $qrId);

        $this->getJson($this->orgPath('qr/'.$qrId))
            ->assertOk()
            ->assertJsonStructure(['qr', 'svg']);

        $this->getJson($this->orgPath('qr/'.$qrId.'/download'))
            ->assertOk();

        $this->getJson($this->orgPath('qr/export?format=json'))
            ->assertOk();

        $domainId = $this->postJson($this->orgPath('domains'), [
            'hostname' => 'go.example-test.com',
        ])
            ->assertCreated()
            ->json('domain.id');

        $this->getJson($this->orgPath('domains'))
            ->assertOk()
            ->assertJsonStructure(['data', 'cname_target']);

        $this->getJson($this->orgPath('domains/'.$domainId))
            ->assertOk()
            ->assertJsonPath('domain.id', $domainId);

        $this->postJson($this->orgPath('domains/'.$domainId.'/verify'))
            ->assertUnprocessable();

        $this->getJson($this->orgPath('domains/export?format=json'))
            ->assertOk();

        $apiKeyId = $this->postJson($this->orgPath('api-keys'), [
            'name' => 'Console-created key',
            'scopes' => ['links:read', 'links:write'],
        ])
            ->assertCreated()
            ->assertJsonStructure(['api_key', 'plain_text'])
            ->json('api_key.id');

        $this->getJson($this->orgPath('api-keys'))
            ->assertOk();

        $this->getJson($this->orgPath('api-keys/'.$apiKeyId))
            ->assertOk()
            ->assertJsonPath('api_key.id', $apiKeyId);

        $this->getJson($this->orgPath('api-keys/export?format=json'))
            ->assertOk();

        // Hit product API so request logs exist.
        $this->withApiKey()
            ->getJson('/api/v1/me')
            ->assertOk();

        $logId = $this->getJson($this->orgPath('api-request-logs'))
            ->assertOk()
            ->json('data.0.id');

        $this->assertNotNull($logId);

        $this->getJson($this->orgPath('api-request-logs/'.$logId))
            ->assertOk()
            ->assertJsonPath('log.id', $logId);

        $this->getJson($this->orgPath('api-request-logs/export?format=json'))
            ->assertOk();

        $auditId = $this->getJson($this->orgPath('audit-logs'))
            ->assertOk()
            ->json('data.0.id');

        $this->assertNotNull($auditId);

        $this->getJson($this->orgPath('audit-logs/'.$auditId))
            ->assertOk()
            ->assertJsonPath('log.id', $auditId);

        $this->getJson($this->orgPath('audit-logs/export?format=json'))
            ->assertOk();

        $this->getJson($this->orgPath('billing'))
            ->assertOk()
            ->assertJsonPath('billing_enabled', false);

        $this->postJson($this->orgPath('billing/checkout'))
            ->assertUnprocessable();

        $exportId = $this->postJson($this->orgPath('export'), [
            'format' => 'json',
        ])
            ->assertOk()
            ->assertJsonStructure(['export', 'download_url'])
            ->json('export.id');

        $this->get($this->orgPath('exports/'.$exportId.'/download'))
            ->assertOk();

        $this->postJson($this->orgPath('import'), [
            'format' => 'json',
            'payload' => json_encode([
                [
                    'destination_url' => 'https://azshrtr.com/imported',
                    'title' => 'Imported',
                ],
            ], JSON_THROW_ON_ERROR),
        ])
            ->assertOk()
            ->assertJsonStructure(['import']);

        $this->postJson($this->orgPath('qr/bulk-delete'), [
            'ids' => [$qrId],
        ])
            ->assertOk()
            ->assertJsonPath('deleted', 1);

        $this->postJson($this->orgPath('domains/bulk-delete'), [
            'ids' => [$domainId],
        ])
            ->assertOk()
            ->assertJsonPath('deleted', 1);

        $this->postJson($this->orgPath('api-keys/bulk-delete'), [
            'ids' => [$apiKeyId],
        ])
            ->assertOk();

        $this->deleteJson($this->orgPath('links/'.$linkId))
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    #[Test]
    public function members_invite_list_show_and_role_update(): void
    {
        $this->createOrganizationContext();
        $this->actingAsApiOwner();

        $inviteId = $this->postJson($this->orgPath('members/invite'), [
            'email' => 'member@azshrtr.com',
            'role' => 'member',
        ])
            ->assertCreated()
            ->json('invitation.id');

        $invitation = OrganizationInvitation::query()->whereKey($inviteId)->firstOrFail();

        $member = OrganizationMember::query()
            ->where('organization_id', $this->apiOrganization->id)
            ->where('user_id', $this->apiUser->id)
            ->firstOrFail();

        $this->getJson($this->orgPath('members'))
            ->assertOk()
            ->assertJsonStructure(['data', 'invitations', 'can_manage']);

        $this->getJson($this->orgPath('members/'.$member->id))
            ->assertOk();

        $this->getJson($this->orgPath('members/export?format=json'))
            ->assertOk();

        $invitee = User::factory()->create([
            'email' => 'member@azshrtr.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($invitee)
            ->postJson('/api/v1/invitations/accept', [
                'token' => $invitation->token,
            ])
            ->assertOk();

        $inviteeMember = OrganizationMember::query()
            ->where('organization_id', $this->apiOrganization->id)
            ->where('user_id', $invitee->id)
            ->firstOrFail();

        $this->actingAsApiOwner()
            ->patchJson($this->orgPath('members/'.$inviteeMember->id), [
                'role' => MemberRole::Admin->value,
            ])
            ->assertOk();

        $this->actingAsApiOwner()
            ->deleteJson($this->orgPath('members/'.$inviteeMember->id))
            ->assertOk();
    }

    #[Test]
    public function billing_cancel_and_resume_http_endpoints(): void
    {
        config(['billing.enabled' => true]);
        $this->createOrganizationContext();
        $this->actingAsApiOwner();

        $proPlan = BillingPlan::query()->where('slug', 'pro')->firstOrFail();
        OrganizationSubscription::query()
            ->where('organization_id', $this->apiOrganization->id)
            ->update([
                'billing_plan_id' => $proPlan->id,
                'status' => SubscriptionStatus::Active,
                'current_period_start' => now()->subMonth(),
                'current_period_end' => now()->addMonth(),
                'cancel_at' => null,
                'cancelled_at' => null,
            ]);

        $this->postJson($this->orgPath('billing/cancel'))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->postJson($this->orgPath('billing/resume'))
            ->assertOk()
            ->assertJsonPath('ok', true);
    }
}
