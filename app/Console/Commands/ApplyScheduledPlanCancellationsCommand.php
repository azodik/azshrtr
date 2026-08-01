<?php

namespace App\Console\Commands;

use App\Services\Billing\BillingService;
use Illuminate\Console\Command;

class ApplyScheduledPlanCancellationsCommand extends Command
{
    protected $signature = 'billing:apply-cancellations';

    protected $description = 'Downgrade subscriptions whose cancel_at has passed';

    public function handle(BillingService $billing): int
    {
        $count = $billing->applyScheduledCancellations();
        $this->components->info("Applied {$count} scheduled cancellation(s).");

        return self::SUCCESS;
    }
}
