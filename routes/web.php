<?php

use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlokLahanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KondisiLahanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\RbsController;
use App\Http\Controllers\RuleBaseController;
use App\Http\Controllers\SettingController;
use App\Http\Middleware\AdminAuthenticated;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', fn() => redirect()->route('dashboard'));

// === ROUTE MAINTENANCE — HAPUS SETELAH FIX ===
Route::get('/setup-database', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return '<pre>Migration berhasil!<br><br>' . $output . '</pre><br><a href="/fix-cache">Clear Cache</a>';
    } catch (\Exception $e) {
        return '<pre>Error: ' . $e->getMessage() . '</pre>';
    }
});

Route::get('/seed-tester', function () {
    \App\Models\Admin::firstOrCreate(
        ['username' => 'test'],
        [
            'password'     => 'test',
            'nama_lengkap' => 'Akun Tester Kuesioner',
        ]
    );
    return 'Akun tester berhasil dibuat! Username: test | Password: test<br><br><a href="/login">Login</a>';
});

Route::get('/fix-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    
    // Hapus semua session files
    $sessionPath = storage_path('framework/sessions');
    if (is_dir($sessionPath)) {
        $files = glob($sessionPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    }
    
    return 'Cache cleared! Session driver: ' . config('session.driver') . 
           ' | APP_URL: ' . config('app.url') .
           ' | Session secure: ' . (config('session.secure') ? 'true' : 'false') .
           ' | Session path: ' . config('session.path') .
           '<br><br><a href="/login">Klik di sini untuk login</a>';
});

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

    // Panduan Penggunaan (disembunyikan sementara — uncomment untuk menampilkan kembali)
    // Route::get('/panduan', function () {
    //     return view('panduan');
    // })->name('panduan');

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
    Route::post('/api/cuaca/fetch', [\App\Http\Controllers\CuacaController::class, 'fetch'])->name('api.cuaca.fetch');

    // API endpoint — Upload SHP/GeoJSON ke GeoJSON polygon
    Route::post('/api/geo-upload', [\App\Http\Controllers\GeoUploadController::class, 'upload'])->name('api.geo.upload');

    // Pengaturan (Ganti Password & Mode Tampilan)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/password', [SettingController::class, 'updatePassword'])->name('password.update');
        Route::put('/theme', [SettingController::class, 'updateTheme'])->name('theme.update');
    });
});


