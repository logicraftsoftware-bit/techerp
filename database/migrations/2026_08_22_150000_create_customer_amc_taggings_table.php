<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_amc_taggings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('machine_id')->constrained()->restrictOnDelete();
            $table->foreignId('amc_plan_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'machine_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_amc_taggings');
    }
};
