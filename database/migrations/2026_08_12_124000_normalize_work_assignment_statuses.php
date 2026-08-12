<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('work_assignments')->whereIn('status', ['assigned', 'accepted'])->update(['status' => 'scheduled']);
        DB::table('work_assignments')->where('status', 'en_route')->update(['status' => 'in_progress']);

        DB::table('service_requests')->whereIn('id', DB::table('work_assignments')->where('status', 'scheduled')->select('service_request_id'))->update(['status' => 'scheduled']);
        DB::table('service_requests')->whereIn('id', DB::table('work_assignments')->where('status', 'in_progress')->select('service_request_id'))->update(['status' => 'in_progress']);
    }

    public function down(): void {}
};
