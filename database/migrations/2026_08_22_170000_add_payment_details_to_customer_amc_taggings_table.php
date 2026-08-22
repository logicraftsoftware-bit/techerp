<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_amc_taggings', function (Blueprint $table) {
            $table->string('payment_collected_by')->nullable()->after('service_count');
            $table->decimal('paid_amount', 14, 2)->nullable()->after('payment_collected_by');
            $table->string('payment_method')->nullable()->after('paid_amount');
            $table->text('payment_remarks')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('customer_amc_taggings', function (Blueprint $table) {
            $table->dropColumn(['payment_collected_by', 'paid_amount', 'payment_method', 'payment_remarks']);
        });
    }
};
