<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Periksa apakah user yang terautentikasi memiliki permission yang dibutuhkan.
     *
     * Penggunaan di route:
     *   Route::middleware('permission:manage_berita')
     *   Route::middleware('permission:manage_berita,manage_layanan')  // salah satu saja
     *
     * @param  string  ...$permissions  Satu atau beberapa permission (OR logic)
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Eager-load roles jika belum
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        if (!$user->hasAnyPermission($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Anda tidak memiliki izin untuk mengakses resource ini.',
                'required_permissions' => $permissions,
            ], 403);
        }

        return $next($request);
    }
}
