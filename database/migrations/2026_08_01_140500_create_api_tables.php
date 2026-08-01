<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('prefix', 16);
            $table->string('key_hash', 64)->unique();
            $table->string('last_four', 4);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'revoked_at']);
        });

        Schema::create('api_key_scopes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('api_key_id')->constrained('api_keys')->cascadeOnDelete();
            $table->string('scope', 64);
            $table->timestamps();
            $table->unique(['api_key_id', 'scope']);
        });

        Schema::create('api_request_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('method', 16);
            $table->string('path');
            $table->unsignedSmallInteger('status');
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['organization_id', 'created_at']);
            $table->index(['api_key_id', 'created_at']);
            $table->index(['organization_id', 'status', 'created_at']);
            $table->index(['organization_id', 'method', 'created_at']);
        });

        Schema::create('api_request_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('period', 16);
            $table->timestamp('period_start');
            $table->unsignedInteger('request_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'api_key_id', 'period', 'period_start'], 'api_agg_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_aggregates');
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('api_key_scopes');
        Schema::dropIfExists('api_keys');
    }
};
