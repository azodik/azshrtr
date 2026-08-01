<?php

namespace App\Services\Links;

use App\Enums\AuditAction;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Domains\PlatformDomain;
use App\Services\Usage\UsageTracker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LinkService
{
    public function __construct(
        private readonly ShortCodeGenerator $codes,
        private readonly DestinationUrlValidator $urls,
        private readonly UsageTracker $usage,
        private readonly AuditLogger $audit,
        private readonly PlatformDomain $platformDomain,
    ) {}

    public function createAnonymous(string $destinationUrl, ?string $ip = null): Link
    {
        $destinationUrl = $this->urls->validate($destinationUrl);
        $ttl = (int) config('azshrtr.guest_link_ttl_minutes', 30);
        $domain = $this->platformDomain->resolve();
        $code = $this->codes->generate($domain->id);

        $link = Link::query()->create([
            'organization_id' => null,
            'user_id' => null,
            'domain_id' => $domain->id,
            'code' => $code,
            'destination_url' => $destinationUrl,
            'expires_at' => now()->addMinutes($ttl),
            'claim_token' => Str::random(48),
            'claim_token_expires_at' => now()->addMinutes($ttl),
            'is_anonymous' => true,
        ]);

        $this->audit->log(
            AuditAction::LinkCreated,
            resourceType: 'link',
            resourceId: $link->id,
            metadata: ['anonymous' => true, 'created_ip' => $ip],
        );

        return $link;
    }

    /**
     * @param  array{destination_url: string, title?: string|null, expires_at?: string|null, password?: string|null, domain_id?: string|null}  $data
     */
    public function createOwned(Organization $organization, User $user, array $data): Link
    {
        $this->usage->assertCanCreateLink($organization);

        $destinationUrl = $this->urls->validate($data['destination_url']);
        $domain = $this->platformDomain->resolve();

        if (! empty($data['domain_id'])) {
            $domain = Domain::query()
                ->where('organization_id', $organization->id)
                ->whereKey($data['domain_id'])
                ->firstOrFail();

            if (! $domain->isVerified()) {
                throw ValidationException::withMessages([
                    'domain_id' => ['Domain must be verified before use.'],
                ]);
            }
        }

        $password = $data['password'] ?? null;
        if (filled($password)) {
            $this->usage->assertCanUsePasswordLinks($organization);
        }

        $link = Link::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'code' => $this->codes->generate($domain->id),
            'destination_url' => $destinationUrl,
            'title' => $data['title'] ?? null,
            'password_hash' => filled($password) ? Hash::make((string) $password) : null,
            'expires_at' => ! empty($data['expires_at']) ? $data['expires_at'] : null,
            'is_anonymous' => false,
        ]);

        $this->usage->incrementLinksCreated($organization);
        $this->audit->log(
            AuditAction::LinkCreated,
            $user,
            $organization,
            'link',
            $link->id,
        );

        return $link;
    }

    /**
     * Create a link during bulk import: no per-row audit/usage (caller batches usage).
     *
     * @param  array{destination_url: string, title?: string|null}  $data
     */
    public function createOwnedForImport(
        Organization $organization,
        User $user,
        array $data,
        ?Domain $domain = null,
    ): Link {
        $this->usage->assertCanCreateLink($organization);

        $destinationUrl = $this->urls->validate($data['destination_url']);
        $domain ??= $this->platformDomain->resolve();

        return Link::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'code' => $this->codes->generate($domain->id),
            'destination_url' => $destinationUrl,
            'title' => $data['title'] ?? null,
            'is_anonymous' => false,
        ]);
    }

    /**
     * @param  array{destination_url?: string, title?: string|null, expires_at?: string|null, password?: string|null, is_disabled?: bool}  $data
     */
    public function update(Link $link, Organization $organization, User $user, array $data): Link
    {
        if (! empty($data['destination_url'])) {
            $link->destination_url = $this->urls->validate($data['destination_url']);
        }

        if (array_key_exists('title', $data)) {
            $link->title = $data['title'];
        }

        if (array_key_exists('expires_at', $data)) {
            $link->expires_at = $data['expires_at'];
        }

        if (array_key_exists('is_disabled', $data)) {
            $link->is_disabled = (bool) $data['is_disabled'];
        }

        if (array_key_exists('password', $data)) {
            $password = $data['password'];
            if (filled($password)) {
                $this->usage->assertCanUsePasswordLinks($organization);
                $link->password_hash = Hash::make((string) $password);
                $this->audit->log(AuditAction::LinkPasswordSet, $user, $organization, 'link', $link->id);
            } elseif ($password === null || $password === '') {
                $link->password_hash = null;
            }
        }

        $link->save();

        $this->audit->log(AuditAction::LinkUpdated, $user, $organization, 'link', $link->id);

        return $link->refresh();
    }

    public function delete(Link $link, Organization $organization, User $user): void
    {
        $link->delete();
        $this->audit->log(AuditAction::LinkDeleted, $user, $organization, 'link', $link->id);
    }
}
