<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'         => 'super_admin',
                'display_name' => 'Super Administrator',
                'description'  => 'Akses penuh ke seluruh fitur sistem.',
            ],
            [
                'name'         => 'admin',
                'display_name' => 'Administrator',
                'description'  => 'Kelola konten: berita, layanan, profil, jumbotron, organisasi.',
            ],
            [
                'name'         => 'resepsionis',
                'display_name' => 'Resepsionis',
                'description'  => 'Kelola registrasi dan data tamu loby.',
            ],
            [
                'name'         => 'petugas_surat',
                'display_name' => 'Petugas Persuratan',
                'description'  => 'Kelola surat masuk, surat keluar, dan disposisi.',
            ],
            [
                'name'         => 'pimpinan',
                'display_name' => 'Pimpinan',
                'description'  => 'Lihat laporan, setujui surat keluar, dan buat/lihat disposisi.',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $this->command->info('Roles seeded successfully.');
    }
}
