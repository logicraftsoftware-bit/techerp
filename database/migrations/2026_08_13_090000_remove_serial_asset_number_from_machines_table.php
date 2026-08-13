<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropUnique(['serial_number']);
            $table->dropUnique(['asset_number']);
            $table->dropColumn(['serial_number', 'asset_number']);
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->unique()->after('model');
            $table->string('asset_number')->nullable()->unique()->after('serial_number');
        });
    }
};
