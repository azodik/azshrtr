<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\SubscriptionStatus;
use App\Models\ApiRequestLog;
use App\Models\AuditLog;
use App\Models\BillingPlan;
use App\Models\Link;
use App\Models\LinkClick;
use App\Models\OrganizationSubscription;
use App\Models\User;
use App\Services\Domains\PlatformDomain;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogRetentionPruneTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function prune_commands_delete_rows_past_plan_retention_in_batches(): void
    {
        $this->seedCore();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Retention Org');
        $free = BillingPlan::query()->where('slug', 'free')->firstOrFail();

        OrganizationSubscription::query()->where('organization_id', $org->id)->update([
            'billing_plan_id' => $free->id,
        ]);
        $org->unsetRelation('subscription');

        AuditLog::query()->create([
            'organization_id' => $org->id,
            'actor_user_id' => $owner->id,
            'action' => AuditAction::OrganizationCreated->value,
            'created_at' => now()->subDays(10),
        ]);
        AuditLog::query()->create([
            'organization_id' => $org->id,
            'actor_user_id' => $owner->id,
            'action' => AuditAction::OrganizationCreated->value,
            'created_at' => now()->subDay(),
        ]);

        ApiRequestLog::query()->create([
            'organization_id' => $org->id,
            'api_key_id' => null,
            'method' => 'GET',
            'path' => '/api/v1/me',
            'status' => 200,
            'latency_ms' => 12,
            'created_at' => now()->subDays(10),
        ]);
        ApiRequestLog::query()->create([
            'organization_id' => $org->id,
            'api_key_id' => null,
            'method' => 'GET',
            'path' => '/api/v1/links',
            'status' => 200,
            'latency_ms' => 8,
            'created_at' => now()->subDay(),
        ]);

        DB::table('api_request_aggregates')->insert([
            [
                'organization_id' => $org->id,
                'api_key_id' => null,
                'period' => 'hour',
                'period_start' => now()->subDays(10)->startOfHour(),
                'request_count' => 5,
                'error_count' => 0,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'organization_id' => $org->id,
                'api_key_id' => null,
                'period' => 'hour',
                'period_start' => now()->subDay()->startOfHour(),
                'request_count' => 2,
                'error_count' => 0,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $domain = app(PlatformDomain::class)->resolve();
        $link = Link::query()->create([
            'organization_id' => $org->id,
            'user_id' => $owner->id,
            'domain_id' => $domain->id,
            'code' => 'retent',
            'destination_url' => 'https://azshrtr.com/retention',
        ]);

        LinkClick::query()->create([
            'link_id' => $link->id,
            'organization_id' => $org->id,
            'clicked_at' => now()->subDays(45),
            'ip_hash' => hash('sha256', '1.1.1.1'),
        ]);
        LinkClick::query()->create([
            'link_id' => $link->id,
            'organization_id' => $org->id,
            'clicked_at' => now()->subDays(2),
            'ip_hash' => hash('sha256', '2.2.2.2'),
        ]);

        $this->artisan('audit:prune', ['--chunk' => 100])->assertSuccessful();
        // Keeps org-created audit + the 1-day-old row; drops the 10-day-old Free row.
        $this->assertSame(2, AuditLog::query()->where('organization_id', $org->id)->count());
        $this->assertFalse(
            AuditLog::query()
                ->where('organization_id', $org->id)
                ->where('created_at', '<', now()->subDays(7))
                ->exists(),
        );

        $this->artisan('api-logs:prune', ['--chunk' => 100])->assertSuccessful();
        $this->assertSame(1, ApiRequestLog::query()->where('organization_id', $org->id)->count());
        $this->assertSame(
            1,
            DB::table('api_request_aggregates')->where('organization_id', $org->id)->count(),
        );

        $this->artisan('clicks:prune', ['--chunk' => 100])->assertSuccessful();
        $this->assertSame(1, LinkClick::query()->where('organization_id', $org->id)->count());
    }

    #[Test]
    public function pro_plan_keeps_longer_api_and_audit_history(): void
    {
        $this->seedCore();

        $owner = User::factory()->create();
        $org = app(OrganizationService::class)->createForUser($owner, 'Pro Retention');
        $pro = BillingPlan::query()->where('slug', 'pro')->firstOrFail();

        OrganizationSubscription::query()->where('organization_id', $org->id)->update([
            'billing_plan_id' => $pro->id,
            'status' => SubscriptionStatus::Active,
        ]);
        $org->unsetRelation('subscription');

        AuditLog::query()->create([
            'organization_id' => $org->id,
            'actor_user_id' => $owner->id,
            'action' => AuditAction::OrganizationCreated->value,
            'created_at' => now()->subDays(10),
        ]);

        ApiRequestLog::query()->create([
            'organization_id' => $org->id,
            'method' => 'GET',
            'path' => '/api/v1/me',
            'status' => 200,
            'latency_ms' => 5,
            'created_at' => now()->subDays(10),
        ]);

        $this->artisan('audit:prune')->assertSuccessful();
        $this->artisan('api-logs:prune')->assertSuccessful();

        // Pro keeps 90 days — org-created audit + 10-day-old row remain.
        $this->assertGreaterThanOrEqual(
            2,
            AuditLog::query()->where('organization_id', $org->id)->count(),
        );
        $this->assertSame(1, ApiRequestLog::query()->where('organization_id', $org->id)->count());
    }
}
