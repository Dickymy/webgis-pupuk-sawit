<?php

namespace App\Console\Commands;

use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use App\Models\RuleBaseLanjutan;
use Illuminate\Console\Command;

class FinalizePahanV2_3 extends Command
{
    protected $signature = 'sawit:finalize-pahan-v2-3 {--dry-run : Hanya deteksi tanpa memperbaiki}';

    protected $description = 'Audit dan finalisasi data untuk Pahan v2.3';

    private int $issues = 0;

    private int $fixed = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? '🔍 Mode DRY-RUN: Hanya mendeteksi masalah.' : '🔧 Mode LIVE: Mendeteksi dan memperbaiki.');
        $this->newLine();

        $this->detectFaseConflicts($dryRun);
        $this->detectNullFaseAutofix($dryRun);
        $this->detectMissingUmurSnapshot();
        $this->detectMissingTanggalReferensi();
        $this->detectUnvalidatedSupportingDose();
        $this->detectOldSchedulePatterns();
        $this->detectLegacyVersions();
        $this->detectInconsistentStatuses();

        $this->newLine();
        $this->info("📊 Total masalah ditemukan: {$this->issues}");
        if (! $dryRun) {
            $this->info("✅ Diperbaiki: {$this->fixed}");
        }

        return $this->issues > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function detectFaseConflicts(bool $dryRun): void
    {
        $this->info('── Konflik Fase ──');
        $bloks = BlokLahan::whereNotNull('fase_tanaman')->whereNotNull('tahun_tanam')->get();

        foreach ($bloks as $blok) {
            $umur = now()->year - $blok->tahun_tanam;
            $conflict = false;

            if ($umur < 3 && $blok->fase_tanaman === 'TM') {
                $conflict = true;
            }
            if ($umur > 3 && $blok->fase_tanaman === 'TBM') {
                $conflict = true;
            }

            if ($conflict) {
                $this->issues++;
                $this->warn("  ⚠ Blok #{$blok->id} '{$blok->nama_blok}': umur={$umur}, fase={$blok->fase_tanaman}");
                if (! $dryRun) {
                    $newFase = $umur < 3 ? 'TBM' : 'TM';
                    $blok->update(['fase_tanaman' => $newFase]);
                    $this->fixed++;
                    $this->line("    → Diperbaiki: fase={$newFase}");
                }
            }
        }
    }

    private function detectNullFaseAutofix(bool $dryRun): void
    {
        $this->info('── Fase NULL yang Bisa Diotomasikan ──');
        $bloks = BlokLahan::whereNull('fase_tanaman')->whereNotNull('tahun_tanam')->get();

        foreach ($bloks as $blok) {
            $umur = now()->year - $blok->tahun_tanam;

            if ($umur === 3) {
                $this->issues++;
                $this->warn("  ⚠ Blok #{$blok->id} '{$blok->nama_blok}': umur=3, fase=NULL — perlu verifikasi manual");

                continue;
            }

            $this->issues++;
            $newFase = $umur < 3 ? 'TBM' : 'TM';
            $this->warn("  ⚠ Blok #{$blok->id} '{$blok->nama_blok}': umur={$umur}, fase=NULL → seharusnya {$newFase}");

            if (! $dryRun) {
                $blok->update(['fase_tanaman' => $newFase]);
                $this->fixed++;
                $this->line("    → Diperbaiki: fase={$newFase}");
            }
        }
    }

    private function detectMissingUmurSnapshot(): void
    {
        $this->info('── Rekomendasi Tanpa Umur Snapshot ──');
        $count = RekomendasiRbs::whereNull('umur_tanaman_snapshot')->count();
        if ($count > 0) {
            $this->issues += $count;
            $this->warn("  ⚠ {$count} rekomendasi tanpa umur_tanaman_snapshot");
        }
    }

    private function detectMissingTanggalReferensi(): void
    {
        $this->info('── Rekomendasi Tanpa Tanggal Referensi ──');
        $count = RekomendasiRbs::whereNull('tanggal_referensi_umur')
            ->where('versi_mesin_rekomendasi', '!=', 'legacy-v1')
            ->count();
        if ($count > 0) {
            $this->issues += $count;
            $this->warn("  ⚠ {$count} rekomendasi tanpa tanggal_referensi_umur");
        }
    }

    private function detectUnvalidatedSupportingDose(): void
    {
        $this->info('── Pupuk Pendukung Belum Tervalidasi ──');
        $rules = RuleBaseLanjutan::aktif()
            ->where(function ($q) {
                $q->whereNull('status_validasi')
                    ->orWhereNotIn('status_validasi', ['TERVERIFIKASI_SUMBER', 'TERVERIFIKASI_AHLI']);
            })
            ->whereNotNull('dosis_anjuran')
            ->where('jenis_pupuk_utama', 'NOT LIKE', '%Urea%')
            ->where('jenis_pupuk_utama', 'NOT LIKE', '%KCl%')
            ->where('jenis_pupuk_utama', 'NOT LIKE', '%MOP%')
            ->get();

        if ($rules->isNotEmpty()) {
            $this->issues += $rules->count();
            foreach ($rules->take(5) as $rule) {
                $this->warn("  ⚠ Rule #{$rule->id}: {$rule->jenis_pupuk_utama} — dosis belum tervalidasi");
            }
            if ($rules->count() > 5) {
                $this->warn('  ... dan '.($rules->count() - 5).' lainnya');
            }
        }
    }

    private function detectOldSchedulePatterns(): void
    {
        $this->info('── Jadwal Pola Lama (60/40, 70/30, Maret/September) ──');
        $rbs = RekomendasiRbs::whereNotNull('jadwal_pemupukan')
            ->where('is_latest', true)
            ->get();

        $count = 0;
        foreach ($rbs as $r) {
            $jadwal = $r->jadwal_pemupukan;
            if (! is_array($jadwal)) {
                continue;
            }
            $jadwalStr = json_encode($jadwal);
            if (str_contains($jadwalStr, '70') || str_contains($jadwalStr, '60') || str_contains($jadwalStr, 'Maret') || str_contains($jadwalStr, 'September')) {
                $count++;
            }
        }

        if ($count > 0) {
            $this->issues += $count;
            $this->warn("  ⚠ {$count} rekomendasi masih menggunakan pola jadwal lama");
        }
    }

    private function detectLegacyVersions(): void
    {
        $this->info('── Histori Versi Lama ──');
        $versions = RekomendasiRbs::selectRaw('versi_mesin_rekomendasi, COUNT(*) as total')
            ->groupBy('versi_mesin_rekomendasi')
            ->get();

        foreach ($versions as $v) {
            $label = $v->versi_mesin_rekomendasi ?? 'NULL';
            $this->line("  {$label}: {$v->total} rekomendasi");
        }
    }

    private function detectInconsistentStatuses(): void
    {
        $this->info('── Status Kondisi dan Kelayakan Tidak Konsisten ──');
        $count = RekomendasiRbs::where('is_latest', true)
            ->where(function ($q) {
                $q->whereNull('status_kondisi_tanaman')
                    ->orWhereNull('status_kelayakan_aplikasi');
            })
            ->where('versi_mesin_rekomendasi', '!=', 'legacy-v1')
            ->count();

        if ($count > 0) {
            $this->issues += $count;
            $this->warn("  ⚠ {$count} rekomendasi aktif tanpa status kondisi atau kelayakan");
        }
    }
}
