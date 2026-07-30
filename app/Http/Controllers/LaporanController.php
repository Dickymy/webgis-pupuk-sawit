<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use App\Services\ObservationCompletenessService;
use App\Services\RealisasiEligibilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function __construct(
        private RealisasiEligibilityService $eligibilityService,
        private ObservationCompletenessService $completenessService,
    ) {}

    public function index(Request $request)
    {
        $isCompletedProgramView = (string) $request->query('status_program') === ProgramPemupukan::STATUS_SELESAI;

        $query = RekomendasiRbs::with(['blokLahan.anggota', 'admin', 'kondisiLahan', 'programPemupukan.realisasiPemupukans'])
            ->latest('tanggal_analisis');

        // Filter histori: default hanya latest
        if (! $request->filled('status_program')
            && ! $request->filled('tahun_program')
            && (! $request->filled('histori') || $request->histori !== 'semua')) {
            $query->where('is_latest', true);
        }

        // Pahan v2.8: Filter berdasarkan status baru (bukan legacy)
        if ($request->filled('status_kondisi_tanaman')) {
            $query->where('status_kondisi_tanaman', $request->status_kondisi_tanaman);
        }

        if ($request->filled('status_kelayakan_aplikasi')) {
            $query->where('status_kelayakan_aplikasi', $request->status_kelayakan_aplikasi);
        }

        if ($request->filled('status_program') || $request->filled('tahun_program')) {
            $query->whereHas('programPemupukan', function ($q) use ($request) {
                if ($request->filled('status_program')) {
                    $q->where('status_program', $request->status_program);
                }
                if ($request->filled('tahun_program')) {
                    $q->where('tahun_program', (int) $request->tahun_program);
                }
            });
        }

        // Filter by anggota
        if ($request->filled('anggota_id')) {
            $query->whereHas('blokLahan', function ($q) use ($request) {
                $q->where('anggota_id', $request->anggota_id);
            });
        }

        // Filter by blok lahan
        if ($request->filled('blok_lahan_id')) {
            $query->where('blok_lahan_id', $request->blok_lahan_id);
        }

        $rekap = $query->get();
        if ($request->filled('status_program') || $request->filled('tahun_program')) {
            $rekap = $rekap->unique('program_pemupukan_id')->values();
        }

        $rekap->each(function ($rekomendasi) {
            $eligibility = $this->eligibilityService->evaluate($rekomendasi);
            $gunakanStatusDinamis = $rekomendasi->is_latest
                && $rekomendasi->program_pemupukan_id
                && ! empty($eligibility['status_stage']);
            $statusOperasional = $gunakanStatusDinamis
                ? $eligibility['status_stage']
                : $rekomendasi->status_stage;
            $ureaOperasional = $gunakanStatusDinamis
                ? ($eligibility['urea_rencana_kg'] ?? 0)
                : ($rekomendasi->urea_aplikasi_saat_ini ?? 0);
            $kclOperasional = $gunakanStatusDinamis
                ? ($eligibility['kcl_rencana_kg'] ?? 0)
                : ($rekomendasi->kcl_aplikasi_saat_ini ?? 0);
            $bolehMencatat = $gunakanStatusDinamis
                ? ($eligibility['boleh_mencatat'] ?? false)
                : in_array($statusOperasional, [
                    CurrentApplicationCalculator::TAHAP_1_SIAP,
                    CurrentApplicationCalculator::TAHAP_1_SEBAGIAN,
                    CurrentApplicationCalculator::TAHAP_2_SIAP,
                ], true) && ($ureaOperasional > 0 || $kclOperasional > 0);

            $rekomendasi->setAttribute('operational_boleh_mencatat', $bolehMencatat);
            $rekomendasi->setAttribute('status_stage_operasional', $statusOperasional);
            $rekomendasi->setAttribute('urea_operasional', $ureaOperasional);
            $rekomendasi->setAttribute('kcl_operasional', $kclOperasional);

            $realisasiAktif = $rekomendasi->programPemupukan
                ? $rekomendasi->programPemupukan->realisasiPemupukans
                    ->where('status_realisasi', '!=', RealisasiPemupukan::STATUS_BATAL)
                : collect();
            $rekomendasi->setAttribute('urea_terealisasi', round((float) $realisasiAktif->sum('urea_realisasi_kg'), 2));
            $rekomendasi->setAttribute('kcl_terealisasi', round((float) $realisasiAktif->sum('kcl_realisasi_kg'), 2));
        });

        if ($request->filled('status_stage')) {
            $rekap = $rekap->filter(
                fn ($r) => $r->status_stage_operasional === $request->status_stage
            )->values();
        }

        // Group by anggota
        $grouped = $rekap->groupBy(function ($r) {
            return $r->blokLahan->anggota_id ?? 0;
        });

        // Build structured data per anggota
        $laporanPerAnggota = $grouped->map(function ($items, $anggotaId) use ($isCompletedProgramView) {
            $anggota = $items->first()->blokLahan->anggota;

            // Pahan v2.8: Subtotal berdasarkan urea_aplikasi_saat_ini dan kcl_aplikasi_saat_ini
            // dari blok yang siap (status_stage izinkan realisasi)
            $blokSiap = $items->filter(
                fn ($r) => (bool) $r->operational_boleh_mencatat
            );
            $blokRingkasan = $isCompletedProgramView ? $items : $blokSiap;

            $latestAnalisis = $items->max(fn ($r) => $r->tanggal_analisis?->timestamp ?? 0);

            return [
                'anggota' => $anggota,
                'items' => $items,
                'jumlah_blok' => $items->count(),
                'total_luas' => $items->sum(fn ($r) => $r->blokLahan->luas_ha),
                'subtotal_urea' => $isCompletedProgramView
                    ? $items->sum('urea_terealisasi')
                    : $blokSiap->sum('urea_operasional'),
                'subtotal_kcl' => $isCompletedProgramView
                    ? $items->sum('kcl_terealisasi')
                    : $blokSiap->sum('kcl_operasional'),
                'blok_layak' => $blokSiap->count(),
                'blok_ringkasan' => $blokRingkasan->count(),
                'latest_analisis' => $latestAnalisis,
            ];
        })->sortByDesc('latest_analisis')->values();

        // Pahan v2.8: Grand total — berdasarkan urea_aplikasi_saat_ini / kcl_aplikasi_saat_ini
        // dari blok yang status_stage siap
        $rekapSiap = $rekap->filter(fn ($r) => (bool) $r->operational_boleh_mencatat);
        $totalUrea = $isCompletedProgramView
            ? $rekap->sum('urea_terealisasi')
            : $rekapSiap->sum('urea_operasional');
        $totalKcl = $isCompletedProgramView
            ? $rekap->sum('kcl_terealisasi')
            : $rekapSiap->sum('kcl_operasional');
        $karungUrea = $totalUrea > 0 ? (int) ceil($totalUrea / 50) : 0;
        $karungKcl = $totalKcl > 0 ? (int) ceil($totalKcl / 50) : 0;
        $blokLayakTotal = $rekapSiap->count();
        $blokRingkasanTotal = $isCompletedProgramView ? $rekap->count() : $blokLayakTotal;

        // Dropdown data
        $anggotas = Anggota::orderBy('nama')->get();
        $blokFilter = $request->filled('anggota_id')
            ? BlokLahan::where('anggota_id', $request->anggota_id)->orderBy('nama_blok')->get()
            : collect();
        $tahunProgram = (int) $request->query('tahun_program', now()->year);
        $programStats = [
            'tahun' => $tahunProgram,
            'semua' => ProgramPemupukan::where('tahun_program', $tahunProgram)->count(),
            'aktif' => ProgramPemupukan::where('tahun_program', $tahunProgram)
                ->where('status_program', ProgramPemupukan::STATUS_AKTIF)->count(),
            'selesai' => ProgramPemupukan::where('tahun_program', $tahunProgram)
                ->where('status_program', ProgramPemupukan::STATUS_SELESAI)->count(),
        ];

        return view('laporan.index', compact(
            'rekap', 'laporanPerAnggota', 'totalUrea', 'totalKcl',
            'karungUrea', 'karungKcl', 'blokLayakTotal', 'blokRingkasanTotal',
            'isCompletedProgramView', 'anggotas', 'blokFilter', 'programStats'
        ));
    }

    public function show(RekomendasiRbs $rekomendasiRbs)
    {
        $rekomendasiRbs->load(['blokLahan.anggota', 'kondisiLahan', 'admin']);
        $observationCompleteness = $rekomendasiRbs->kondisiLahan
            ? $this->completenessService->evaluate($rekomendasiRbs->kondisiLahan)
            : null;

        return view('laporan.show', compact('rekomendasiRbs', 'observationCompleteness'));
    }

    public function exportPdf(RekomendasiRbs $rekomendasiRbs)
    {
        $rekomendasiRbs->load(['blokLahan.anggota', 'kondisiLahan', 'admin', 'programPemupukan']);
        $observationCompleteness = $rekomendasiRbs->kondisiLahan
            ? $this->completenessService->evaluate($rekomendasiRbs->kondisiLahan)
            : null;

        // Histori PDF mengikuti program tahunan agar realisasi dari analisis sebelumnya
        // dalam program yang sama tidak hilang dari laporan terbaru.
        $realisasiQuery = $rekomendasiRbs->programPemupukan
            ? $rekomendasiRbs->programPemupukan->realisasiPemupukans()
            : $rekomendasiRbs->realisasiPemupukans();

        $realisasis = $realisasiQuery
            ->orderBy('tahap')
            ->orderBy('tanggal_realisasi')
            ->get();

        $pdf = Pdf::loadView('laporan.pdf', compact('rekomendasiRbs', 'realisasis', 'observationCompleteness'));
        $pdf->setPaper('a4', 'portrait');

        $filename = 'Laporan_'.str_replace(' ', '_', $rekomendasiRbs->blokLahan->nama_blok).'_'.$rekomendasiRbs->tanggal_analisis->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
