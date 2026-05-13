<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * GET /admin/admins
     * Daftar semua admin beserta role.
     */
    public function index(Request $request)
    {
        $query = Admin::with('roles:id,name,display_name')
            ->select('id', 'name', 'email', 'created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }

        $admins = $query->orderBy('name')->paginate($request->integer('per_page', 15));

        return $this->success($admins);
    }

    /**
     * POST /admin/admins
     * Buat admin baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:admins,email',
            'password' => ['required', Password::min(8)],
            'roles'    => 'required|array|min:1',
            'roles.*'  => 'exists:roles,id',
        ]);

        $admin = Admin::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $admin->roles()->sync($data['roles']);

        return $this->success(
            $admin->load('roles:id,name,display_name'),
            'Admin berhasil dibuat.',
            201
        );
    }

    /**
     * GET /admin/admins/{admin}
     */
    public function show(Admin $admin)
    {
        return $this->success(
            $admin->load('roles:id,name,display_name')->makeVisible([])
                  ->makeHidden(['password', 'remember_token'])
        );
    }

    /**
     * POST /admin/admins/{admin}
     * Update data admin (name, email, password opsional).
     */
    public function update(Request $request, Admin $admin)
    {
        $data = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email|unique:admins,email,' . $admin->id,
            'password' => ['nullable', Password::min(8)],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $admin->update($data);

        return $this->success(
            $admin->load('roles:id,name,display_name'),
            'Data admin diperbarui.'
        );
    }

    /**
     * PUT /admin/admins/{admin}/roles
     * Sync (replace) role pada admin.
     */
    public function syncRoles(Request $request, Admin $admin)
    {
        $data = $request->validate([
            'roles'   => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $admin->roles()->sync($data['roles']);

        return $this->success(
            $admin->load('roles:id,name,display_name'),
            'Role admin diperbarui.'
        );
    }

    /**
     * DELETE /admin/admins/{admin}
     */
    public function destroy(Request $request, Admin $admin)
    {
        // Prevent self-deletion
        if ($request->user()->id === $admin->id) {
            return $this->error('Tidak dapat menghapus akun sendiri.', 422);
        }

        $admin->roles()->detach();
        $admin->tokens()->delete();
        $admin->delete();

        return $this->success(null, 'Admin berhasil dihapus.');
    }

    /**
     * GET /admin/permissions
     * Daftar semua permissions yang tersedia
     */
    public function permissions()
    {
        $permissions = \App\Models\Permission::all(['id', 'name', 'description']);
        return $this->success($permissions);
    }

    /**
     * GET /admin/roles
     * Daftar semua role yang tersedia.
     */
    public function roles()
    {
        $roles = Role::withCount('admins')->with('permissions:id,name,description')->get(['id', 'name', 'display_name', 'description']);
        return $this->success($roles);
    }

    /**
     * POST /admin/roles
     * Buat role baru beserta permissions-nya.
     */
    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:50|unique:roles,name|regex:/^[a-z_]+$/',
            'display_name' => 'required|string|max:100',
            'description'  => 'nullable|string|max:255',
            'permissions'  => 'nullable|array',
            'permissions.*'=> 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
        ]);

        if (isset($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return $this->success($role->load('permissions:id,name,description'), 'Role berhasil dibuat.', 201);
    }

    /**
     * GET /admin/roles/{role}
     * Detail role beserta daftar admin yang memilikinya.
     */
    public function showRole(Role $role)
    {
        $role->load('admins:id,name,email', 'permissions:id,name,description');
        $role->loadCount('admins');
        return $this->success($role);
    }

    /**
     * PUT /admin/roles/{role}
     * Update role beserta permissions-nya.
     */
    public function updateRole(Request $request, Role $role)
    {
        $data = $request->validate([
            'display_name' => 'sometimes|required|string|max:100',
            'description'  => 'nullable|string|max:255',
            'permissions'  => 'nullable|array',
            'permissions.*'=> 'exists:permissions,id',
        ]);

        $role->update($request->only(['display_name', 'description']));

        if (isset($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return $this->success($role->load('permissions:id,name,description'), 'Role diperbarui.');
    }

    /**
     * DELETE /admin/roles/{role}
     * Hapus role (hanya jika tidak ada admin yang menggunakannya).
     */
    public function destroyRole(Role $role)
    {
        if ($role->admins()->exists()) {
            return $this->error(
                "Role '{$role->display_name}' masih digunakan oleh {$role->admins()->count()} admin. Cabut role dari semua admin terlebih dahulu.",
                422
            );
        }

        $role->delete();

        return $this->success(null, 'Role berhasil dihapus.');
    }
}
