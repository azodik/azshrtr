<?php

namespace Tests\Unit;

use App\Enums\MemberRole;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberRoleTest extends TestCase
{
    #[Test]
    public function owner_can_manage_billing_and_members(): void
    {
        $role = MemberRole::Owner;

        $this->assertTrue($role->canManageBilling());
        $this->assertTrue($role->canManageMembers());
        $this->assertTrue($role->canWriteLinks());
    }

    #[Test]
    public function admin_can_manage_members_but_not_billing(): void
    {
        $role = MemberRole::Admin;

        $this->assertFalse($role->canManageBilling());
        $this->assertTrue($role->canManageMembers());
        $this->assertTrue($role->canWriteLinks());
    }

    #[Test]
    public function member_cannot_manage_billing_or_members(): void
    {
        $role = MemberRole::Member;

        $this->assertFalse($role->canManageBilling());
        $this->assertFalse($role->canManageMembers());
        $this->assertTrue($role->canWriteLinks());
    }
}
