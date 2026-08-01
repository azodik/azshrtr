@extends('layouts.docs')

@section('title', 'Install — azshrtr docs')
@section('meta_description', 'Install Azshrtr with Laravel Herd, bare PHP, or Docker Compose. MariaDB, cron, and first-run setup.')

@section('docs')
    <p>
        Three ways to run Azshrtr: <strong class="font-medium text-ink">Laravel Herd</strong> (macOS),
        plain PHP + web server, or <strong class="font-medium text-ink">Docker Compose</strong>.
        Redis is optional — defaults use the database for cache, queue, and session.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Requirements</h2>
    <ul class="list-disc space-y-1 pl-5">
        <li>PHP 8.5+ with extensions: <code class="rounded bg-fog px-1.5 py-0.5 text-sm">pdo_mysql</code> / MariaDB, <code class="rounded bg-fog px-1.5 py-0.5 text-sm">mbstring</code>, <code class="rounded bg-fog px-1.5 py-0.5 text-sm">openssl</code>, <code class="rounded bg-fog px-1.5 py-0.5 text-sm">tokenizer</code>, <code class="rounded bg-fog px-1.5 py-0.5 text-sm">json</code>, <code class="rounded bg-fog px-1.5 py-0.5 text-sm">ctype</code>, <code class="rounded bg-fog px-1.5 py-0.5 text-sm">bcmath</code></li>
        <li>Composer 2</li>
        <li>Node.js 24+</li>
        <li>MariaDB 10.11+ (or MySQL-compatible). SQLite is OK for local demo / PHPUnit only.</li>
        <li>Redis — optional (VPS / Docker)</li>
    </ul>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Laravel Herd (macOS)</h2>
    <ol class="list-decimal space-y-2 pl-5">
        <li>Clone the repo and link a secured site:</li>
    </ol>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>git clone https://github.com/azodik/azshrtr.git
cd azshrtr
herd link azshrtr --secure --isolate=8.5</code></pre>
    <ol class="list-decimal space-y-2 pl-5" start="2">
        <li>Create a MariaDB database (Herd → Database, or CLI), e.g. database/user <code class="rounded bg-fog px-1.5 py-0.5 text-sm">azshrtr</code>.</li>
        <li>Install and set up:</li>
    </ol>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>composer install
cp .env.example .env
# Edit .env — at minimum:
#   APP_URL=https://azshrtr.test
#   DB_CONNECTION=mariadb
#   DB_DATABASE=azshrtr
#   DB_USERNAME=...
#   DB_PASSWORD=...
#   SANCTUM_STATEFUL_DOMAINS=azshrtr.test,localhost,127.0.0.1
#   AZSHRTR_DOMAIN_ROOT=azshrtr.test
#   SESSION_SECURE_COOKIE=true
php artisan azshrtr:setup
npm install
npm run build
# Dev assets (optional): npm run dev</code></pre>
    <p>
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">azshrtr:setup</code> ensures
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">.env</code> keys, generates
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">APP_KEY</code> if missing, runs migrations, and seeds Free/Pro billing plans.
    </p>
    <div class="overflow-x-auto rounded-md border border-mist/60">
        <table class="w-full text-left text-sm">
            <thead class="bg-fog/60 text-ink">
                <tr>
                    <th class="px-3 py-2 font-semibold">Surface</th>
                    <th class="px-3 py-2 font-semibold">URL</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist/50">
                <tr>
                    <td class="px-3 py-2">Marketing / hero shortener</td>
                    <td class="px-3 py-2 font-mono text-xs">https://azshrtr.com <span class="text-ink-soft">(local Herd: https://azshrtr.test)</span></td>
                </tr>
                <tr>
                    <td class="px-3 py-2">Docs</td>
                    <td class="px-3 py-2 font-mono text-xs">https://azshrtr.com/docs</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">Console</td>
                    <td class="px-3 py-2 font-mono text-xs">https://azshrtr.com/console</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">Register</td>
                    <td class="px-3 py-2 font-mono text-xs">https://azshrtr.com/console/register</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Without Docker (PHP + web server)</h2>
    <p>
        Same Composer / setup / npm steps. Point Nginx or Apache
        <strong class="font-medium text-ink">document root</strong> at
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">public/</code>.
        Prefer HTTPS so secure session cookies work with the console.
    </p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>composer install
cp .env.example .env
# Set APP_URL, DB_*, SANCTUM_STATEFUL_DOMAINS, AZSHRTR_DOMAIN_ROOT
php artisan azshrtr:setup
npm install && npm run build

# Quick local check without a vhost:
php artisan serve
# → http://127.0.0.1:8000  (set APP_URL to match; SESSION_SECURE_COOKIE=false if not on HTTPS)</code></pre>
    <p>
        Production deploy helper (migrate + asset build + caches):
    </p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>./deploy.sh</code></pre>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Docker Compose</h2>
    <p>
        Builds the app image (PHP-FPM + Nginx), MariaDB 11.4, and Redis.
        Host ports: app <code class="rounded bg-fog px-1.5 py-0.5 text-sm">8080</code>,
        MariaDB <code class="rounded bg-fog px-1.5 py-0.5 text-sm">3307</code>,
        Redis <code class="rounded bg-fog px-1.5 py-0.5 text-sm">6381</code>.
    </p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>cp .env.example .env
