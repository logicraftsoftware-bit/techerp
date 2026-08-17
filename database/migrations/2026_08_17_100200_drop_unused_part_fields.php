<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn(['compatible_models', 'tax_percent']);
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->string('compatible_models')->nullable()->after('brand_id');
            $table->decimal('tax_percent', 5, 2)->default(0)->after('selling_price');
        });
    }
};
