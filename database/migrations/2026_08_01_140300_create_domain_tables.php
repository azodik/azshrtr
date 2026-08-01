<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('hostname')->unique();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->string('status', 32)->default('pending')->index();
            $table->string('cloudflare_hostname_id')->nullable();
            $table->string('cloudflare_status')->nullable();
            $table->string('cloudflare_ssl_status')->nullable();
            $table->string('verification_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'status']);
            $table->index(['is_system', 'hostname']);
        });

        Schema::create('domain_dns_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('purpose', 32);
            $table->string('type', 16);
            $table->string('name');
            $table->text('value');
            $table->timestamps();
            $table->unique(['domain_id', 'purpose', 'type', 'name'], 'domain_dns_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_dns_records');
        Schema::dropIfExists('domains');
    }
};
