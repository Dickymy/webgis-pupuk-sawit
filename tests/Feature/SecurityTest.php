<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Route maintenance publik sudah dihapus.
     */
    public function test_no_public_setup_database_route(): void
    {
        $response = $this->get('/setup-database');
        $response->assertStatus(404);
    }

    public function test_no_public_seed_tester_route(): void
    {
        $response = $this->get('/seed-tester');
        $response->assertStatus(404);
    }

    public function test_no_public_fix_cache_route(): void
    {
        $response = $this->get('/fix-cache');
        $response->assertStatus(404);
    }

    /**
     * Route protected tidak dapat diakses tanpa login.
     */
    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_rbs_index_requires_authentication(): void
    {
        $response = $this->get('/rbs');
        $response->assertRedirect('/login');
    }

    public function test_blok_lahan_requires_authentication(): void
    {
        $response = $this->get('/blok-lahan');
        $response->assertRedirect('/login');
    }

    public function test_laporan_requires_authentication(): void
    {
        $response = $this->get('/laporan');
        $response->assertRedirect('/login');
    }

    /**
     * Login berhasil dengan kredensial benar.
     */
    public function test_login_with_valid_credentials(): void
    {
        Admin::create([
            'username'     => 'testadmin',
            'password'     => 'password123',
            'nama_lengkap' => 'Test Admin',
        ]);

        $response = $this->post('/login', [
            'username' => 'testadmin',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(Admin::where('username', 'testadmin')->first(), 'admin');
    }

    /**
     * Login gagal dengan password salah.
     */
    public function test_login_with_wrong_password(): void
    {
        Admin::create([
            'username'     => 'testadmin',
            'password'     => 'password123',
            'nama_lengkap' => 'Test Admin',
        ]);

        $response = $this->post('/login', [
            'username' => 'testadmin',
            'password' => 'wrongpassword',
        ]);

        // Aplikasi redirect back (ke halaman sebelumnya atau root)
        $response->assertRedirect();
        $this->assertGuest('admin');
    }
}
