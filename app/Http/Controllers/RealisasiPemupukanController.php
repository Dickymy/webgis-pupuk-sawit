<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRealisasiPemupukanRequest;
use App\Http\Requests\UpdateRealisasiPemupukanRequest;
use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiOperasionalHistory;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use App\Services\FertilizationRealizationService;
use App\Services\RealisasiEligibilityService;
use App\Services\RecommendationOperationalRefreshService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * RealisasiPemupukanController — CRUD realisasi pemupukan.
 *
 * Pahan v2.7:
 * - Form hanya dibuka jika RealisasiEligibilityService mengizinkan
 * - Rencana, tahap, tahun program dihitung server (4.2)
 * - Status SELESAI divalidasi terhadap jumlah kumulatif (4.3)
 * - Tahap ditentukan server (4.4)
 * - Program pemupukan terintegrasi (4.5)
 * - Histori operasional dicatat setiap perubahan (4.6)
 */
class RealisasiPemupukanController extends Controller
{
    public function __construct(
        private FertilizationRealizationService $realizationService,
        private CurrentApplicationCalculator $currentAppCalculator,
        private RecommendationOperationalRefreshService $refreshService,
        private RealisasiEligibilityService $eligibilityService,
    ) {}

    /**
     * Daftar semua realisasi pemupukan.
     */
    public function index(Request $request)
    {
        $query = RealisasiPemupukan::with(['rekomendasiRbs.blokLahan.anggota', 'blokLahan.anggota', 'admin'])
            ->orderByDesc('tanggal_realisasi');

        if ($request->filled('blok_lahan_id')) {
            $query->where('blok_lahan_id', $request->blok_lahan_id);
        }

        if ($request->filled('tahun_program')) {
            $query->where('tahun_program', $request->tahun_program);
        }

        if ($request->filled('status_realisasi')) {
            $query->where('status_realisasi', $request->status_realisasi);
        }

        $realisasis = $query->paginate(15)->withQueryString();

        return view('realisasi_pemupukan.index', compact('realisasis'));
    }

    /**
     * Form buat realisasi baru — ditolak jika tidak layak (4.1).
     */
    public function create(RekomendasiRbs $rekomendasiRbs)
    {
        $blok = $rekomendasiRbs->blokLahan;
        $blok->load('anggota');

        // Evaluasi kelayakan — v2.7: server menentukan segalanya
        $eligibility = $this->eligibilityService->evaluate($rekomendasiRbs);

        if (! $eligibility['boleh_mencatat']) {
            return redirect()
                ->route('rbs.detail', $blok)
                ->with('error', 'Realisasi tidak dapat dicatat: '.$eligibility['reason']);
        }

        $realizationSummary = $eligibility['realization_summary'];
        $currentApp = $eligibility['current_app'];
        $tahapDefault = $eligibility['active_stage'];
        $ureaRencana = $eligibility['urea_rencana_kg'];
        $kclRencana = $eligibility['kcl_rencana_kg'];

        return view('realisasi_pemupukan.create', compact(
            'rekomendasiRbs',
            'blok',
            'realizationSummary',
            'currentApp',
            'tahapDefault',
            'ureaRencana',
            'kclRencana',
            'eligibility'
        ));
    }

