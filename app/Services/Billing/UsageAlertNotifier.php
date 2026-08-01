<?php

namespace App\Services\Billing;

use App\Enums\MemberRole;
use App\Mail\UsageAlertMail;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationUsageMonth;
use App\Models\User;
use App\Services\Mail\LocalizedMailer;
use App\Services\Usage\UsageTracker;
use Illuminate\Support\Facades\Log;

class UsageAlertNotifier
{
    public function __construct(
        private readonly PlanEntitlements $entitlements,
        private readonly UsageTracker $usage,
        private readonly LocalizedMailer $mailer,
    ) {}

    public function checkAfterIncrement(Organization $organization): void
    {
        if (! $this->entitlements->billingEnabled()) {
            return;
        }

        if ($this->entitlements->isPro($organization)) {
            $this->checkProOrganization($organization);

            return;
        }

        $owner = $this->entitlements->owner($organization);
        if ($owner === null) {
            return;
        }

        $this->checkFreePool($owner, $organization);
    }

    private function checkProOrganization(Organization $organization): void
    {
        $plan = $this->entitlements->planFor($organization);
        $counter = $this->usage->counter($organization);

        $this->evaluateMetric(
            organization: $organization,
            counter: $counter,
            metricKey: 'links',
            used: (int) $counter->links_created,
            limit: $plan->links_per_month,
            planName: $plan->name,
            recipients: $this->ownerAndAdmins($organization),
        );

        $this->evaluateMetric(
            organization: $organization,
            counter: $counter,
            metricKey: 'qr',
            used: (int) $counter->qr_generated,
            limit: $plan->qr_per_month,
            planName: $plan->name,
            recipients: $this->ownerAndAdmins($organization),
        );
    }

    private function checkFreePool(User $owner, Organization $triggerOrganization): void
    {
        $free = $this->entitlements->freePlan();
        $pool = $this->entitlements->freePoolUsage($owner);
        $anchorId = $pool['organization_ids'][0] ?? $triggerOrganization->id;
        $anchor = Organization::query()->find($anchorId) ?? $triggerOrganization;
        $counter = $this->usage->counter($anchor);

        $this->evaluateMetric(
            organization: $anchor,
            counter: $counter,
            metricKey: 'links',
            used: $pool['links_created'],
            limit: $free->links_per_month,
            planName: $free->name,
            recipients: [$owner],
            alertPrefix: 'pool_',
        );

        $this->evaluateMetric(
            organization: $anchor,
            counter: $counter,
            metricKey: 'qr',
            used: $pool['qr_generated'],
            limit: $free->qr_per_month,
            planName: $free->name,
            recipients: [$owner],
            alertPrefix: 'pool_',
        );
    }

    /**
     * @param  list<User>  $recipients
     */
    private function evaluateMetric(
        Organization $organization,
        OrganizationUsageMonth $counter,
        string $metricKey,
        int $used,
        ?int $limit,
        string $planName,
        array $recipients,
        string $alertPrefix = '',
    ): void {
        if ($limit === null || $limit <= 0 || $recipients === []) {
            return;
        }

        $percent = ($used / $limit) * 100;
        $alerts = is_array($counter->alerts) ? $counter->alerts : [];

        foreach ($this->thresholds() as $threshold) {
            if ($percent + 0.0001 < $threshold) {
                continue;
            }

            $kindKey = $alertPrefix.$metricKey.'_'.(int) $threshold;
            if (! empty($alerts[$kindKey])) {
                continue;
            }

            $kind = $threshold >= 100 ? 'limit' : 'warning';
            $billingUrl = rtrim((string) config('app.url'), '/').'/console/'.$organization->id.'/billing';

            foreach ($recipients as $recipient) {
                $this->mailer->sendToUser(
                    $recipient,
                    new UsageAlertMail(
                        kind: $kind,
                        userName: $recipient->name,
                        organizationName: $organization->name,
                        planName: $planName,
                        metricKey: $metricKey,
                        used: $used,
                        limit: $limit,
                        percent: $percent,
                        threshold: (int) $threshold,
                        billingUrl: $billingUrl,
                    ),
                );
            }

            $alerts[$kindKey] = now()->toIso8601String();
            Log::info('Usage alert sent', [
                'organization_id' => $organization->id,
                'metric' => $metricKey,
                'threshold' => $threshold,
                'used' => $used,
                'limit' => $limit,
            ]);
        }

        if ($alerts !== (is_array($counter->alerts) ? $counter->alerts : [])) {
            $counter->forceFill(['alerts' => $alerts])->save();
        }
    }

    /**
     * @return list<float>
     */
    private function thresholds(): array
    {
        $configured = config('azshrtr.usage_alerts.thresholds', [89, 90, 100]);
        if (! is_array($configured) || $configured === []) {
            return [89.0, 90.0, 100.0];
        }

        $thresholds = array_map(static fn (mixed $value): float => (float) $value, $configured);
        sort($thresholds);

        return array_values(array_unique($thresholds));
    }

    /**
     * @return list<User>
     */
    private function ownerAndAdmins(Organization $organization): array
    {
        return OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->whereIn('role', [MemberRole::Owner, MemberRole::Admin])
            ->with('user')
            ->get()
            ->map(fn (OrganizationMember $member): ?User => $member->user)
            ->filter(fn (?User $user): bool => $user instanceof User && $user->is_active)
            ->unique('id')
            ->values()
            ->all();
    }
}
