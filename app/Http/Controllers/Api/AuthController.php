<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Revoke all previous tokens
        $admin->tokens()->delete();

        // Eager-load roles untuk permission calculation
        $admin->load('roles');

        $token = $admin->createToken('api-token')->plainTextToken;

        // Set token as httpOnly, Secure cookie — token never exposed to JavaScript
        $cookie = cookie(
            name: 'auth_token',
            value: $token,
            minutes: 60 * 24 * 7,                           // 7 hari
            path: '/',
            domain: null,
            secure: app()->environment('production'),        // HTTPS only in production
            httpOnly: true,
            raw: false,
            sameSite: 'strict',
        );

        return $this->success([
            'admin' => [
                'id'          => $admin->id,
                'name'        => $admin->name,
                'email'       => $admin->email,
                'roles'       => $admin->roles->map(fn($r) => [
                    'id'           => $r->id,
                    'name'         => $r->name,
                    'display_name' => $r->display_name,
                ]),
                'permissions' => $admin->getPermissions()->all(),
            ],
        ], 'Login berhasil.')->cookie($cookie);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles:id,name,display_name');
        return $this->success([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->roles,
            // PBAC: permission yang dimiliki user ini (digunakan frontend untuk gate UI)
            'permissions' => $user->getPermissions()->all(),
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $admin = $request->user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return $this->error('Password lama tidak sesuai.', 422);
        }

        $admin->update(['password' => Hash::make($request->new_password)]);

        // Revoke all tokens after password change
        $admin->tokens()->delete();

        return $this->success(null, 'Password berhasil diubah. Silakan login kembali.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        // Clear the httpOnly auth cookie
        $expiredCookie = cookie(
            name: 'auth_token',
            value: '',
            minutes: -1,
            path: '/',
            domain: null,
            secure: app()->environment('production'),
            httpOnly: true,
            raw: false,
            sameSite: 'strict',
        );

        return $this->success(null, 'Logout berhasil.')->cookie($expiredCookie);
    }
}
