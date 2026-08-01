<?php

namespace App\Services\Billing;

use App\Enums\MemberRole;
use App\Enums\SubscriptionStatus;
use App\Models\ApiKey;
use App\Models\BillingPlan;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationUsageMonth;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanEntitlements
{
    public function billingEnabled(): bool
    {
        return (bool) config('billing.enabled');
    }

    public function planFor(Organization $organization): BillingPlan
    {
        $organization->loadMissing('subscription.plan');

        $subscription = $organization->subscription;
        if (
            $subscription !== null
            && $subscription->plan !== null
            && in_array($subscription->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)
        ) {
            return $subscription->plan;
        }

        return BillingPlan::query()->where('slug', 'free')->firstOrFail();
    }

    public function isPro(Organization $organization): bool
    {
        return $this->planFor($organization)->slug === 'pro';
    }

    public function isFree(Organization $organization): bool
    {
        return ! $this->isPro($organization);
    }

    public function owner(Organization $organization): ?User
    {
        $member = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('role', MemberRole::Owner)
            ->where('status', 'active')
            ->first();

        return $member?->user;
    }

    /**
     * @return list<string>
     */
    public function freeOrganizationIdsForOwner(User $owner): array
    {
        $ownedOrgIds = OrganizationMember::query()
            ->where('user_id', $owner->id)
            ->where('role', MemberRole::Owner)
            ->where('status', 'active')
            ->pluck('organization_id');

        if ($ownedOrgIds->isEmpty()) {
            return [];
        }

        return Organization::query()
            ->whereIn('id', $ownedOrgIds)
            ->with('subscription.plan')
            ->get()
            ->filter(fn (Organization $org): bool => $this->isFree($org))
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * @return array{links_created: int, qr_generated: int, api_keys: int, organization_ids: list<string>}
     */
    public function freePoolUsage(User $owner, ?string $period = null): array
    {
        $period ??= $this->currentPeriod();
        $orgIds = $this->freeOrganizationIdsForOwner($owner);

        if ($orgIds === []) {
            return [
                'links_created' => 0,
                'qr_generated' => 0,
                'api_keys' => 0,
                'organization_ids' => [],
            ];
        }

        $counters = OrganizationUsageMonth::query()
            ->whereIn('organization_id', $orgIds)
            ->where('period', $period)
            ->get();

        $apiKeys = ApiKey::query()
            ->whereIn('organization_id', $orgIds)
            ->whereNull('revoked_at')
            ->count();

        return [
            'links_created' => (int) $counters->sum('links_created'),
            'qr_generated' => (int) $counters->sum('qr_generated'),
            'api_keys' => $apiKeys,
            'organization_ids' => $orgIds,
        ];
    }

    public function freePlan(): BillingPlan
    {
        return BillingPlan::query()->where('slug', 'free')->firstOrFail();
    }

    public function linksPerMonth(Organization $organization): ?int
    {
        if (! $this->billingEnabled()) {
            return null;
        }

        return $this->planFor($organization)->links_per_month;
    }

    public function qrPerMonth(Organization $organization): ?int
    {
        if (! $this->billingEnabled()) {
            return null;
        }

        return $this->planFor($organization)->qr_per_month;
    }

    public function apiKeysLimit(Organization $organization): int
    {
        if (! $this->billingEnabled()) {
            return 100;
        }

        return (int) $this->planFor($organization)->api_keys_limit;
    }

    public function allowsCustomDomains(Organization $organization): bool
    {
        if (! $this->billingEnabled()) {
            return true;
        }

        return (bool) $this->planFor($organization)->allows_custom_domains;
    }

    public function allowsPasswordLinks(Organization $organization): bool
    {
        if (! $this->billingEnabled()) {
            return true;
        }

        return (bool) $this->planFor($organization)->allows_password_links;
    }

    public function auditRetentionDays(Organization $organization): int
    {
        return (int) $this->planFor($organization)->audit_retention_days;
    }

    public function clickRetentionDays(Organization $organization): int
    {
        return (int) $this->planFor($organization)->click_retention_days;
    }

    public function apiLogRetentionDays(Organization $organization): int
    {
        $plan = $this->planFor($organization);
        $days = (int) ($plan->api_log_retention_days ?? 0);

        if ($days > 0) {
            return $days;
        }

        // Fallback for installs that have not migrated the column yet.
        return $this->isPro($organization) ? 90 : 7;
    }

    public function currentPeriod(): string
    {
        $tz = (string) config('azshrtr.usage_timezone', 'UTC');

        return Carbon::now($tz)->format('Y-m');
    }

    /**
     * @return Collection<int, Organization>
     */
    public function freeOrganizationsForOwner(User $owner): Collection
    {
        $ids = $this->freeOrganizationIdsForOwner($owner);

        return Organization::query()->whereIn('id', $ids)->get();
    }
}
