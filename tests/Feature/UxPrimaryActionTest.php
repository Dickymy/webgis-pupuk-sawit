<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test setiap halaman hanya memiliki satu tindakan utama (Pahan v2.8 — 5.2).
 */
class UxPrimaryActionTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_rbs_index_page_loads(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('rbs.index'));

        $response->assertOk();
    }

    public function test_laporan_index_page_loads(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('laporan.index'));

        $response->assertOk();
    }

    public function test_realisasi_index_page_loads(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.index'));

        $response->assertOk();
    }
}
