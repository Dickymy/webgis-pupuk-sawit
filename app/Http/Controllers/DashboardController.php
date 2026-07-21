<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationFeasibilityStatus;
use App\Enums\PlantConditionStatus;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\ProgramPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;

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

            $statusKondisi = $rbs?->status_kondisi_tanaman ?? 'BELUM_DIOBSERVASI';
            $statusKelayakan = $rbs?->status_kelayakan_aplikasi;

            $tindakanBerikutnya = '';
            if ($rbs) {
                $tindakanBerikutnya = match ($rbs->status_stage) {
                    'TAHAP_1_SIAP' => 'Catat realisasi Tahap 1',
                    'TAHAP_1_SEBAGIAN' => 'Lanjutkan realisasi Tahap 1',
                    'MENUNGGU_INTERVAL' => 'Menunggu 60 hari',
                    'MENUNGGU_KELAYAKAN' => 'Menunggu kelayakan',
                    'TAHAP_2_SIAP' => 'Catat realisasi Tahap 2',
                    'SELESAI_TAHUNAN' => 'Selesai tahun ini',
                    default => $rbs->alasan_tahap ?? '',
                };
            }

            return [
                'id' => $blok->id,
                'nama_blok' => $blok->nama_blok,
                'nama_pemilik' => $blok->nama_pemilik,
                'luas_ha' => $blok->luas_ha,
                'sph' => $blok->sph,
                'umur_tanaman' => $blok->umur_tanaman,
                'fase_tanaman' => $blok->fase_label,
                'geojson' => json_decode($blok->koordinat_geojson, true),
                'status_kondisi' => $statusKondisi,
                'status_kondisi_label' => PlantConditionStatus::labelFromValue($statusKondisi),
                'status_kelayakan' => $statusKelayakan,
                'status_kelayakan_label' => ApplicationFeasibilityStatus::labelFromValue($statusKelayakan),
                'masalah_rbs' => $rbs?->masalah_teridentifikasi ?? [],
                'pupuk_rbs' => $rbs?->rekomendasi_pupuk ?? [],
                'saran_rbs' => $rbs?->saran_tindakan_utama ?? '',
                'tgl_analisis_rbs' => $rbs?->tanggal_analisis?->format('d/m/Y') ?? '-',
                'jumlah_rule' => $rbs?->jumlah_rule_terpicu ?? 0,
                'urea_aplikasi_saat_ini' => $rbs?->urea_aplikasi_saat_ini,
                'kcl_aplikasi_saat_ini' => $rbs?->kcl_aplikasi_saat_ini,
                'urea_total_estimasi_tahunan' => $rbs?->urea_total_estimasi_tahunan,
                'kcl_total_estimasi_tahunan' => $rbs?->kcl_total_estimasi_tahunan,
                'active_stage' => $rbs?->active_stage,
                'status_stage' => $rbs?->status_stage,
                'tindakan_berikutnya' => $tindakanBerikutnya,
                'skor_keandalan' => $rbs?->kelengkapan_data_score,
                'kategori_keandalan' => $rbs?->kategori_keandalan,
                'versi_mesin' => $rbs?->versi_mesin_rekomendasi,
                'analisis_kedaluwarsa' => $rbs?->tanggal_analisis?->diffInDays(now()) > 90,
                'belum_ada_kondisi' => ! $blok->kondisiTerbaru,
            ];
        });

        // Stats sederhana sesuai revisi
        $stagesSiap = [
            CurrentApplicationCalculator::TAHAP_1_SIAP,
            CurrentApplicationCalculator::TAHAP_1_SEBAGIAN,
            CurrentApplicationCalculator::TAHAP_2_SIAP,
        ];

        $stats = [
            'total_anggota' => Anggota::count(),
            'total_blok' => $blokLahans->count(),
            'total_luas' => $blokLahans->sum('luas_ha'),
            'belum_kondisi' => $blokLahans->filter(fn ($b) => ! $b->kondisiTerbaru)->count(),
            'siap_dipupuk' => $blokLahans->filter(fn ($b) => in_array($b->rekomendasiRbsTerbaru?->status_stage, $stagesSiap))->count(),
            'menunggu_interval' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_stage === CurrentApplicationCalculator::MENUNGGU_INTERVAL)->count(),
            'program_selesai' => ProgramPemupukan::where('status_program', ProgramPemupukan::STATUS_SELESAI)->count(),
            // Statistik kondisi tanaman
            'gejala_berat' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'GEJALA_BERAT')->count(),
            'terindikasi_defisiensi' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI')->count(),
            'terindikasi_defisiensi_ringan' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI_RINGAN')->count(),
            'kondisi_normal' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'NORMAL_VISUAL')->count(),
            'sudah_analisis' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru)->count(),
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

        // Blok perlu tindakan
        $blokPerluTindakan = $blokLahans->filter(function ($blok) {
            // Belum punya kondisi
            if (! $blok->kondisiTerbaru) {
                return true;
            }
            // Punya kondisi tapi belum dianalisis
            if ($blok->kondisiTerbaru && ! $blok->rekomendasiRbsTerbaru) {
                return true;
            }
            // Analisis kedaluwarsa (>90 hari)
            if ($blok->rekomendasiRbsTerbaru && $blok->rekomendasiRbsTerbaru->tanggal_analisis->diffInDays(now()) > 90) {
                return true;
            }

            return false;
        })->values();

        return view('dashboard.index', compact('mapData', 'stats', 'statsBulanLalu', 'blokPerluTindakan'));
    }
}
