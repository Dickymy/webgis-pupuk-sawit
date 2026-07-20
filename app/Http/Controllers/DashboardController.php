<?php

namespace App\Http\Controllers;

use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;

class DashboardController extends Controller
{
    public function index()
    {
        $blokLahans = BlokLahan::with([
            'anggota',
            'rekomendasiRbsTerbaru',
            'kondisiTerbaru',
        ])->get();

        $mapData = $blokLahans->map(function ($blok) {
            $rbs = $blok->rekomendasiRbsTerbaru;
            $statusDb = $rbs?->status_kebutuhan_dominan ?? 'Belum Dianalisis';

            return [
                'id' => $blok->id,
                'nama_blok' => $blok->nama_blok,
                'nama_pemilik' => $blok->nama_pemilik,
                'luas_ha' => $blok->luas_ha,
                'sph' => $blok->sph,
                'umur_tanaman' => $blok->umur_tanaman,
                'fase_tanaman' => $blok->fase_label,
                'geojson' => json_decode($blok->koordinat_geojson, true),
                'status_rbs' => $statusDb,
                'status_label' => RekomendasiRbs::labelStatus($statusDb),
                'masalah_rbs' => $rbs?->masalah_teridentifikasi ?? [],
                'pupuk_rbs' => $rbs?->rekomendasi_pupuk ?? [],
                'saran_rbs' => $rbs?->saran_tindakan_utama ?? '',
                'tgl_analisis_rbs' => $rbs?->tanggal_analisis?->format('d/m/Y') ?? '-',
                'jumlah_rule' => $rbs?->jumlah_rule_terpicu ?? 0,
                'dosis_urea' => $rbs?->dosis_urea,
                'dosis_kcl' => $rbs?->dosis_kcl,
                'total_urea' => $rbs?->total_urea,
                'total_kcl' => $rbs?->total_kcl,
                // Pahan-v2 indicators
                'urea_estimasi' => $rbs?->urea_estimasi_kg_per_pokok_tahun,
                'kcl_estimasi' => $rbs?->kcl_estimasi_kg_per_pokok_tahun,
                'skor_keandalan' => $rbs?->kelengkapan_data_score,
                'kategori_keandalan' => $rbs?->kategori_keandalan,
                'status_kondisi' => $rbs?->status_kondisi_tanaman,
                'status_kelayakan' => $rbs?->status_kelayakan_aplikasi,
                'versi_mesin' => $rbs?->versi_mesin_rekomendasi,
                // Flags
                'perlu_verifikasi_fase' => ($blok->fase_tanaman === null && $blok->umur_tanaman === 3),
                'analisis_kedaluwarsa' => $rbs?->tanggal_analisis?->diffInDays(now()) > 90,
                'belum_ada_kondisi' => ! $blok->kondisiTerbaru,
            ];
        });

        // Stats saat ini — berdasarkan status_kondisi_tanaman dan status_kelayakan_aplikasi
        $stats = [
            'total_blok' => $blokLahans->count(),
            'total_luas' => $blokLahans->sum('luas_ha'),
            'sudah_analisis' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru)->count(),
            'belum_kondisi' => $blokLahans->filter(fn ($b) => ! $b->kondisiTerbaru)->count(),
            // Statistik kondisi tanaman (dari status_kondisi_tanaman)
            'gejala_berat' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'GEJALA_BERAT')->count(),
            'terindikasi_defisiensi' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI')->count(),
            'terindikasi_defisiensi_ringan' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI_RINGAN')->count(),
            'kondisi_normal' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'NORMAL_VISUAL')->count(),
            'perlu_verifikasi' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'PERLU_VERIFIKASI')->count(),
            'belum_diobservasi' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'BELUM_DIOBSERVASI')->count(),
            // Statistik kelayakan aplikasi (dari status_kelayakan_aplikasi)
            'layak_dijadwalkan' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'LAYAK_DIJADWALKAN')->count(),
            'tunda_hujan_rendah' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'TUNDA_HUJAN_RENDAH')->count(),
            'tunda_hujan_tinggi' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'TUNDA_HUJAN_TINGGI')->count(),
            'tunda_interval' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'TUNDA_INTERVAL')->count(),
            'tunda_drainase' => $blokLahans->filter(fn ($b) => in_array($b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi, ['PERLU_PERBAIKAN_DRAINASE', 'TUNDA_DRAINASE']))->count(),
            'perlu_verifikasi_data' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA')->count(),
            'terlambat' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'TERLAMBAT_PERLU_DIJADWALKAN')->count(),
            // Legacy stats for backwards compatibility
            'darurat' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'GEJALA_BERAT')->count(),
            'segera' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI')->count(),
        ];

        // Delta stats bulan lalu — berdasarkan status baru
        $bulanLalu = now()->subMonth();
        $rbsBulanLalu = RekomendasiRbs::where('tanggal_analisis', '>=', $bulanLalu->startOfMonth()->toDateString())
            ->where('tanggal_analisis', '<=', $bulanLalu->endOfMonth()->toDateString())
            ->get();

        $statsBulanLalu = [
            'gejala_berat' => $rbsBulanLalu->where('status_kondisi_tanaman', 'GEJALA_BERAT')->count(),
            'terindikasi_defisiensi' => $rbsBulanLalu->where('status_kondisi_tanaman', 'TERINDIKASI_DEFISIENSI')->count(),
            // Legacy compatibility
            'darurat' => $rbsBulanLalu->where('status_kondisi_tanaman', 'GEJALA_BERAT')->count(),
            'segera' => $rbsBulanLalu->where('status_kondisi_tanaman', 'TERINDIKASI_DEFISIENSI')->count(),
        ];

        // Blok perlu perhatian (E1): belum dianalisis atau > 90 hari
        $blokPerluPerhatian = $blokLahans->filter(function ($blok) {
            // Punya kondisi tapi belum pernah dianalisis
            if ($blok->kondisiTerbaru && ! $blok->rekomendasiRbsTerbaru) {
                return true;
            }
            // Analisis terakhir > 90 hari
            if ($blok->rekomendasiRbsTerbaru && $blok->rekomendasiRbsTerbaru->tanggal_analisis->diffInDays(now()) > 90) {
                return true;
            }

            return false;
        })->values();

        return view('dashboard.index', compact('mapData', 'stats', 'statsBulanLalu', 'blokPerluPerhatian'));
    }
}
