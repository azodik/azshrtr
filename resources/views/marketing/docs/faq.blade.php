@extends('layouts.docs')

@section('title', 'FAQ — azshrtr docs')
@section('meta_description', 'Frequently asked questions about Azshrtr roles, API isolation, self-hosting, guest links, Redis, and plans.')

@section('docs')
    <h2 class="font-display text-xl font-semibold text-ink">Can one organization’s API access another organization’s data?</h2>
    <p>
        No. Product API keys are bound to a single organization. Every product API call
        (<code class="rounded bg-fog px-1.5 py-0.5 text-sm">/api/v1/me</code>,
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">/api/v1/links</code>, …)
        uses the key’s organization — never a path parameter you can swap.
        Asking for another org’s link ID returns 404.
    </p>
    <p class="mt-2">
        Console (session) APIs require active membership in the
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">{organizationId}</code> from the URL.
        Users who are not members of that workspace also get 404 — not a peek at another team’s data.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">What can Owner, Admin, and Member do?</h2>
    <p class="mb-3">
        Roles apply inside one workspace. Product API keys ignore console roles; they only have the scopes you grant the key.
    </p>
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-left text-sm">
            <thead>
                <tr class="border-b border-mist/70 text-ink-soft">
                    <th class="py-2 pr-4 font-medium">Capability</th>
                    <th class="py-2 pr-4 font-medium">Owner</th>
                    <th class="py-2 pr-4 font-medium">Admin</th>
                    <th class="py-2 pr-4 font-medium">Member</th>
                </tr>
            </thead>
            <tbody class="align-top text-ink">
                <tr class="border-b border-mist/50">
                    <td class="py-2 pr-4">Links, QR codes, domains, analytics, import/export, audit &amp; API logs</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">Yes</td>
                </tr>
                <tr class="border-b border-mist/50">
                    <td class="py-2 pr-4">View team roster &amp; pending invites</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">Yes (no invite links)</td>
                </tr>
                <tr class="border-b border-mist/50">
                    <td class="py-2 pr-4">Invite, resend, revoke, change roles, remove members</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">No</td>
                </tr>
                <tr class="border-b border-mist/50">
                    <td class="py-2 pr-4">Create / revoke API keys</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">No (can view key metadata)</td>
                </tr>
                <tr class="border-b border-mist/50">
                    <td class="py-2 pr-4">View plan &amp; invoices</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">Yes</td>
                </tr>
                <tr class="border-b border-mist/50">
                    <td class="py-2 pr-4">Checkout, cancel, or resume Pro billing</td>
                    <td class="py-2 pr-4">Yes</td>
                    <td class="py-2 pr-4">No</td>
                    <td class="py-2 pr-4">No</td>
                </tr>
                <tr>
                    <td class="py-2 pr-4">Change or remove the Owner</td>
                    <td class="py-2 pr-4">Blocked</td>
                    <td class="py-2 pr-4">Blocked</td>
                    <td class="py-2 pr-4">N/A</td>
                </tr>
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-sm text-ink-soft">
        Admins can invite other admins. Only the Owner can manage billing. Invite accept tokens are emailed to the invitee and shown in the console only to Owner/Admin.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">How long are audit, API, and click logs kept?</h2>
    <p>
        Retention follows the workspace plan. Cron deletes old rows in batches so large tables stay healthy:
    </p>
    <ul class="mt-2 list-disc space-y-1 pl-5">
        <li><strong>Free</strong> — audit &amp; API logs 7 days; click analytics 30 days</li>
        <li><strong>Pro</strong> — audit &amp; API logs 90 days; click analytics 365 days</li>
    </ul>
    <p class="mt-2">
        Raw product API hits older than 24 hours are rolled into hourly aggregates first
        (<code class="rounded bg-fog px-1.5 py-0.5 text-sm">api-logs:aggregate</code>), then both raw rows and aggregates
        past plan retention are removed (<code class="rounded bg-fog px-1.5 py-0.5 text-sm">api-logs:prune</code>).
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Do I need Redis?</h2>
    <p>
        No. Defaults use the database for cache, queue, and session. Redis is optional for VPS/Docker performance.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">What happens to anonymous links?</h2>
    <p>
        Homepage shortens create a guest link with a claim token and a TTL (default 30 minutes).
        Claim it into an account to keep it; otherwise <code class="rounded bg-fog px-1.5 py-0.5 text-sm">links:purge-expired</code>
        hard-deletes it after expiry (runs every minute when cron is set up).
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Can guests pick a custom slug?</h2>
    <p>Not in v1 — codes are auto-generated. Owned links can use custom domains (Pro).</p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Is the API Free-tier allowed?</h2>
    <p>
        Yes. Free can create API keys (Owner/Admin) and call the API within the same monthly link/QR caps.
        Pro raises key limits and removes caps. Keys never reach another workspace’s data.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Shared hosting cron?</h2>
    <p>
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">* * * * * php artisan schedule:run</code>.
        Keep <code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_CRON_QUEUE=true</code> unless you have a long-running worker.
        See <a href="{{ route('docs.show', ['page' => 'shared-hosting']) }}" class="mkt-link">Shared hosting</a>.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Which database?</h2>
    <p>
        MariaDB 10.11+ (or MySQL-compatible). SQLite is for local/demo and CI only.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">How large can import / export be?</h2>
    <p>
        Imports accept up to 10,000 rows or 5MB of payload per request
        (<code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_IMPORT_MAX_ROWS</code> /
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_IMPORT_MAX_PAYLOAD_BYTES</code>).
        Exports stream to disk in chunks so large workspaces don’t exhaust PHP memory.
        Docker sets <code class="rounded bg-fog px-1.5 py-0.5 text-sm">post_max_size=12M</code>;
        oversized HTTP bodies return JSON 413 when Laravel sees them.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Where do I configure everything?</h2>
    <p>
        <a href="{{ route('docs.show', ['page' => 'configuration']) }}" class="mkt-link">Configuration</a>
        and the repo <code class="rounded bg-fog px-1.5 py-0.5 text-sm">.env.example</code>.
    </p>
@endsection
