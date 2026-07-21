<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * BackupList — Tampilkan daftar backup yang tersedia.
 *
 * php artisan sawit:backup-list
 */
class BackupList extends Command
{
    protected $signature = 'sawit:backup-list';

    protected $description = 'Tampilkan daftar backup database yang tersedia.';

    public function handle(): int
    {
        $directory = 'backups';

        if (! Storage::exists($directory)) {
            $this->warn('Belum ada backup. Jalankan: php artisan sawit:backup-database');

            return self::SUCCESS;
        }

        $files = Storage::files($directory);
        $sqlFiles = array_filter($files, fn ($f) => str_ends_with($f, '.sql'));

        if (empty($sqlFiles)) {
            $this->warn('Belum ada file backup ditemukan.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($sqlFiles as $file) {
            $size = Storage::size($file);
            $lastModified = date('Y-m-d H:i:s', Storage::lastModified($file));
            $rows[] = [
                basename($file),
                number_format($size / 1024, 1).' KB',
                $lastModified,
            ];
        }

        // Sort by newest first
        usort($rows, fn ($a, $b) => strcmp($b[2], $a[2]));

        $this->table(['File', 'Ukuran', 'Tanggal'], $rows);
        $this->info('Lokasi: storage/app/backups/');

        return self::SUCCESS;
    }
}
