<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('customer_amc_taggings', function (Blueprint $table) {
            $table->string('customer_type')->default('amc');
            $table->unsignedBigInteger('amc_plan_id')->nullable()->change();
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }
    public function down(): void {
        Schema::table('customer_amc_taggings', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });
    }
};
