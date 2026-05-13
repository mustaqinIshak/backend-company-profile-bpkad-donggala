<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeritaController;
use App\Http\Controllers\Api\DisposisiController;
use App\Http\Controllers\Api\JabatanController;
use App\Http\Controllers\Api\JumbotronController;
use App\Http\Controllers\Api\KontakController;
use App\Http\Controllers\Api\LayananController;
use App\Http\Controllers\Api\OrganisasiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SuratKeluarController;
use App\Http\Controllers\Api\SuratMasukController;
use App\Http\Controllers\Api\TamuController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – BPKAD Kabupaten Donggala
|--------------------------------------------------------------------------
*/

// ── Public routes ──────────────────────────────────────────────────────

// Auth – rate limited: 5 attempts per minute per IP (server-side brute-force protection)
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Profile (read-only public)
Route::get('/profile', [ProfileController::class, 'show']);

// Jumbotron (active only)
Route::get('/jumbotron', [JumbotronController::class, 'index']);
Route::get('/jumbotron/{jumbotron}', [JumbotronController::class, 'show']);

// Organisasi (read-only public)
Route::get('/organisasi', [OrganisasiController::class, 'index']);
Route::get('/organisasi/bidang/{bidang}', [OrganisasiController::class, 'showByBidang']);
Route::get('/organisasi/{organisasi}', [OrganisasiController::class, 'show']);
Route::get('/organisasi/{organisasi}/jabatan', [JabatanController::class, 'index']);
Route::get('/organisasi/{organisasi}/jabatan/{jabatan}', [JabatanController::class, 'show']);

// Berita (read-only public)
Route::get('/berita', [BeritaController::class, 'index']);
Route::get('/berita/slug/{slug}', [BeritaController::class, 'showBySlug']);
Route::get('/berita/{berita}', [BeritaController::class, 'show']);

// Layanan (read-only public)
Route::get('/layanan', [LayananController::class, 'index']);
Route::get('/layanan/{layanan}', [LayananController::class, 'show']);

// Kontak – public submit
Route::post('/kontak', [KontakController::class, 'store']);

// Tamu – public registration (loby)
Route::post('/tamu', [TamuController::class, 'register']);

