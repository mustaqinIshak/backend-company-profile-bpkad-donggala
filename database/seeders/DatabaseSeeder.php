<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin if not exists
        if (!Admin::where('email', env('ADMIN_EMAIL', 'admin@bpkad-donggala.go.id'))->exists()) {
            Admin::create([
                'name'     => env('ADMIN_NAME', 'Administrator'),
                'email'    => env('ADMIN_EMAIL', 'admin@bpkad-donggala.go.id'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin123')),
            ]);

            $this->command->info('Default admin created: '.env('ADMIN_EMAIL', 'admin@bpkad-donggala.go.id'));
        } else {
            $this->command->info('Admin already exists, skipping.');
        }
    }
}
