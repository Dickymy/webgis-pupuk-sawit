<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
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

        // Filter by status
        if ($request->filled('status_kebutuhan_dominan')) {
            $query->where('status_kebutuhan_dominan', $request->status_kebutuhan_dominan);
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

        // Build structured data per anggota — sort: yang baru dianalisis di atas
        $laporanPerAnggota = $grouped->map(function ($items, $anggotaId) {
            $anggota = $items->first()->blokLahan->anggota;

            // Hanya hitung total dari blok yang layak dipupuk (Normal/Segera)
            $blokLayak = $items->filter(function ($r) {
                return in_array($r->status_kebutuhan_dominan, ['Normal', 'Segera']);
            });

            $latestAnalisis = $items->max(fn ($r) => $r->tanggal_analisis?->timestamp ?? 0);

            return [
                'anggota' => $anggota,
                'items' => $items,
                'jumlah_blok' => $items->count(),
                'total_luas' => $items->sum(fn ($r) => $r->blokLahan->luas_ha),
                'subtotal_urea' => $blokLayak->sum('total_urea'),
                'subtotal_kcl' => $blokLayak->sum('total_kcl'),
                'blok_layak' => $blokLayak->count(),
                'latest_analisis' => $latestAnalisis,
            ];
        })->sortByDesc('latest_analisis')->values();

        // Grand total — hanya dari blok layak pupuk (status Normal + Segera)
        $rekapLayak = $rekap->filter(function ($r) {
            return in_array($r->status_kebutuhan_dominan, ['Normal', 'Segera']);
        });
        $totalUrea = $rekapLayak->sum('total_urea');
        $totalKcl = $rekapLayak->sum('total_kcl');
        $karungUrea = $totalUrea > 0 ? (int) ceil($totalUrea / 50) : 0;
        $karungKcl = $totalKcl > 0 ? (int) ceil($totalKcl / 50) : 0;
        $blokLayakTotal = $rekapLayak->count();

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
