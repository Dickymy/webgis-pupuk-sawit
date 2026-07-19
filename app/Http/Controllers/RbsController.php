<?php

namespace App\Http\Controllers;

use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use App\Models\RuleBaseLanjutan;
use App\Services\RbsService;
use Illuminate\Http\Request;

class RbsController extends Controller
{
    public function __construct(private RbsService $rbsService) {}

    /**
     * Daftar blok + status analisis RBS (grouped by anggota, dengan filter).
     */
    public function index(Request $request)
    {
        $query = BlokLahan::with([
            'anggota',
            'kondisiTerbaru',
            'rekomendasiRbsTerbaru',
        ]);

        // Filter by anggota
        if ($request->filled('anggota_id')) {
            $query->where('anggota_id', $request->anggota_id);
        }

        // Filter by specific blok
        if ($request->filled('blok_lahan_id')) {
            $query->where('id', $request->blok_lahan_id);
        }

        $allFiltered = $query->orderBy('anggota_id')->orderBy('nama_blok')->get();

        // Group by anggota — sort: anggota yang baru input/update blok di atas
        $grouped = $allFiltered->groupBy('anggota_id')->map(function ($bloks) {
            $anggota = $bloks->first()->anggota;
            // Timestamp terbaru dari blok atau kondisi lahan
            $latestActivity = $bloks->max(function ($b) {
                $blokTime = $b->updated_at?->timestamp ?? 0;
                $kondisiTime = $b->kondisiTerbaru?->created_at?->timestamp ?? 0;
                return max($blokTime, $kondisiTime);
            });
            return [
                'anggota'         => $anggota,
                'bloks'           => $bloks,
                'latest_activity' => $latestActivity,
            ];
        })->sortByDesc('latest_activity')->values();

        $anggotas = \App\Models\Anggota::orderBy('nama')->get();

        // Stats (global)
        $allBloks = BlokLahan::with('rekomendasiRbsTerbaru', 'kondisiTerbaru')->get();
        $stats = [
            'total'          => $allBloks->count(),
            'sudah_analisis' => $allBloks->filter(fn($b) => $b->rekomendasiRbsTerbaru)->count(),
            'darurat'        => $allBloks->filter(fn($b) => $b->rekomendasiRbsTerbaru?->status_kebutuhan_dominan === 'Darurat')->count(),
            'segera'         => $allBloks->filter(fn($b) => $b->rekomendasiRbsTerbaru?->status_kebutuhan_dominan === 'Segera')->count(),
            'belum_kondisi'  => $allBloks->filter(fn($b) => !$b->kondisiTerbaru)->count(),
        ];

        // Blok options for filter
        $blokFilter = $request->filled('anggota_id')
            ? BlokLahan::where('anggota_id', $request->anggota_id)->orderBy('nama_blok')->get()
            : collect();

        return view('rbs.index', compact('grouped', 'anggotas', 'blokFilter', 'stats'));
    }

    /**
     * Analisis satu blok.
     */
    public function analisis(BlokLahan $blokLahan)
    {
        try {
            $hasil = $this->rbsService->analisis($blokLahan);
            return redirect()
                ->route('rbs.detail', $blokLahan)
                ->with('success', "Analisis RBS blok '{$blokLahan->nama_blok}' berhasil. Status: {$hasil['rekomendasi']->status_kebutuhan_dominan}.");
        } catch (\Exception $e) {
            return redirect()->route('rbs.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Analisis semua blok yang memiliki data kondisi.
     */
    public function analisisSemua()
    {
        $hasil    = $this->rbsService->analisisSemua();
        $berhasil = count($hasil['results']);
        $gagal    = count($hasil['errors']);

        $message = "Analisis selesai: {$berhasil} blok berhasil dianalisis.";
        if ($gagal > 0) {
            $message .= " {$gagal} blok gagal: " . implode('; ', $hasil['errors']);
        }

        return redirect()->route('rbs.index')
            ->with($gagal > 0 ? 'warning' : 'success', $message);
    }

    /**
     * Detail hasil analisis satu blok.
     */
    public function detail(BlokLahan $blokLahan)
    {
        $blokLahan->load([
            'kondisiTerbaru',
            'kondisiLahans' => fn($q) => $q->latest('tanggal_observasi')->limit(5),
            'rekomendasiRbsTerbaru.kondisiLahan',
            'rekomendasiRbsTerbaru.admin',
        ]);

        // Histori rekomendasi (Fitur 1)
        $historiRekomendasi = RekomendasiRbs::where('blok_lahan_id', $blokLahan->id)
            ->where('is_latest', false)
            ->latest('tanggal_analisis')
            ->limit(20)
            ->get();

        return view('rbs.detail', compact('blokLahan', 'historiRekomendasi'));
    }

    /**
     * API endpoint untuk popup peta WebGIS.
     */
    public function apiPopup(BlokLahan $blokLahan)
    {
        $rbs = $blokLahan->rekomendasiRbsTerbaru;
        if (!$rbs) {
            return response()->json([
                'status'  => 'Belum Dianalisis',
                'masalah' => [],
                'pupuk'   => [],
                'saran'   => '',
            ]);
        }

        return response()->json([
            'status'            => $rbs->status_kebutuhan_dominan,
            'warna_badge'       => $rbs->warna_badge,
            'tanggal'           => $rbs->tanggal_analisis->format('d/m/Y'),
            'masalah'           => $rbs->masalah_teridentifikasi,
            'pupuk'             => $rbs->rekomendasi_pupuk,
            'saran'             => $rbs->saran_tindakan_utama,
            'jumlah_rule'       => $rbs->jumlah_rule_terpicu,
            // Pahan-v2
            'fase'              => $rbs->fase_tanaman_snapshot,
            'umur'              => $rbs->umur_tanaman_snapshot,
            'urea_estimasi'     => $rbs->urea_estimasi_kg_per_pokok_tahun,
            'kcl_estimasi'      => $rbs->kcl_estimasi_kg_per_pokok_tahun,
            'skor_keandalan'    => $rbs->kelengkapan_data_score,
            'kategori_keandalan' => $rbs->kategori_keandalan,
            'status_kondisi'    => $rbs->status_kondisi_tanaman,
            'status_kelayakan'  => $rbs->status_kelayakan_aplikasi,
        ]);
    }

    /**
     * API: daftar blok yang belum dianalisis (untuk AJAX progress bar B3).
     */
    public function daftarBlokBelumAnalisis()
    {
        $bloks = BlokLahan::whereHas('kondisiLahans')
            ->with('anggota')
            ->get()
            ->map(fn($b) => [
                'id'        => $b->id,
                'nama_blok' => $b->nama_blok,
                'pemilik'   => $b->nama_pemilik,
            ]);

        return response()->json($bloks->values());
    }
}
