<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
| REST API v1 — dokumentasi OpenAPI: /api/docs (Swagger UI), spesifikasi: /openapi.yaml
| Autentikasi: Bearer token (Sanctum) dari POST /api/v1/auth/login
*/
Route::prefix('v1')->group(function () {
    // Publik — hanya data aman (tanpa ID dokter / data pribadi pasien)
    Route::get('public/antrian', [Api\PublicApiController::class, 'antrian']);
    Route::get('public/dokter', [Api\PublicApiController::class, 'dokter']);
    Route::get('public/pengumuman', [Api\PublicApiController::class, 'pengumuman']);
    Route::get('public/alur', [Api\PublicApiController::class, 'alur']);

    // Kiosk / layar TV — permintaan wajib ditandatangani HMAC-SHA256 (X-Kiosk-Id, X-Timestamp, X-Signature)
    Route::middleware(['hmac', 'throttle:120,1'])->prefix('kiosk')->group(function () {
        Route::get('antrian', [Api\KioskApiController::class, 'antrian']);
        Route::post('checkin', [Api\KioskApiController::class, 'checkin']);
    });

    Route::post('auth/register', [Api\AuthApiController::class, 'register'])->middleware('throttle:10,1');
    Route::post('auth/login', [Api\AuthApiController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'aktif'])->group(function () {
        Route::get('auth/me', [Api\AuthApiController::class, 'me']);
        Route::post('auth/logout', [Api\AuthApiController::class, 'logout']);

        Route::get('notifikasi', [Api\NotifikasiApiController::class, 'index']);
        Route::patch('notifikasi/{id}', [Api\NotifikasiApiController::class, 'baca']);

        // Antrian: pasien (milik sendiri) & loket (semua)
        Route::get('antrian', [Api\AntrianApiController::class, 'index']);
        Route::post('antrian', [Api\AntrianApiController::class, 'store'])->middleware('izin:antrian.ambil');
        Route::get('antrian/{antrian}', [Api\AntrianApiController::class, 'show']);
        Route::patch('antrian/{antrian}', [Api\AntrianApiController::class, 'update']);
        Route::delete('antrian/{antrian}', [Api\AntrianApiController::class, 'destroy']);

        // Konsultasi online
        Route::get('konsultasi', [Api\KonsultasiApiController::class, 'index']);
        Route::post('konsultasi', [Api\KonsultasiApiController::class, 'store'])->middleware('izin:konsultasi.ajukan');
        Route::get('konsultasi/{konsultasi}', [Api\KonsultasiApiController::class, 'show']);
        Route::put('konsultasi/{konsultasi}', [Api\KonsultasiApiController::class, 'update'])->middleware('izin:konsultasi.tanggapi');

        // Resep
        Route::get('resep', [Api\ResepApiController::class, 'index']);
        Route::get('resep/{resep}', [Api\ResepApiController::class, 'show']);
        Route::patch('resep/{resep}', [Api\ResepApiController::class, 'update'])->middleware('izin:resep.proses');

        // Master data (CRUD)
        Route::get('obat', [Api\ObatApiController::class, 'index'])->middleware('izin:obat.lihat');
        Route::get('obat/{obat}', [Api\ObatApiController::class, 'show'])->middleware('izin:obat.lihat');
        Route::post('obat', [Api\ObatApiController::class, 'store'])->middleware('izin:obat.kelola');
        Route::put('obat/{obat}', [Api\ObatApiController::class, 'update'])->middleware('izin:obat.kelola');
        Route::delete('obat/{obat}', [Api\ObatApiController::class, 'destroy'])->middleware('izin:obat.kelola');

        Route::apiResource('pengumuman', Api\PengumumanApiController::class)->middleware('izin:pengumuman.kelola')->parameters(['pengumuman' => 'pengumuman']);
        Route::apiResource('pasien', Api\PasienApiController::class)->middleware('izin:pasien.lihat')->parameters(['pasien' => 'pasien']);

        Route::get('laporan/bulanan', [Api\LaporanApiController::class, 'show'])->middleware('izin:laporan.lihat');
    });
});

