<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles & permissions first
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        // Create default admin if not exists
        $admin = Admin::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@bpkad-donggala.go.id')],
            [
                'name'     => env('ADMIN_NAME', 'Administrator'),
                'email'    => env('ADMIN_EMAIL', 'admin@bpkad-donggala.go.id'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin123')),
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->command->info('Default admin created: ' . $admin->email);
        } else {
            $this->command->info('Admin already exists, skipping creation.');
        }

        // Attach super_admin role to the default admin if not already assigned
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole && !$admin->roles()->where('role_id', $superAdminRole->id)->exists()) {
            $admin->roles()->attach($superAdminRole);
            $this->command->info('super_admin role assigned to: ' . $admin->email);
        }
    }
}
