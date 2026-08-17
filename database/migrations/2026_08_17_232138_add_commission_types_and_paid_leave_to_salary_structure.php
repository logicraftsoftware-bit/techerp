<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_type_technician', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commission_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('commission_type_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commission_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('technicians', function (Blueprint $table) {
            $table->unsignedInteger('monthly_paid_leave_days')->default(0)->after('esi');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('monthly_paid_leave_days')->default(0)->after('esi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_type_technician');
        Schema::dropIfExists('commission_type_user');

        Schema::table('technicians', function (Blueprint $table) {
            $table->dropColumn('monthly_paid_leave_days');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('monthly_paid_leave_days');
        });
    }
};
