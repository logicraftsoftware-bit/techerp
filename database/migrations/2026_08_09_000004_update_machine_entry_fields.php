<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->string('machine_type')->nullable()->change();
            $table->foreignId('customer_id')->nullable()->change();
            $table->date('manufacturing_date')->nullable()->after('asset_number');
            $table->string('service_period')->nullable()->after('manufacturing_date');
            $table->decimal('buying_price', 14, 2)->default(0)->after('service_period');
            $table->decimal('selling_price', 14, 2)->default(0)->after('buying_price');
            $table->unsignedInteger('total_stock')->default(0)->after('selling_price');
            $table->string('location_name')->nullable()->after('total_stock');
        });
    }

    public function down(): void
    {
        Schema::table('machines', fn (Blueprint $table) => $table->dropColumn(['manufacturing_date', 'service_period', 'buying_price', 'selling_price', 'total_stock', 'location_name']));
    }
};
