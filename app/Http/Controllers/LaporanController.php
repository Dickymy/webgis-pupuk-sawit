<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $query = RekomendasiRbs::with(['blokLahan.anggota', 'admin', 'kondisiLahan'])
            ->latest('tanggal_analisis');

        // Filter histori: default hanya latest
        if (! $request->filled('histori') || $request->histori !== 'semua') {
            $query->where('is_latest', true);
        }

        // Pahan v2.8: Filter berdasarkan status baru (bukan legacy)
        if ($request->filled('status_kondisi_tanaman')) {
            $query->where('status_kondisi_tanaman', $request->status_kondisi_tanaman);
        }

        if ($request->filled('status_kelayakan_aplikasi')) {
            $query->where('status_kelayakan_aplikasi', $request->status_kelayakan_aplikasi);
        }

        if ($request->filled('status_stage')) {
            $query->where('status_stage', $request->status_stage);
        }

        if ($request->filled('status_program')) {
            $query->whereHas('programPemupukan', function ($q) use ($request) {
                $q->where('status_program', $request->status_program);
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

        // Group by anggota
        $grouped = $rekap->groupBy(function ($r) {
            return $r->blokLahan->anggota_id ?? 0;
        });

        // Pahan v2.8: Blok dianggap siap jika status_stage mengizinkan realisasi
        // DAN urea/kcl_aplikasi_saat_ini > 0
        $stagesSiap = [
            CurrentApplicationCalculator::TAHAP_1_SIAP,
            CurrentApplicationCalculator::TAHAP_1_SEBAGIAN,
            CurrentApplicationCalculator::TAHAP_2_SIAP,
        ];

        // Build structured data per anggota
        $laporanPerAnggota = $grouped->map(function ($items, $anggotaId) use ($stagesSiap) {
            $anggota = $items->first()->blokLahan->anggota;

            // Pahan v2.8: Subtotal berdasarkan urea_aplikasi_saat_ini dan kcl_aplikasi_saat_ini
            // dari blok yang siap (status_stage izinkan realisasi)
            $blokSiap = $items->filter(function ($r) use ($stagesSiap) {
                return in_array($r->status_stage, $stagesSiap)
                    && (($r->urea_aplikasi_saat_ini ?? 0) > 0 || ($r->kcl_aplikasi_saat_ini ?? 0) > 0);
            });

            $latestAnalisis = $items->max(fn ($r) => $r->tanggal_analisis?->timestamp ?? 0);

            return [
                'anggota' => $anggota,
                'items' => $items,
                'jumlah_blok' => $items->count(),
                'total_luas' => $items->sum(fn ($r) => $r->blokLahan->luas_ha),
                'subtotal_urea' => $blokSiap->sum('urea_aplikasi_saat_ini'),
                'subtotal_kcl' => $blokSiap->sum('kcl_aplikasi_saat_ini'),
                'blok_layak' => $blokSiap->count(),
                'latest_analisis' => $latestAnalisis,
            ];
        })->sortByDesc('latest_analisis')->values();

        // Pahan v2.8: Grand total — berdasarkan urea_aplikasi_saat_ini / kcl_aplikasi_saat_ini
        // dari blok yang status_stage siap
        $rekapSiap = $rekap->filter(function ($r) use ($stagesSiap) {
            return in_array($r->status_stage, $stagesSiap)
                && (($r->urea_aplikasi_saat_ini ?? 0) > 0 || ($r->kcl_aplikasi_saat_ini ?? 0) > 0);
        });
        $totalUrea = $rekapSiap->sum('urea_aplikasi_saat_ini');
        $totalKcl = $rekapSiap->sum('kcl_aplikasi_saat_ini');
        $karungUrea = $totalUrea > 0 ? (int) ceil($totalUrea / 50) : 0;
        $karungKcl = $totalKcl > 0 ? (int) ceil($totalKcl / 50) : 0;
        $blokLayakTotal = $rekapSiap->count();

        // Dropdown data
        $anggotas = Anggota::orderBy('nama')->get();
        $blokFilter = $request->filled('anggota_id')
            ? BlokLahan::where('anggota_id', $request->anggota_id)->orderBy('nama_blok')->get()
            : collect();

        return view('laporan.index', compact(
            'rekap', 'laporanPerAnggota', 'totalUrea', 'totalKcl',
            'karungUrea', 'karungKcl', 'blokLayakTotal', 'anggotas', 'blokFilter'
        ));
    }

    public function show(RekomendasiRbs $rekomendasiRbs)
    {
        $rekomendasiRbs->load(['blokLahan.anggota', 'kondisiLahan', 'admin']);

        return view('laporan.show', compact('rekomendasiRbs'));
    }

    public function exportPdf(RekomendasiRbs $rekomendasiRbs)
    {
        $rekomendasiRbs->load(['blokLahan.anggota', 'kondisiLahan', 'admin', 'realisasiPemupukans']);

        // Pahan v2.7: Sediakan data realisasi untuk histori pada PDF
        $realisasis = $rekomendasiRbs->realisasiPemupukans()
            ->orderBy('tahap')
            ->orderBy('tanggal_realisasi')
            ->get();

        $pdf = Pdf::loadView('laporan.pdf', compact('rekomendasiRbs', 'realisasis'));
        $pdf->setPaper('a4', 'portrait');

        $filename = 'Laporan_'.str_replace(' ', '_', $rekomendasiRbs->blokLahan->nama_blok).'_'.$rekomendasiRbs->tanggal_analisis->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
