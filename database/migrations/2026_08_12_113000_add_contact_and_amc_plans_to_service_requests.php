<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('contact_phone', 20)->nullable()->after('customer_id');
        });

        Schema::create('amc_plan_service_request', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amc_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['amc_plan_id', 'service_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amc_plan_service_request');
        Schema::table('service_requests', fn (Blueprint $table) => $table->dropColumn('contact_phone'));
    }
};
