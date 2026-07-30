<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use App\Notifications\RealisasiNotification;
use App\Services\ObservationCompletenessService;
use App\Services\RbsService;
use App\Services\RealisasiEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RbsController extends Controller
{
    public function __construct(
        private RbsService $rbsService,
        private RealisasiEligibilityService $eligibilityService,
        private ObservationCompletenessService $completenessService,
    ) {}

    /**
     * Daftar blok + status analisis RBS (grouped by anggota, dengan filter).
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'semua');
        $statusValid = ['semua', 'perlu-tindakan', 'belum-observasi', 'perlu-rekomendasi', 'siap-realisasi', 'menunggu-interval', 'menunggu', 'selesai'];
        if (! in_array($status, $statusValid, true)) {
            $status = 'semua';
        }

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
        $allFiltered->each(fn ($blok) => $blok->setAttribute(
            'operational_eligibility',
            $this->operationalEligibility($blok)
        ));
        $allFiltered = $allFiltered->filter(function ($blok) use ($status) {
            $kondisi = $blok->kondisiTerbaru;
            $rekomendasi = $blok->rekomendasiRbsTerbaru;
            $eligibility = $blok->operational_eligibility;
            $perluDiperbarui = $kondisi && $rekomendasi
                && $kondisi->updated_at->gt($rekomendasi->updated_at);
            $dataBelumCukup = $this->recommendationNeedsObservation($rekomendasi);

            return match ($status) {
                'perlu-tindakan' => ! $kondisi
                    || ! $rekomendasi
                    || $perluDiperbarui
                    || $dataBelumCukup
                    || ($eligibility['boleh_mencatat'] ?? false),
                'belum-observasi' => ! $kondisi,
                'perlu-rekomendasi' => $kondisi && (! $rekomendasi || $perluDiperbarui || $dataBelumCukup),
                'siap-realisasi' => (bool) ($eligibility['boleh_mencatat'] ?? false),
                'menunggu-interval' => ($eligibility['status_stage'] ?? null) === 'MENUNGGU_INTERVAL',
                'menunggu' => in_array($eligibility['status_stage'] ?? null, [
                    'MENUNGGU_INTERVAL',
                    'MENUNGGU_KELAYAKAN',
                    'PERLU_VERIFIKASI_REALISASI',
                ], true),
                'selesai' => ($eligibility['status_stage'] ?? null) === 'SELESAI_TAHUNAN'
                    || $rekomendasi?->is_program_selesai === true,
                default => true,
            };
        })->values();

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
                'anggota' => $anggota,
                'bloks' => $bloks,
                'latest_activity' => $latestActivity,
            ];
        })->sortByDesc('latest_activity')->values();

        $anggotas = Anggota::orderBy('nama')->get();

        // Stats (global)
        $allBloks = BlokLahan::with('rekomendasiRbsTerbaru', 'kondisiTerbaru')->get();
        $allBloks->each(fn ($blok) => $blok->setAttribute(
            'operational_eligibility',
            $this->operationalEligibility($blok)
        ));
        $stats = [
            'total' => $allBloks->count(),
            'sudah_analisis' => $allBloks->filter(fn ($b) => $b->rekomendasiRbsTerbaru)->count(),
            'darurat' => $allBloks->filter(fn ($b) => $b->rekomendasiRbsTerbaru?->status_kondisi_tanaman === 'GEJALA_BERAT')->count(),
            'segera' => $allBloks->filter(fn ($b) => in_array($b->rekomendasiRbsTerbaru?->status_kondisi_tanaman, ['TERINDIKASI_DEFISIENSI_RINGAN', 'TERINDIKASI_DEFISIENSI'], true))->count(),
            'belum_kondisi' => $allBloks->filter(fn ($b) => ! $b->kondisiTerbaru)->count(),
            'perlu_rekomendasi' => $allBloks->filter(function ($b) {
                if (! $b->kondisiTerbaru) {
                    return false;
                }

                return ! $b->rekomendasiRbsTerbaru
                    || $b->kondisiTerbaru->updated_at->gt($b->rekomendasiRbsTerbaru->updated_at)
                    || $this->recommendationNeedsObservation($b->rekomendasiRbsTerbaru);
            })->count(),
            'siap_realisasi' => $allBloks->filter(
                fn ($b) => (bool) ($b->operational_eligibility['boleh_mencatat'] ?? false)
            )->count(),
        ];

        // Blok options for filter
        $blokFilter = $request->filled('anggota_id')
            ? BlokLahan::where('anggota_id', $request->anggota_id)->orderBy('nama_blok')->get()
            : collect();

        return view('rbs.index', compact('grouped', 'anggotas', 'blokFilter', 'stats', 'status'));
    }

    /**
     * Gunakan kalkulasi dinamis untuk rekomendasi yang sudah terhubung ke program.
     * Data historis/legacy tanpa program tetap memakai snapshot rekomendasinya.
     */
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

    /**
     * Analisis satu blok.
     */
    public function analisis(BlokLahan $blokLahan)
    {
        try {
            $hasil = $this->rbsService->analisis($blokLahan);

            // Pahan v2.8: Kirim notifikasi jika tahap siap realisasi
            $this->sendAnalysisNotification($blokLahan, $hasil['rekomendasi']);

            return redirect()
                ->route('rbs.detail', $blokLahan)
                ->with('success', "Analisis RBS blok '{$blokLahan->nama_blok}' berhasil. Kondisi: {$hasil['rekomendasi']->label_kondisi_tanaman}.");
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
        $hasil = $this->rbsService->analisisSemua();
        $berhasil = count($hasil['results']);
        $gagal = count($hasil['errors']);

        // Pahan v2.8: Kirim notifikasi untuk blok yang siap realisasi
        foreach ($hasil['results'] as $item) {
            $this->sendAnalysisNotification($item['blok'], $item['result']['rekomendasi']);
        }

        $message = "Analisis selesai: {$berhasil} blok berhasil dianalisis.";
        if ($gagal > 0) {
            $message .= " {$gagal} blok gagal: ".implode('; ', $hasil['errors']);
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
            'kondisiLahans' => fn ($q) => $q->latest('tanggal_observasi')->limit(5),
            'rekomendasiRbsTerbaru.kondisiLahan',
            'rekomendasiRbsTerbaru.admin',
        ]);

        // Histori rekomendasi (Fitur 1)
        $historiRekomendasi = RekomendasiRbs::where('blok_lahan_id', $blokLahan->id)
            ->where('is_latest', false)
            ->latest('tanggal_analisis')
            ->limit(20)
            ->get();

        $observationCompleteness = $blokLahan->kondisiTerbaru
            ? $this->completenessService->evaluate($blokLahan->kondisiTerbaru)
            : null;

        return view('rbs.detail', compact('blokLahan', 'historiRekomendasi', 'observationCompleteness'));
    }

    /**
     * API endpoint untuk popup peta WebGIS.
     */
    public function apiPopup(BlokLahan $blokLahan)
    {
        $rbs = $blokLahan->rekomendasiRbsTerbaru;
        if (! $rbs) {
            return response()->json([
                'status' => 'Belum Dianalisis',
                'masalah' => [],
                'pupuk' => [],
                'saran' => '',
            ]);
        }

        return response()->json([
            'status' => $rbs->label_kondisi_tanaman,
            'kelayakan' => $rbs->label_kelayakan,
            'warna_badge' => $rbs->warna_badge,
            'tanggal' => $rbs->tanggal_analisis->format('d/m/Y'),
            'masalah' => $rbs->masalah_teridentifikasi,
            'pupuk' => $rbs->rekomendasi_pupuk,
            'saran' => $rbs->saran_tindakan_utama,
            'jumlah_rule' => $rbs->jumlah_rule_terpicu,
            // Pahan-v2.2 — gunakan label lengkap
            'fase' => $rbs->label_fase,
            'umur' => $rbs->umur_tanaman_snapshot,
            'urea_estimasi' => $rbs->urea_estimasi_kg_per_pokok_tahun,
            'kcl_estimasi' => $rbs->kcl_estimasi_kg_per_pokok_tahun,
            'skor_keandalan' => $rbs->kelengkapan_data_score,
            'kategori_keandalan' => $rbs->kategori_keandalan,
            'status_kondisi' => $rbs->label_kondisi_tanaman,
            'status_kelayakan' => $rbs->label_kelayakan,
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
            ->map(fn ($b) => [
                'id' => $b->id,
                'nama_blok' => $b->nama_blok,
                'pemilik' => $b->nama_pemilik,
            ]);

        return response()->json($bloks->values());
    }

    /**
     * Kirim notifikasi jika analisis menunjukkan blok siap realisasi.
     */
    private function sendAnalysisNotification(BlokLahan $blokLahan, $rekomendasi): void
    {
        if (! $rekomendasi) {
            return;
        }

        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            return;
        }

        $statusStage = $rekomendasi->status_stage ?? null;
        $siapRealisasi = in_array($statusStage, ['TAHAP_1_SIAP', 'TAHAP_2_SIAP']);

        if (! $siapRealisasi) {
            return;
        }

        // Cek apakah sudah ada notifikasi serupa yang belum dibaca (hindari spam)
        $existing = $admin->unreadNotifications()
            ->where('data->tipe', 'tahap_siap')
            ->where('data->meta->blok', $blokLahan->nama_blok)
            ->where('data->meta->tahap', $rekomendasi->active_stage)
            ->first();

        if ($existing) {
            return;
        }

        $tahap = $rekomendasi->active_stage ?? 1;
        $url = route('rbs.detail', $blokLahan);

        $admin->notify(
            RealisasiNotification::tahapSiap($blokLahan->nama_blok, $tahap, $url)
        );
    }
}
