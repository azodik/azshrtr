<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\MemberRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\Request;

trait EnsuresOrganizationMembership
{
    protected function organization(Request $request, string $organizationId): Organization
    {
        /** @var User $user */
        $user = $request->user();

        $member = OrganizationMember::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $organization = Organization::query()->findOrFail($organizationId);
        $request->attributes->set('organization_member', $member);

        return $organization;
    }

    protected function memberRole(Request $request): MemberRole
    {
        /** @var OrganizationMember $member */
        $member = $request->attributes->get('organization_member');

        return $member->role;
    }
}
