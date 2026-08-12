<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('amc_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_code')->unique();
            $table->string('plan_name');
            $table->foreignId('machine_category_id')->constrained('machine_categories')->restrictOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete();
            $table->string('plan_type');
            $table->text('description')->nullable();
            $table->string('duration');
            $table->boolean('parts_included')->default(false);
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amc_plans');
    }
};
