<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AdminSeeder extends Seeder
{
    /**
     * Membuat akun admin awal berdasarkan environment variable.
     *
     * Environment variables yang diperlukan:
     *   INITIAL_ADMIN_USERNAME  — username admin (wajib)
     *   INITIAL_ADMIN_PASSWORD  — password admin minimal 8 karakter (wajib)
     *   INITIAL_ADMIN_NAME     — nama lengkap (opsional, default: Administrator)
     *
     * Akun tester dibuat hanya jika:
     *   CREATE_TESTER_ACCOUNT=true
     *   TESTER_USERNAME        — username tester (wajib jika create tester)
     *   TESTER_PASSWORD        — password tester minimal 8 karakter (wajib jika create tester)
     */
    public function run(): void
    {
        $this->seedAdminUtama();
        $this->seedTesterOpsional();
    }

    private function seedAdminUtama(): void
    {
        $username = env('INITIAL_ADMIN_USERNAME');
        $password = env('INITIAL_ADMIN_PASSWORD');
        $nama = env('INITIAL_ADMIN_NAME', 'Administrator');

        if (empty($username) || empty($password)) {
            $this->command?->warn('INITIAL_ADMIN_USERNAME dan INITIAL_ADMIN_PASSWORD belum diset di .env — akun admin tidak dibuat.');
            return;
        }

        if (strlen($password) < 8) {
            $this->command?->error('INITIAL_ADMIN_PASSWORD harus minimal 8 karakter — akun admin tidak dibuat.');
            return;
        }

        Admin::firstOrCreate(
            ['username' => $username],
            [
                'password'     => $password,
                'nama_lengkap' => $nama,
            ]
        );

        $this->command?->info("Akun admin '{$username}' siap digunakan.");
    }

    private function seedTesterOpsional(): void
    {
        $createTester = filter_var(env('CREATE_TESTER_ACCOUNT', false), FILTER_VALIDATE_BOOLEAN);

        if (!$createTester) {
            return;
        }

        // Jangan buat tester di production
        if (app()->environment('production')) {
            $this->command?->warn('CREATE_TESTER_ACCOUNT=true diabaikan di environment production.');
            return;
        }

        $username = env('TESTER_USERNAME');
        $password = env('TESTER_PASSWORD');

        if (empty($username) || empty($password)) {
            $this->command?->warn('TESTER_USERNAME dan TESTER_PASSWORD belum diset — akun tester tidak dibuat.');
            return;
        }

        if (strlen($password) < 8) {
            $this->command?->error('TESTER_PASSWORD harus minimal 8 karakter — akun tester tidak dibuat.');
            return;
        }

        Admin::firstOrCreate(
            ['username' => $username],
            [
                'password'     => $password,
                'nama_lengkap' => 'Akun Tester',
            ]
        );

        $this->command?->info("Akun tester '{$username}' siap digunakan.");
    }
}
