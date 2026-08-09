<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('entry_type')->default('crm')->index()->after('customer_code');
            $table->string('refer_type')->default('self')->index()->after('entry_type');
            $table->foreignId('referred_by_technician_id')->nullable()->after('refer_type')->constrained('technicians')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_technician_id');
            $table->dropColumn(['entry_type', 'refer_type']);
        });
    }
};
