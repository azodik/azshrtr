<?php

namespace App\Enums;

enum MemberRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function canManageBilling(): bool
    {
        return $this === self::Owner;
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canWriteLinks(): bool
    {
        return true;
    }
}
