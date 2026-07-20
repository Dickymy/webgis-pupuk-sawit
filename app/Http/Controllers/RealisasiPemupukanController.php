<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRealisasiPemupukanRequest;
use App\Http\Requests\UpdateRealisasiPemupukanRequest;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use App\Services\FertilizationRealizationService;
use App\Services\RecommendationOperationalRefreshService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RealisasiPemupukanController extends Controller
{
    public function __construct(
        private FertilizationRealizationService $realizationService,
        private CurrentApplicationCalculator $currentAppCalculator,
        private RecommendationOperationalRefreshService $refreshService,
    ) {}

    /**
     * Daftar semua realisasi pemupukan.
     */
    public function index(Request $request)
    {
        $query = RealisasiPemupukan::with(['rekomendasiRbs.blokLahan.anggota', 'blokLahan.anggota', 'admin'])
            ->orderByDesc('tanggal_realisasi');

        // Filter per blok jika disediakan
        if ($request->filled('blok_lahan_id')) {
            $query->where('blok_lahan_id', $request->blok_lahan_id);
        }

        // Filter per tahun program
        if ($request->filled('tahun_program')) {
            $query->where('tahun_program', $request->tahun_program);
        }

        // Filter per status
        if ($request->filled('status_realisasi')) {
            $query->where('status_realisasi', $request->status_realisasi);
        }

        $realisasis = $query->paginate(15)->withQueryString();

        return view('realisasi_pemupukan.index', compact('realisasis'));
    }

    /**
     * Form buat realisasi baru dari rekomendasi RBS tertentu.
     */
    public function create(RekomendasiRbs $rekomendasiRbs)
    {
        $blok = $rekomendasiRbs->blokLahan;
        $blok->load('anggota');

        // Ambil ringkasan realisasi saat ini
        $realizationSummary = $this->realizationService->getRealizationSummary($blok, $rekomendasiRbs->id);

        // Hitung current application
        $annualSnapshot = [
            'urea_total_estimasi_tahunan' => $rekomendasiRbs->urea_total_estimasi_tahunan,
            'kcl_total_estimasi_tahunan' => $rekomendasiRbs->kcl_total_estimasi_tahunan,
        ];
        $currentApp = $this->currentAppCalculator->calculate([
            'annual_snapshot' => $annualSnapshot,
            'window_result' => ['layak' => true], // Assume layak for form display
            'realization_summary' => $realizationSummary,
            'analysis_date' => now(),
        ]);

        // Tentukan tahap default
        $tahapDefault = $currentApp['active_stage'] ?: 1;

        // Hitung rencana tahap
        $ureaRencana = $currentApp['urea_aplikasi_saat_ini'];
        $kclRencana = $currentApp['kcl_aplikasi_saat_ini'];

        return view('realisasi_pemupukan.create', compact(
            'rekomendasiRbs',
            'blok',
            'realizationSummary',
            'currentApp',
            'tahapDefault',
            'ureaRencana',
            'kclRencana'
        ));
    }

    /**
     * Simpan realisasi baru.
     */
    public function store(StoreRealisasiPemupukanRequest $request)
    {
        $validated = $request->validated();

        $realisasi = DB::transaction(function () use ($validated) {
            $rekomendasi = RekomendasiRbs::findOrFail($validated['rekomendasi_rbs_id']);

            return RealisasiPemupukan::create([
                'rekomendasi_rbs_id' => $validated['rekomendasi_rbs_id'],
                'blok_lahan_id' => $rekomendasi->blok_lahan_id,
                'admin_id' => Auth::guard('admin')->id(),
                'tahun_program' => $validated['tahun_program'] ?? now()->year,
                'tahap' => $validated['tahap'],
                'tanggal_realisasi' => $validated['tanggal_realisasi'],
                'urea_rencana_kg' => $validated['urea_rencana_kg'],
                'kcl_rencana_kg' => $validated['kcl_rencana_kg'],
                'urea_realisasi_kg' => $validated['urea_realisasi_kg'],
                'kcl_realisasi_kg' => $validated['kcl_realisasi_kg'],
                'status_realisasi' => $validated['status_realisasi'],
                'catatan_pelaksana' => $validated['catatan_pelaksana'] ?? null,
                'confirmed_over_plan' => $validated['confirmed_over_plan'] ?? false,
                'override_annual_limit' => $validated['override_annual_limit'] ?? false,
                'override_reason' => $validated['override_reason'] ?? null,
            ]);
        });

        // Refresh operasional rekomendasi setelah realisasi disimpan
        $this->refreshService->refreshAfterRealization($realisasi);

        return redirect()
            ->route('realisasi-pemupukan.show', $realisasi)
            ->with('success', 'Realisasi pemupukan berhasil dicatat.');
    }

    /**
     * Detail realisasi pemupukan.
     */
    public function show(RealisasiPemupukan $realisasiPemupukan)
    {
        $realisasiPemupukan->load(['rekomendasiRbs.blokLahan.anggota', 'blokLahan.anggota', 'admin']);

        return view('realisasi_pemupukan.show', compact('realisasiPemupukan'));
    }

    /**
     * Form edit realisasi.
     */
    public function edit(RealisasiPemupukan $realisasiPemupukan)
    {
        $realisasiPemupukan->load(['rekomendasiRbs.blokLahan.anggota', 'blokLahan.anggota']);

        $blok = $realisasiPemupukan->blokLahan;
        $rekomendasiRbs = $realisasiPemupukan->rekomendasiRbs;

        // Ringkasan realisasi (exclude current record untuk kalkulasi sisa)
        $realizationSummary = $this->realizationService->getRealizationSummary($blok, $rekomendasiRbs->id);

        return view('realisasi_pemupukan.edit', compact(
            'realisasiPemupukan',
            'blok',
            'rekomendasiRbs',
            'realizationSummary'
        ));
    }

    /**
     * Update realisasi.
     */
    public function update(UpdateRealisasiPemupukanRequest $request, RealisasiPemupukan $realisasiPemupukan)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $realisasiPemupukan) {
            $realisasiPemupukan->update([
                'tanggal_realisasi' => $validated['tanggal_realisasi'],
                'urea_realisasi_kg' => $validated['urea_realisasi_kg'],
                'kcl_realisasi_kg' => $validated['kcl_realisasi_kg'],
                'status_realisasi' => $validated['status_realisasi'],
                'catatan_pelaksana' => $validated['catatan_pelaksana'] ?? null,
                'confirmed_over_plan' => $validated['confirmed_over_plan'] ?? false,
                'override_annual_limit' => $validated['override_annual_limit'] ?? false,
                'override_reason' => $validated['override_reason'] ?? null,
            ]);
        });

        // Refresh operasional rekomendasi setelah realisasi diupdate
        $this->refreshService->refreshAfterRealization($realisasiPemupukan);

        return redirect()
            ->route('realisasi-pemupukan.show', $realisasiPemupukan)
            ->with('success', 'Realisasi pemupukan berhasil diperbarui.');
    }

    /**
     * Batalkan realisasi (soft — ubah status ke BATAL, tidak menghapus record).
     */
    public function cancel(RealisasiPemupukan $realisasiPemupukan)
    {
        if ($realisasiPemupukan->status_realisasi === RealisasiPemupukan::STATUS_BATAL) {
            return redirect()
                ->route('realisasi-pemupukan.show', $realisasiPemupukan)
                ->with('warning', 'Realisasi ini sudah dibatalkan sebelumnya.');
        }

        DB::transaction(function () use ($realisasiPemupukan) {
            $realisasiPemupukan->update([
                'status_realisasi' => RealisasiPemupukan::STATUS_BATAL,
            ]);
        });

        // Refresh operasional rekomendasi setelah pembatalan
        $this->refreshService->refreshAfterRealization($realisasiPemupukan);

        return redirect()
            ->route('realisasi-pemupukan.show', $realisasiPemupukan)
            ->with('success', 'Realisasi pemupukan berhasil dibatalkan. Record tetap tersimpan untuk audit.');
    }
}
