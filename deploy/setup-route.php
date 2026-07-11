<?php

/**
 * ==========================================================
 * ROUTE SEMENTARA UNTUK SETUP DATABASE DI INFINITYFREE
 * ==========================================================
 * 
 * CARA PAKAI:
 * 1. Copy isi blok Route di bawah ini ke routes/web.php (di bagian paling bawah)
 * 2. Upload routes/web.php ke server
 * 3. Akses URL: https://domain-kamu.infinityfreeapp.com/setup-database
 * 4. Tunggu sampai muncul "Berhasil!"
 * 5. HAPUS route ini dari routes/web.php setelah selesai!
 * 
 * PENTING: Hapus setelah selesai agar orang lain tidak bisa reset database kamu!
 */

// === COPY DARI SINI ===

use Illuminate\Support\Facades\Artisan;

Route::get('/setup-database', function () {
    $output = [];
    
    try {
        // Jalankan migration
        Artisan::call('migrate', ['--force' => true]);
        $output[] = '✅ Migration berhasil!';
        $output[] = Artisan::output();
        
        // Jalankan seeder
        Artisan::call('db:seed', ['--force' => true]);
        $output[] = '✅ Seeding berhasil!';
        $output[] = Artisan::output();
        
        // Clear cache
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        $output[] = '✅ Cache cleared!';
        
        $output[] = '';
        $output[] = '🎉 SETUP SELESAI! Silakan hapus route /setup-database dari routes/web.php';
        
    } catch (\Exception $e) {
        $output[] = '❌ Error: ' . $e->getMessage();
        $output[] = 'File: ' . $e->getFile() . ':' . $e->getLine();
    }
    
    return '<pre>' . implode("\n", $output) . '</pre>';
});

// === SAMPAI SINI ===
