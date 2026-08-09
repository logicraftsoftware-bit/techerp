<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name')->unique();
            $table->timestamps();
        });
        Schema::table('machines', fn (Blueprint $table) => $table->foreignId('brand_id')->nullable()->after('machine_type')->constrained()->restrictOnDelete());
        DB::table('machines')->whereNotNull('brand')->where('brand', '<>', '')->orderBy('id')->each(function ($machine) {
            $brandId = DB::table('brands')->where('brand_name', $machine->brand)->value('id') ?? DB::table('brands')->insertGetId(['brand_name' => $machine->brand, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('machines')->where('id', $machine->id)->update(['brand_id' => $brandId]);
        });
    }

    public function down(): void
    {
        Schema::table('machines', fn (Blueprint $table) => $table->dropConstrainedForeignId('brand_id'));
        Schema::dropIfExists('brands');
    }
};