    /**
     * Simpan realisasi baru — server menghitung rencana, tahap, tahun (4.2, 4.4).
     */
    public function store(StoreRealisasiPemupukanRequest $request)
    {
        $validated = $request->validated();

        $rekomendasi = RekomendasiRbs::findOrFail($validated['rekomendasi_rbs_id']);
        $blok = $rekomendasi->blokLahan;

        // Re-evaluasi kelayakan (server-side, tidak percaya browser)
        $eligibility = $this->eligibilityService->evaluate($rekomendasi);

        if (! $eligibility['boleh_mencatat']) {
            return redirect()
                ->route('rbs.detail', $blok)
                ->with('error', 'Realisasi tidak dapat dicatat: '.$eligibility['reason']);
        }

        // Server menentukan nilai resmi — BUKAN dari request (4.2)
        $tahapResmi = $eligibility['active_stage'];
        $tahunProgramResmi = $eligibility['tahun_program'];
        $ureaRencanaResmi = $eligibility['urea_rencana_kg'];
        $kclRencanaResmi = $eligibility['kcl_rencana_kg'];

        // Validasi status SELESAI terhadap jumlah kumulatif (4.3)
        $statusRealisasi = $validated['status_realisasi'];
        if ($statusRealisasi === RealisasiPemupukan::STATUS_SELESAI) {
            $validasiSelesai = $this->validateStatusSelesai(
                $blok,
                $rekomendasi,
                $tahapResmi,
                (float) $validated['urea_realisasi_kg'],
                (float) $validated['kcl_realisasi_kg'],
                $eligibility['realization_summary']
            );

            if (! $validasiSelesai['valid']) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['status_realisasi' => $validasiSelesai['message']]);
            }
        }

        // Ensure/create program pemupukan (4.5)
        $program = $this->ensureProgram($blok, $tahunProgramResmi, $rekomendasi);

        $realisasi = DB::transaction(function () use ($validated, $rekomendasi, $blok, $tahapResmi, $tahunProgramResmi, $ureaRencanaResmi, $kclRencanaResmi, $program, $statusRealisasi) {
            return RealisasiPemupukan::create([
                'rekomendasi_rbs_id' => $rekomendasi->id,
                'blok_lahan_id' => $blok->id,
                'program_pemupukan_id' => $program->id,
                'admin_id' => Auth::guard('admin')->id(),
                'tahun_program' => $tahunProgramResmi,
                'tahap' => $tahapResmi,
                'tanggal_realisasi' => $validated['tanggal_realisasi'],
                'urea_rencana_kg' => $ureaRencanaResmi,
                'kcl_rencana_kg' => $kclRencanaResmi,
                'urea_realisasi_kg' => $validated['urea_realisasi_kg'],
                'kcl_realisasi_kg' => $validated['kcl_realisasi_kg'],
                'status_realisasi' => $statusRealisasi,
                'catatan_pelaksana' => $validated['catatan_pelaksana'] ?? null,
                'confirmed_over_plan' => $validated['confirmed_over_plan'] ?? false,
                'override_annual_limit' => $validated['override_annual_limit'] ?? false,
                'override_reason' => $validated['override_reason'] ?? null,
            ]);
        });

        // Refresh operasional + catat histori (4.6)
        $this->refreshService->refreshAfterRealization($realisasi);
        $this->recordOperationalHistory($rekomendasi, $program, $realisasi, RekomendasiOperasionalHistory::REALISASI_DIBUAT);

        return redirect()
            ->route('realisasi-pemupukan.show', $realisasi)
            ->with('success', 'Realisasi pemupukan berhasil dicatat.');
    }

    /**
     * Detail realisasi pemupukan.
     */
    public function show(RealisasiPemupukan $realisasiPemupukan)
    {
        $realisasiPemupukan->load(['rekomendasiRbs.blokLahan.anggota', 'blokLahan.anggota', 'admin', 'programPemupukan']);

        // Histori operasional terkait
        $historiOperasional = RekomendasiOperasionalHistory::where('source_realisasi_id', $realisasiPemupukan->id)
            ->orderByDesc('created_at')
            ->get();

        return view('realisasi_pemupukan.show', compact('realisasiPemupukan', 'historiOperasional'));
    }

    /**
     * Form edit realisasi.
     */
    public function edit(RealisasiPemupukan $realisasiPemupukan)
    {
        $realisasiPemupukan->load(['rekomendasiRbs.blokLahan.anggota', 'blokLahan.anggota']);

        $blok = $realisasiPemupukan->blokLahan;
        $rekomendasiRbs = $realisasiPemupukan->rekomendasiRbs;

        $realizationSummary = $this->realizationService->getRealizationSummary($blok, $rekomendasiRbs->id);

        return view('realisasi_pemupukan.edit', compact(
            'realisasiPemupukan',
            'blok',
            'rekomendasiRbs',
            'realizationSummary'
        ));
    }

    /**
     * Update realisasi — validasi status SELESAI (4.3).
     */
    public function update(UpdateRealisasiPemupukanRequest $request, RealisasiPemupukan $realisasiPemupukan)
    {
        $validated = $request->validated();

        // Validasi status SELESAI (4.3)
        $statusRealisasi = $validated['status_realisasi'];
        if ($statusRealisasi === RealisasiPemupukan::STATUS_SELESAI) {
            $blok = $realisasiPemupukan->blokLahan;
            $rekomendasi = $realisasiPemupukan->rekomendasiRbs;
            $summary = $this->realizationService->getRealizationSummary($blok, $rekomendasi->id);

            // Kurangi record saat ini dari total (akan diupdate)
            $summaryAdjusted = $summary;
            if ($realisasiPemupukan->isAktif()) {
                $summaryAdjusted['total_urea_realisasi'] -= (float) $realisasiPemupukan->urea_realisasi_kg;
                $summaryAdjusted['total_kcl_realisasi'] -= (float) $realisasiPemupukan->kcl_realisasi_kg;
                if ($realisasiPemupukan->tahap === 1) {
                    $summaryAdjusted['urea_realisasi_tahap_1'] -= (float) $realisasiPemupukan->urea_realisasi_kg;
                    $summaryAdjusted['kcl_realisasi_tahap_1'] -= (float) $realisasiPemupukan->kcl_realisasi_kg;
                } else {
                    $summaryAdjusted['urea_realisasi_tahap_2'] = ($summaryAdjusted['urea_realisasi_tahap_2'] ?? 0) - (float) $realisasiPemupukan->urea_realisasi_kg;
                    $summaryAdjusted['kcl_realisasi_tahap_2'] = ($summaryAdjusted['kcl_realisasi_tahap_2'] ?? 0) - (float) $realisasiPemupukan->kcl_realisasi_kg;
                }
            }

            $validasiSelesai = $this->validateStatusSelesai(
                $blok,
                $rekomendasi,
                $realisasiPemupukan->tahap,
                (float) $validated['urea_realisasi_kg'],
                (float) $validated['kcl_realisasi_kg'],
                $summaryAdjusted
            );

            if (! $validasiSelesai['valid']) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['status_realisasi' => $validasiSelesai['message']]);
            }
        }

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

        // Refresh operasional + catat histori
        $this->refreshService->refreshAfterRealization($realisasiPemupukan);
        $rekomendasi = $realisasiPemupukan->rekomendasiRbs;
        $program = $realisasiPemupukan->programPemupukan;
        $this->recordOperationalHistory($rekomendasi, $program, $realisasiPemupukan, RekomendasiOperasionalHistory::REALISASI_DIPERBARUI);

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

        // Refresh operasional + catat histori
        $this->refreshService->refreshAfterRealization($realisasiPemupukan);
        $rekomendasi = $realisasiPemupukan->rekomendasiRbs;
        $program = $realisasiPemupukan->programPemupukan;
        $this->recordOperationalHistory($rekomendasi, $program, $realisasiPemupukan, RekomendasiOperasionalHistory::REALISASI_DIBATALKAN);

        return redirect()
            ->route('realisasi-pemupukan.show', $realisasiPemupukan)
            ->with('success', 'Realisasi pemupukan berhasil dibatalkan. Record tetap tersimpan untuk audit.');
    }

    // ─── Private Methods ─────────────────────────────────────

    /**
     * Validasi status SELESAI terhadap jumlah kumulatif (4.3).
     *
     * Status SELESAI hanya diterima jika total kumulatif >= rencana tahap - toleransi.
     */
    private function validateStatusSelesai(
        $blok,
        RekomendasiRbs $rekomendasi,
        int $tahap,
        float $ureaRealisasiBaru,
        float $kclRealisasiBaru,
        array $realizationSummary
    ): array {
        $tolerance = 0.01;

        $totalUreaTahunan = (float) ($rekomendasi->urea_total_estimasi_tahunan ?? 0);
        $totalKclTahunan = (float) ($rekomendasi->kcl_total_estimasi_tahunan ?? 0);

        // Rencana Tahap 1 = 50% kebutuhan tahunan
        $ureaRencanaTahap = round($totalUreaTahunan * 0.50, 2);
        $kclRencanaTahap = round($totalKclTahunan * 0.50, 2);

        if ($tahap === 2) {
            // Tahap 2 = sisa tahunan setelah Tahap 1
            $ureaRealisasiT1 = (float) ($realizationSummary['urea_realisasi_tahap_1'] ?? 0);
            $kclRealisasiT1 = (float) ($realizationSummary['kcl_realisasi_tahap_1'] ?? 0);
            $ureaRencanaTahap = max(0, $totalUreaTahunan - $ureaRealisasiT1);
            $kclRencanaTahap = max(0, $totalKclTahunan - $kclRealisasiT1);
        }

        // Total kumulatif tahap setelah record baru
        $ureaTotalTahap = $ureaRealisasiBaru;
        $kclTotalTahap = $kclRealisasiBaru;

        if ($tahap === 1) {
            $ureaTotalTahap += (float) ($realizationSummary['urea_realisasi_tahap_1'] ?? 0);
            $kclTotalTahap += (float) ($realizationSummary['kcl_realisasi_tahap_1'] ?? 0);
        } else {
            $ureaTotalTahap += (float) ($realizationSummary['urea_realisasi_tahap_2'] ?? 0);
            $kclTotalTahap += (float) ($realizationSummary['kcl_realisasi_tahap_2'] ?? 0);
        }

        $ureaTerpenuhi = $ureaRencanaTahap <= 0 || ($ureaTotalTahap >= ($ureaRencanaTahap - $tolerance));
        $kclTerpenuhi = $kclRencanaTahap <= 0 || ($kclTotalTahap >= ($kclRencanaTahap - $tolerance));

        if (! $ureaTerpenuhi || ! $kclTerpenuhi) {
            $detail = [];
            if (! $ureaTerpenuhi) {
                $detail[] = 'Urea: '.number_format($ureaTotalTahap, 2).' / '.number_format($ureaRencanaTahap, 2).' kg';
            }
            if (! $kclTerpenuhi) {
                $detail[] = 'KCl: '.number_format($kclTotalTahap, 2).' / '.number_format($kclRencanaTahap, 2).' kg';
            }

            return [
                'valid' => false,
                'message' => 'Status Selesai tidak dapat dipilih karena jumlah realisasi belum memenuhi rencana tahap. '.implode('; ', $detail),
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Ensure program pemupukan exists atau buat baru (4.5).
     */
    private function ensureProgram($blok, int $tahunProgram, RekomendasiRbs $rekomendasi): ProgramPemupukan
    {
        $program = ProgramPemupukan::where('blok_lahan_id', $blok->id)
            ->where('tahun_program', $tahunProgram)
            ->where('status_program', ProgramPemupukan::STATUS_AKTIF)
            ->first();

        if (! $program) {
            $program = ProgramPemupukan::create([
                'uuid' => Str::uuid()->toString(),
                'blok_lahan_id' => $blok->id,
                'tahun_program' => $tahunProgram,
                'rekomendasi_awal_id' => $rekomendasi->id,
                'status_program' => ProgramPemupukan::STATUS_AKTIF,
            ]);
        }

        return $program;
    }

    /**
     * Catat histori operasional (4.6).
     */
    private function recordOperationalHistory(
        ?RekomendasiRbs $rekomendasi,
        ?ProgramPemupukan $program,
        RealisasiPemupukan $realisasi,
        string $eventType
    ): void {
        if (! $rekomendasi) {
            return;
        }

        // Refresh rekomendasi untuk mendapatkan state terbaru
        $rekomendasi->refresh();

        RekomendasiOperasionalHistory::create([
            'rekomendasi_rbs_id' => $rekomendasi->id,
            'program_pemupukan_id' => $program?->id,
            'event_type' => $eventType,
            'active_stage' => $rekomendasi->active_stage,
            'status_stage' => $rekomendasi->status_stage,
            'urea_aplikasi_saat_ini' => $rekomendasi->urea_aplikasi_saat_ini,
            'kcl_aplikasi_saat_ini' => $rekomendasi->kcl_aplikasi_saat_ini,
            'urea_sisa_tahunan' => $rekomendasi->urea_sisa_tahunan,
            'kcl_sisa_tahunan' => $rekomendasi->kcl_sisa_tahunan,
            'tanggal_minimum_tahap_berikutnya' => $rekomendasi->tanggal_minimum_tahap_berikutnya,
            'alasan_tahap' => $rekomendasi->alasan_tahap,
            'analysis_fingerprint' => $rekomendasi->analysis_fingerprint,
            'source_realisasi_id' => $realisasi->id,
            'created_at' => now(),
        ]);
    }
}
