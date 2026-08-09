<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('joining_date')->constrained()->nullOnDelete();
            $table->foreignId('reporting_user_id')->nullable()->after('reporting_manager_id')->constrained('users')->nullOnDelete();
            $table->string('password')->nullable()->after('pin_code');
        });
    }

    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('reporting_user_id');
            $table->dropColumn('password');
        });
    }
};
