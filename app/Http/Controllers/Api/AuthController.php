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
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Revoke all previous tokens
        $admin->tokens()->delete();

        $token = $admin->createToken('api-token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'admin' => [
                'id'    => $admin->id,
                'name'  => $admin->name,
                'email' => $admin->email,
            ],
        ], 'Login berhasil.');
    }

    public function me(Request $request)
    {
        return $this->success([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
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

        return $this->success(null, 'Logout berhasil.');
    }
}
