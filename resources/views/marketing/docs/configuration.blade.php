@extends('layouts.docs')

@section('title', 'Configuration — azshrtr docs')
@section('meta_description', 'Configure Azshrtr: .env keys for MariaDB, Redis, billing, custom domains, and shared hosting.')

@section('docs')
    <p>
        Copy <code class="rounded bg-fog px-1.5 py-0.5 text-sm">.env.example</code> to
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">.env</code>, then run
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">php artisan azshrtr:setup</code>.
        Values below are the ones that matter for day-to-day operation.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Application</h2>
    <div class="overflow-x-auto rounded-md border border-mist/60">
        <table class="w-full text-left text-sm">
            <thead class="bg-fog/60 text-ink">
                <tr>
                    <th class="px-3 py-2 font-semibold">Variable</th>
                    <th class="px-3 py-2 font-semibold">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist/50">
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">APP_URL</td>
                    <td class="px-3 py-2">Public URL you open in the browser (e.g. <code class="text-xs">https://azshrtr.test</code> or <code class="text-xs">https://azshrtr.com</code>). Short links and claim URLs are built from this.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">APP_FORCE_URL</td>
                    <td class="px-3 py-2">Set <code class="text-xs">true</code> in Docker Compose when the published host port differs from the container port.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">APP_DEBUG</td>
                    <td class="px-3 py-2">Must be <code class="text-xs">false</code> in production.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">SANCTUM_STATEFUL_DOMAINS</td>
                    <td class="px-3 py-2">Hosts allowed for cookie/session console auth (comma-separated). Include your Herd/production host without scheme.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Database</h2>
    <p>
        <strong class="font-medium text-ink">MariaDB</strong> is the documented primary.
        SQLite is fine for local demos and PHPUnit only.
    </p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=azshrtr
DB_USERNAME=root
DB_PASSWORD=</code></pre>
    <p>
        Docker Compose maps MariaDB to host port <code class="rounded bg-fog px-1.5 py-0.5 text-sm">3307</code>
        with user/password/database <code class="rounded bg-fog px-1.5 py-0.5 text-sm">azshrtr</code>.
    </p>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Cache, queue, session (Redis optional)</h2>
    <p>
        Defaults work on shared hosting <strong class="font-medium text-ink">without Redis</strong>:
    </p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database</code></pre>
    <p>
        On VPS/Docker you may switch any of these to <code class="rounded bg-fog px-1.5 py-0.5 text-sm">redis</code>
        and set <code class="rounded bg-fog px-1.5 py-0.5 text-sm">REDIS_HOST</code> / <code class="rounded bg-fog px-1.5 py-0.5 text-sm">REDIS_PORT</code>.
        Never require Redis for a minimum install.
    </p>
    <p>
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_CRON_QUEUE=true</code> (default) drains the database queue from the
        1-minute scheduler via <code class="rounded bg-fog px-1.5 py-0.5 text-sm">queue:work --stop-when-empty --max-time=50</code>.
        Set it to <code class="rounded bg-fog px-1.5 py-0.5 text-sm">false</code> if you run a dedicated worker (Supervisor).
    </p>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Product</h2>
    <div class="overflow-x-auto rounded-md border border-mist/60">
        <table class="w-full text-left text-sm">
            <thead class="bg-fog/60 text-ink">
                <tr>
                    <th class="px-3 py-2 font-semibold">Variable</th>
                    <th class="px-3 py-2 font-semibold">Default</th>
                    <th class="px-3 py-2 font-semibold">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist/50">
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">AZSHRTR_DOMAIN_ROOT</td>
                    <td class="px-3 py-2 font-mono text-xs">azshrtr.com</td>
                    <td class="px-3 py-2">Hostname treated as the primary short-link host (local: <code class="text-xs">azshrtr.test</code>).</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">AZSHRTR_GUEST_LINK_TTL_MINUTES</td>
                    <td class="px-3 py-2 font-mono text-xs">30</td>
                    <td class="px-3 py-2">Anonymous homepage links expire unless claimed. Purge runs every minute.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">AZSHRTR_USAGE_TIMEZONE</td>
                    <td class="px-3 py-2 font-mono text-xs">UTC</td>
                    <td class="px-3 py-2">Calendar month for Free plan link/QR counters.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">AZSHRTR_CUSTOM_DOMAIN_CNAME_TARGET</td>
                    <td class="px-3 py-2 font-mono text-xs">customers.azshrtr.com</td>
                    <td class="px-3 py-2">CNAME target shown to Pro users for custom short domains.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">AZSHRTR_DOMAIN_DNS_VERIFY</td>
                    <td class="px-3 py-2 font-mono text-xs">true</td>
                    <td class="px-3 py-2">When Cloudflare SaaS is off, Verify checks live TXT records.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Billing (Dodo Payments)</h2>
    <p>
        Self-host / OSS: leave billing off — usage is unlimited locally.
    </p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>AZSHRTR_BILLING_ENABLED=false
AZSHRTR_BILLING_CURRENCY=USD</code></pre>
    <p>
        Azshrtr Cloud: enable billing and configure Dodo. Sync the yearly Pro product with:
    </p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>php artisan setup:dodo --webhook=https://azshrtr.com/api/v1/webhooks/dodo</code></pre>
    <div class="overflow-x-auto rounded-md border border-mist/60">
        <table class="w-full text-left text-sm">
            <thead class="bg-fog/60 text-ink">
                <tr>
                    <th class="px-3 py-2 font-semibold">Variable</th>
                    <th class="px-3 py-2 font-semibold">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist/50">
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">DODO_PAYMENTS_API_KEY</td>
                    <td class="px-3 py-2">Required when billing is enabled.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">DODO_PAYMENTS_WEBHOOK_SECRET</td>
                    <td class="px-3 py-2">Webhook signature verification for <code class="text-xs">POST /api/v1/webhooks/dodo</code>.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">DODO_PAYMENTS_ENVIRONMENT</td>
                    <td class="px-3 py-2"><code class="text-xs">test_mode</code> or live.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">DODO_PAYMENTS_BASE_URL</td>
                    <td class="px-3 py-2">Default test: <code class="text-xs">https://test.dodopayments.com</code>.</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">DODO_PRODUCT_PRO</td>
                    <td class="px-3 py-2">Yearly Pro product id (written by <code class="text-xs">setup:dodo</code>).</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">DODO_PAYMENTS_RETURN_URL</td>
                    <td class="px-3 py-2">Supports <code class="text-xs">{organization_id}</code> placeholder.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <p>See <a href="{{ route('docs.show', ['page' => 'billing']) }}" class="mkt-link">Billing</a> for plan limits and checkout behaviour.</p>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Custom domains (Cloudflare for SaaS)</h2>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED=false
# CLOUDFLARE_API_TOKEN=   # Zone → SSL and Certificates → Edit (Custom Hostnames)
# CLOUDFLARE_ZONE_ID=
# CLOUDFLARE_CUSTOM_HOSTNAME_SSL_METHOD=txt</code></pre>
    <p>
        When enabled, adding a domain creates a Cloudflare Custom Hostname and stores CNAME / ownership / SSL TXT instructions.
        When disabled, Verify uses DNS TXT via <code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_DOMAIN_DNS_VERIFY</code>.
        Details: <a href="{{ route('docs.show', ['page' => 'custom-domains']) }}" class="mkt-link">Custom domains</a>.
    </p>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Console social login</h2>
    <p>Leave empty to disable. Callbacks:</p>
    <ul class="list-disc space-y-1 pl-5">
        <li>Google → <code class="rounded bg-fog px-1.5 py-0.5 text-sm">{APP_URL}/console/auth/google/callback</code></li>
        <li>GitHub → <code class="rounded bg-fog px-1.5 py-0.5 text-sm">{APP_URL}/console/auth/github/callback</code></li>
    </ul>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>CONSOLE_GOOGLE_CLIENT_ID=
CONSOLE_GOOGLE_CLIENT_SECRET=
CONSOLE_GITHUB_CLIENT_ID=
CONSOLE_GITHUB_CLIENT_SECRET=</code></pre>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Mail &amp; marketing</h2>
    <p>
        Default mailer is <code class="rounded bg-fog px-1.5 py-0.5 text-sm">log</code>. Configure SMTP (or your provider) for password resets and usage alerts in production.
        Optional SEO / analytics IDs:
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">GOOGLE_SITE_VERIFICATION</code>,
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">GOOGLE_TAG_MANAGER_ID</code>,
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">GOOGLE_ANALYTICS_ID</code>.
        Scripts load only when set.
    </p>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Scheduler</h2>
    <p>Required cron (minimum every minute):</p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>* * * * * cd /path/to/azshrtr && php artisan schedule:run >> /dev/null 2>&1</code></pre>
    <ul class="list-disc space-y-1 pl-5">
        <li><code class="text-sm">links:purge-expired</code> — every minute</li>
        <li><code class="text-sm">queue:work --stop-when-empty</code> — every minute when <code class="text-sm">AZSHRTR_CRON_QUEUE=true</code></li>
        <li><code class="text-sm">billing:apply-cancellations</code> — hourly</li>
        <li><code class="text-sm">domains:sync-cloudflare</code> — hourly</li>
        <li><code class="text-sm">api-logs:aggregate</code> — hourly (rolls raw hits &gt;24h into hourly aggregates)</li>
        <li><code class="text-sm">audit:prune</code> / <code class="text-sm">api-logs:prune</code> / <code class="text-sm">clicks:prune</code> — daily, batched, plan retention (Free: audit/API 7d, clicks 30d; Pro: audit/API 90d, clicks 365d)</li>
    </ul>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Preflight</h2>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>php artisan azshrtr:launch-check</code></pre>
    <p>
        Checks PHP extensions, database connectivity, billing env, and reminds you about cron.
        Full reference remains in the repo <code class="rounded bg-fog px-1.5 py-0.5 text-sm">.env.example</code>.
    </p>
@endsection
