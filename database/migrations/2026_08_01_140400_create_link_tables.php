<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('domain_id')->constrained('domains')->restrictOnDelete();
            $table->string('code', 64);
            $table->text('destination_url');
            $table->string('title')->nullable();
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('claim_token', 64)->nullable()->unique();
            $table->timestamp('claim_token_expires_at')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_disabled')->default(false);
            $table->unsignedBigInteger('click_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['domain_id', 'code']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['is_anonymous', 'expires_at']);
            $table->index('expires_at');
        });

        Schema::create('link_clicks', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('link_id')->constrained('links')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->timestamp('clicked_at')->useCurrent();
            $table->string('referrer', 512)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('device_bucket', 32)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('region', 64)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->index(['link_id', 'clicked_at']);
            $table->index(['organization_id', 'clicked_at']);
            $table->index(['organization_id', 'country', 'clicked_at']);
            $table->index(['organization_id', 'browser', 'clicked_at']);
        });

        Schema::create('qr_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('link_id')->nullable()->constrained()->nullOnDelete();
            $table->text('content');
            $table->unsignedSmallInteger('size')->default(256);
            $table->unsignedTinyInteger('margin')->default(1);
            $table->string('format', 8)->default('png');
            $table->json('options')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('link_clicks');
        Schema::dropIfExists('links');
    }
};
