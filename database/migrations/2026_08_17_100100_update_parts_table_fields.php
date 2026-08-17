<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->foreignId('machine_category_id')->nullable()->after('category')->constrained('machine_categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('unit')->constrained('units')->nullOnDelete();
            $table->date('start_date')->nullable()->after('brand_id');
            $table->boolean('has_amc')->default(false)->after('tax_percent');
            $table->boolean('has_warranty')->default(false)->after('has_amc');
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->unsignedInteger('warranty_months')->nullable()->change();
            $table->dropColumn(['category', 'unit']);
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->string('category')->nullable()->after('part_name');
            $table->string('unit')->default('piece')->after('compatible_models');
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('machine_category_id');
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn(['start_date', 'has_amc', 'has_warranty']);
            $table->unsignedInteger('warranty_months')->default(0)->change();
        });
    }
};
