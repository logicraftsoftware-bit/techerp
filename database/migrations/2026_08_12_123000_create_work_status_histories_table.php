<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('remarks')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['work_assignment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_status_histories');
    }
};
