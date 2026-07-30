<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRealisasiPemupukanRequest;
use App\Http\Requests\UpdateRealisasiPemupukanRequest;
use App\Models\Anggota;
use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiOperasionalHistory;
use App\Models\RekomendasiRbs;
use App\Notifications\RealisasiNotification;
use App\Services\CurrentApplicationCalculator;
use App\Services\FertilizationRealizationService;
use App\Services\ProgramPemupukanService;
use App\Services\ProgramStatusService;
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
        private ProgramPemupukanService $programService,
        private ProgramStatusService $programStatusService,
    ) {}

    /**
     * Daftar semua realisasi pemupukan — grouped by anggota.
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'siap');
        if (! in_array($tab, ['siap', 'menunggu', 'riwayat'], true)) {
            $tab = 'siap';
        }

        $latestQuery = RekomendasiRbs::with([
            'blokLahan.anggota',
            'blokLahan.kondisiTerbaru',
            'programPemupukan',
        ])->where('is_latest', true);

        if ($request->filled('anggota_id')) {
            $latestQuery->whereHas('blokLahan', fn ($q) => $q->where('anggota_id', $request->anggota_id));
        }

        $operasional = $latestQuery->get()->map(function ($rekomendasi) {
            return [
                'rekomendasi' => $rekomendasi,
                'eligibility' => $this->eligibilityService->evaluate($rekomendasi),
            ];
        });

        $siapItems = $operasional->filter(fn ($item) => $item['eligibility']['boleh_mencatat'])->values();
        $menungguItems = $operasional->filter(fn ($item) => in_array($item['eligibility']['status_stage'], [
            CurrentApplicationCalculator::MENUNGGU_INTERVAL,
            CurrentApplicationCalculator::MENUNGGU_KELAYAKAN,
            CurrentApplicationCalculator::PERLU_VERIFIKASI_REALISASI,
        ], true))->values();

        $groupOperasional = function ($items) {
            return $items->groupBy(fn ($item) => $item['rekomendasi']->blokLahan?->anggota_id ?? 0)
                ->map(function ($groupItems) {
                    return [
                        'anggota' => $groupItems->first()['rekomendasi']->blokLahan?->anggota,
                        'items' => $groupItems->values(),
                    ];
                })
                ->sortBy(fn ($group) => $group['anggota']?->nama ?? 'zzz')
                ->values();
        };

        $groupedSiap = $groupOperasional($siapItems);
        $groupedMenunggu = $groupOperasional($menungguItems);

        $query = RealisasiPemupukan::with(['rekomendasiRbs.blokLahan.anggota', 'blokLahan.anggota', 'admin']);

        if ($request->filled('anggota_id')) {
            $query->whereHas('blokLahan', fn ($q) => $q->where('anggota_id', $request->anggota_id));
        }

        if ($request->filled('status_realisasi')) {
            $query->where('status_realisasi', $request->status_realisasi);
        }

        $realisasis = $query->orderByDesc('tanggal_realisasi')->get();

        $grouped = $realisasis->groupBy(function ($r) {
            return $r->blokLahan?->anggota_id ?? 0;
        })->map(function ($items) {
            $anggota = $items->first()->blokLahan?->anggota;
            $sorted = $items->sortBy(function ($r) {
                $priority = match ($r->status_realisasi) {
                    'SEBAGIAN' => 0,
                    'SELESAI' => 1,
                    'BATAL' => 9,
                    default => 5,
                };

                return $priority.'-'.(9999999999 - ($r->tanggal_realisasi?->timestamp ?? 0));
            })->values();

            return [
                'anggota' => $anggota,
                'items' => $sorted,
            ];
        })->sortBy(fn ($g) => $g['anggota']?->nama ?? 'zzz')->values();

        $anggotas = Anggota::orderBy('nama')->get();
        $workflowStats = [
            'siap' => $siapItems->count(),
            'menunggu' => $menungguItems->count(),
            'riwayat' => $realisasis->count(),
        ];

        return view('realisasi_pemupukan.index', compact(
            'grouped', 'groupedSiap', 'groupedMenunggu', 'anggotas', 'workflowStats', 'tab'
        ));
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

        // Generate submission token untuk perlindungan double-submit
        $submissionToken = Str::uuid()->toString();

        return view('realisasi_pemupukan.create', compact(
            'rekomendasiRbs',
            'blok',
            'realizationSummary',
            'currentApp',
            'tahapDefault',
            'ureaRencana',
            'kclRencana',
            'eligibility',
            'submissionToken'
        ));
    }

    /**
     * Simpan realisasi baru — server menghitung rencana, tahap, tahun (4.2, 4.4).
     *
     * Perlindungan double-submit:
     * 1. Submission token (idempotensi) — token yang sama tidak bisa dipakai dua kali
     * 2. Duplikasi semantik — payload identik dalam 5 menit terakhir ditolak
     * 3. Locking — lockForUpdate pada rekomendasi & program dalam transaksi
     */
    public function store(StoreRealisasiPemupukanRequest $request)
    {
        $validated = $request->validated();
        $submissionToken = $validated['submission_token'] ?? Str::uuid()->toString();

        $rekomendasi = RekomendasiRbs::findOrFail($validated['rekomendasi_rbs_id']);
        $blok = $rekomendasi->blokLahan;

        // ═══ PERLINDUNGAN 1: Cek submission token sudah pernah dipakai ═══
        $existingByToken = RealisasiPemupukan::where('submission_token', $submissionToken)->first();
        if ($existingByToken) {
            // Token sudah dipakai — redirect ke realisasi yang sudah tersimpan
            return redirect()
                ->route('realisasi-pemupukan.show', $existingByToken)
                ->with('warning', 'Realisasi ini sudah tersimpan sebelumnya. Tidak ada data duplikat yang dibuat.');
        }

        // ═══ PERLINDUNGAN 2: Duplikasi semantik (payload identik dalam 5 menit) ═══
        $semanticDuplicate = $this->findSemanticDuplicate(
            $blok->id,
            $validated['rekomendasi_rbs_id'],
            (float) $validated['urea_realisasi_kg'],
            (float) $validated['kcl_realisasi_kg'],
            $validated['tanggal_realisasi'],
            $validated['status_realisasi']
        );

        if ($semanticDuplicate) {
            return redirect()
                ->route('realisasi-pemupukan.show', $semanticDuplicate)
                ->with('warning', 'Realisasi ini sudah tersimpan sebelumnya. Tidak ada data duplikat yang dibuat.');
        }

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

        // ═══ PERLINDUNGAN 3: Transaksi dengan locking ketat ═══
        try {
            $realisasi = DB::transaction(function () use ($validated, $rekomendasi, $blok, $tahunProgramResmi, $ureaRencanaResmi, $kclRencanaResmi, $program, $statusRealisasi, $submissionToken) {
                // Lock rekomendasi dan program untuk mencegah concurrent write
                $lockedRekomendasi = RekomendasiRbs::lockForUpdate()->find($rekomendasi->id);
                $lockedProgram = ProgramPemupukan::lockForUpdate()->find($program->id);

                // Re-check submission token di dalam transaksi (race condition protection)
                $existingInTx = RealisasiPemupukan::where('submission_token', $submissionToken)->first();
                if ($existingInTx) {
                    return $existingInTx;
                }

                // Re-evaluasi kelayakan setelah lock (tahap mungkin sudah berubah)
                $freshEligibility = $this->eligibilityService->evaluate($lockedRekomendasi);
                if (! $freshEligibility['boleh_mencatat']) {
                    throw new \RuntimeException('STAGE_CHANGED:'.$freshEligibility['reason']);
                }

                // Gunakan tahap terbaru setelah lock
                $freshTahap = $freshEligibility['active_stage'];

                // Cek duplikasi semantik lagi di dalam transaksi
                $semanticInTx = $this->findSemanticDuplicate(
                    $blok->id,
                    $lockedRekomendasi->id,
                    (float) $validated['urea_realisasi_kg'],
                    (float) $validated['kcl_realisasi_kg'],
                    $validated['tanggal_realisasi'],
                    $statusRealisasi
                );

                if ($semanticInTx) {
                    return $semanticInTx;
                }

                return RealisasiPemupukan::create([
                    'rekomendasi_rbs_id' => $lockedRekomendasi->id,
                    'blok_lahan_id' => $blok->id,
                    'program_pemupukan_id' => $lockedProgram->id,
                    'admin_id' => Auth::guard('admin')->id(),
                    'tahun_program' => $tahunProgramResmi,
                    'tahap' => $freshTahap,
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
                    'submission_token' => $submissionToken,
                ]);
            });
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'STAGE_CHANGED:')) {
                return redirect()
                    ->route('rbs.detail', $blok)
                    ->with('error', 'Tahap pemupukan telah diperbarui oleh pencatatan sebelumnya. Silakan periksa data terbaru.');
            }
            throw $e;
        }

        // Jika realisasi sudah ada sebelumnya (dari duplicate check di dalam transaksi)
        if (! $realisasi->wasRecentlyCreated) {
            return redirect()
                ->route('realisasi-pemupukan.show', $realisasi)
                ->with('warning', 'Realisasi ini sudah tersimpan sebelumnya. Tidak ada data duplikat yang dibuat.');
        }

        // Refresh operasional + catat histori (4.6)
        $this->refreshService->refreshAfterRealization($realisasi);
        $this->recordOperationalHistory($rekomendasi, $program, $realisasi, RekomendasiOperasionalHistory::REALISASI_DIBUAT);

        // Pahan v2.8: Sinkronisasi status program setelah realisasi
        $postCurrentApp = $this->eligibilityService->evaluate($rekomendasi);
        if (! empty($postCurrentApp['current_app'])) {
            $this->programStatusService->synchronizeStatus($program, $postCurrentApp['current_app']);
        }

        // Pahan v2.8: Kirim notifikasi
        $this->sendRealisasiNotification($realisasi, $rekomendasi, $postCurrentApp['current_app'] ?? []);

        return redirect()
            ->route('realisasi-pemupukan.show', $realisasi)
            ->with('success', 'Realisasi pemupukan berhasil disimpan.');
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

        $realizationSummary = $rekomendasiRbs->programPemupukan
            ? $this->realizationService->getRealizationSummaryForProgram($rekomendasiRbs->programPemupukan)
            : $this->realizationService->getRealizationSummary($blok, $rekomendasiRbs->id);

        return view('realisasi_pemupukan.edit', compact(
            'realisasiPemupukan',
            'blok',
            'rekomendasiRbs',
            'realizationSummary'
        ));
    }

    /**
     * Update realisasi — validasi status SELESAI (4.3).
     *
     * Perlindungan double-submit pada update:
     * - Optimistic locking via updated_at
     * - Request identik tanpa perubahan tidak menambah histori
     */
    public function update(UpdateRealisasiPemupukanRequest $request, RealisasiPemupukan $realisasiPemupukan)
    {
        $validated = $request->validated();

        // Optimistic locking: cek apakah record berubah sejak form dibuka
        if ($request->has('_expected_updated_at')) {
            $expectedUpdatedAt = $request->input('_expected_updated_at');
            if ($realisasiPemupukan->updated_at->toDateTimeString() !== $expectedUpdatedAt) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Data telah diubah oleh pengguna lain. Muat ulang halaman dan coba kembali.');
            }
        }

        // Deteksi apakah ada perubahan nyata
        $adaPerubahan = (string) $realisasiPemupukan->tanggal_realisasi->toDateString() !== $validated['tanggal_realisasi']
            || (float) $realisasiPemupukan->urea_realisasi_kg !== (float) $validated['urea_realisasi_kg']
            || (float) $realisasiPemupukan->kcl_realisasi_kg !== (float) $validated['kcl_realisasi_kg']
            || $realisasiPemupukan->status_realisasi !== $validated['status_realisasi']
            || ($realisasiPemupukan->catatan_pelaksana ?? '') !== ($validated['catatan_pelaksana'] ?? '');

        if (! $adaPerubahan) {
            return redirect()
                ->route('realisasi-pemupukan.show', $realisasiPemupukan)
                ->with('success', 'Tidak ada perubahan yang perlu disimpan.');
        }

        // Validasi status SELESAI (4.3)
        $statusRealisasi = $validated['status_realisasi'];
        if ($statusRealisasi === RealisasiPemupukan::STATUS_SELESAI) {
            $blok = $realisasiPemupukan->blokLahan;
            $rekomendasi = $realisasiPemupukan->rekomendasiRbs;
            $summary = $rekomendasi->programPemupukan
                ? $this->realizationService->getRealizationSummaryForProgram($rekomendasi->programPemupukan)
                : $this->realizationService->getRealizationSummary($blok, $rekomendasi->id);

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
     * Cari duplikasi semantik: realisasi aktif identik dalam 5 menit terakhir.
     *
     * Kriteria duplikat:
     * - blok_lahan_id sama
     * - rekomendasi_rbs_id sama
     * - urea_realisasi_kg sama (toleransi 0.01)
     * - kcl_realisasi_kg sama (toleransi 0.01)
     * - tanggal_realisasi sama
     * - status bukan BATAL
     * - dibuat dalam 5 menit terakhir
     */
    private function findSemanticDuplicate(
        int $blokLahanId,
        int $rekomendasiRbsId,
        float $ureaRealisasi,
        float $kclRealisasi,
        string $tanggalRealisasi,
        string $statusRealisasi
    ): ?RealisasiPemupukan {
        $driver = DB::connection()->getDriverName();

        return RealisasiPemupukan::where('blok_lahan_id', $blokLahanId)
            ->where('rekomendasi_rbs_id', $rekomendasiRbsId)
            ->whereDate('tanggal_realisasi', $tanggalRealisasi)
            ->where('status_realisasi', '!=', RealisasiPemupukan::STATUS_BATAL)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->where(function ($query) use ($ureaRealisasi, $kclRealisasi, $driver) {
                if ($driver === 'sqlite') {
                    $query->whereRaw('CAST(urea_realisasi_kg AS REAL) BETWEEN ? AND ?', [$ureaRealisasi - 0.02, $ureaRealisasi + 0.02])
                        ->whereRaw('CAST(kcl_realisasi_kg AS REAL) BETWEEN ? AND ?', [$kclRealisasi - 0.02, $kclRealisasi + 0.02]);
                } else {
                    // MySQL/MariaDB: decimal columns can be compared directly
                    $query->whereRaw('urea_realisasi_kg BETWEEN ? AND ?', [$ureaRealisasi - 0.02, $ureaRealisasi + 0.02])
                        ->whereRaw('kcl_realisasi_kg BETWEEN ? AND ?', [$kclRealisasi - 0.02, $kclRealisasi + 0.02]);
                }
            })
            ->first();
    }

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
     * Ensure program pemupukan exists via ProgramPemupukanService (Pahan v2.8).
     * Juga memastikan rekomendasi terhubung ke program yang sama.
     */
    private function ensureProgram($blok, int $tahunProgram, RekomendasiRbs $rekomendasi): ProgramPemupukan
    {
        $program = $this->programService->resolveActiveProgram($blok, $tahunProgram, $rekomendasi);

        // Pastikan rekomendasi terhubung ke program yang sama (Pahan v2.8: 4.3)
        if ($rekomendasi->program_pemupukan_id !== $program->id) {
            $rekomendasi->update(['program_pemupukan_id' => $program->id]);
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

    /**
     * Kirim notifikasi setelah realisasi berhasil dicatat.
     */
    private function sendRealisasiNotification(
        RealisasiPemupukan $realisasi,
        RekomendasiRbs $rekomendasi,
        array $currentApp
    ): void {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            return;
        }

        $blok = $realisasi->blokLahan;
        $namaBlok = $blok->nama_blok ?? 'Blok';
        $url = route('realisasi-pemupukan.show', $realisasi);
        $statusStage = $currentApp['status_stage'] ?? null;

        // Notifikasi utama: realisasi berhasil dicatat
        $admin->notify(
            RealisasiNotification::realisasiDicatat($namaBlok, $realisasi->tahap, $url)
        );

        // Notifikasi tambahan berdasarkan status setelah realisasi
        if ($statusStage === 'SELESAI_TAHUNAN') {
            $admin->notify(
                RealisasiNotification::programSelesai($namaBlok, route('rbs.detail', $blok))
            );
        } elseif ($statusStage === 'MENUNGGU_INTERVAL') {
            // Tahap 1 selesai, Tahap 2 nanti
            // Notifikasi akan diperlukan nanti saat interval terpenuhi (via scheduled command)
        } elseif ($realisasi->status_realisasi === 'SEBAGIAN') {
            $admin->notify(
                RealisasiNotification::realisasiSebagian($namaBlok, $realisasi->tahap, route('rbs.detail', $blok))
            );
        }
    }
}
