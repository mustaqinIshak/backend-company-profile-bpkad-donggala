<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeritaController;
use App\Http\Controllers\Api\JabatanController;
use App\Http\Controllers\Api\JumbotronController;
use App\Http\Controllers\Api\KontakController;
use App\Http\Controllers\Api\LayananController;
use App\Http\Controllers\Api\OrganisasiController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – BPKAD Kabupaten Donggala
|--------------------------------------------------------------------------
*/

// ── Public routes ──────────────────────────────────────────────────────

// Auth
Route::post('/auth/login', [AuthController::class, 'login']);

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

// ── Protected routes (require Sanctum token) ───────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

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

    // Kontak management (admin inbox)
    Route::get('/admin/kontak', [KontakController::class, 'index']);
    Route::get('/admin/kontak/{kontak}', [KontakController::class, 'show']);
    Route::patch('/admin/kontak/{kontak}/status', [KontakController::class, 'updateStatus']);
    Route::delete('/admin/kontak/{kontak}', [KontakController::class, 'destroy']);
});
