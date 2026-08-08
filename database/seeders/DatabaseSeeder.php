<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $admin = User::updateOrCreate(['email' => 'admin@fieldcrm.test'], [
            'name' => 'System Administrator',
            'phone' => '9999999999',
            'password' => 'ChangeMe123!',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->roles()->sync([Role::where('slug', 'super-admin')->value('id')]);
    }
}
