<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('customer_amc_tagging_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amc_service_number')->nullable()->after('customer_amc_tagging_id');
            $table->unique(['customer_amc_tagging_id', 'amc_service_number'], 'service_requests_amc_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropUnique('service_requests_amc_slot_unique');
            $table->dropConstrainedForeignId('customer_amc_tagging_id');
            $table->dropColumn('amc_service_number');
        });
    }
};
