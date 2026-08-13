<?php

namespace App\Console\Commands;

use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HealthCheck — Periksa integritas data dan konsistensi sistem.
 *
 * php artisan sawit:health-check --dry-run
 */
class HealthCheck extends Command
{
    protected $signature = 'sawit:health-check {--dry-run : Hanya audit, tidak mengubah data}';

    protected $description = 'Periksa integritas database dan konsistensi data SawitGIS.';

    private int $issueCount = 0;

    private int $warningCount = 0;

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info(' HEALTH CHECK — SawitGIS Database Integrity');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Database
        $this->info('── DATABASE ──────────────────────────────────────────────────');
        $this->checkConnection();

        // Program
        $this->info('── PROGRAM ───────────────────────────────────────────────────');
        $this->checkProgramAktifGanda();
        $this->checkProgramSelesaiDenganSisa();
        $this->checkProgramAktifSudahTerpenuhi();

        // Rekomendasi
        $this->info('── REKOMENDASI ───────────────────────────────────────────────');
        $this->checkRekomendasiTerbaruGanda();
        $this->checkRekomendasiTanpaProgram();
        $this->checkSnapshotKosong();
        $this->checkVersiMesin();
        $this->checkFingerprintKosong();

        // Realisasi
        $this->info('── REALISASI ─────────────────────────────────────────────────');
        $this->checkRealisasiTanpaBlok();
        $this->checkRealisasiTanpaRekomendasi();
        $this->checkMismatchProgram();
        $this->checkTahap2TanpaTahap1();
        $this->checkTahap2SebelumIntervalMinimum();
        $this->checkTanggalMasaDepan();
        $this->checkSelesaiDiBawahRencana();
        $this->checkBatalTerhitung();
        $this->checkSubmissionTokenGanda();
        $this->checkDuplikasiAktifIdentik();

        // Histori
        $this->info('── HISTORI ───────────────────────────────────────────────────');
        $this->checkHistoriTersedia();

        // Konfigurasi
        $this->info('── KONFIGURASI ───────────────────────────────────────────────');
        $this->checkConfigVersion();
        $this->checkBackupNotPublic();

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');

        if ($this->issueCount === 0 && $this->warningCount === 0) {
            $this->info('✅ Tidak ada masalah ditemukan. Database sehat.');

            return self::SUCCESS;
        }

        if ($this->issueCount > 0) {
            $this->error("❌ Ditemukan {$this->issueCount} masalah kritis, {$this->warningCount} peringatan.");

            return self::FAILURE;
        }

        $this->warn("⚠ Ditemukan {$this->warningCount} peringatan (tidak kritis).");

