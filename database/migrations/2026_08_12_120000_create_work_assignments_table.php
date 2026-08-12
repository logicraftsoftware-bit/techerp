<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_code')->unique();
            $table->foreignId('service_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('technician_id')->constrained()->restrictOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->string('assignment_role')->default('primary');
            $table->date('scheduled_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('priority')->default('normal')->index();
            $table->string('status')->default('assigned')->index();
            $table->text('service_address');
            $table->text('work_instructions')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['technician_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_assignments');
    }
};
