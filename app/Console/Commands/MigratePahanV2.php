<?php

namespace App\Console\Commands;

use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use App\Models\RuleBaseLanjutan;
use Illuminate\Console\Command;

/**
 * Command untuk migrasi data lama ke format Pahan-v2.
 *
 * Aman dijalankan berulang kali (idempotent).
 * Tidak mengubah data jika --dry-run digunakan.
 */
class MigratePahanV2 extends Command
{
    protected $signature = 'sawit:migrate-pahan-v2 {--dry-run : Tampilkan perubahan tanpa mengeksekusi}';

    protected $description = 'Migrasi data lama ke format Pahan-v2 (fase tanaman, label rekomendasi lama)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $prefix = $dryRun ? '[DRY-RUN] ' : '';

        $this->info($prefix.'═══════════════════════════════════════════');
        $this->info($prefix.' MIGRASI DATA KE PAHAN-V2');
        $this->info($prefix.'═══════════════════════════════════════════');
        $this->newLine();

        // ─── 1. Identifikasi blok tanpa fase ───
        $blokTanpaFase = BlokLahan::whereNull('fase_tanaman')->get();
        $this->info($prefix."Blok tanpa fase_tanaman: {$blokTanpaFase->count()}");

        $autoTBM = 0;
        $autoTM = 0;
        $perluVerifikasi = 0;

        foreach ($blokTanpaFase as $blok) {
            $umur = $blok->umur_tanaman;
            if ($umur === null) {
                $perluVerifikasi++;
                $this->line("  - {$blok->nama_blok}: tahun_tanam kosong → PERLU VERIFIKASI");
            } elseif ($umur < 3) {
                $autoTBM++;
                if (! $dryRun) {
                    $blok->update(['fase_tanaman' => 'TBM']);
                }
                $this->line("  - {$blok->nama_blok}: umur {$umur} → TBM");
            } elseif ($umur === 3) {
                $perluVerifikasi++;
                $this->line("  - {$blok->nama_blok}: umur 3 → PERLU VERIFIKASI (bisa TBM/TM)");
            } else {
                $autoTM++;
                if (! $dryRun) {
                    $blok->update(['fase_tanaman' => 'TM']);
                }
                $this->line("  - {$blok->nama_blok}: umur {$umur} → TM");
            }
        }

        $this->newLine();
        $this->info($prefix."Auto-set TBM: {$autoTBM} | Auto-set TM: {$autoTM} | Perlu verifikasi: {$perluVerifikasi}");
        $this->newLine();

        // ─── 2. Label rekomendasi lama sebagai legacy-v1 ───
        $rekLama = RekomendasiRbs::whereNull('versi_mesin_rekomendasi')
            ->orWhere('versi_mesin_rekomendasi', 'legacy-v1')
            ->count();

        $rekTanpaVersi = RekomendasiRbs::whereNull('versi_mesin_rekomendasi')->count();

        $this->info($prefix."Rekomendasi tanpa versi mesin: {$rekTanpaVersi}");

        if (! $dryRun && $rekTanpaVersi > 0) {
            RekomendasiRbs::whereNull('versi_mesin_rekomendasi')
                ->update(['versi_mesin_rekomendasi' => 'legacy-v1']);
            $this->info($prefix.'  → Ditandai sebagai legacy-v1');
        }

        $this->newLine();

        // ─── 3. Rule tanpa kode ───
        $ruleTanpaKode = RuleBaseLanjutan::whereNull('kode_rule')->count();
        $this->info($prefix."Rule tanpa kode_rule: {$ruleTanpaKode}");

        if (! $dryRun && $ruleTanpaKode > 0) {
            // Assign auto-kode untuk yang belum punya
            $rules = RuleBaseLanjutan::whereNull('kode_rule')->get();
            foreach ($rules as $i => $rule) {
                $prefix_code = 'LEGACY';
                $rule->update([
                    'kode_rule' => $prefix_code.'-'.str_pad($rule->id, 3, '0', STR_PAD_LEFT),
                    'status_validasi' => 'PERLU_VALIDASI_AHLI',
                ]);
            }
            $this->info('  → Auto-assigned kode LEGACY-xxx');
        }

        $this->newLine();

        // ─── 4. Statistik akhir ───
        $this->info($prefix.'═══════════════════════════════════════════');
        $this->info($prefix.' RINGKASAN');
        $this->info($prefix.'═══════════════════════════════════════════');
        $this->table(['Metrik', 'Jumlah'], [
            ['Total blok', BlokLahan::count()],
            ['Blok dengan fase', BlokLahan::whereNotNull('fase_tanaman')->count()],
            ['Blok tanpa fase (perlu verifikasi)', BlokLahan::whereNull('fase_tanaman')->count()],
            ['Total rekomendasi', RekomendasiRbs::count()],
            ['Rekomendasi legacy-v1', RekomendasiRbs::where('versi_mesin_rekomendasi', 'legacy-v1')->count()],
            ['Rekomendasi pahan-v2', RekomendasiRbs::where('versi_mesin_rekomendasi', 'pahan-v2')->count()],
            ['Rule dengan sumber', RuleBaseLanjutan::where('status_validasi', 'TERVERIFIKASI_SUMBER')->count()],
            ['Rule perlu validasi', RuleBaseLanjutan::where('status_validasi', 'PERLU_VALIDASI_AHLI')->count()],
        ]);

        if ($dryRun) {
            $this->newLine();
            $this->warn('Mode DRY-RUN: Tidak ada perubahan yang disimpan.');
            $this->info('Jalankan tanpa --dry-run untuk mengeksekusi migrasi.');
        } else {
            $this->newLine();
            $this->info('✅ Migrasi selesai.');
        }

        return self::SUCCESS;
    }
}
