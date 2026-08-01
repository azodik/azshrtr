<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payment_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 64)->index();
            $table->string('provider', 32)->default('dodo')->index();
            $table->string('provider_event_id')->nullable()->unique();
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('provider_checkout_session_id')->nullable()->index();
            $table->string('provider_subscription_id')->nullable()->index();
            $table->string('provider_customer_id')->nullable()->index();
            $table->string('status', 64)->nullable();
            $table->string('currency', 3)->nullable();
            $table->integer('amount_cents')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamp('emailed_at')->nullable();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'kind', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payment_events');
    }
};
