<?php

namespace App\Services\Links;

use App\Enums\AuditAction;
use App\Models\Link;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Usage\UsageTracker;
use Illuminate\Validation\ValidationException;

class ClaimService
{
    public function __construct(
        private readonly UsageTracker $usage,
        private readonly AuditLogger $audit,
    ) {}

    public function claim(string $token, Organization $organization, User $user): Link
    {
        $link = Link::query()
            ->where('claim_token', $token)
            ->where('is_anonymous', true)
            ->first();

        if ($link === null) {
            throw ValidationException::withMessages([
                'token' => ['This claim link is invalid or already used.'],
            ]);
        }

        if ($link->claim_token_expires_at !== null && $link->claim_token_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['This claim link has expired.'],
            ]);
        }

        if ($link->isExpired()) {
            throw ValidationException::withMessages([
                'token' => ['This short link has expired.'],
            ]);
        }

        $this->usage->assertCanCreateLink($organization);

        $link->forceFill([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'is_anonymous' => false,
            'expires_at' => null,
            'claim_token' => null,
            'claim_token_expires_at' => null,
        ])->save();

        $this->usage->incrementLinksCreated($organization);
        $this->audit->log(AuditAction::LinkClaimed, $user, $organization, 'link', $link->id);

        return $link->refresh();
    }
}
