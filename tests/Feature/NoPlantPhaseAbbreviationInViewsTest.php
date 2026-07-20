<?php

namespace Tests\Feature;

use Tests\TestCase;

class NoPlantPhaseAbbreviationInViewsTest extends TestCase
{
    /**
     * Allowlist: penggunaan internal yang sah.
     * Pattern ini TIDAK dianggap sebagai singkatan visible.
     */
    private array $allowedPatterns = [
        'value="TBM"',
        'value="TM"',
        "value='TBM'",
        "value='TM'",
        "=== 'TBM'",
        "=== 'TM'",
        "== 'TBM'",
        "== 'TM'",
        '=== "TBM"',
        '=== "TM"',
        "!== 'TBM'",
        "!== 'TM'",
        "=> 'TBM'",
        "=> 'TM'",
        'id="banner-tbm"',
        'id="tandan-tbm-note"',
        'id="tandan-hidden-tbm"',
        "getElementById('banner-tbm')",
        "getElementById('tandan-tbm-note')",
        "getElementById('tandan-hidden-tbm')",
        '{{-- Banner Info TBM',
        '// TBM:',
    ];

    /**
     * Patterns yang menunjukkan singkatan tampil sebagai teks visible.
     */
    private array $forbiddenPatterns = [
        '/>\s*TBM\s*</',       // <span>TBM</span>
        '/>\s*TM\s*</',        // <span>TM</span>
        '/>\s*fase TBM/',      // >fase TBM
        '/>\s*fase TM/',       // >fase TM
        '/>\s*sawit TBM/',     // >sawit TBM
        '/"TBM"/',             // Label "TBM" di JSON tooltip
        '/"TM"/',              // Label "TM" di JSON tooltip
    ];

    public function test_no_visible_tbm_tm_abbreviation_in_blade_files(): void
    {
        $viewsPath = resource_path('views');
        $bladeFiles = $this->getBladeFiles($viewsPath);

        $this->assertNotEmpty($bladeFiles, 'Tidak ditemukan file Blade.');

        $violations = [];

        foreach ($bladeFiles as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNum => $line) {
                // Skip if line matches allowed pattern
                if ($this->isAllowed($line)) {
                    continue;
                }

                // Check forbidden patterns
                foreach ($this->forbiddenPatterns as $pattern) {
                    if (preg_match($pattern, $line)) {
                        $relativePath = str_replace($viewsPath.DIRECTORY_SEPARATOR, '', $file);
                        $violations[] = "{$relativePath}:".($lineNum + 1).' => '.trim($line);
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Singkatan TBM/TM ditemukan dalam teks visible:\n".implode("\n", $violations)
        );
    }

    public function test_blade_files_count(): void
    {
        $viewsPath = resource_path('views');
        $bladeFiles = $this->getBladeFiles($viewsPath);

        // Minimal ada beberapa file Blade
        $this->assertGreaterThan(10, count($bladeFiles));
    }

    private function isAllowed(string $line): bool
    {
        foreach ($this->allowedPatterns as $allowed) {
            if (str_contains($line, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function getBladeFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
