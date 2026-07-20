<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Menggantikan route /fix-cache yang dihapus karena alasan keamanan.
 * Hanya dapat dijalankan melalui terminal.
 */
class MaintenanceClearCache extends Command
{
    protected $signature = 'sawit:clear-cache';

    protected $description = 'Bersihkan config, cache, route, dan view cache';

    public function handle(): int
    {
        Artisan::call('config:clear');
        $this->info('Config cache cleared.');

        Artisan::call('cache:clear');
        $this->info('Application cache cleared.');

        Artisan::call('route:clear');
        $this->info('Route cache cleared.');

        Artisan::call('view:clear');
        $this->info('View cache cleared.');

        $this->newLine();
        $this->info('Semua cache berhasil dibersihkan.');

        return self::SUCCESS;
    }
}
