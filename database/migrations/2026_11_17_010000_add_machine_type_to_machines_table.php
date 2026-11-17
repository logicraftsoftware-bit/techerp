<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original master-data migration already creates this column.
        if (! Schema::hasColumn('machines', 'machine_type')) {
            Schema::table('machines', function (Blueprint $table) {
                $table->string('machine_type')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Preserve the column owned by the original master-data migration.
    }
};