php artisan key:generate   # writes APP_KEY into .env
# Export APP_KEY for Compose, or ensure it is in the environment:
#   export APP_KEY="$(grep '^APP_KEY=' .env | cut -d= -f2-)"
docker compose up --build</code></pre>
    <p>
        Open <a href="http://localhost:8080" class="mkt-link" rel="noopener">http://localhost:8080</a>.
        Compose sets <code class="rounded bg-fog px-1.5 py-0.5 text-sm">APP_FORCE_URL=true</code> and
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">APP_URL=http://localhost:8080</code>.
        The entrypoint runs migrations on start.
    </p>
    <div class="overflow-x-auto rounded-md border border-mist/60">
        <table class="w-full text-left text-sm">
            <thead class="bg-fog/60 text-ink">
                <tr>
                    <th class="px-3 py-2 font-semibold">Service</th>
                    <th class="px-3 py-2 font-semibold">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist/50">
                <tr>
                    <td class="px-3 py-2">App</td>
                    <td class="px-3 py-2 font-mono text-xs">http://localhost:8080</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">MariaDB</td>
                    <td class="px-3 py-2">Host <code class="text-xs">127.0.0.1:3307</code> · db/user/pass <code class="text-xs">azshrtr</code></td>
                </tr>
                <tr>
                    <td class="px-3 py-2">Redis</td>
                    <td class="px-3 py-2">Host <code class="text-xs">127.0.0.1:6381</code> (used inside Compose for cache/queue/session)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">First account</h2>
    <p>
        Open <code class="rounded bg-fog px-1.5 py-0.5 text-sm">/console/register</code>.
        Registration creates your user and a personal organization (owner) on the Free plan.
        Guest links from the homepage can be claimed into that org via the claim URL.
    </p>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Cron (required for expiry + queue)</h2>
    <p>
        Guest link purge and (on shared hosting) the queue drain need a
        <strong class="font-medium text-ink">1-minute</strong> cron.
        On some shared hosting, point the job at the helper script (shell operators are often unavailable):
    </p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>/bin/bash /path/to/azshrtr/scripts/run-scheduler.sh</code></pre>
    <p>On a normal shell crontab:</p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>* * * * * cd /path/to/azshrtr && php artisan schedule:run >> /dev/null 2>&1</code></pre>
    <p>
        Defaults: <code class="rounded bg-fog px-1.5 py-0.5 text-sm">CACHE_STORE=database</code>,
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">QUEUE_CONNECTION=database</code>,
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">SESSION_DRIVER=database</code>,
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_CRON_QUEUE=true</code>.
        More: <a href="{{ route('docs.show', ['page' => 'shared-hosting']) }}" class="mkt-link">Shared hosting</a>.
    </p>
    <p>Local Herd without system cron — run once in a terminal while developing:</p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>php artisan schedule:work</code></pre>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Permissions</h2>
    <p>Ensure the web / PHP user can write:</p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>chmod -R ug+rwx storage bootstrap/cache
# or: chown -R www-data:www-data storage bootstrap/cache</code></pre>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Before production</h2>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>php artisan azshrtr:launch-check
npm run build
# APP_DEBUG=false
# APP_ENV=production
# SESSION_SECURE_COOKIE=true
# Real MAIL_* for password resets</code></pre>
    <ul class="list-disc space-y-1 pl-5">
        <li>Env reference: <a href="{{ route('docs.show', ['page' => 'configuration']) }}" class="mkt-link">Configuration</a></li>
        <li>Cloud billing: <a href="{{ route('docs.show', ['page' => 'billing']) }}" class="mkt-link">Billing</a> · <code class="rounded bg-fog px-1.5 py-0.5 text-sm">php artisan setup:dodo</code></li>
        <li>Custom short domains: <a href="{{ route('docs.show', ['page' => 'custom-domains']) }}" class="mkt-link">Custom domains</a></li>
        <li>API keys: <a href="{{ route('docs.show', ['page' => 'api']) }}" class="mkt-link">API</a></li>
    </ul>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Verify the install</h2>
    <ol class="list-decimal space-y-2 pl-5">
        <li>Homepage loads; shorten <code class="rounded bg-fog px-1.5 py-0.5 text-sm">https://azshrtr.com</code> and confirm a short URL + countdown.</li>
        <li>Open the short URL — you should redirect to the destination.</li>
        <li>Register in the console; claim the guest link or create an owned link.</li>
        <li><code class="rounded bg-fog px-1.5 py-0.5 text-sm">GET /api/v1/health</code> returns <code class="rounded bg-fog px-1.5 py-0.5 text-sm">ok</code> plus a MariaDB/database check.</li>
    </ol>
@endsection
