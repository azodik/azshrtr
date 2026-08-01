<?php

namespace App\Services\Domains;

use App\Enums\AuditAction;
use App\Models\Domain;
use App\Models\DomainDnsRecord;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Cloudflare\CustomDomainCloudflareService;
use App\Services\DomainDnsVerifier;
use App\Services\Usage\UsageTracker;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DomainService
{
    public function __construct(
        private readonly CustomDomainCloudflareService $cloudflare,
        private readonly DomainDnsVerifier $dns,
        private readonly UsageTracker $usage,
        private readonly AuditLogger $audit,
    ) {}

    public function add(Organization $organization, User $user, string $hostname): Domain
    {
        $this->usage->assertCanAddDomain($organization);

        $hostname = strtolower(trim($hostname));
        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $hostname)) {
            throw ValidationException::withMessages([
                'hostname' => ['Enter a valid hostname (e.g. go.azshrtr.com).'],
            ]);
        }

        $token = Str::random(32);

        $domain = Domain::query()->create([
            'organization_id' => $organization->id,
            'hostname' => $hostname,
            'is_system' => false,
            'status' => 'pending',
            'verification_token' => $token,
        ]);

        DomainDnsRecord::query()->create([
            'domain_id' => $domain->id,
            'purpose' => 'cname',
            'type' => 'CNAME',
            'name' => $hostname,
            'value' => $this->cloudflare->cnameTarget(),
        ]);

        DomainDnsRecord::query()->create([
            'domain_id' => $domain->id,
            'purpose' => 'ownership',
            'type' => 'TXT',
            'name' => '_azshrtr-challenge.'.$hostname,
            'value' => $token,
        ]);

        if ($this->cloudflare->enabled()) {
            $domain = $this->cloudflare->provision($domain);
        }

        $this->audit->log(AuditAction::DomainAdded, $user, $organization, 'domain', $domain->id);

        return $domain->load('dnsRecords');
    }

    public function verify(Domain $domain, Organization $organization, User $user): Domain
    {
        if ($this->cloudflare->enabled() && filled($domain->cloudflare_hostname_id)) {
            $domain = $this->cloudflare->refreshAndVerify($domain);
        } else {
            $token = (string) $domain->verification_token;
            if (! $this->dns->tokenPresent($domain->hostname, $token)) {
                throw ValidationException::withMessages([
                    'hostname' => ['TXT verification record not found yet. Add the DNS record and try again.'],
                ]);
            }

            $domain->forceFill([
                'verified_at' => now(),
                'status' => 'verified',
            ])->save();
        }

        $this->audit->log(AuditAction::DomainVerified, $user, $organization, 'domain', $domain->id);

        return $domain->refresh()->load('dnsRecords');
    }

    public function delete(Domain $domain, Organization $organization, User $user): void
    {
        if ($domain->is_system) {
            throw ValidationException::withMessages([
                'hostname' => ['System domains cannot be deleted.'],
            ]);
        }

        if ($this->cloudflare->enabled()) {
            $this->cloudflare->deleteRemote($domain);
        }

        $domain->delete();
        $this->audit->log(AuditAction::DomainDeleted, $user, $organization, 'domain', $domain->id);
    }
}
