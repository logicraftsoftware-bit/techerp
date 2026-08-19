<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->time('expected_check_in_time')->nullable()->after('monthly_paid_leave_days');
            $table->time('expected_check_out_time')->nullable()->after('expected_check_in_time');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->time('expected_check_in_time')->nullable()->after('monthly_paid_leave_days');
            $table->time('expected_check_out_time')->nullable()->after('expected_check_in_time');
        });
    }

    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->dropColumn(['expected_check_in_time', 'expected_check_out_time']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['expected_check_in_time', 'expected_check_out_time']);
        });
    }
};
