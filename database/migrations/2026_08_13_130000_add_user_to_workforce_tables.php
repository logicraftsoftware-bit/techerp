<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['technician_id', 'attendance_date']);
            $table->foreignId('technician_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->after('technician_id')->constrained()->cascadeOnDelete();
            $table->unique(['technician_id', 'user_id', 'attendance_date']);
        });
        Schema::table('technician_leaves', function (Blueprint $table) {
            $table->foreignId('technician_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->after('technician_id')->constrained()->cascadeOnDelete();
        });
        Schema::table('salary_records', function (Blueprint $table) {
            $table->dropUnique(['technician_id', 'salary_month']);
            $table->foreignId('technician_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->after('technician_id')->constrained()->cascadeOnDelete();
            $table->unique(['technician_id', 'user_id', 'salary_month']);
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('technician_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->after('technician_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['technician_id', 'user_id', 'attendance_date']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique(['technician_id', 'attendance_date']);
        });
        Schema::table('technician_leaves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('salary_records', function (Blueprint $table) {
            $table->dropUnique(['technician_id', 'user_id', 'salary_month']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique(['technician_id', 'salary_month']);
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
