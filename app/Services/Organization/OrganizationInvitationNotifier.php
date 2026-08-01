<?php

namespace App\Services\Organization;

use App\Enums\MemberRole;
use App\Mail\InvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\Mail\LocalizedMailer;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrganizationInvitationNotifier
{
    public function __construct(
        private readonly LocalizedMailer $mailer,
    ) {}

    public function notifyInvited(
        OrganizationInvitation $invitation,
        Organization $organization,
        User $inviter,
        bool $resent = false,
    ): void {
        $kind = $resent ? 'resent' : 'invited';
        $inviteUrl = url('/console/invite/'.$invitation->token);
        $expiresLabel = $invitation->expires_at
            ?->timezone(config('app.timezone'))
            ->toFormattedDateString();

        $this->sendToInvitee(
            email: $invitation->email,
            mailable: new InvitationMail(
                kind: $kind,
                userName: $this->inviteeDisplayName($invitation->email),
                organizationName: $organization->name,
                inviterName: $inviter->name,
                roleLabel: $this->roleLabel($invitation->role),
                actionUrl: $inviteUrl,
                expiresLabel: $expiresLabel,
            ),
            fallbackLocale: $this->mailer->localeFor($inviter),
            context: [
                'kind' => $kind,
                'organization_id' => $organization->id,
                'invitation_id' => $invitation->id,
            ],
        );
    }

    public function notifyAccepted(
        OrganizationInvitation $invitation,
        Organization $organization,
        User $member,
        User $inviter,
    ): void {
        $membersUrl = rtrim((string) config('app.url'), '/').'/console/'.$organization->id.'/members';
        $consoleUrl = rtrim((string) config('app.url'), '/').'/console/'.$organization->id;

        $this->sendToInvitee(
            email: $member->email,
            mailable: new InvitationMail(
                kind: 'accepted',
                userName: $member->name,
                organizationName: $organization->name,
                inviterName: $inviter->name,
                roleLabel: $this->roleLabel($invitation->role === MemberRole::Owner
                    ? MemberRole::Member
                    : $invitation->role),
                actionUrl: $consoleUrl,
            ),
            fallbackLocale: $this->mailer->localeFor($member, $this->mailer->localeFor($inviter)),
            context: [
                'kind' => 'accepted',
                'organization_id' => $organization->id,
                'invitation_id' => $invitation->id,
            ],
        );

        foreach ($this->ownerAndAdmins($organization, excludeUserId: $member->id) as $admin) {
            try {
                $this->mailer->sendToUser(
                    $admin,
                    new InvitationMail(
                        kind: 'accepted_admin',
                        userName: $admin->name,
                        organizationName: $organization->name,
                        inviterName: $inviter->name,
                        roleLabel: $this->roleLabel($invitation->role === MemberRole::Owner
                            ? MemberRole::Member
                            : $invitation->role),
                        actionUrl: $membersUrl,
                        memberName: $member->name,
                    ),
                );
            } catch (Throwable $e) {
                Log::warning('Invitation lifecycle email failed', [
                    'kind' => 'accepted_admin',
                    'organization_id' => $organization->id,
                    'user_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function notifyRevoked(
        OrganizationInvitation $invitation,
        Organization $organization,
        User $actor,
    ): void {
        $homeUrl = rtrim((string) config('app.url'), '/');

        $this->sendToInvitee(
            email: $invitation->email,
            mailable: new InvitationMail(
                kind: 'revoked',
                userName: $this->inviteeDisplayName($invitation->email),
                organizationName: $organization->name,
                inviterName: $actor->name,
                roleLabel: $this->roleLabel($invitation->role),
                actionUrl: $homeUrl,
            ),
            fallbackLocale: $this->mailer->localeFor($actor),
            context: [
                'kind' => 'revoked',
                'organization_id' => $organization->id,
                'invitation_id' => $invitation->id,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function sendToInvitee(
        string $email,
        InvitationMail $mailable,
        string $fallbackLocale,
        array $context,
    ): void {
        try {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->first();

            if ($user !== null) {
                $this->mailer->sendToUser($user, $mailable, $fallbackLocale);
            } else {
                $this->mailer->send($email, $mailable, $fallbackLocale);
            }
        } catch (Throwable $e) {
            Log::warning('Invitation lifecycle email failed', [
                ...$context,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function inviteeDisplayName(string $email): string
    {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if ($user !== null && filled($user->name)) {
            return $user->name;
        }

        $local = strstr($email, '@', true);

        return is_string($local) && $local !== '' ? $local : $email;
    }

    private function roleLabel(MemberRole $role): string
    {
        return __('mail.invitation_role.'.$role->value);
    }

    /**
     * @return list<User>
     */
    private function ownerAndAdmins(Organization $organization, ?string $excludeUserId = null): array
    {
        return OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->whereIn('role', [MemberRole::Owner, MemberRole::Admin])
            ->when(
                $excludeUserId !== null,
                fn ($query) => $query->where('user_id', '!=', $excludeUserId),
            )
            ->with('user')
            ->get()
            ->map(fn (OrganizationMember $member): ?User => $member->user)
            ->filter(fn (?User $user): bool => $user instanceof User && $user->is_active)
            ->unique('id')
            ->values()
            ->all();
    }
}
