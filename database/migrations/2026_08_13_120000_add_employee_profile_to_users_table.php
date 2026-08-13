<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->string('gender')->nullable()->after('avatar');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('emergency_contact', 20)->nullable()->after('date_of_birth');
            $table->text('address')->nullable()->after('emergency_contact');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('pin_code', 10)->nullable()->after('state');
            $table->date('joining_date')->nullable()->after('pin_code');
            $table->foreignId('department_id')->nullable()->after('joining_date')->constrained()->nullOnDelete();
            $table->string('designation')->nullable()->after('department_id');
            $table->foreignId('reporting_manager_id')->nullable()->after('designation')->constrained('users')->nullOnDelete();
            $table->string('employment_type')->nullable()->after('reporting_manager_id');
            $table->string('employment_status')->default('active')->after('employment_type');
            $table->string('salary_type')->default('monthly')->after('employment_status');
            $table->decimal('monthly_salary', 12, 2)->default(0)->after('salary_type');
            $table->decimal('daily_salary', 12, 2)->default(0)->after('monthly_salary');
            $table->decimal('overtime_rate', 12, 2)->default(0)->after('daily_salary');
            $table->decimal('travel_allowance', 12, 2)->default(0)->after('overtime_rate');
            $table->decimal('food_allowance', 12, 2)->default(0)->after('travel_allowance');
            $table->decimal('other_allowance', 12, 2)->default(0)->after('food_allowance');
            $table->decimal('pf', 12, 2)->default(0)->after('other_allowance');
            $table->decimal('esi', 12, 2)->default(0)->after('pf');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('reporting_manager_id');
            $table->dropColumn([
                'employee_code', 'gender', 'date_of_birth', 'emergency_contact', 'address', 'city', 'state', 'pin_code',
                'joining_date', 'designation', 'employment_type', 'employment_status', 'salary_type', 'monthly_salary',
                'daily_salary', 'overtime_rate', 'travel_allowance', 'food_allowance', 'other_allowance', 'pf', 'esi',
            ]);
        });
    }
};
