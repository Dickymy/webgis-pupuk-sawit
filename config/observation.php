<?php

$diagnosticLeafConditions = [
    'Daun Bawah Menguning',
    'Bercak Kuning/Transparan pada Daun Tua',
    'Tepi Daun Tua Menguning pada Bagian Terbuka',
    'Daun Muda Berbentuk Kait atau Memendek',
];

return [
    /*
    |--------------------------------------------------------------------------
    | Pilihan kondisi daun
    |--------------------------------------------------------------------------
    |
    | Daftar ini dipakai bersama oleh form Observasi dan pengelolaan Rule Based
    | agar kondisi IF selalu dapat dipilih sebagai fakta observasi.
    |
    */
    'normal_leaf_condition' => 'Hijau Normal',

    // Hanya gejala yang saat ini memiliki dasar rule akademik yang digunakan.
    'diagnostic_leaf_conditions' => $diagnosticLeafConditions,

    'leaf_conditions' => array_merge(['Hijau Normal'], $diagnosticLeafConditions),

    /*
    | Label yang ditampilkan kepada petugas lapangan. Nilai yang disimpan
    | tetap sama dengan fakta pada rule agar proses pencocokan tidak berubah.
    */
    'leaf_condition_labels' => [
        'Hijau Normal' => 'Tidak ditemukan gejala yang diperiksa',
        'Daun Bawah Menguning' => 'Daun bagian bawah menguning',
        'Bercak Kuning/Transparan pada Daun Tua' => 'Bercak kuning atau transparan pada daun tua',
        'Tepi Daun Tua Menguning pada Bagian Terbuka' => 'Tepi daun tua menguning pada bagian terbuka',
        'Daun Muda Berbentuk Kait atau Memendek' => 'Daun muda berbentuk kait atau memendek',
    ],

    'leaf_condition_descriptions' => [
        'Hijau Normal' => 'Tidak terlihat salah satu gejala daun yang tersedia pada pemeriksaan ini.',
        'Daun Bawah Menguning' => 'Gejala terlihat terutama pada daun bagian bawah atau daun tua.',
        'Bercak Kuning/Transparan pada Daun Tua' => 'Bercak terlihat pada daun tua dan dapat tampak tembus cahaya.',
        'Tepi Daun Tua Menguning pada Bagian Terbuka' => 'Gejala dominan pada tepi daun tua yang terkena cahaya.',
        'Daun Muda Berbentuk Kait atau Memendek' => 'Bentuk daun muda tidak normal, seperti kait atau lebih pendek.',
    ],

    /*
    | Nilai khusus hanya dipakai pada form. Controller mengubahnya menjadi
    | NULL sehingga tidak dianggap sebagai fakta yang cocok dengan rule.
    */
    'unmatched_leaf_values' => [
        '__gejala_lain' => 'Ada gejala lain',
        '__tidak_pasti' => 'Belum dapat dipastikan',
    ],
];
