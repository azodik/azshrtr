<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Mail\InvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberInviteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_invite_and_invitee_can_accept_with_emails(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['email' => 'owner@azshrtr.com']);
        $invitee = User::factory()->create(['email' => 'teammate@azshrtr.com']);
        $organization = $this->makeOrgWithOwner($owner);

        $this->actingAs($owner)
            ->postJson("/api/v1/organizations/{$organization->id}/members/invite", [
                'email' => 'teammate@azshrtr.com',
                'role' => 'member',
            ])
            ->assertCreated()
            ->assertJsonPath('invitation.email', 'teammate@azshrtr.com');

        Mail::assertSent(InvitationMail::class, function (InvitationMail $mail) use ($organization): bool {
            return $mail->kind === 'invited'
                && $mail->organizationName === $organization->name
                && $mail->hasTo('teammate@azshrtr.com');
        });

        $invitation = OrganizationInvitation::query()->firstOrFail();

        $this->actingAs($invitee)
            ->postJson('/api/v1/invitations/accept', ['token' => $invitation->token])
            ->assertOk()
            ->assertJsonPath('organization.id', $organization->id);

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $invitee->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        Mail::assertSent(InvitationMail::class, function (InvitationMail $mail) use ($organization): bool {
            return $mail->kind === 'accepted'
                && $mail->organizationName === $organization->name
                && $mail->hasTo('teammate@azshrtr.com');
        });

        Mail::assertSent(InvitationMail::class, function (InvitationMail $mail) use ($organization, $owner): bool {
            return $mail->kind === 'accepted_admin'
                && $mail->organizationName === $organization->name
                && $mail->hasTo($owner->email);
        });
    }

    #[Test]
    public function reinviting_same_email_sends_resent_email(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $organization = $this->makeOrgWithOwner($owner);

        $this->actingAs($owner)
            ->postJson("/api/v1/organizations/{$organization->id}/members/invite", [
                'email' => 'new@azshrtr.com',
                'role' => 'admin',
            ])
            ->assertCreated();

        Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->kind === 'invited');

        $this->actingAs($owner)
            ->postJson("/api/v1/organizations/{$organization->id}/members/invite", [
                'email' => 'new@azshrtr.com',
                'role' => 'admin',
            ])
            ->assertCreated();

        Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->kind === 'resent'
            && $mail->hasTo('new@azshrtr.com'));
    }

    #[Test]
    public function revoking_invitation_emails_invitee(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $organization = $this->makeOrgWithOwner($owner);

        $this->actingAs($owner)
            ->postJson("/api/v1/organizations/{$organization->id}/members/invite", [
                'email' => 'pending@azshrtr.com',
                'role' => 'member',
            ])
            ->assertCreated();

        $invitation = OrganizationInvitation::query()->firstOrFail();

        $this->actingAs($owner)
            ->deleteJson("/api/v1/organizations/{$organization->id}/invitations/{$invitation->id}")
            ->assertOk();

        $this->assertDatabaseMissing('organization_invitations', [
            'id' => $invitation->id,
        ]);

        Mail::assertSent(InvitationMail::class, function (InvitationMail $mail) use ($organization): bool {
            return $mail->kind === 'revoked'
                && $mail->organizationName === $organization->name
                && $mail->hasTo('pending@azshrtr.com');
        });
    }

    private function makeOrgWithOwner(User $owner): Organization
    {
        $organization = Organization::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-'.uniqid(),
            'billing_email' => $owner->email,
        ]);

        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => MemberRole::Owner,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $organization;
    }
}
