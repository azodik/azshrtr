<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\MemberRole;
use App\Enums\SubscriptionStatus;
use App\Models\BillingPlan;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\OrganizationSubscription;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\OrganizationInvitationNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OrganizationInvitationNotifier $invitationNotifier,
    ) {}

    public function createForUser(User $user, ?string $name = null): Organization
    {
        $base = Str::slug($name ?: ($user->name."'s workspace")) ?: 'workspace';
        $slug = $base;
        $i = 1;
        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        $organization = Organization::query()->create([
            'name' => $name ?: ($user->name."'s workspace"),
            'slug' => $slug,
            'billing_email' => $user->email,
        ]);

        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => MemberRole::Owner,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $free = BillingPlan::query()->where('slug', 'free')->first();
        if ($free !== null) {
            OrganizationSubscription::query()->create([
                'organization_id' => $organization->id,
                'billing_plan_id' => $free->id,
                'status' => SubscriptionStatus::Active,
            ]);
        }

        $this->audit->log(AuditAction::OrganizationCreated, $user, $organization, 'organization', $organization->id);

        return $organization;
    }

    public function invite(
        Organization $organization,
        User $actor,
        string $email,
        MemberRole $role,
    ): OrganizationInvitation {
        $email = Str::lower(trim($email));

        if ($role === MemberRole::Owner) {
            throw ValidationException::withMessages([
                'role' => ['Owner role cannot be assigned via invitation.'],
            ]);
        }

        $alreadyMember = $organization->members()
            ->whereHas('user', fn ($query) => $query->whereRaw('LOWER(email) = ?', [$email]))
            ->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => ['This user is already a member of the organization.'],
            ]);
        }

        $wasPending = OrganizationInvitation::query()
            ->where('organization_id', $organization->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->exists();

        $invitation = OrganizationInvitation::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'email' => $email,
            ],
            [
                'invited_by' => $actor->id,
                'role' => $role,
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ],
        );

        $this->audit->log(
            AuditAction::MemberInvited,
            $actor,
            $organization,
            'invitation',
            $invitation->id,
            ['email' => $email, 'role' => $role->value, 'resent' => $wasPending],
        );

        $this->invitationNotifier->notifyInvited(
            $invitation,
            $organization,
            $actor,
            resent: $wasPending,
        );

        return $invitation;
    }

    public function acceptInvitation(string $token, User $user): OrganizationMember
    {
        $invitation = OrganizationInvitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'token' => ['This invitation is no longer valid.'],
            ]);
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation has expired.'],
            ]);
        }

        if (Str::lower($user->email) !== Str::lower($invitation->email)) {
            throw ValidationException::withMessages([
                'email' => ['Sign in with the invited email address to accept.'],
            ]);
        }

        return DB::transaction(function () use ($invitation, $user): OrganizationMember {
            $role = $invitation->role === MemberRole::Owner
                ? MemberRole::Member
                : $invitation->role;

            $member = OrganizationMember::query()->updateOrCreate(
                [
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $role,
                    'status' => 'active',
                    'joined_at' => now(),
                ],
            );

            $invitation->update(['accepted_at' => now()]);

            $organization = Organization::query()->find($invitation->organization_id);
            $this->audit->log(
                AuditAction::MemberJoined,
                $user,
                $organization,
                'member',
                $member->id,
            );

            if ($organization !== null) {
                $inviter = User::query()->find($invitation->invited_by) ?? $user;
                $this->invitationNotifier->notifyAccepted(
                    $invitation->fresh() ?? $invitation,
                    $organization,
                    $user,
                    $inviter,
                );
            }

            return $member;
        });
    }

    public function revokeInvitation(
        Organization $organization,
        OrganizationInvitation $invitation,
        User $actor,
    ): void {
        $snapshot = $invitation->replicate();
        $snapshot->id = $invitation->id;

        $invitation->delete();

        $this->audit->log(
            AuditAction::InvitationRevoked,
            $actor,
            $organization,
            'invitation',
            $snapshot->id,
            ['email' => $snapshot->email, 'role' => $snapshot->role?->value],
        );

        $this->invitationNotifier->notifyRevoked($snapshot, $organization, $actor);
    }
}
