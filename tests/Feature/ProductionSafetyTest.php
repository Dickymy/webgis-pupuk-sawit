<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionSafetyTest extends TestCase
{
    public function test_env_production_debug_off(): void
    {
        $envProd = base_path('.env.production');
        if (! File::exists($envProd)) {
            $this->markTestSkipped('.env.production not found');
        }

        $content = File::get($envProd);
        $this->assertStringContainsString('APP_DEBUG=false', $content);
    }

    public function test_no_public_backups_directory(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('backups'));
    }

    public function test_no_migrate_fresh_in_production_commands(): void
    {
        // Ensure no command does migrate:fresh outside testing
        $commands = File::allFiles(app_path('Console/Commands'));
        $this->assertNotEmpty($commands, 'Direktori command harus berisi file yang dapat diaudit.');

        foreach ($commands as $file) {
            $content = File::get($file->getPathname());
            if (str_contains($content, 'migrate:fresh') || str_contains($content, 'db:wipe')) {
                // If it exists, it must have environment guard
                $this->assertTrue(
                    str_contains($content, "environment('testing')") || str_contains($content, 'testing'),
                    "File {$file->getFilename()} has migrate:fresh without testing guard"
                );
            }
        }
    }

    public function test_backup_storage_not_in_public(): void
    {
        $this->assertStringNotContainsString(
            'public',
            storage_path('app/backups'),
            'Backup path should not be in public directory'
        );
    }

    public function test_csrf_middleware_active(): void
    {
        // Laravel 11 handles CSRF via global middleware — verify it's not disabled
        $bootstrapFile = base_path('bootstrap/app.php');
        $content = file_get_contents($bootstrapFile);

        // CSRF should not be explicitly removed
        $this->assertStringNotContainsString(
            'withoutMiddleware',
            $content,
            'Global middleware should not disable CSRF'
        );
        $this->assertStringNotContainsString(
            "validateCsrfTokens(except: ['*'])",
            $content,
            'CSRF tidak boleh dikecualikan untuk seluruh route'
        );
        $this->assertStringContainsString(
            '$middleware->validateCsrfTokens();',
            $content,
            'Middleware CSRF harus diaktifkan secara eksplisit'
        );
    }
}
