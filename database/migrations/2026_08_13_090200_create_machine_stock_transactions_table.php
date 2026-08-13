<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_type');
            $table->integer('quantity');
            $table->unsignedInteger('balance_after');
            $table->string('reference')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_stock_transactions');
    }
};