// ── Protected routes (require Sanctum token) ───────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // ── Admin management (super_admin only) ────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        // Role management
        Route::get('/admin/permissions', [AdminController::class, 'permissions']);
        Route::get('/admin/roles', [AdminController::class, 'roles']);
        Route::post('/admin/roles', [AdminController::class, 'storeRole']);
        Route::get('/admin/roles/{role}', [AdminController::class, 'showRole']);
        Route::put('/admin/roles/{role}', [AdminController::class, 'updateRole']);
        Route::delete('/admin/roles/{role}', [AdminController::class, 'destroyRole']);

        // Admin user management
        Route::get('/admin/admins', [AdminController::class, 'index']);
        Route::post('/admin/admins', [AdminController::class, 'store']);
        Route::get('/admin/admins/{admin}', [AdminController::class, 'show']);
        Route::post('/admin/admins/{admin}', [AdminController::class, 'update']);
        Route::put('/admin/admins/{admin}/roles', [AdminController::class, 'syncRoles']);
        Route::delete('/admin/admins/{admin}', [AdminController::class, 'destroy']);
    });

    // ── Content management (admin, super_admin) ─────────────────────────
    Route::middleware('role:admin,super_admin')->group(function () {

        // Profile management
        Route::post('/admin/profile', [ProfileController::class, 'update']);

        // Jumbotron management
        Route::post('/admin/jumbotron', [JumbotronController::class, 'store']);
        Route::post('/admin/jumbotron/{jumbotron}', [JumbotronController::class, 'update']);
        Route::delete('/admin/jumbotron/{jumbotron}', [JumbotronController::class, 'destroy']);
        Route::patch('/admin/jumbotron/{jumbotron}/toggle', [JumbotronController::class, 'toggleActive']);

        // Organisasi management
        Route::put('/admin/organisasi/bidang/{bidang}', [OrganisasiController::class, 'storeOrUpdate']);

        // Jabatan management
        Route::post('/admin/organisasi/{organisasi}/jabatan', [JabatanController::class, 'store']);
        Route::post('/admin/organisasi/{organisasi}/jabatan/{jabatan}', [JabatanController::class, 'update']);
        Route::delete('/admin/organisasi/{organisasi}/jabatan/{jabatan}', [JabatanController::class, 'destroy']);

        // Berita management
        Route::post('/admin/berita', [BeritaController::class, 'store']);
        Route::post('/admin/berita/{berita}', [BeritaController::class, 'update']);
        Route::delete('/admin/berita/{berita}', [BeritaController::class, 'destroy']);

        // Layanan management
        Route::post('/admin/layanan', [LayananController::class, 'store']);
        Route::post('/admin/layanan/{layanan}', [LayananController::class, 'update']);
        Route::delete('/admin/layanan/{layanan}', [LayananController::class, 'destroy']);
    });

    // ── Kontak inbox (admin, super_admin, resepsionis) ──────────────────
    Route::middleware('role:admin,super_admin,resepsionis')->group(function () {
        Route::get('/admin/kontak', [KontakController::class, 'index']);
        Route::get('/admin/kontak/{kontak}', [KontakController::class, 'show']);
        Route::patch('/admin/kontak/{kontak}/status', [KontakController::class, 'updateStatus']);
        Route::delete('/admin/kontak/{kontak}', [KontakController::class, 'destroy']);
    });

    // ── Tamu management (resepsionis, admin, super_admin) ───────────────
    Route::middleware('role:resepsionis,admin,super_admin')->group(function () {
        Route::get('/admin/tamu', [TamuController::class, 'index']);
        Route::get('/admin/tamu/{tamu}', [TamuController::class, 'show']);
        Route::patch('/admin/tamu/{tamu}/status', [TamuController::class, 'updateStatus']);
        Route::patch('/admin/tamu/{tamu}/checkout', [TamuController::class, 'checkout']);
        Route::delete('/admin/tamu/{tamu}', [TamuController::class, 'destroy']);
    });

    // ── Surat Masuk (petugas_surat, admin, super_admin) ─────────────────
    Route::middleware('role:petugas_surat,admin,super_admin')->group(function () {
        Route::get('/admin/surat-masuk', [SuratMasukController::class, 'index']);
        Route::post('/admin/surat-masuk', [SuratMasukController::class, 'store']);
        Route::get('/admin/surat-masuk/{suratMasuk}', [SuratMasukController::class, 'show']);
        Route::post('/admin/surat-masuk/{suratMasuk}', [SuratMasukController::class, 'update']);
        Route::patch('/admin/surat-masuk/{suratMasuk}/status', [SuratMasukController::class, 'updateStatus']);
        Route::delete('/admin/surat-masuk/{suratMasuk}', [SuratMasukController::class, 'destroy']);
    });

    // ── Disposisi (petugas_surat, pimpinan, admin, super_admin) ─────────
    Route::middleware('role:petugas_surat,pimpinan,admin,super_admin')->group(function () {
        Route::get('/admin/surat-masuk/{suratMasuk}/disposisi', [DisposisiController::class, 'index']);
        Route::post('/admin/surat-masuk/{suratMasuk}/disposisi', [DisposisiController::class, 'store']);
        Route::get('/admin/surat-masuk/{suratMasuk}/disposisi/{disposisi}', [DisposisiController::class, 'show']);
        Route::patch('/admin/surat-masuk/{suratMasuk}/disposisi/{disposisi}/status', [DisposisiController::class, 'updateStatus']);
        Route::patch('/admin/surat-masuk/{suratMasuk}/disposisi/{disposisi}/balas', [DisposisiController::class, 'balas']);
        Route::delete('/admin/surat-masuk/{suratMasuk}/disposisi/{disposisi}', [DisposisiController::class, 'destroy']);
    });

    // ── Surat Keluar ─────────────────────────────────────────────────────
    // CRUD & kirim: petugas_surat, admin, super_admin
    Route::middleware('role:petugas_surat,admin,super_admin')->group(function () {
        Route::get('/admin/surat-keluar', [SuratKeluarController::class, 'index']);
        Route::post('/admin/surat-keluar', [SuratKeluarController::class, 'store']);
        Route::get('/admin/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'show']);
        Route::post('/admin/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'update']);
        Route::patch('/admin/surat-keluar/{suratKeluar}/ajukan', [SuratKeluarController::class, 'ajukan']);
        Route::patch('/admin/surat-keluar/{suratKeluar}/kirim', [SuratKeluarController::class, 'kirim']);
        Route::patch('/admin/surat-keluar/{suratKeluar}/arsip', [SuratKeluarController::class, 'arsip']);
        Route::delete('/admin/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'destroy']);
    });

    // Setujui surat keluar: pimpinan, super_admin
    Route::middleware('role:pimpinan,super_admin')->group(function () {
        Route::patch('/admin/surat-keluar/{suratKeluar}/setujui', [SuratKeluarController::class, 'setujui']);
        // Pimpinan juga dapat melihat surat keluar
        Route::get('/admin/surat-keluar-pimpinan', [SuratKeluarController::class, 'index']);
    });
});

