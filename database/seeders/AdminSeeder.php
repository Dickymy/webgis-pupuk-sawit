<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate agar aman dijalankan ulang (re-deploy Railway)
        Admin::firstOrCreate(
            ['username' => 'admin'],
            [
                'password'     => 'admin123',
                'nama_lengkap' => 'Administrator',
            ]
        );

        Admin::firstOrCreate(
            ['username' => 'test'],
            [
                'password'     => 'test',
                'nama_lengkap' => 'Akun Tester Kuesioner',
            ]
        );
    }
}
