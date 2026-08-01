<?php

namespace App\Services\Billing;

use App\Enums\MemberRole;
use App\Mail\BillingPaymentMail;
use App\Mail\SubscriptionChangeMail;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\Mail\LocalizedMailer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingNotifier
{
    public function __construct(
        private readonly LocalizedMailer $mailer,
    ) {}

    public function notifyUpgraded(Organization $organization): void
    {
        $this->sendSubscription($organization, 'upgraded');
    }

    public function notifyDowngradeScheduled(Organization $organization, ?Carbon $effectiveAt): void
    {
        $this->sendSubscription(
            $organization,
            'downgrade_scheduled',
            $effectiveAt?->timezone(config('app.timezone'))->toFormattedDateString(),
        );
    }

    public function notifyDowngraded(Organization $organization): void
    {
        $this->sendSubscription($organization, 'downgraded');
    }

    /**
     * @param  'payment_succeeded'|'payment_failed'|'checkout_abandoned'|'refund_initiated'|'refund_succeeded'  $kind
     */
    public function notifyPaymentOutcome(
        Organization $organization,
        string $kind,
        ?string $amountLabel = null,
    ): void {
        $billingUrl = rtrim((string) config('app.url'), '/').'/console/'.$organization->id.'/billing';

        foreach ($this->ownerAndAdmins($organization) as $user) {
            try {
                $this->mailer->sendToUser(
                    $user,
                    new BillingPaymentMail(
                        kind: $kind,
                        userName: $user->name,
                        organizationName: $organization->name,
                        billingUrl: $billingUrl,
                        amountLabel: $amountLabel,
                    ),
                );
            } catch (Throwable $e) {
                Log::warning('Billing payment outcome email failed', [
                    'kind' => $kind,
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  'upgraded'|'downgrade_scheduled'|'downgraded'  $kind
     */
    private function sendSubscription(Organization $organization, string $kind, ?string $effectiveDate = null): void
    {
        $billingUrl = rtrim((string) config('app.url'), '/').'/console/'.$organization->id.'/billing';

        foreach ($this->ownerAndAdmins($organization) as $user) {
            try {
                $this->mailer->sendToUser(
                    $user,
                    new SubscriptionChangeMail(
                        kind: $kind,
                        userName: $user->name,
                        organizationName: $organization->name,
                        billingUrl: $billingUrl,
                        effectiveDate: $effectiveDate,
                    ),
                );
            } catch (Throwable $e) {
                Log::warning('Billing subscription email failed', [
                    'kind' => $kind,
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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
