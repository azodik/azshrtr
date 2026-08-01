<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('links_per_month')->nullable();
            $table->unsignedInteger('qr_per_month')->nullable();
            $table->unsignedInteger('api_keys_limit')->default(2);
            $table->unsignedInteger('audit_retention_days')->default(7);
            $table->unsignedInteger('click_retention_days')->default(30);
            $table->unsignedInteger('price_cents_yearly')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('dodo_product_id')->nullable()->index();
            $table->boolean('is_public')->default(true);
            $table->boolean('allows_custom_domains')->default(false);
            $table->boolean('allows_password_links')->default(false);
            $table->json('features')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('billing_customers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('dodo');
            $table->string('provider_customer_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('organization_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('billing_plan_id')->constrained('billing_plans')->restrictOnDelete();
            $table->string('status')->index();
            $table->string('provider_subscription_id')->nullable()->unique();
            $table->string('provider_checkout_session_id')->nullable()->index();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('cancel_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['billing_plan_id', 'status']);
            $table->index(['status', 'current_period_end']);
        });

        Schema::create('dodo_webhooks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_id')->nullable()->unique();
            $table->string('event_type')->nullable()->index();
            $table->json('payload');
            $table->string('status', 32)->default('received');
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dodo_webhooks');
        Schema::dropIfExists('organization_subscriptions');
        Schema::dropIfExists('billing_customers');
        Schema::dropIfExists('billing_plans');
    }
};
