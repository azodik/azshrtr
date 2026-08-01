<?php

namespace Tests;

use Database\Seeders\BillingPlanSeeder;
use Database\Seeders\PlatformDomainSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedCore(): void
    {
        $this->seed([
            BillingPlanSeeder::class,
            PlatformDomainSeeder::class,
        ]);
    }
}