        return self::SUCCESS;
    }

    private function checkConnection(): void
    {
        try {
            DB::connection()->getPdo();
            $this->line('   ✓ Koneksi database aktif');
        } catch (\Exception $e) {
            $this->error('   ✗ Koneksi database gagal: '.$e->getMessage());
            $this->issueCount++;
        }
    }

    private function checkProgramAktifGanda(): void
    {
        if (! Schema::hasTable('program_pemupukans')) {
            return;
        }

        $ganda = DB::table('program_pemupukans')
            ->select('blok_lahan_id', 'tahun_program')
            ->where('status_program', 'AKTIF')
            ->groupBy('blok_lahan_id', 'tahun_program')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($ganda > 0) {
            $this->error("   ✗ {$ganda} kombinasi blok/tahun memiliki program aktif ganda");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada program aktif ganda');
        }
    }

    private function checkProgramSelesaiDenganSisa(): void
    {
        if (! Schema::hasTable('program_pemupukans')) {
            return;
        }

        $programs = ProgramPemupukan::where('status_program', 'SELESAI')->get();
        $issues = 0;

        foreach ($programs as $program) {
            $rbs = RekomendasiRbs::where('program_pemupukan_id', $program->id)
                ->where('is_latest', true)
                ->first();

            if ($rbs && ((float) ($rbs->urea_sisa_tahunan ?? 0) > 0.01 || (float) ($rbs->kcl_sisa_tahunan ?? 0) > 0.01)) {
                $issues++;
            }
        }

        if ($issues > 0) {
            $this->warn("   ⚠ {$issues} program SELESAI masih memiliki sisa pupuk");
            $this->warningCount++;
        } else {
            $this->line('   ✓ Program selesai konsisten');
        }
    }

    private function checkProgramAktifSudahTerpenuhi(): void
    {
        if (! Schema::hasTable('program_pemupukans')) {
            return;
        }

        $programs = ProgramPemupukan::where('status_program', 'AKTIF')->get();
        $issues = 0;

        foreach ($programs as $program) {
            $rbs = RekomendasiRbs::where('program_pemupukan_id', $program->id)
                ->where('is_latest', true)
                ->first();

            if ($rbs
                && (float) ($rbs->urea_sisa_tahunan ?? 1) <= 0
                && (float) ($rbs->kcl_sisa_tahunan ?? 1) <= 0
            ) {
                $issues++;
            }
        }

        if ($issues > 0) {
            $this->error("   ✗ {$issues} program AKTIF tetapi kebutuhan tahunan sudah terpenuhi");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada program aktif yang sudah terpenuhi');
        }
    }

    private function checkRekomendasiTerbaruGanda(): void
    {
        $ganda = DB::table('rekomendasi_rbs')
            ->select('blok_lahan_id')
            ->where('is_latest', true)
            ->groupBy('blok_lahan_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($ganda > 0) {
            $this->error("   ✗ {$ganda} blok memiliki lebih dari satu rekomendasi terbaru");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada rekomendasi terbaru ganda');
        }
    }

    private function checkRekomendasiTanpaProgram(): void
    {
        if (! Schema::hasColumn('rekomendasi_rbs', 'program_pemupukan_id')) {
            return;
        }

        $count = RekomendasiRbs::where('is_latest', true)
            ->whereNull('program_pemupukan_id')
            ->where('urea_total_estimasi_tahunan', '>', 0)
            ->count();

        if ($count > 0) {
            $this->warn("   ⚠ {$count} rekomendasi terbaru (dengan dosis) tanpa program");
            $this->warningCount++;
        } else {
            $this->line('   ✓ Semua rekomendasi terbaru memiliki program');
        }
    }

    private function checkSnapshotKosong(): void
    {
        $count = RekomendasiRbs::where('is_latest', true)
            ->where('urea_total_estimasi_tahunan', '>', 0)
            ->where(function ($q) {
                $q->whereNull('luas_ha_snapshot')
                    ->orWhereNull('sph_snapshot')
                    ->orWhereNull('jumlah_pokok_snapshot');
            })
            ->count();

        if ($count > 0) {
            $this->warn("   ⚠ {$count} rekomendasi baru dengan snapshot kosong");
            $this->warningCount++;
        } else {
            $this->line('   ✓ Snapshot lengkap pada rekomendasi baru');
        }
    }

    private function checkVersiMesin(): void
    {
        $expected = config('fertilization.engine_version');
        $nonMatch = RekomendasiRbs::where('is_latest', true)
            ->where('versi_mesin_rekomendasi', '!=', $expected)
            ->whereNotNull('versi_mesin_rekomendasi')
            ->count();

        if ($nonMatch > 0) {
            $this->warn("   ⚠ {$nonMatch} rekomendasi terbaru bukan versi {$expected}");
            $this->warningCount++;
        } else {
            $this->line("   ✓ Versi mesin konsisten ({$expected})");
        }
    }

    private function checkFingerprintKosong(): void
    {
        $count = RekomendasiRbs::where('is_latest', true)
            ->whereNull('analysis_fingerprint')
            ->count();

        if ($count > 0) {
            $this->warn("   ⚠ {$count} rekomendasi terbaru tanpa fingerprint");
            $this->warningCount++;
        } else {
            $this->line('   ✓ Fingerprint lengkap');
        }
    }

    private function checkRealisasiTanpaBlok(): void
    {
        $count = RealisasiPemupukan::whereNull('blok_lahan_id')->count();

        if ($count > 0) {
            $this->error("   ✗ {$count} realisasi tanpa blok_lahan_id");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Semua realisasi terhubung ke blok');
        }
    }

    private function checkRealisasiTanpaRekomendasi(): void
    {
        $count = RealisasiPemupukan::whereNull('rekomendasi_rbs_id')
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        if ($count > 0) {
            $this->error("   ✗ {$count} realisasi aktif tanpa rekomendasi");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Semua realisasi aktif terhubung ke rekomendasi');
        }
    }

    private function checkMismatchProgram(): void
    {
        if (! Schema::hasColumn('realisasi_pemupukans', 'program_pemupukan_id')) {
            return;
        }

        $mismatch = DB::table('realisasi_pemupukans as r')
            ->join('rekomendasi_rbs as rbs', 'r.rekomendasi_rbs_id', '=', 'rbs.id')
            ->whereNotNull('r.program_pemupukan_id')
            ->whereNotNull('rbs.program_pemupukan_id')
            ->whereColumn('r.program_pemupukan_id', '!=', 'rbs.program_pemupukan_id')
            ->where('r.status_realisasi', '!=', 'BATAL')
            ->count();

        if ($mismatch > 0) {
            $this->error("   ✗ {$mismatch} realisasi dengan mismatch program");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada mismatch program');
        }
    }

    private function checkTahap2TanpaTahap1(): void
    {
        $tahap2Bloks = RealisasiPemupukan::where('tahap', 2)
            ->where('status_realisasi', '!=', 'BATAL')
            ->select('blok_lahan_id', 'program_pemupukan_id')
            ->distinct()
            ->get();

        $issues = 0;
        foreach ($tahap2Bloks as $record) {
            $query = RealisasiPemupukan::where('blok_lahan_id', $record->blok_lahan_id)
                ->where('tahap', 1)
                ->where('status_realisasi', '!=', 'BATAL');

            if ($record->program_pemupukan_id) {
                $query->where('program_pemupukan_id', $record->program_pemupukan_id);
            }

            if ($query->count() === 0) {
                $issues++;
            }
        }

        if ($issues > 0) {
            $this->error("   ✗ {$issues} Tahap 2 tanpa Tahap 1");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Semua Tahap 2 memiliki Tahap 1');
        }
    }

    private function checkTahap2SebelumIntervalMinimum(): void
    {
        $minInterval = (int) config('fertilization.window.min_interval_days', 120);
        $tahap2Records = RealisasiPemupukan::where('tahap', 2)
            ->where('status_realisasi', '!=', 'BATAL')
            ->get();

        $issues = 0;
        foreach ($tahap2Records as $record) {
            $tahap1Terakhir = RealisasiPemupukan::where('blok_lahan_id', $record->blok_lahan_id)
                ->when($record->program_pemupukan_id, fn ($query, $programId) => $query->where('program_pemupukan_id', $programId))
                ->where('tahap', 1)
                ->where('status_realisasi', '!=', 'BATAL')
                ->whereDate('tanggal_realisasi', '<=', $record->tanggal_realisasi)
                ->max('tanggal_realisasi');

            if ($tahap1Terakhir && $record->tanggal_realisasi) {
                $diff = Carbon::parse($tahap1Terakhir)->diffInDays($record->tanggal_realisasi, true);
                if ($diff < $minInterval) {
                    $issues++;
                }
            }
        }

        if ($issues > 0) {
            $this->error("   ✗ {$issues} realisasi Tahap 2 < {$minInterval} hari setelah Tahap 1");
            $this->issueCount++;
        } else {
            $this->line("   ✓ Interval {$minInterval} hari terpenuhi");
        }
    }

    private function checkTanggalMasaDepan(): void
    {
        $count = RealisasiPemupukan::where('tanggal_realisasi', '>', now())
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        if ($count > 0) {
            $this->warn("   ⚠ {$count} realisasi dengan tanggal di masa depan");
            $this->warningCount++;
        } else {
            $this->line('   ✓ Tidak ada tanggal masa depan');
        }
    }

    private function checkSelesaiDiBawahRencana(): void
    {
        // Cek realisasi SELESAI yang per-record realisasinya di bawah rencana.
        // Catatan: realisasi boleh SELESAI meski per-record < rencana
        // jika ada beberapa record SEBAGIAN sebelumnya yang secara kumulatif memenuhi rencana.
        // Check ini hanya memberi peringatan untuk kasus yang patut diperiksa.
        $issues = RealisasiPemupukan::where('status_realisasi', 'SELESAI')
            ->whereRaw('(urea_rencana_kg > 0 AND urea_realisasi_kg < urea_rencana_kg * 0.50)')
            ->whereRaw('(kcl_rencana_kg > 0 AND kcl_realisasi_kg < kcl_rencana_kg * 0.50)')
            ->count();

        if ($issues > 0) {
            $this->warn("   ⚠ {$issues} realisasi SELESAI dengan jumlah < 50% rencana (patut dicek manual)");
            $this->warningCount++;
        } else {
            $this->line('   ✓ Realisasi selesai konsisten');
        }
    }

    private function checkBatalTerhitung(): void
    {
        // This is a code-level check — ensure cancelled realizations aren't counted
        $file = app_path('Services/FertilizationRealizationService.php');
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (str_contains($content, "!= 'BATAL'") || str_contains($content, 'STATUS_BATAL') || str_contains($content, 'aktif()')) {
                $this->line('   ✓ Realisasi BATAL tidak ikut terhitung (code check)');
            } else {
                $this->warn('   ⚠ Perlu verifikasi filter realisasi BATAL di FertilizationRealizationService');
                $this->warningCount++;
            }
        }
    }

    private function checkSubmissionTokenGanda(): void
    {
        if (! Schema::hasColumn('realisasi_pemupukans', 'submission_token')) {
            return;
        }

        $ganda = DB::table('realisasi_pemupukans')
            ->select('submission_token')
            ->whereNotNull('submission_token')
            ->groupBy('submission_token')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($ganda > 0) {
            $this->error("   ✗ {$ganda} submission_token digunakan lebih dari sekali (KRITIS: duplikat)");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada submission_token ganda');
        }
    }

    private function checkDuplikasiAktifIdentik(): void
    {
        // Cari dua realisasi aktif identik yang dibuat dalam waktu sangat dekat (< 5 menit)
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: gunakan julianday untuk diff dalam detik
            $duplicates = DB::table('realisasi_pemupukans as a')
                ->join('realisasi_pemupukans as b', function ($join) {
                    $join->on('a.blok_lahan_id', '=', 'b.blok_lahan_id')
                        ->on('a.rekomendasi_rbs_id', '=', 'b.rekomendasi_rbs_id')
                        ->on('a.tahap', '=', 'b.tahap')
                        ->on('a.tanggal_realisasi', '=', 'b.tanggal_realisasi')
                        ->on('a.urea_realisasi_kg', '=', 'b.urea_realisasi_kg')
                        ->on('a.kcl_realisasi_kg', '=', 'b.kcl_realisasi_kg')
                        ->whereColumn('a.id', '<', 'b.id');
                })
                ->where('a.status_realisasi', '!=', 'BATAL')
                ->where('b.status_realisasi', '!=', 'BATAL')
                ->whereRaw('ABS((julianday(b.created_at) - julianday(a.created_at)) * 86400) < 300')
                ->count();
        } else {
            // MySQL: TIMESTAMPDIFF
            $duplicates = DB::table('realisasi_pemupukans as a')
                ->join('realisasi_pemupukans as b', function ($join) {
                    $join->on('a.blok_lahan_id', '=', 'b.blok_lahan_id')
                        ->on('a.rekomendasi_rbs_id', '=', 'b.rekomendasi_rbs_id')
                        ->on('a.tahap', '=', 'b.tahap')
                        ->on('a.tanggal_realisasi', '=', 'b.tanggal_realisasi')
                        ->on('a.urea_realisasi_kg', '=', 'b.urea_realisasi_kg')
                        ->on('a.kcl_realisasi_kg', '=', 'b.kcl_realisasi_kg')
                        ->whereColumn('a.id', '<', 'b.id');
                })
                ->where('a.status_realisasi', '!=', 'BATAL')
                ->where('b.status_realisasi', '!=', 'BATAL')
                ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, a.created_at, b.created_at)) < 300')
                ->count();
        }

        if ($duplicates > 0) {
            $this->error("   ✗ {$duplicates} pasangan realisasi aktif identik dalam < 5 menit (KRITIS: duplikat double-submit)");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada duplikasi realisasi aktif identik');
        }
    }

    private function checkHistoriTersedia(): void
    {
        if (! Schema::hasTable('rekomendasi_operasional_histories')) {
            $this->warn('   ⚠ Tabel histori operasional belum ada');
            $this->warningCount++;

            return;
        }

        $this->line('   ✓ Tabel histori operasional tersedia');
    }

    private function checkConfigVersion(): void
    {
        $version = config('fertilization.engine_version');
        $this->line("   ✓ Engine version: {$version}");

    }

    private function checkBackupNotPublic(): void
    {
        $publicBackup = public_path('backups');
        if (is_dir($publicBackup)) {
            $this->error('   ✗ Direktori backups berada di public/!');
            $this->issueCount++;
        } else {
            $this->line('   ✓ Backup tidak di public/');
        }
    }
}
