<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('customer_type')->default('individual')->index();
            $table->string('customer_name');
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile', 20)->index();
            $table->string('alternate_mobile', 20)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('whatsapp', 20)->nullable();
            $table->string('gst_number', 20)->nullable()->unique();
            $table->string('pan_number', 15)->nullable();
            $table->text('address');
            $table->string('city')->index();
            $table->string('state')->index();
            $table->string('pin_code', 10);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('mobile', 20);
            $table->string('alternate_mobile', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'is_primary']);
        });
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('machine_code')->unique();
            $table->string('machine_name');
            $table->string('machine_type')->index();
            $table->string('brand')->nullable()->index();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->string('asset_number')->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('installation_date')->nullable();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->date('amc_start')->nullable();
            $table->date('amc_end')->nullable();
            $table->unsignedInteger('service_frequency')->nullable()->comment('Days');
            $table->string('status')->default('active')->index();
            $table->string('site_name')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pin_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'status']);
        });
        Schema::create('machine_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->string('document_type')->index();
            $table->string('title');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('name');
            $table->string('profile_photo')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('mobile', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('emergency_contact', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pin_code', 10)->nullable();
            $table->date('joining_date');
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->string('technician_type')->nullable();
            $table->foreignId('reporting_manager_id')->nullable()->constrained('technicians')->nullOnDelete();
            $table->string('employment_type')->default('full_time')->index();
            $table->string('status')->default('active')->index();
            $table->string('salary_type')->default('monthly');
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->decimal('daily_salary', 12, 2)->default(0);
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->decimal('overtime_rate', 12, 2)->default(0);
            $table->decimal('travel_allowance', 12, 2)->default(0);
            $table->decimal('food_allowance', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('pf', 12, 2)->default(0);
            $table->decimal('esi', 12, 2)->default(0);
            $table->decimal('advance', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->default('other')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('skill_technician', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('skill_level')->default('beginner');
            $table->decimal('experience_years', 4, 1)->default(0);
            $table->string('certification')->nullable();
            $table->date('certification_expiry')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['technician_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_technician');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('machine_documents');
        Schema::dropIfExists('machines');
        Schema::dropIfExists('customer_contacts');
        Schema::dropIfExists('technicians');
        Schema::dropIfExists('customers');
    }
};
