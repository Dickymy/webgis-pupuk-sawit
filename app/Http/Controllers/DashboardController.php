<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationFeasibilityStatus;
use App\Enums\PlantConditionStatus;
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

            // Status kondisi tanaman (sumber utama warna polygon dan filter)
            $statusKondisi = $rbs?->status_kondisi_tanaman ?? 'BELUM_DIOBSERVASI';
            $statusKelayakan = $rbs?->status_kelayakan_aplikasi;

            return [
                'id' => $blok->id,
                'nama_blok' => $blok->nama_blok,
                'nama_pemilik' => $blok->nama_pemilik,
                'luas_ha' => $blok->luas_ha,
                'sph' => $blok->sph,
                'umur_tanaman' => $blok->umur_tanaman,
                'fase_tanaman' => $blok->fase_label,
                'geojson' => json_decode($blok->koordinat_geojson, true),
                // Status baru (v2.5) — sumber utama
                'status_kondisi' => $statusKondisi,
                'status_kondisi_label' => PlantConditionStatus::labelFromValue($statusKondisi),
                'status_kelayakan' => $statusKelayakan,
                'status_kelayakan_label' => ApplicationFeasibilityStatus::labelFromValue($statusKelayakan),
                // Data analisis
                'masalah_rbs' => $rbs?->masalah_teridentifikasi ?? [],
                'pupuk_rbs' => $rbs?->rekomendasi_pupuk ?? [],
                'saran_rbs' => $rbs?->saran_tindakan_utama ?? '',
                'tgl_analisis_rbs' => $rbs?->tanggal_analisis?->format('d/m/Y') ?? '-',
                'jumlah_rule' => $rbs?->jumlah_rule_terpicu ?? 0,
                // Dosis & kebutuhan
                'urea_aplikasi_saat_ini' => $rbs?->urea_aplikasi_saat_ini,
                'kcl_aplikasi_saat_ini' => $rbs?->kcl_aplikasi_saat_ini,
                'urea_total_estimasi_tahunan' => $rbs?->urea_total_estimasi_tahunan,
                'kcl_total_estimasi_tahunan' => $rbs?->kcl_total_estimasi_tahunan,
                'active_stage' => $rbs?->active_stage,
                'status_stage' => $rbs?->status_stage,
                // Pahan-v2 indicators
                'skor_keandalan' => $rbs?->kelengkapan_data_score,
                'kategori_keandalan' => $rbs?->kategori_keandalan,
                'versi_mesin' => $rbs?->versi_mesin_rekomendasi,
                // Flags
                'perlu_verifikasi_fase' => ($blok->fase_tanaman === null && $blok->umur_tanaman === 3),
                'analisis_kedaluwarsa' => $rbs?->tanggal_analisis?->diffInDays(now()) > 90,
                'belum_ada_kondisi' => ! $blok->kondisiTerbaru,
            ];
        });

        // Stats — sepenuhnya berdasarkan status_kondisi_tanaman dan status_kelayakan_aplikasi
        $stats = [
            'total_blok' => $blokLahans->count(),
            'total_luas' => $blokLahans->sum('luas_ha'),
            'sudah_analisis' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru)->count(),
            'belum_kondisi' => $blokLahans->filter(fn ($b) => ! $b->kondisiTerbaru)->count(),
            // Statistik kondisi tanaman
            'gejala_berat' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'GEJALA_BERAT')->count(),
            'terindikasi_defisiensi' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI')->count(),
            'terindikasi_defisiensi_ringan' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI_RINGAN')->count(),
            'kondisi_normal' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'NORMAL_VISUAL')->count(),
            'perlu_verifikasi' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'PERLU_VERIFIKASI')->count(),
            'belum_diobservasi' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'BELUM_DIOBSERVASI' || ! $b->rekomendasiRbsTerbaru)->count(),
            // Statistik kelayakan aplikasi
            'layak_dijadwalkan' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'LAYAK_DIJADWALKAN')->count(),
            'terlambat' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'TERLAMBAT_PERLU_DIJADWALKAN')->count(),
            'tunda_total' => $blokLahans->filter(fn ($b) => in_array($b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi, ['TUNDA_HUJAN_RENDAH', 'TUNDA_HUJAN_TINGGI', 'TUNDA_INTERVAL', 'PERLU_PERBAIKAN_DRAINASE', 'TUNDA_DRAINASE']))->count(),
        ];

        // Delta stats bulan lalu
        $bulanLalu = now()->subMonth();
        $rbsBulanLalu = RekomendasiRbs::where('tanggal_analisis', '>=', $bulanLalu->startOfMonth()->toDateString())
            ->where('tanggal_analisis', '<=', $bulanLalu->endOfMonth()->toDateString())
            ->get();

        $statsBulanLalu = [
            'gejala_berat' => $rbsBulanLalu->where('status_kondisi_tanaman', 'GEJALA_BERAT')->count(),
            'terindikasi_defisiensi' => $rbsBulanLalu->where('status_kondisi_tanaman', 'TERINDIKASI_DEFISIENSI')->count(),
        ];

        // Blok perlu perhatian
        $blokPerluPerhatian = $blokLahans->filter(function ($blok) {
            if ($blok->kondisiTerbaru && ! $blok->rekomendasiRbsTerbaru) {
                return true;
            }
            if ($blok->rekomendasiRbsTerbaru && $blok->rekomendasiRbsTerbaru->tanggal_analisis->diffInDays(now()) > 90) {
                return true;
            }

            return false;
        })->values();

        return view('dashboard.index', compact('mapData', 'stats', 'statsBulanLalu', 'blokPerluPerhatian'));
    }
}
