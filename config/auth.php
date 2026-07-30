<?php

use App\Models\Admin;

return [
    'defaults' => [
        'guard' => 'admin',
    ],

    'guards' => [
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],
    ],

    // Penggantian password dilakukan oleh halaman pengaturan akun,
    // sehingga aplikasi tidak memakai password broker dan tabel token reset.
    'passwords' => [],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
