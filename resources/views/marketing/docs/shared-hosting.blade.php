@extends('layouts.docs')

@section('title', 'Shared hosting — azshrtr docs')
@section('meta_description', 'Run Azshrtr on shared hosting with 1-minute cron and database queue.')

@section('docs')
    <p>Redis is optional. Defaults use database drivers for cache, queue, and session.</p>
    <p>
        On some shared hosting, the document root is <code class="text-sm">public_html/</code>.
        After each deploy, sync Laravel’s <code class="text-sm">public/</code> with
        <code class="text-sm">./scripts/sync-public-to-public-html.sh</code>
        (or run <code class="text-sm">./deploy.sh</code>, which does this automatically).
    </p>

    <h2 class="font-display pt-2 text-xl font-semibold text-ink">Deploy</h2>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm"><code>cd ~/domains/your-domain.com
./deploy.sh
# optional:
#   PHP_CLI=/opt/alt/php85/usr/bin/php ./deploy.sh
#   SKIP_PUBLIC_HTML_SYNC=1 ./deploy.sh
#   SKIP_SCHEDULER_CRON=1 ./deploy.sh     # print control-panel cron only</code></pre>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Cron (control panel)</h2>
    <p>
        Some shared-hosting control panels do <strong class="font-medium text-ink">not</strong> run cron jobs through a shell,
        so <code class="text-sm">cd</code>, <code class="text-sm">&amp;&amp;</code>, and redirects break.
        Use the shipped helper every minute:
    </p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm"><code>/bin/bash /home/USER/domains/your-domain.com/scripts/run-scheduler.sh</code></pre>
    <p>
        That script <code class="text-sm">cd</code>s into the app root, picks PHP via
        <code class="text-sm">PHP_CLI</code>, <code class="text-sm">storage/scheduler-php-cli</code>, or
        <code class="text-sm">PATH</code>, and appends output to
        <code class="text-sm">storage/logs/scheduler.log</code>.
    </p>
    <p>Optional — pin the PHP binary once (absolute path from the host):</p>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm"><code>echo /opt/alt/php85/usr/bin/php > storage/scheduler-php-cli</code></pre>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Cron (shell / VPS)</h2>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm"><code>* * * * * cd /path/to/azshrtr && php artisan schedule:run >> /dev/null 2>&1
# or:
* * * * * /bin/bash /path/to/azshrtr/scripts/run-scheduler.sh</code></pre>

    <p>Set <code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_CRON_QUEUE=true</code> so the scheduler drains the queue each minute with <code class="text-sm">queue:work --stop-when-empty --max-time=50</code>.</p>
    <p>Point the web root at <code class="text-sm">public/</code>. Run <code class="text-sm">php artisan azshrtr:launch-check</code> before go-live.</p>
@endsection
