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
        // Curah hujan bulanan (mm/bulan)
        'rainfall_min_mm' => 100,
        'rainfall_max_mm' => 250,

        // Interval minimum antar aplikasi sejenis (hari)
        'min_interval_days' => 60,

        // Batas hari keterlambatan pemupukan
        'late_threshold_days' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bobot Skor Kelengkapan & Keandalan Data
    |--------------------------------------------------------------------------
    */
    'reliability_weights' => [
        'identitas_blok' => 15, // luas, SPH, tahun/tanggal tanam
        'fase_terverifikasi' => 10,
        'ph_dan_metode' => 10,
        'curah_hujan' => 15, // curah hujan bulanan + periode
        'tgl_pemupukan' => 10,
        'data_visual' => 15, // daun, pelepah, defisiensi
        'drainase_gulma_hama' => 10,
        'rule_bersumber' => 10,
        'validasi_ahli' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Kategori Keandalan
    |--------------------------------------------------------------------------
    */
    'reliability_categories' => [
        ['min' => 0,  'max' => 49,  'label' => 'Data Tidak Cukup'],
        ['min' => 50, 'max' => 69,  'label' => 'Estimasi Awal'],
        ['min' => 70, 'max' => 84,  'label' => 'Cukup Kuat'],
        ['min' => 85, 'max' => 100, 'label' => 'Kuat secara Data'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Versi Mesin Rekomendasi
    |--------------------------------------------------------------------------
    */
    'engine_version' => 'pahan-v2.6',

    /*
    |--------------------------------------------------------------------------
    | Legacy Multipliers (NONAKTIF — disimpan untuk dokumentasi)
    |--------------------------------------------------------------------------
    |
    | Multiplier berikut telah dinonaktifkan dari perhitungan produksi.
    | Alasan: Angka multiplier belum memiliki sumber yang cukup kuat
    | dari buku Iyung Pahan (2013).
    |
    | Jika diperlukan, gunakan mekanisme expert_adjustment.
    |
    */
    'legacy_multipliers' => [
        'enabled' => false,
        'soil' => [
            'Tanah Lempung' => ['urea' => 1.0,  'kcl' => 1.0],
            'Tanah Lempung Berpasir' => ['urea' => 1.1,  'kcl' => 1.15],
            'Tanah Berpasir' => ['urea' => 1.25, 'kcl' => 1.35],
            'Tanah Liat' => ['urea' => 0.9,  'kcl' => 0.9],
            'Tanah Gambut' => ['urea' => 0.7,  'kcl' => 1.5],
            'Tanah Aluvial' => ['urea' => 1.0,  'kcl' => 1.0],
            'Tanah Podsolik Merah Kuning (PMK)' => ['urea' => 1.15, 'kcl' => 1.2],
            'Tanah Laterit' => ['urea' => 1.15, 'kcl' => 1.2],
            'Tanah Berbatu' => ['urea' => 1.2,  'kcl' => 1.2],
        ],
        'topography' => [
            'Datar 0-15°' => ['urea' => 1.0, 'kcl' => 1.0],
            'Bergelombang 15-30°' => ['urea' => 1.1, 'kcl' => 1.1],
            'Curam >30°' => ['urea' => 1.2, 'kcl' => 1.2],
        ],
        'time' => [
            'recent_days' => 60,
            'recent_factor' => 0.75,
            'normal_factor' => 1.0,
            'late_days' => 120,
            'late_factor' => 1.25,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Expert Adjustment Limits
    |--------------------------------------------------------------------------
    */
    'expert_adjustment' => [
        'min_factor' => 0.50,
        'max_factor' => 2.00,
        'default' => 1.00,
    ],
];
