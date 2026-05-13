<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data master permission, dipindahkan dari config/permissions.php
        $permissions = [
            // General
            'view_dashboard'        => 'Melihat halaman dashboard',
            'manage_own_account'    => 'Mengubah password dan melihat akun sendiri',
            
            // Konten
            'manage_profile'        => 'Mengelola profil instansi',
            'manage_jumbotron'      => 'Mengelola slide jumbotron',
            'manage_organisasi'     => 'Mengelola struktur organisasi dan jabatan',
            'manage_berita'         => 'Membuat, mengedit, dan menghapus berita',
            'manage_layanan'        => 'Membuat, mengedit, dan menghapus layanan',
            
            // Kontak
            'view_kontak'           => 'Melihat pesan kontak masuk',
            'manage_kontak'         => 'Memperbarui status dan menghapus pesan kontak',
            
            // Tamu Loby
            'view_tamu'             => 'Melihat daftar tamu loby',
            'manage_tamu'           => 'Menerima, menolak, checkout, dan menghapus tamu',
            
            // Surat Masuk
            'view_surat_masuk'      => 'Melihat surat masuk',
            'manage_surat_masuk'    => 'Mencatat, mengedit, dan menghapus surat masuk',
            
            // Disposisi
            'view_disposisi'        => 'Melihat disposisi',
            'manage_disposisi'      => 'Membuat dan memperbarui status disposisi',
            'reply_disposisi'       => 'Membalas disposisi (catatan balasan)',
            
            // Surat Keluar
            'view_surat_keluar'     => 'Melihat surat keluar',
            'manage_surat_keluar'   => 'Membuat draft, mengedit, mengirim, dan mengarsipkan surat keluar',
            'approve_surat_keluar'  => 'Menyetujui surat keluar (khusus pimpinan)',
            
            // Admin Management
            'manage_admin_users'    => 'Mengelola akun admin dan role (super_admin)',
        ];

        // 1. Insert/Update semua permissions ke tabel `permissions`
        $permissionModels = [];
        foreach ($permissions as $name => $description) {
            $permissionModels[$name] = Permission::updateOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Mapping dari config/permissions.php lama
        $rolePermissions = [
            'super_admin' => array_keys($permissions), // Beri seluruhnya via database

            'admin' => [
                'view_dashboard', 'manage_own_account', 'manage_profile', 'manage_jumbotron',
                'manage_organisasi', 'manage_berita', 'manage_layanan', 'view_kontak',
                'manage_kontak', 'view_tamu', 'manage_tamu', 'view_surat_masuk',
                'manage_surat_masuk', 'view_disposisi', 'manage_disposisi', 'reply_disposisi',
                'view_surat_keluar', 'manage_surat_keluar'
            ],

            'resepsionis' => [
                'view_dashboard', 'manage_own_account', 'view_kontak', 'manage_kontak',
                'view_tamu', 'manage_tamu'
            ],

            'petugas_surat' => [
                'view_dashboard', 'manage_own_account', 'view_surat_masuk', 'manage_surat_masuk',
                'view_disposisi', 'manage_disposisi', 'reply_disposisi', 'view_surat_keluar',
                'manage_surat_keluar'
            ],

            'pimpinan' => [
                'view_dashboard', 'manage_own_account', 'view_surat_masuk', 'view_disposisi',
                'manage_disposisi', 'reply_disposisi', 'view_surat_keluar', 'approve_surat_keluar'
            ],
        ];

        // 2. Hubungkan permission ke role di tabel `permission_role`
        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                // Konversi array string permission ke array of ID
                $permissionIds = collect($perms)->map(function ($pName) use ($permissionModels) {
                    return $permissionModels[$pName]->id ?? null;
                })->filter()->toArray();

                // Sync: menggantikan yang lama dengan ini
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
