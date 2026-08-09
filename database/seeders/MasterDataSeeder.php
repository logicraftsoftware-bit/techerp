<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Electrical', 'Mechanical', 'HVAC', 'Plumbing', 'Installation', 'Maintenance', 'Repair', 'Other'] as $name) {
            Skill::firstOrCreate(['name' => $name], ['category' => strtolower($name), 'is_active' => true]);
        }
    }
}
