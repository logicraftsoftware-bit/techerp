<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->string('request_type')->index();
            $table->string('service_type')->index();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('machine_category_id')->nullable()->constrained('machine_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('product_name')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('subject');
            $table->text('complaint')->nullable();
            $table->string('priority')->default('normal')->index();
            $table->date('preferred_date')->nullable()->index();
            $table->string('preferred_time')->nullable();
            $table->text('service_address');
            $table->string('city');
            $table->string('state');
            $table->string('pin_code', 10);
            $table->text('notes')->nullable();
            $table->string('status')->default('open')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
