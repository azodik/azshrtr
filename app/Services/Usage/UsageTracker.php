<?php

namespace App\Services\Usage;

use App\Models\BillingPlan;
use App\Models\Organization;
use App\Models\OrganizationUsageMonth;
use App\Services\Billing\PlanEntitlements;
use App\Services\Billing\UsageAlertNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class UsageTracker
{
    public function __construct(private readonly PlanEntitlements $entitlements) {}

    public function currentPeriod(): string
    {
        $tz = (string) config('azshrtr.usage_timezone', 'UTC');

        return Carbon::now($tz)->format('Y-m');
    }

    public function counter(Organization $organization): OrganizationUsageMonth
    {
        return OrganizationUsageMonth::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'period' => $this->currentPeriod(),
            ],
            [
                'links_created' => 0,
                'qr_generated' => 0,
                'api_calls' => 0,
            ],
        );
    }

    public function assertCanCreateLink(Organization $organization): void
    {
        if (! $this->entitlements->billingEnabled()) {
            return;
        }

        if ($this->entitlements->isPro($organization)) {
            $limit = $this->entitlements->linksPerMonth($organization);
            if ($limit === null) {
                return;
            }
            $used = $this->counter($organization)->links_created;
            if ($used >= $limit) {
                throw ValidationException::withMessages([
                    'url' => ["Monthly link limit of {$limit} reached. Upgrade to Pro for unlimited links."],
                ]);
            }

            return;
        }

        $owner = $this->entitlements->owner($organization);
        if ($owner === null) {
            return;
        }

        $free = $this->entitlements->freePlan();
        $limit = $free->links_per_month;
        if ($limit === null) {
            return;
        }

        $used = $this->entitlements->freePoolUsage($owner)['links_created'];
        if ($used >= $limit) {
            throw ValidationException::withMessages([
                'url' => ["Shared Free monthly link limit of {$limit} reached across your Free workspaces. Upgrade an org to Pro for unlimited links."],
            ]);
        }
    }

    public function assertCanGenerateQr(Organization $organization): void
    {
        if (! $this->entitlements->billingEnabled()) {
            return;
        }

        if ($this->entitlements->isPro($organization)) {
            $limit = $this->entitlements->qrPerMonth($organization);
            if ($limit === null) {
                return;
            }
            $used = $this->counter($organization)->qr_generated;
            if ($used >= $limit) {
                throw ValidationException::withMessages([
                    'qr' => ["Monthly QR limit of {$limit} reached. Upgrade to Pro for unlimited QR codes."],
                ]);
            }

            return;
        }

        $owner = $this->entitlements->owner($organization);
        if ($owner === null) {
            return;
        }

        $free = $this->entitlements->freePlan();
        $limit = $free->qr_per_month;
        if ($limit === null) {
            return;
        }

        $used = $this->entitlements->freePoolUsage($owner)['qr_generated'];
        if ($used >= $limit) {
            throw ValidationException::withMessages([
                'qr' => ["Shared Free monthly QR limit of {$limit} reached across your Free workspaces. Upgrade an org to Pro for unlimited QR."],
            ]);
        }
    }

    public function assertCanUsePasswordLinks(Organization $organization): void
    {
        if (! $this->entitlements->allowsPasswordLinks($organization)) {
            throw ValidationException::withMessages([
                'password' => ['Password-protected links require Pro.'],
            ]);
        }
    }

    public function assertCanAddDomain(Organization $organization): void
    {
        if (! $this->entitlements->allowsCustomDomains($organization)) {
            throw ValidationException::withMessages([
                'hostname' => ['Custom domains require Pro.'],
            ]);
        }
    }

    public function assertCanCreateApiKey(Organization $organization): void
    {
        $limit = $this->entitlements->apiKeysLimit($organization);

        if ($this->entitlements->isPro($organization) || ! $this->entitlements->billingEnabled()) {
            $count = $organization->apiKeys()->whereNull('revoked_at')->count();
            if ($count >= $limit) {
                throw ValidationException::withMessages([
                    'name' => ["API key limit of {$limit} reached for your plan."],
                ]);
            }

            return;
        }

        $owner = $this->entitlements->owner($organization);
        if ($owner === null) {
            return;
        }

        $freeLimit = (int) $this->entitlements->freePlan()->api_keys_limit;
        $used = $this->entitlements->freePoolUsage($owner)['api_keys'];
        if ($used >= $freeLimit) {
            throw ValidationException::withMessages([
                'name' => ["Shared Free API key limit of {$freeLimit} reached across your Free workspaces."],
            ]);
        }
    }

    public function incrementLinksCreated(Organization $organization): void
    {
        $this->incrementLinksCreatedBy($organization, 1);
    }

    public function incrementLinksCreatedBy(Organization $organization, int $count): void
    {
        if ($count <= 0) {
            return;
        }

        $this->counter($organization)->increment('links_created', $count);
        app(UsageAlertNotifier::class)->checkAfterIncrement($organization);
    }

    public function incrementQrGenerated(Organization $organization): void
    {
        $this->counter($organization)->increment('qr_generated');
        app(UsageAlertNotifier::class)->checkAfterIncrement($organization);
    }

    public function incrementApiCalls(Organization $organization): void
    {
        $this->counter($organization)->increment('api_calls');
    }

    public function plan(Organization $organization): BillingPlan
    {
        return $this->entitlements->planFor($organization);
    }
}
