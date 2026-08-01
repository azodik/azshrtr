<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LaunchCheckCommand extends Command
{
    protected $signature = 'azshrtr:launch-check';

    protected $description = 'Preflight checks for PHP extensions, DB, schedule, billing env';

    public function handle(): int
    {
        $ok = true;

        foreach (['pdo', 'mbstring', 'openssl', 'tokenizer', 'json', 'ctype'] as $ext) {
            $present = extension_loaded($ext);
            $this->components->twoColumnDetail($ext, $present ? 'ok' : 'MISSING');
            $ok = $ok && $present;
        }

        try {
            DB::connection()->getPdo();
            $this->components->twoColumnDetail('database', 'ok');
            $this->components->twoColumnDetail('links table', Schema::hasTable('links') ? 'ok' : 'MISSING');
        } catch (\Throwable $e) {
            $this->components->twoColumnDetail('database', 'FAIL: '.$e->getMessage());
            $ok = false;
        }

        $this->components->twoColumnDetail(
            'billing',
            config('billing.enabled') ? (filled(config('billing.dodo.api_key')) ? 'enabled+keyed' : 'enabled MISSING KEY') : 'disabled (self-host)',
        );

        $this->components->twoColumnDetail('cron_queue', config('azshrtr.cron_queue') ? 'true' : 'false');
        $this->components->twoColumnDetail(
            'schedule',
            'ensure cron → scripts/run-scheduler.sh (or `* * * * * php artisan schedule:run`)',
        );

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
