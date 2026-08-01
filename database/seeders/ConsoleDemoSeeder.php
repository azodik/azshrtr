<?php

namespace Database\Seeders;

use App\Enums\AuditAction;
use App\Enums\MemberRole;
use App\Enums\SubscriptionStatus;
use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Models\AuditLog;
use App\Models\BillingPlan;
use App\Models\Domain;
use App\Models\DomainDnsRecord;
use App\Models\Link;
use App\Models\LinkClick;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationUsageMonth;
use App\Models\QrCode;
use App\Models\User;
use App\Services\ApiKeys\ApiKeyService;
use App\Services\Domains\PlatformDomain;
use App\Services\Links\LinkService;
use App\Services\OrganizationService;
use App\Services\Usage\UsageTracker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsoleDemoSeeder extends Seeder
{
    public const DEMO_EMAIL = 'demo@azshrtr.com';

    public const DEMO_PASSWORD = 'password';

    public const DEMO_ORG_SLUG = 'demo-workspace';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('ConsoleDemoSeeder skipped outside local/testing.');

            return;
        }

        $this->call([
            BillingPlanSeeder::class,
            PlatformDomainSeeder::class,
        ]);

        $this->purgePreviousDemo();

        $owner = User::query()->updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => 'Demo Owner',
                'password' => self::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'is_active' => true,
                'preferred_locale' => 'en',
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'alex@azshrtr.com'],
            [
                'name' => 'Alex Admin',
                'password' => self::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $member = User::query()->updateOrCreate(
            ['email' => 'morgan@azshrtr.com'],
            [
                'name' => 'Morgan Member',
                'password' => self::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $organization = app(OrganizationService::class)->createForUser($owner, 'Demo Workspace');
        $organization->forceFill([
            'slug' => self::DEMO_ORG_SLUG,
            'is_demo' => true,
            'billing_email' => self::DEMO_EMAIL,
        ])->save();

        $pro = BillingPlan::query()->where('slug', 'pro')->firstOrFail();
        OrganizationSubscription::query()
            ->where('organization_id', $organization->id)
            ->update([
                'billing_plan_id' => $pro->id,
                'status' => SubscriptionStatus::Active,
            ]);

        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'role' => MemberRole::Admin,
            'status' => 'active',
            'joined_at' => now()->subDays(12),
        ]);

        OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => MemberRole::Member,
            'status' => 'active',
            'joined_at' => now()->subDays(5),
        ]);

        $extraMembers = [
            ['name' => 'Sam Rivera', 'email' => 'sam@azshrtr.com', 'role' => MemberRole::Admin, 'days' => 40],
            ['name' => 'Jordan Lee', 'email' => 'jordan@azshrtr.com', 'role' => MemberRole::Member, 'days' => 35],
            ['name' => 'Casey Nguyen', 'email' => 'casey@azshrtr.com', 'role' => MemberRole::Member, 'days' => 28],
            ['name' => 'Riley Quinn', 'email' => 'riley@azshrtr.com', 'role' => MemberRole::Member, 'days' => 22],
            ['name' => 'Avery Kim', 'email' => 'avery@azshrtr.com', 'role' => MemberRole::Member, 'days' => 18],
            ['name' => 'Cameron Blake', 'email' => 'cameron@azshrtr.com', 'role' => MemberRole::Admin, 'days' => 15],
            ['name' => 'Drew Patel', 'email' => 'drew@azshrtr.com', 'role' => MemberRole::Member, 'days' => 12],
            ['name' => 'Harper Singh', 'email' => 'harper@azshrtr.com', 'role' => MemberRole::Member, 'days' => 9],
            ['name' => 'Jamie Ortega', 'email' => 'jamie@azshrtr.com', 'role' => MemberRole::Member, 'days' => 7],
            ['name' => 'Taylor Brooks', 'email' => 'taylor@azshrtr.com', 'role' => MemberRole::Member, 'days' => 4],
            ['name' => 'Reese Okonkwo', 'email' => 'reese@azshrtr.com', 'role' => MemberRole::Member, 'days' => 3],
            ['name' => 'Skyler Chen', 'email' => 'skyler@azshrtr.com', 'role' => MemberRole::Member, 'days' => 2],
            ['name' => 'Quinn Haddad', 'email' => 'quinn@azshrtr.com', 'role' => MemberRole::Member, 'days' => 1],
        ];

        foreach ($extraMembers as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => self::DEMO_PASSWORD,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            OrganizationMember::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => $row['role'],
                'status' => 'active',
                'joined_at' => now()->subDays($row['days']),
                'created_at' => now()->subDays($row['days'] + 1),
            ]);
        }

        $pendingInvites = [
            ['email' => 'pending@azshrtr.com', 'role' => MemberRole::Member, 'days' => 5],
            ['email' => 'nova@azshrtr.com', 'role' => MemberRole::Admin, 'days' => 7],
            ['email' => 'leo@partner.example', 'role' => MemberRole::Member, 'days' => 3],
            ['email' => 'mira@agency.example', 'role' => MemberRole::Member, 'days' => 10],
            ['email' => 'chris@vendor.example', 'role' => MemberRole::Member, 'days' => 2],
        ];

        foreach ($pendingInvites as $invite) {
            OrganizationInvitation::query()->create([
                'organization_id' => $organization->id,
                'email' => $invite['email'],
                'role' => $invite['role'],
                'token' => Str::random(64),
                'invited_by' => $owner->id,
                'expires_at' => now()->addDays($invite['days']),
                'created_at' => now()->subDays(fake()->numberBetween(0, 6)),
            ]);
        }

        $customDomain = Domain::query()->create([
            'organization_id' => $organization->id,
            'hostname' => 'go.demo.azshrtr.com',
            'is_system' => false,
            'is_primary' => false,
            'status' => 'verified',
            'verification_token' => Str::random(32),
            'verified_at' => now()->subDays(10),
        ]);

        DomainDnsRecord::query()->create([
            'domain_id' => $customDomain->id,
            'purpose' => 'cname',
            'type' => 'CNAME',
            'name' => 'go.demo.azshrtr.com',
            'value' => 'customers.azshrtr.com',
        ]);

        $pendingDomain = Domain::query()->create([
            'organization_id' => $organization->id,
            'hostname' => 'links.acme.example',
            'is_system' => false,
            'is_primary' => false,
            'status' => 'pending',
            'verification_token' => Str::random(32),
            'verified_at' => null,
        ]);

        DomainDnsRecord::query()->create([
            'domain_id' => $pendingDomain->id,
            'purpose' => 'ownership',
            'type' => 'TXT',
            'name' => '_azshrtr-challenge.links.acme.example',
            'value' => $pendingDomain->verification_token ?? 'demo-token',
        ]);

        $linkService = app(LinkService::class);
        $destinations = [
            ['url' => 'https://azshrtr.com/blog/launch-2026', 'title' => 'Launch post'],
            ['url' => 'https://azshrtr.com/docs/getting-started', 'title' => 'Getting started'],
            ['url' => 'https://azshrtr.com/pricing', 'title' => 'Pricing page'],
            ['url' => 'https://azshrtr.com/changelog', 'title' => 'Changelog'],
            ['url' => 'https://azshrtr.com/careers', 'title' => 'Careers'],
            ['url' => 'https://azshrtr.com/support/contact', 'title' => 'Support'],
            ['url' => 'https://github.com/azodik/azshrtr', 'title' => 'GitHub repo'],
            ['url' => 'https://azshrtr.com/events/webinar', 'title' => 'Webinar signup'],
            ['url' => 'https://azshrtr.com/newsletter', 'title' => null],
            ['url' => 'https://azshrtr.com/press-kit', 'title' => 'Press kit'],
            ['url' => 'https://azshrtr.com/product/tour', 'title' => 'Product tour'],
            ['url' => 'https://azshrtr.com/status', 'title' => 'Status page'],
            ['url' => 'https://azshrtr.com/blog/case-study', 'title' => 'Case study'],
            ['url' => 'https://azshrtr.com/partners', 'title' => 'Partners'],
            ['url' => 'https://azshrtr.com/security', 'title' => 'Security'],
            ['url' => 'https://azshrtr.com/docs/api', 'title' => 'API docs'],
            ['url' => 'https://azshrtr.com/docs/webhooks', 'title' => 'Webhooks'],
            ['url' => 'https://azshrtr.com/community', 'title' => 'Community'],
            ['url' => 'https://azshrtr.com/download', 'title' => 'Download'],
            ['url' => 'https://azshrtr.com/mobile', 'title' => 'Mobile app'],
            ['url' => 'https://azshrtr.com/brand', 'title' => 'Brand assets'],
            ['url' => 'https://azshrtr.com/referral', 'title' => 'Referral program'],
            ['url' => 'https://azshrtr.com/onboarding', 'title' => 'Onboarding'],
            ['url' => 'https://azshrtr.com/faq', 'title' => 'FAQ'],
            ['url' => 'https://azshrtr.com/legal/terms', 'title' => 'Terms'],
            ['url' => 'https://azshrtr.com/legal/privacy', 'title' => 'Privacy'],
            ['url' => 'https://azshrtr.com/customers/acme', 'title' => 'Acme story'],
            ['url' => 'https://azshrtr.com/customers/globex', 'title' => 'Globex story'],
            ['url' => 'https://azshrtr.com/campaigns/spring', 'title' => 'Spring campaign'],
            ['url' => 'https://azshrtr.com/campaigns/summer', 'title' => 'Summer campaign'],
            ['url' => 'https://azshrtr.com/campaigns/fall', 'title' => 'Fall campaign'],
            ['url' => 'https://azshrtr.com/campaigns/winter', 'title' => 'Winter campaign'],
            ['url' => 'https://azshrtr.com/demo/request', 'title' => 'Request demo'],
            ['url' => 'https://azshrtr.com/demo/sandbox', 'title' => 'Sandbox'],
            ['url' => 'https://azshrtr.com/integrations/slack', 'title' => 'Slack'],
            ['url' => 'https://azshrtr.com/integrations/zapier', 'title' => 'Zapier'],
        ];

        $links = [];
        foreach ($destinations as $index => $row) {
            $payload = [
                'destination_url' => $row['url'],
                'title' => $row['title'],
            ];
            if ($index < 3) {
                $payload['domain_id'] = $customDomain->id;
            }
            $links[] = $linkService->createOwned($organization, $owner, $payload);
        }

        $countries = ['US', 'IN', 'DE', 'GB', 'BR', 'JP', 'AU', 'CA', 'FR', 'SG'];
        $devices = ['desktop', 'mobile', 'tablet'];
        $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge'];

        foreach ($links as $index => $link) {
            $clicks = fake()->numberBetween(8, 140);
            $link->forceFill(['click_count' => $clicks])->save();

            for ($i = 0; $i < min($clicks, 40); $i++) {
                LinkClick::query()->create([
                    'link_id' => $link->id,
                    'organization_id' => $organization->id,
                    'clicked_at' => now()->subDays(fake()->numberBetween(0, 28))->subMinutes(fake()->numberBetween(0, 1400)),
                    'referrer' => fake()->optional(0.6)->url(),
                    'user_agent' => 'Mozilla/5.0 (demo)',
                    'device_bucket' => fake()->randomElement($devices),
                    'browser' => fake()->randomElement($browsers),
                    'country' => fake()->randomElement($countries),
                    'region' => fake()->optional()->stateAbbr(),
                    'city' => fake()->optional()->city(),
                    'ip_hash' => hash('sha256', fake()->ipv4()),
                ]);
            }

            $sizes = [128, 256, 256, 512];
            QrCode::query()->create([
                'organization_id' => $organization->id,
                'link_id' => $link->id,
                'content' => $link->shortUrl(),
                'size' => $sizes[$index % count($sizes)],
                'margin' => 1,
                'format' => 'svg',
                'options' => null,
                'created_at' => now()->subDays(fake()->numberBetween(0, 40))->subMinutes(fake()->numberBetween(0, 800)),
            ]);
        }

        $customQrContents = [
            'https://azshrtr.com/poster/spring-sale',
            'https://azshrtr.com/booth/qr-landing',
            'WIFI:T:WPA;S:DemoGuest;P:welcome123;;',
            'mailto:hello@azshrtr.com?subject=Demo%20inquiry',
            'https://azshrtr.com/packaging/insert',
            'https://azshrtr.com/print/menu',
            'tel:+15551234567',
            'https://azshrtr.com/event/badge',
            'https://azshrtr.com/console',
            'https://azshrtr.com/offline/catalog',
        ];

        foreach ($customQrContents as $index => $customContent) {
            QrCode::query()->create([
                'organization_id' => $organization->id,
                'link_id' => null,
                'content' => $customContent,
                'size' => fake()->randomElement([128, 256, 384, 512]),
                'margin' => 1,
                'format' => 'svg',
                'options' => ['seeded' => true, 'kind' => 'custom'],
                'created_at' => now()->subDays($index + 1)->subMinutes(fake()->numberBetween(0, 400)),
            ]);
        }

        $qrGenerated = QrCode::query()->where('organization_id', $organization->id)->count();

        $apiKeyService = app(ApiKeyService::class);
        $primaryKey = $apiKeyService->create($organization, $owner, 'CI production')['api_key'];
        $stagingKey = $apiKeyService->create($organization, $admin, 'Local scripts')['api_key'];
        $revoked = $apiKeyService->create($organization, $owner, 'Old mobile app')['api_key'];
        $apiKeyService->revoke($revoked, $organization, $owner);

        $methods = ['GET', 'POST', 'PATCH', 'DELETE'];
        $paths = [
            '/api/v1/links',
            '/api/v1/links/'.$links[0]->id,
            '/api/v1/qr',
            '/api/v1/domains',
            '/api/v1/analytics/overview',
            '/api/v1/health',
        ];
        $statuses = [200, 200, 200, 201, 204, 400, 401, 404, 422, 429, 500];

        $apiLogCount = 120;
        for ($i = 0; $i < $apiLogCount; $i++) {
            ApiRequestLog::query()->create([
                'organization_id' => $organization->id,
                'api_key_id' => fake()->randomElement([$primaryKey->id, $stagingKey->id]),
                'method' => fake()->randomElement($methods),
                'path' => fake()->randomElement($paths),
                'status' => fake()->randomElement($statuses),
                'latency_ms' => fake()->numberBetween(12, 420),
                'created_at' => now()->subHours(fake()->numberBetween(1, 240)),
            ]);
        }

        $auditActions = [
            AuditAction::OrganizationCreated,
            AuditAction::MemberInvited,
            AuditAction::MemberJoined,
            AuditAction::LinkCreated,
            AuditAction::LinkUpdated,
            AuditAction::QrGenerated,
            AuditAction::DomainAdded,
            AuditAction::DomainVerified,
            AuditAction::ApiKeyCreated,
            AuditAction::ApiKeyRevoked,
            AuditAction::BillingUpgraded,
            AuditAction::ExportCompleted,
            AuditAction::ProfileUpdated,
        ];

        foreach ($auditActions as $offset => $action) {
            AuditLog::query()->create([
                'organization_id' => $organization->id,
                'actor_user_id' => fake()->randomElement([$owner->id, $admin->id]),
                'action' => $action->value,
                'resource_type' => 'demo',
                'resource_id' => (string) Str::uuid(),
                'ip_address' => fake()->ipv4(),
                'user_agent' => 'DemoSeeder/1.0',
                'metadata' => ['seeded' => true],
                'created_at' => now()->subDays($offset)->subMinutes(fake()->numberBetween(0, 200)),
            ]);
        }

        foreach ($links as $link) {
            AuditLog::query()->create([
                'organization_id' => $organization->id,
                'actor_user_id' => $owner->id,
                'action' => AuditAction::LinkCreated->value,
                'resource_type' => 'link',
                'resource_id' => $link->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DemoSeeder/1.0',
                'metadata' => ['code' => $link->code],
                'created_at' => $link->created_at ?? now()->subDays(fake()->numberBetween(1, 20)),
            ]);
        }

        for ($i = 0; $i < 40; $i++) {
            AuditLog::query()->create([
                'organization_id' => $organization->id,
                'actor_user_id' => fake()->randomElement([$owner->id, $admin->id, $member->id]),
                'action' => fake()->randomElement($auditActions)->value,
                'resource_type' => fake()->randomElement(['link', 'qr', 'domain', 'api_key', 'member']),
                'resource_id' => (string) Str::uuid(),
                'ip_address' => fake()->ipv4(),
                'user_agent' => 'DemoSeeder/1.0',
                'metadata' => ['seeded' => true, 'batch' => 'extra'],
                'created_at' => now()->subHours(fake()->numberBetween(1, 720)),
            ]);
        }

        $usage = app(UsageTracker::class)->counter($organization);
        $usage->forceFill([
            'links_created' => count($links),
            'qr_generated' => $qrGenerated,
            'api_calls' => $apiLogCount,
        ])->save();

        $platformHost = app(PlatformDomain::class)->resolve()->hostname;

        $this->command?->info('Console demo data ready.');
        $this->command?->table(
            ['Field', 'Value'],
            [
                ['Login email', self::DEMO_EMAIL],
                ['Password', self::DEMO_PASSWORD],
                ['Organization', $organization->name.' ('.$organization->slug.')'],
                ['Plan', 'Pro'],
                ['Links', (string) count($links)],
                ['Members', (string) OrganizationMember::query()->where('organization_id', $organization->id)->count()],
                ['QR codes', (string) $qrGenerated],
                ['API logs', (string) $apiLogCount],
                ['Console URL', rtrim((string) config('app.url'), '/').'/console'],
                ['Short host', $platformHost],
            ],
        );
    }

    private function purgePreviousDemo(): void
    {
        $existing = Organization::query()
            ->withTrashed()
            ->where('slug', self::DEMO_ORG_SLUG)
            ->orWhere('is_demo', true)
            ->get();

        foreach ($existing as $organization) {
            DB::transaction(function () use ($organization): void {
                $orgId = $organization->id;

                LinkClick::query()->where('organization_id', $orgId)->delete();
                QrCode::query()->where('organization_id', $orgId)->delete();
                ApiRequestLog::query()->where('organization_id', $orgId)->delete();
                AuditLog::query()->where('organization_id', $orgId)->delete();
                OrganizationUsageMonth::query()->where('organization_id', $orgId)->delete();
                OrganizationInvitation::query()->where('organization_id', $orgId)->delete();

                $linkIds = Link::withTrashed()->where('organization_id', $orgId)->pluck('id');
                if ($linkIds->isNotEmpty()) {
                    LinkClick::query()->whereIn('link_id', $linkIds)->delete();
                    Link::withTrashed()->whereIn('id', $linkIds)->forceDelete();
                }

                $keyIds = ApiKey::query()->where('organization_id', $orgId)->pluck('id');
                if ($keyIds->isNotEmpty()) {
                    DB::table('api_key_scopes')->whereIn('api_key_id', $keyIds)->delete();
                    ApiKey::query()->whereIn('id', $keyIds)->delete();
                }

                $domainIds = Domain::withTrashed()->where('organization_id', $orgId)->pluck('id');
                if ($domainIds->isNotEmpty()) {
                    DomainDnsRecord::query()->whereIn('domain_id', $domainIds)->delete();
                    Domain::withTrashed()->whereIn('id', $domainIds)->forceDelete();
                }

                OrganizationMember::query()->where('organization_id', $orgId)->delete();
                OrganizationSubscription::query()->where('organization_id', $orgId)->delete();
                $organization->forceDelete();
            });
        }
    }
}
