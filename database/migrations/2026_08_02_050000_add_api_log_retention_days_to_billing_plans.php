<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_plans', function (Blueprint $table): void {
            $table->unsignedInteger('api_log_retention_days')->default(7)->after('click_retention_days');
        });

        DB::table('billing_plans')->where('slug', 'free')->update(['api_log_retention_days' => 7]);
        DB::table('billing_plans')->where('slug', 'pro')->update(['api_log_retention_days' => 90]);
    }

    public function down(): void
    {
        Schema::table('billing_plans', function (Blueprint $table): void {
            $table->dropColumn('api_log_retention_days');
        });
    }
};
