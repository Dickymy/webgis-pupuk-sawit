<?php

namespace App\Console\Commands;

use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiOperasionalHistory;
use App\Models\RekomendasiRbs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ResetDemoData — Hapus HANYA data demo (prefix "DEMO -").
 *
 * php artisan sawit:reset-demo-data
 * php artisan sawit:reset-demo-data --dry-run
 * php artisan sawit:reset-demo-data --force  (untuk production)
 */
class ResetDemoData extends Command
{
    protected $signature = 'sawit:reset-demo-data {--dry-run : Hanya tampilkan apa yang akan dihapus} {--force : Izinkan di production}';

    protected $description = 'Hapus semua data demo (prefix DEMO -) tanpa menyentuh data nyata.';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Command ini tidak dapat berjalan di production tanpa flag --force.');

            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        // Find demo anggota
        $demoAnggota = Anggota::where('nama', 'like', 'DEMO -%')->get();
        $demoBlok = BlokLahan::where('nama_blok', 'like', 'DEMO -%')->get();
        $demoBlokIds = $demoBlok->pluck('id')->toArray();

        // Count related records
        $kondisiCount = KondisiLahan::whereIn('blok_lahan_id', $demoBlokIds)->count();
        $rekomendasiCount = RekomendasiRbs::whereIn('blok_lahan_id', $demoBlokIds)->count();
        $realisasiCount = RealisasiPemupukan::whereIn('blok_lahan_id', $demoBlokIds)->count();
        $programCount = ProgramPemupukan::whereIn('blok_lahan_id', $demoBlokIds)->count();

        $this->info('Data demo yang akan dihapus:');
        $this->table(
            ['Jenis', 'Jumlah'],
            [
                ['Anggota', $demoAnggota->count()],
                ['Blok Lahan', $demoBlok->count()],
                ['Kondisi Lahan', $kondisiCount],
                ['Rekomendasi RBS', $rekomendasiCount],
                ['Realisasi Pemupukan', $realisasiCount],
                ['Program Pemupukan', $programCount],
            ]
        );

        if ($dryRun) {
            $this->warn('[DRY-RUN] Tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Yakin ingin menghapus semua data demo di atas?')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($demoBlokIds, $demoAnggota) {
            // Delete in order of dependencies
            // Histori operasional terhubung via rekomendasi_rbs_id
            $rekomendasiIds = RekomendasiRbs::whereIn('blok_lahan_id', $demoBlokIds)->pluck('id');
            RekomendasiOperasionalHistory::whereIn('rekomendasi_rbs_id', $rekomendasiIds)->delete();
            RealisasiPemupukan::whereIn('blok_lahan_id', $demoBlokIds)->delete();
            RekomendasiRbs::whereIn('blok_lahan_id', $demoBlokIds)->delete();
            ProgramPemupukan::whereIn('blok_lahan_id', $demoBlokIds)->delete();
            KondisiLahan::whereIn('blok_lahan_id', $demoBlokIds)->delete();
            BlokLahan::whereIn('id', $demoBlokIds)->delete();
            Anggota::whereIn('id', $demoAnggota->pluck('id'))->delete();
        });

        $this->info('✅ Data demo berhasil dihapus.');

        return self::SUCCESS;
    }
}
