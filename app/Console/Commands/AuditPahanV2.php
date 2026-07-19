<?php

namespace App\Console\Commands;

use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use App\Models\RuleBaseLanjutan;
use Illuminate\Console\Command;

/**
 * Command audit untuk memeriksa konsistensi data Pahan-v2.
 */
class AuditPahanV2 extends Command
{
    protected $signature = 'sawit:audit-pahan-v2';
    protected $description = 'Audit konsistensi data dan rule Pahan-v2';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════');
        $this->info(' AUDIT PAHAN-V2');
        $this->info('═══════════════════════════════════════════');
        $this->newLine();

        $issues = [];

        // 1. Blok tanpa fase
        $blokTanpaFase = BlokLahan::whereNull('fase_tanaman')->count();
        $this->line("Blok tanpa fase_tanaman: {$blokTanpaFase}");
        if ($blokTanpaFase > 0) $issues[] = "Ada {$blokTanpaFase} blok tanpa fase tanaman";

        // 2. Blok umur 3 tahun belum diverifikasi
        $blokUmur3 = BlokLahan::whereNull('fase_tanaman')
            ->whereRaw('(YEAR(CURDATE()) - tahun_tanam) = 3')
            ->count();
        $this->line("Blok umur 3 tahun belum verifikasi: {$blokUmur3}");
        if ($blokUmur3 > 0) $issues[] = "Ada {$blokUmur3} blok umur 3 tahun perlu verifikasi fase";

        // 3. Rekomendasi tanpa versi mesin
        $rekTanpaVersi = RekomendasiRbs::whereNull('versi_mesin_rekomendasi')->count();
        $this->line("Rekomendasi tanpa versi_mesin: {$rekTanpaVersi}");
        if ($rekTanpaVersi > 0) $issues[] = "Ada {$rekTanpaVersi} rekomendasi tanpa versi mesin";

        // 4. Rule tanpa kode
        $ruleTanpaKode = RuleBaseLanjutan::whereNull('kode_rule')
            ->orWhere('kode_rule', '')
            ->count();
        $this->line("Rule tanpa kode_rule: {$ruleTanpaKode}");
        if ($ruleTanpaKode > 0) $issues[] = "Ada {$ruleTanpaKode} rule tanpa kode";

        // 5. Rule tanpa sumber
        $ruleTanpaSumber = RuleBaseLanjutan::whereNull('sumber_penulis')
            ->whereNull('sumber_judul')
            ->count();
        $this->line("Rule tanpa sumber: {$ruleTanpaSumber}");
        if ($ruleTanpaSumber > 0) $issues[] = "Ada {$ruleTanpaSumber} rule tanpa sumber";

        // 6. Rule PERLU_VALIDASI_AHLI
        $rulePerluValidasi = RuleBaseLanjutan::where('status_validasi', 'PERLU_VALIDASI_AHLI')->count();
        $this->line("Rule PERLU_VALIDASI_AHLI: {$rulePerluValidasi}");

        // 7. Rule yang masih mengandung teks dosis legacy
        $ruleWithLegacyDose = RuleBaseLanjutan::where(function ($q) {
            $q->where('dosis_anjuran', 'LIKE', '%kurangi dosis%')
              ->orWhere('dosis_anjuran', 'LIKE', '%Kurangi dosis%')
              ->orWhere('dosis_anjuran', 'LIKE', '%dosis penuh%')
              ->orWhere('dosis_anjuran', 'LIKE', '%kg/pokok%')
              ->orWhere('saran_tindakan', 'LIKE', '%kurangi dosis%')
              ->orWhere('saran_tindakan', 'LIKE', '%Kurangi dosis%');
        })->count();
        $this->line("Rule dengan teks dosis legacy: {$ruleWithLegacyDose}");
        if ($ruleWithLegacyDose > 0) $issues[] = "Ada {$ruleWithLegacyDose} rule dengan teks dosis legacy yang berpotensi konflik";

        // 8. Rekomendasi terbaru masih legacy-v1
        $rekLatestLegacy = RekomendasiRbs::where('is_latest', true)
            ->where('versi_mesin_rekomendasi', 'legacy-v1')
            ->count();
        $this->line("Rekomendasi terbaru masih legacy-v1: {$rekLatestLegacy}");
        if ($rekLatestLegacy > 0) $issues[] = "Ada {$rekLatestLegacy} rekomendasi aktif masih versi legacy-v1";

        // Ringkasan
        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info(' RINGKASAN');
        $this->info('═══════════════════════════════════════════');

        $this->table(['Metrik', 'Jumlah'], [
            ['Total blok', BlokLahan::count()],
            ['Total rule', RuleBaseLanjutan::count()],
            ['Total rekomendasi', RekomendasiRbs::count()],
            ['Rule aktif', RuleBaseLanjutan::where('aktif', true)->count()],
            ['Rule terverifikasi', RuleBaseLanjutan::where('status_validasi', 'TERVERIFIKASI_SUMBER')->count()],
        ]);

        if (empty($issues)) {
            $this->newLine();
            $this->info('✓ Tidak ada masalah ditemukan.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('MASALAH DITEMUKAN:');
        foreach ($issues as $i => $issue) {
            $this->line('  ' . ($i + 1) . '. ' . $issue);
        }

        return self::FAILURE;
    }
}
