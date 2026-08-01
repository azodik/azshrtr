<?php

namespace Database\Seeders;

use App\Models\BillingPlan;
use Illuminate\Database\Seeder;

class BillingPlanSeeder extends Seeder
{
    public function run(): void
    {
        BillingPlan::query()->updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'description' => '3,000 links and 300 QR codes per month',
                'links_per_month' => 3000,
                'qr_per_month' => 300,
                'api_keys_limit' => 2,
                'audit_retention_days' => 7,
                'click_retention_days' => 30,
                'api_log_retention_days' => 7,
                'price_cents_yearly' => 0,
                'currency' => 'USD',
                'is_public' => true,
                'allows_custom_domains' => false,
                'allows_password_links' => false,
                'sort_order' => 1,
            ],
        );

        BillingPlan::query()->updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'description' => 'Unlimited links & QR, custom domains, password links — $20/year',
                'links_per_month' => null,
                'qr_per_month' => null,
                'api_keys_limit' => 20,
                'audit_retention_days' => 90,
                'click_retention_days' => 365,
                'api_log_retention_days' => 90,
                'price_cents_yearly' => 2000,
                'currency' => 'USD',
                'is_public' => true,
                'allows_custom_domains' => true,
                'allows_password_links' => true,
                'sort_order' => 2,
                'dodo_product_id' => env('DODO_PRODUCT_PRO'),
            ],
        );
    }
}
