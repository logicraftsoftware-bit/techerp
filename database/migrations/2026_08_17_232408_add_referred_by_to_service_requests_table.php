<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('referred_by_technician_id')->nullable()->after('created_by')->constrained('technicians')->nullOnDelete();
            $table->foreignId('referred_by_user_id')->nullable()->after('referred_by_technician_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_technician_id');
            $table->dropConstrainedForeignId('referred_by_user_id');
        });
    }
};
