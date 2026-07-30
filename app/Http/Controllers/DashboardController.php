<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationFeasibilityStatus;
use App\Enums\PlantConditionStatus;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\ProgramPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use App\Services\RealisasiEligibilityService;

class DashboardController extends Controller
{
    public function __construct(
        private RealisasiEligibilityService $eligibilityService,
    ) {}

    public function index()
    {
        $blokLahans = BlokLahan::with([
            'anggota',
            'rekomendasiRbsTerbaru',
            'kondisiTerbaru',
        ])->get();

        $blokLahans->each(fn ($blok) => $blok->setAttribute(
            'operational_eligibility',
            $this->operationalEligibility($blok)
        ));

        $mapData = $blokLahans->map(function ($blok) {
            $rbs = $blok->rekomendasiRbsTerbaru;
            $eligibility = $blok->operational_eligibility;

            $statusKondisi = $rbs?->status_kondisi_tanaman ?? 'BELUM_DIOBSERVASI';
            $statusKelayakan = $rbs?->status_kelayakan_aplikasi;
            $statusStage = $eligibility['status_stage'] ?? $rbs?->status_stage;
            $activeStage = $eligibility['active_stage'] ?? $rbs?->active_stage;
            [$statusPeta, $statusPetaLabel] = $this->mapActionStatus($blok, $rbs, $eligibility);

            $tindakanBerikutnya = '';
            if ($rbs) {
                $tindakanBerikutnya = match ($statusStage) {
                    'TAHAP_1_SIAP' => 'Catat realisasi Tahap 1',
                    'TAHAP_1_SEBAGIAN' => 'Lanjutkan realisasi Tahap 1',
                    'MENUNGGU_INTERVAL' => 'Menunggu '.config('fertilization.window.min_interval_days', 120).' hari',
                    'MENUNGGU_KELAYAKAN' => 'Menunggu kondisi lapangan',
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
                'status_peta' => $statusPeta,
                'status_peta_label' => $statusPetaLabel,
                'status_kondisi' => $statusKondisi,
                'status_kondisi_label' => PlantConditionStatus::labelFromValue($statusKondisi),
                'status_kelayakan' => $statusKelayakan,
                'status_kelayakan_label' => ApplicationFeasibilityStatus::labelFromValue($statusKelayakan),
                'masalah_rbs' => $rbs?->masalah_teridentifikasi ?? [],
                'pupuk_rbs' => $rbs?->rekomendasi_pupuk ?? [],
                'saran_rbs' => $rbs?->saran_tindakan_utama ?? '',
                'tgl_analisis_rbs' => $rbs?->tanggal_analisis?->format('d/m/Y') ?? '-',
                'jumlah_rule' => $rbs?->jumlah_rule_terpicu ?? 0,
                'urea_aplikasi_saat_ini' => $eligibility['urea_rencana_kg'] ?? $rbs?->urea_aplikasi_saat_ini,
                'kcl_aplikasi_saat_ini' => $eligibility['kcl_rencana_kg'] ?? $rbs?->kcl_aplikasi_saat_ini,
                'urea_total_estimasi_tahunan' => $rbs?->urea_total_estimasi_tahunan,
                'kcl_total_estimasi_tahunan' => $rbs?->kcl_total_estimasi_tahunan,
                'active_stage' => $activeStage,
                'status_stage' => $statusStage,
                'rekomendasi_id' => $rbs?->id,
                'kondisi_id' => $blok->kondisiTerbaru?->id,
                'data_belum_cukup' => $this->recommendationNeedsObservation($rbs),
                'tindakan_berikutnya' => $tindakanBerikutnya,
                'skor_keandalan' => $rbs?->kelengkapan_data_score,
                'kategori_keandalan' => $rbs?->kategori_keandalan,
                'versi_mesin' => $rbs?->versi_mesin_rekomendasi,
                'belum_ada_kondisi' => ! $blok->kondisiTerbaru,
            ];
        });

        $stats = [
            'total_anggota' => Anggota::count(),
            'total_blok' => $blokLahans->count(),
            'total_luas' => $blokLahans->sum('luas_ha'),
            'belum_kondisi' => $blokLahans->filter(fn ($b) => ! $b->kondisiTerbaru)->count(),
            'siap_dipupuk' => $blokLahans->filter(
                fn ($b) => (bool) ($b->operational_eligibility['boleh_mencatat'] ?? false)
            )->count(),
            'menunggu_interval' => $blokLahans->filter(
                fn ($b) => ($b->operational_eligibility['status_stage'] ?? null) === CurrentApplicationCalculator::MENUNGGU_INTERVAL
            )->count(),
            'program_selesai' => ProgramPemupukan::where('tahun_program', now()->year)
                ->where('status_program', ProgramPemupukan::STATUS_SELESAI)->count(),
            // Statistik kondisi tanaman
            'gejala_berat' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'GEJALA_BERAT')->count(),
            'terindikasi_defisiensi' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI')->count(),
            'terindikasi_defisiensi_ringan' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI_RINGAN')->count(),
            'kondisi_normal' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'NORMAL_VISUAL')->count(),
            'sudah_analisis' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru)->count(),
            'layak_dijadwalkan' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'LAYAK_DIJADWALKAN')->count(),
            'terlambat' => $blokLahans->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi === 'TERLAMBAT_PERLU_DIJADWALKAN')->count(),
            'tunda_total' => $blokLahans->filter(fn ($b) => in_array($b->rekomendasiRbsTerbaru?->status_kelayakan_aplikasi, ['TUNDA_HUJAN_RENDAH', 'TUNDA_HUJAN_TINGGI', 'TUNDA_TANAH_KERING', 'TUNDA_INTERVAL', 'PERLU_PERBAIKAN_DRAINASE', 'TUNDA_DRAINASE']))->count(),
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

        // Blok perlu tindakan (lebih lengkap — v2.9)
        $blokPerluTindakan = $blokLahans->filter(function ($blok) {
            // Belum punya kondisi
            if (! $blok->kondisiTerbaru) {
                return true;
            }
            // Punya kondisi tapi belum dianalisis
            if ($blok->kondisiTerbaru && ! $blok->rekomendasiRbsTerbaru) {
                return true;
            }
            if ($this->recommendationNeedsObservation($blok->rekomendasiRbsTerbaru)) {
                return true;
            }
            // Tahap siap (perlu dicatat realisasi)
            if ($blok->operational_eligibility['boleh_mencatat'] ?? false) {
                return true;
            }

            return false;
        })->values();

        return view('dashboard.index', compact('mapData', 'stats', 'statsBulanLalu', 'blokPerluTindakan'));
    }

    /**
     * Status peta menunjukkan tindakan yang perlu dilakukan, bukan diagnosis unsur hara.
     * Kondisi tanaman dan kesiapan pemupukan tetap tersedia sebagai informasi terpisah.
     *
     * @return array{0: string, 1: string}
     */
    private function mapActionStatus(BlokLahan $blok, ?RekomendasiRbs $rekomendasi, ?array $eligibility): array
    {
        if (! $blok->kondisiTerbaru || ! $rekomendasi || $this->recommendationNeedsObservation($rekomendasi)) {
            return ['BELUM_DIPERIKSA', 'Belum Diperiksa'];
        }

        if (in_array($rekomendasi->status_kondisi_tanaman, [
            'GEJALA_BERAT',
            'TERINDIKASI_DEFISIENSI',
            'TERINDIKASI_DEFISIENSI_RINGAN',
        ], true)) {
            return ['ADA_GEJALA', 'Ditemukan Gejala'];
        }

        if ((bool) ($eligibility['boleh_mencatat'] ?? false)) {
            return ['SIAP_DIPUPUK', 'Siap Dipupuk'];
        }

        return ['DITUNDA', 'Belum Saatnya Dipupuk'];
    }

    private function recommendationNeedsObservation(?RekomendasiRbs $rekomendasi): bool
    {
        return $rekomendasi
            && (in_array($rekomendasi->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
                || $rekomendasi->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA');
    }

    private function operationalEligibility(BlokLahan $blok): ?array
    {
        $rekomendasi = $blok->rekomendasiRbsTerbaru;
        if (! $rekomendasi) {
            return null;
        }

        $evaluated = $this->eligibilityService->evaluate($rekomendasi);
        if ($rekomendasi->program_pemupukan_id && ! empty($evaluated['status_stage'])) {
            return $evaluated;
        }

        return [
            'boleh_mencatat' => $rekomendasi->is_tahap_siap
                && (($rekomendasi->urea_aplikasi_saat_ini ?? 0) > 0
                    || ($rekomendasi->kcl_aplikasi_saat_ini ?? 0) > 0),
            'status_stage' => $rekomendasi->status_stage,
            'active_stage' => $rekomendasi->active_stage,
            'urea_rencana_kg' => $rekomendasi->urea_aplikasi_saat_ini ?? 0,
            'kcl_rencana_kg' => $rekomendasi->kcl_aplikasi_saat_ini ?? 0,
        ];
    }
}
