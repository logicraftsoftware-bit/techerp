<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->date('purchase_date')->nullable()->after('machine_id');
            $table->date('amc_start_date')->nullable()->after('purchase_date');
            $table->date('amc_end_date')->nullable()->after('amc_start_date');
            $table->string('payment_collected_by')->nullable()->after('amc_end_date');
            $table->decimal('paid_amount', 14, 2)->nullable()->after('payment_collected_by');
            $table->string('payment_method')->nullable()->after('paid_amount');
            $table->text('payment_remarks')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['purchase_date', 'amc_start_date', 'amc_end_date', 'payment_collected_by', 'paid_amount', 'payment_method', 'payment_remarks']);
        });
    }
};
