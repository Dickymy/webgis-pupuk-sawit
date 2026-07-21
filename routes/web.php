<?php

use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlokLahanController;
use App\Http\Controllers\CuacaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeoUploadController;
use App\Http\Controllers\KondisiLahanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RbsController;
use App\Http\Controllers\RealisasiPemupukanController;
use App\Http\Controllers\RuleBaseController;
use App\Http\Controllers\SettingController;
use App\Http\Middleware\AdminAuthenticated;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', fn () => redirect()->route('dashboard'));

// Authentication routes (guest only)
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes — requires admin authentication
Route::middleware(AdminAuthenticated::class)->group(function () {

    // Dashboard (WebGIS)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Anggota Kelompok Tani
    Route::resource('anggota', AnggotaController::class)->except(['show']);

    // Manajemen Blok Lahan (termasuk kriteria agronomis)
    Route::resource('blok-lahan', BlokLahanController::class);

    // Kondisi Lahan
    Route::resource('kondisi-lahan', KondisiLahanController::class)->except(['show']);

    // Rule Base RBS
    Route::get('rule-base/info', [RuleBaseController::class, 'info'])->name('rule-base.info');
    Route::resource('rule-base', RuleBaseController::class)->except(['show']);

    // Analisis RBS (Rule-Based System) — Satu-satunya mesin analisis
    Route::prefix('rbs')->name('rbs.')->group(function () {
        Route::get('/', [RbsController::class, 'index'])->name('index');
        Route::get('/daftar-blok-belum-analisis', [RbsController::class, 'daftarBlokBelumAnalisis'])->name('daftarBlokBelumAnalisis');
        Route::post('/analisis/{blokLahan}', [RbsController::class, 'analisis'])->name('analisis');
        Route::post('/analisis-semua', [RbsController::class, 'analisisSemua'])->name('analisisSemua');
        Route::get('/detail/{blokLahan}', [RbsController::class, 'detail'])->name('detail');
    });

    // Laporan (berbasis rekomendasi RBS)
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/{rekomendasiRbs}/pdf', [LaporanController::class, 'exportPdf'])->name('pdf');
        Route::get('/{rekomendasiRbs}', [LaporanController::class, 'show'])->name('show');
    });

    // API endpoint — RBS popup WebGIS
    Route::get('/api/rbs-popup/{blokLahan}', [RbsController::class, 'apiPopup'])->name('api.rbs.popup');

    // API endpoint — Cuaca otomatis dari Open-Meteo
    Route::post('/api/cuaca/fetch', [CuacaController::class, 'fetch'])->name('api.cuaca.fetch');

    // API endpoint — Upload SHP/GeoJSON ke GeoJSON polygon
    Route::post('/api/geo-upload', [GeoUploadController::class, 'upload'])->name('api.geo.upload');

    // Realisasi Pemupukan (Pahan v2.6)
    Route::prefix('realisasi-pemupukan')->name('realisasi-pemupukan.')->group(function () {
        Route::get('/', [RealisasiPemupukanController::class, 'index'])->name('index');
        Route::get('/create/{rekomendasiRbs}', [RealisasiPemupukanController::class, 'create'])->name('create');
        Route::post('/', [RealisasiPemupukanController::class, 'store'])->name('store');
        Route::get('/{realisasiPemupukan}', [RealisasiPemupukanController::class, 'show'])->name('show');
        Route::get('/{realisasiPemupukan}/edit', [RealisasiPemupukanController::class, 'edit'])->name('edit');
        Route::put('/{realisasiPemupukan}', [RealisasiPemupukanController::class, 'update'])->name('update');
        Route::patch('/{realisasiPemupukan}/batal', [RealisasiPemupukanController::class, 'cancel'])->name('cancel');
    });

    // Pengaturan (Ganti Password & Mode Tampilan)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/password', [SettingController::class, 'updatePassword'])->name('password.update');
        Route::put('/theme', [SettingController::class, 'updateTheme'])->name('theme.update');
    });

    // Notifikasi (Pahan v2.8)
    Route::prefix('api/notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'recent'])->name('recent');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('readAll');
    });
});
