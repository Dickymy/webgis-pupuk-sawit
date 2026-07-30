<?php

/**
 * Konfigurasi Sistem Pemupukan SawitGIS
 *
 * Referensi Utama:
 * Pahan, I. 2013. Panduan Lengkap Kelapa Sawit. Cetakan XI.
 * Jakarta: Penebar Swadaya. Bab 9, Tabel 9.13 & 9.14, hal. 163-164.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Strategi Estimasi Dosis
    |--------------------------------------------------------------------------
    |
    | Menentukan bagaimana sistem memilih nilai tunggal dari rentang referensi.
    | Opsi: 'minimum', 'midpoint', 'maximum', 'expert_value'
    |
    */
    'reference_dose_strategy' => env('DOSE_STRATEGY', 'midpoint'),

    /*
    |--------------------------------------------------------------------------
    | Tabel Referensi Dosis Pahan 2013
    |--------------------------------------------------------------------------
    |
    | Kisaran dosis tahunan per pokok (kg/pokok/tahun)
    | Sumber: Tabel 9.13 & 9.14, Bab 9, hal. 163-164
    |
    */
    'dose_reference' => [
        'TBM' => [
            '1' => [
                'urea_min' => 0.50, 'urea_max' => 0.70,
                'kcl_min' => 0.75, 'kcl_max' => 1.25,
                'label' => 'TBM Tahun ke-1',
            ],
            '2' => [
                'urea_min' => 0.70, 'urea_max' => 0.85,
                'kcl_min' => 1.00, 'kcl_max' => 1.75,
                'label' => 'TBM Tahun ke-2',
            ],
            '3' => [
                'urea_min' => 0.90, 'urea_max' => 1.25,
                'kcl_min' => 1.20, 'kcl_max' => 2.25,
                'label' => 'TBM Tahun ke-3',
            ],
        ],
        'TM' => [
            '3-5' => [
                'urea_min' => 0.90, 'urea_max' => 1.75,
                'kcl_min' => 1.20, 'kcl_max' => 2.50,
                'label' => 'TM Umur 3–5 tahun',
            ],
            '6-15' => [
                'urea_min' => 1.00, 'urea_max' => 3.00,
                'kcl_min' => 1.50, 'kcl_max' => 3.50,
                'label' => 'TM Umur 6–15 tahun',
            ],
            '16+' => [
                'urea_min' => 1.50, 'urea_max' => 2.50,
                'kcl_min' => 1.50, 'kcl_max' => 2.25,
                'label' => 'TM Umur di atas 15 tahun',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Referensi Bibliografis
    |--------------------------------------------------------------------------
    */
    'reference_source' => [
        'author' => 'Iyung Pahan',
        'year' => 2013,
        'title' => 'Panduan Lengkap Kelapa Sawit',
        'edition' => 'Cetakan XI',
        'publisher' => 'Penebar Swadaya',
        'city' => 'Jakarta',
        'chapter' => 9,
        'tables' => ['9.13', '9.14'],
        'pages' => '163-164',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parameter Kelayakan Waktu Aplikasi (Fertilization Window)
    |--------------------------------------------------------------------------
    */
    'window' => [
        // Curah hujan bulanan (mm/bulan).
        // PPKS 2025: rentang optimal 100-250 mm/bulan.
        // Pradiko dkk. (PPKS 2021): tunda pada <60 atau >300 mm/bulan.
        'rainfall_optimal_min_mm' => 100,
        'rainfall_optimal_max_mm' => 250,
        'rainfall_defer_below_mm' => 60,
        'rainfall_defer_above_mm' => 300,

        // Jeda minimum operasional. PPKS merekomendasikan 2-3 aplikasi/tahun;
        // 120 hari dipakai sebagai batas minimum untuk mencegah >3 tahap/tahun.
        // Ini adalah adaptasi desain penelitian, bukan angka dosis dari Pahan.
        'min_interval_days' => 120,

    ],

    /*
    |--------------------------------------------------------------------------
    | Bobot Kelengkapan Data Pendukung
    |--------------------------------------------------------------------------
    */
    'reliability_weights' => [
        'identitas_blok' => 20, // luas, SPH, tahun/tanggal tanam
        'fase_terverifikasi' => 10,
        'curah_hujan' => 30, // angka/kategori, periode, dan sumber
        'tgl_pemupukan' => 15,
        'data_visual' => 15, // kondisi daun; foto hanya dokumentasi
        'kondisi_lapangan' => 10, // kelembapan dan drainase

    ],

    /*
    |--------------------------------------------------------------------------
    | Kategori Kelengkapan Data Pendukung
    |--------------------------------------------------------------------------
    */
    'reliability_categories' => [
        ['min' => 0,  'max' => 69,  'label' => 'Perlu Dilengkapi'],
        ['min' => 70, 'max' => 89,  'label' => 'Cukup Lengkap'],
        ['min' => 90, 'max' => 100, 'label' => 'Lengkap'],

    ],

    /*
    |--------------------------------------------------------------------------
    | Versi Mesin Rekomendasi
    |--------------------------------------------------------------------------
    */
    'engine_version' => 'pahan-v2.9',

];
