<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('links:purge-expired')->everyMinute();

if (filter_var(env('AZSHRTR_CRON_QUEUE', true), FILTER_VALIDATE_BOOLEAN)) {
    Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute();
}

Schedule::command('billing:apply-cancellations')->hourly();
Schedule::command('domains:sync-cloudflare')->hourly();
Schedule::command('api-logs:aggregate')->hourly();
Schedule::command('audit:prune')->daily();
Schedule::command('api-logs:prune')->daily();
Schedule::command('clicks:prune')->daily();
