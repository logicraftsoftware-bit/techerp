<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('attendance_status');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['technician_id', 'attendance_date']);
        });
        Schema::create('technician_leaves', function (Blueprint $table) {
            $table->id();
            $table->string('leave_code')->unique();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->string('leave_type');
            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedInteger('total_days');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->text('approval_remarks')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();
        });
        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->string('salary_code')->unique();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->date('salary_month');
            $table->unsignedInteger('working_days');
            $table->decimal('present_days', 6, 2)->default(0);
            $table->decimal('paid_leave_days', 6, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('basic_salary', 14, 2);
            $table->decimal('allowances', 14, 2)->default(0);
            $table->decimal('overtime_amount', 14, 2)->default(0);
            $table->decimal('deductions', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2);
            $table->string('status')->default('draft');
            $table->date('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
            $table->unique(['technician_id', 'salary_month']);
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_code')->unique();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expense_date');
            $table->string('expense_type');
            $table->decimal('amount', 14, 2);
            $table->string('receipt_path')->nullable();
            $table->text('description');
            $table->string('status')->default('pending');
            $table->text('approval_remarks')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['expenses', 'salary_records', 'technician_leaves', 'attendances'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
