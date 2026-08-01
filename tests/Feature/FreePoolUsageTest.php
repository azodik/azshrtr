<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Enums\SubscriptionStatus;
use App\Models\BillingPlan;
use App\Models\OrganizationMember;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationUsageMonth;
use App\Models\User;
use App\Services\OrganizationService;
use App\Services\Usage\UsageTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FreePoolUsageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function free_link_limit_is_shared_across_owner_free_orgs(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();

        $owner = User::factory()->create();
        $orgs = app(OrganizationService::class);
        $a = $orgs->createForUser($owner, 'Workspace A');
        $b = $orgs->createForUser($owner, 'Workspace B');

        $period = app(UsageTracker::class)->currentPeriod();
        OrganizationUsageMonth::query()->create([
            'organization_id' => $a->id,
            'period' => $period,
            'links_created' => 2999,
            'qr_generated' => 0,
            'api_calls' => 0,
        ]);
        OrganizationUsageMonth::query()->create([
            'organization_id' => $b->id,
            'period' => $period,
            'links_created' => 1,
            'qr_generated' => 0,
            'api_calls' => 0,
        ]);

        $tracker = app(UsageTracker::class);

        $this->expectException(ValidationException::class);
        $tracker->assertCanCreateLink($b);
    }

    #[Test]
    public function pro_org_usage_is_isolated_from_free_pool(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();

        $owner = User::factory()->create();
        $orgs = app(OrganizationService::class);
        $free = $orgs->createForUser($owner, 'Free workspace');
        $pro = $orgs->createForUser($owner, 'Pro workspace');

        $proPlan = BillingPlan::query()->where('slug', 'pro')->firstOrFail();
        OrganizationSubscription::query()->where('organization_id', $pro->id)->update([
            'billing_plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
        ]);
        $pro->unsetRelation('subscription');

        $period = app(UsageTracker::class)->currentPeriod();
        OrganizationUsageMonth::query()->create([
            'organization_id' => $free->id,
            'period' => $period,
            'links_created' => 3000,
            'qr_generated' => 0,
            'api_calls' => 0,
        ]);

        app(UsageTracker::class)->assertCanCreateLink($pro->fresh());
        $this->assertTrue(true);
    }

    #[Test]
    public function member_actions_burn_owner_free_pool(): void
    {
        config(['billing.enabled' => true]);
        $this->seedCore();

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Shared');

        OrganizationMember::query()->create([
            'organization_id' => $org->id,
            'user_id' => $member->id,
            'role' => MemberRole::Member,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $period = app(UsageTracker::class)->currentPeriod();
        OrganizationUsageMonth::query()->create([
            'organization_id' => $org->id,
            'period' => $period,
            'links_created' => 3000,
            'qr_generated' => 0,
            'api_calls' => 0,
        ]);

        $this->expectException(ValidationException::class);
        app(UsageTracker::class)->assertCanCreateLink($org);
    }
}
