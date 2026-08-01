<?php

namespace App\Services\Cloudflare;

use App\Models\Domain;
use App\Models\DomainDnsRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CustomDomainCloudflareService
{
    public function __construct(
        private readonly CloudflareCustomHostnameClient $client,
    ) {}

    public function enabled(): bool
    {
        return $this->client->enabled();
    }

    public function cnameTarget(): string
    {
        return (string) config('azshrtr.domains.cname_target', 'customers.azshrtr.com');
    }

    public function provision(Domain $domain): Domain
    {
        try {
            $created = $this->client->create($domain->hostname);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'hostname' => [$exception->getMessage()],
            ]);
        }

        $this->applyCloudflareState($domain, $created);

        return $domain->fresh(['dnsRecords']) ?? $domain;
    }

    public function refreshAndVerify(Domain $domain): Domain
    {
        $hostnameId = $domain->cloudflare_hostname_id;
        if (! is_string($hostnameId) || $hostnameId === '') {
            throw ValidationException::withMessages([
                'hostname' => ['This domain is not linked to Cloudflare. Remove it and add it again.'],
            ]);
        }

        try {
            $remote = $this->client->get($hostnameId);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'hostname' => [$exception->getMessage()],
            ]);
        }

        $ready = $remote['status'] === 'active'
            && ($remote['ssl_status'] === null || $remote['ssl_status'] === 'active');

        $this->applyCloudflareState($domain, $remote, $ready);

        $domain = $domain->fresh(['dnsRecords']) ?? $domain;

        if (! $ready) {
            throw ValidationException::withMessages([
                'hostname' => [
                    'Cloudflare status: '.$remote['status']
                    .'. Publish DNS records (ownership TXT, SSL TXT, CNAME to '.$this->cnameTarget().'), then verify again.',
                ],
            ]);
        }

        return $domain;
    }

    public function deleteRemote(Domain $domain): void
    {
        $hostnameId = $domain->cloudflare_hostname_id;
        if (! is_string($hostnameId) || $hostnameId === '') {
            return;
        }

        try {
            $this->client->delete($hostnameId);
        } catch (RuntimeException) {
        }
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function applyCloudflareState(Domain $domain, array $remote, bool $verified = false): void
    {
        DB::transaction(function () use ($domain, $remote, $verified): void {
            $domain->forceFill([
                'cloudflare_hostname_id' => $remote['id'],
                'cloudflare_status' => $remote['status'],
                'cloudflare_ssl_status' => $remote['ssl_status'],
                'verification_token' => $remote['ownership_verification']['value'] ?? $domain->verification_token,
                'status' => $verified ? 'verified' : 'pending',
                'verified_at' => $verified ? now() : null,
            ])->save();

            DomainDnsRecord::query()->where('domain_id', $domain->id)->delete();

            DomainDnsRecord::query()->create([
                'domain_id' => $domain->id,
                'purpose' => 'cname',
                'type' => 'CNAME',
                'name' => $domain->hostname,
                'value' => $this->cnameTarget(),
            ]);

            if (($remote['ownership_verification'] ?? null) !== null) {
                DomainDnsRecord::query()->create([
                    'domain_id' => $domain->id,
                    'purpose' => 'ownership',
                    'type' => (string) $remote['ownership_verification']['type'],
                    'name' => (string) $remote['ownership_verification']['name'],
                    'value' => (string) $remote['ownership_verification']['value'],
                ]);
            }

            foreach ($remote['ssl_validation_records'] ?? [] as $index => $sslRecord) {
                DomainDnsRecord::query()->create([
                    'domain_id' => $domain->id,
                    'purpose' => 'ssl',
                    'type' => (string) $sslRecord['type'],
                    'name' => (string) $sslRecord['name'].($index > 0 ? '#'.$index : ''),
                    'value' => (string) $sslRecord['value'],
                ]);
            }
        });
    }
}
