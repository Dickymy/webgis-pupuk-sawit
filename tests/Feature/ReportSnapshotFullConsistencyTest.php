<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Test konsistensi penggunaan snapshot pada laporan (Pahan v2.7 — 4.11).
 *
 * Laporan web dan PDF harus menggunakan snapshot sebagai sumber utama:
 * - umur_tanaman_snapshot
 * - fase_tanaman_snapshot
 * - luas_ha_snapshot
 * - sph_snapshot
 * - jumlah_pokok_snapshot
 *
 * Fallback ke data blok terkini hanya untuk legacy tanpa snapshot.
 */
class ReportSnapshotFullConsistencyTest extends TestCase
{
    /**
     * Laporan show view menggunakan snapshot untuk luas/SPH/pokok.
     */
    public function test_laporan_show_uses_snapshot_for_luas(): void
    {
        $file = resource_path('views/laporan/show.blade.php');
        $content = File::get($file);

        $this->assertStringContainsString('luas_ha_snapshot', $content,
            'Laporan show harus menggunakan luas_ha_snapshot');
        $this->assertStringContainsString('sph_snapshot', $content,
            'Laporan show harus menggunakan sph_snapshot');
        $this->assertStringContainsString('jumlah_pokok_snapshot', $content,
            'Laporan show harus menggunakan jumlah_pokok_snapshot');
    }

    /**
     * Laporan show view menggunakan snapshot untuk umur/fase pada Kriteria Agronomis.
     */
    public function test_laporan_show_uses_snapshot_for_umur_fase(): void
    {
        $file = resource_path('views/laporan/show.blade.php');
        $content = File::get($file);

        $this->assertStringContainsString('umur_tanaman_snapshot', $content,
            'Laporan show harus menggunakan umur_tanaman_snapshot');
        $this->assertStringContainsString('fase_tanaman_snapshot', $content,
            'Laporan show harus menggunakan fase_tanaman_snapshot');
    }

    /**
     * PDF template menggunakan snapshot.
     */
    public function test_pdf_uses_snapshot(): void
    {
        $file = resource_path('views/laporan/pdf.blade.php');
        $content = File::get($file);

        $this->assertStringContainsString('luas_ha_snapshot', $content,
            'PDF harus menggunakan luas_ha_snapshot');
        $this->assertStringContainsString('sph_snapshot', $content,
            'PDF harus menggunakan sph_snapshot');
        $this->assertStringContainsString('jumlah_pokok_snapshot', $content,
            'PDF harus menggunakan jumlah_pokok_snapshot');
        $this->assertStringContainsString('umur_tanaman_snapshot', $content,
            'PDF harus menggunakan umur_tanaman_snapshot');
    }

    /**
     * RBS partial menggunakan snapshot untuk metode aplikasi.
     */
    public function test_rbs_partial_uses_snapshot_for_metode(): void
    {
        $file = resource_path('views/rbs/partials/_hasil_rbs.blade.php');
        $content = File::get($file);

        // Pahan v2.7: Metode harus memakai umur/fase snapshot
        $this->assertStringContainsString('umur_tanaman_snapshot', $content,
            'RBS partial harus menggunakan umur_tanaman_snapshot untuk metode aplikasi');
        $this->assertStringContainsString('fase_tanaman_snapshot', $content,
            'RBS partial harus menggunakan fase_tanaman_snapshot untuk metode aplikasi');
    }

    /**
     * PDF menampilkan riwayat realisasi pemupukan.
     */
    public function test_pdf_contains_realisasi_history_section(): void
    {
        $file = resource_path('views/laporan/pdf.blade.php');
        $content = File::get($file);

        $this->assertStringContainsString('RIWAYAT REALISASI PEMUPUKAN', $content,
            'PDF harus menampilkan bagian Riwayat Realisasi Pemupukan');
        $this->assertStringContainsString('realisasis', $content,
            'PDF harus mengiterasi data realisasis');
    }

    /**
     * Laporan show view tidak menggunakan status_kebutuhan_dominan untuk warna banner.
     */
    public function test_laporan_banner_uses_kondisi_tanaman_not_legacy(): void
    {
        $file = resource_path('views/laporan/show.blade.php');
        $content = File::get($file);

        // Banner color should use status_kondisi_tanaman
        $this->assertStringContainsString('status_kondisi_tanaman', $content);

        // The $sc (status color) match should NOT reference 'Darurat', 'Segera' etc.
        // Check that the catatan style section uses status_kondisi_tanaman
        $this->assertStringContainsString('match($rekomendasiRbs->status_kondisi_tanaman)', $content,
            'Catatan style harus menggunakan status_kondisi_tanaman');
    }
}
