<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    // ── Role helpers ────────────────────────────────────────────────────

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    // ── Permission helpers (PBAC) ────────────────────────────────────────

    /**
     * Kembalikan semua permission yang dimiliki admin ini
     * berdasarkan role-role yang ditetapkan (diambil dari database).
     *
     * Mengembalikan Collection<string> yang ter-deduplikasi.
     */
    public function getPermissions(): Collection
    {
        if (!$this->relationLoaded('roles.permissions')) {
            $this->load('roles.permissions');
        }

        // Jika user adalah super_admin, otomatis dapat semua (opsional tapi aman)
        // Sebagai alternatif seutuhnya dinamis, Anda bisa membuang cek ini dan
        // mengandalkan data tabel permission_role untuk super_admin.
        if ($this->hasRole('super_admin')) {
            return Permission::pluck('name');
        }

        $permissions = collect();
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions->push($permission->name);
            }
        }

        return $permissions->unique()->values();
    }

    /**
     * Cek apakah admin memiliki permission tertentu.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->getPermissions()->contains($permission);
    }

    /**
     * Cek apakah admin memiliki setidaknya satu dari permission yang diberikan.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $userPerms = $this->getPermissions();

        foreach ($permissions as $permission) {
            if ($userPerms->contains($permission)) {
                return true;
            }
        }

        return false;
    }
}
