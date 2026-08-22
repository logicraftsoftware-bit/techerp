<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_amc_taggings', function (Blueprint $table) {
            $table->unsignedInteger('service_count')->default(1)->after('amc_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_amc_taggings', function (Blueprint $table) {
            $table->dropColumn('service_count');
        });
    }
};
