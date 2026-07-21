<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * BackupDatabase — Backup database MySQL ke storage/app/backups.
 *
 * php artisan sawit:backup-database
 */
class BackupDatabase extends Command
{
    protected $signature = 'sawit:backup-database';

    protected $description = 'Backup database MySQL ke storage/app/backups/';

    public function handle(): int
    {
        $connection = config('database.default');

        if ($connection !== 'mysql') {
            $this->error("Backup hanya mendukung MySQL. Koneksi saat ini: {$connection}");

            return self::FAILURE;
        }

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $timestamp = now()->format('Y-m-d_His');
        $filename = "backup_{$database}_{$timestamp}.sql";
        $directory = 'backups';

        // Ensure backups directory exists
        if (! Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        $filepath = storage_path("app/{$directory}/{$filename}");

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '--password='.escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $this->info("Memulai backup database '{$database}'...");

        $returnCode = null;
        $output = [];
        exec($command.' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Backup gagal: '.implode("\n", $output));
            $this->error('Pastikan mysqldump tersedia di PATH.');

            return self::FAILURE;
        }

        $size = filesize($filepath);
        $sizeFormatted = number_format($size / 1024, 1).' KB';

        $this->info("✅ Backup berhasil: {$filename} ({$sizeFormatted})");
        $this->info("   Lokasi: storage/app/{$directory}/{$filename}");

        return self::SUCCESS;
    }
}
