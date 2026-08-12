<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('supplier_code')->unique();
            $t->string('company_name');
            $t->string('contact_person')->nullable();
            $t->string('mobile', 20);
            $t->string('email')->nullable();
            $t->string('gst_number')->nullable();
            $t->string('pan_number')->nullable();
            $t->text('address')->nullable();
            $t->string('city')->nullable();
            $t->string('state')->nullable();
            $t->string('pin_code', 10)->nullable();
            $t->unsignedInteger('payment_terms_days')->default(0);
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('parts', function (Blueprint $t) {
            $t->id();
            $t->string('part_code')->unique();
            $t->string('part_name');
            $t->string('category');
            $t->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $t->string('compatible_models')->nullable();
            $t->string('unit')->default('piece');
            $t->decimal('purchase_price', 14, 2)->default(0);
            $t->decimal('selling_price', 14, 2)->default(0);
            $t->decimal('tax_percent', 5, 2)->default(0);
            $t->unsignedInteger('current_stock')->default(0);
            $t->unsignedInteger('minimum_stock')->default(0);
            $t->unsignedInteger('warranty_months')->default(0);
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('inventory_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('part_id')->constrained()->restrictOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->string('transaction_type');
            $t->integer('quantity');
            $t->unsignedInteger('balance_after');
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->string('warehouse')->nullable();
            $t->string('reference')->nullable();
            $t->text('remarks')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('part_issues', function (Blueprint $t) {
            $t->id();
            $t->string('issue_code')->unique();
            $t->foreignId('part_id')->constrained()->restrictOnDelete();
            $t->foreignId('technician_id')->constrained()->restrictOnDelete();
            $t->foreignId('work_assignment_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedInteger('issued_quantity');
            $t->unsignedInteger('used_quantity')->default(0);
            $t->unsignedInteger('returned_quantity')->default(0);
            $t->unsignedInteger('damaged_quantity')->default(0);
            $t->string('status')->default('issued');
            $t->text('remarks')->nullable();
            $t->timestamps();
        });
        Schema::create('job_parts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('work_assignment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('part_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->decimal('rate', 14, 2);
            $t->decimal('tax_percent', 5, 2)->default(0);
            $t->string('serial_number')->nullable();
            $t->boolean('under_warranty')->default(false);
            $t->timestamps();
        });
        Schema::create('part_requests', function (Blueprint $t) {
            $t->id();
            $t->string('request_code')->unique();
            $t->foreignId('technician_id')->constrained()->restrictOnDelete();
            $t->foreignId('work_assignment_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('part_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->string('urgency')->default('normal');
            $t->string('status')->default('pending');
            $t->text('reason')->nullable();
            $t->text('remarks')->nullable();
            $t->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['part_requests', 'job_parts', 'part_issues', 'inventory_transactions', 'parts', 'suppliers'] as $x) {
            Schema::dropIfExists($x);
        }
    }
};
