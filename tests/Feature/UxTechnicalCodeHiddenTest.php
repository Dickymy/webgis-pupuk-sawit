<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Test kode teknis tidak tampil langsung di UI (Pahan v2.8 — 5.3).
 */
class UxTechnicalCodeHiddenTest extends TestCase
{
    /**
     * Kode teknis yang TIDAK boleh ditampilkan langsung ke user.
     */
    private array $technicalCodes = [
        'TAHAP_1_SIAP',
        'TAHAP_1_SEBAGIAN',
        'MENUNGGU_INTERVAL',
        'MENUNGGU_KELAYAKAN',
        'TAHAP_2_SIAP',
        'SELESAI_TAHUNAN',
        'PERLU_VERIFIKASI_REALISASI',
        'LAYAK_DIJADWALKAN',
        'TERLAMBAT_PERLU_DIJADWALKAN',
        'TUNDA_HUJAN_RENDAH',
        'TUNDA_HUJAN_TINGGI',
        'TUNDA_INTERVAL',
        'PERLU_PERBAIKAN_DRAINASE',
        'GEJALA_BERAT',
        'TERINDIKASI_DEFISIENSI',
        'NORMAL_VISUAL',
        'BELUM_DIOBSERVASI',
    ];

    public function test_technical_codes_not_displayed_raw_in_views(): void
    {
        $viewPaths = [
            resource_path('views/rbs'),
            resource_path('views/realisasi_pemupukan'),
            resource_path('views/laporan'),
            resource_path('views/dashboard'),
        ];

        $violations = [];

        foreach ($viewPaths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $content = File::get($file->getPathname());
                $relPath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());

                foreach ($this->technicalCodes as $code) {
                    // Check for raw display: {{ 'CODE' }} or > CODE <
                    if (preg_match('/>\s*'.preg_quote($code, '/').'\s*</', $content)) {
                        $violations[] = "{$relPath}: raw display of {$code}";
                    }
                    if (preg_match('/\{\{\s*[\'"]'.preg_quote($code, '/').'[\'"]\s*\}\}/', $content)) {
                        $violations[] = "{$relPath}: direct echo of '{$code}'";
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Technical codes displayed raw in views:\n".implode("\n", $violations)
        );
    }

    public function test_user_facing_code_has_no_broken_utf8_characters(): void
    {
        $paths = [
            app_path(),
            resource_path('views'),
        ];

        $brokenMarkers = [
            "\u{00E2}\u{20AC}\u{201D}",
            "\u{00E2}\u{2020}\u{2019}",
            "\u{00C2}",
            "\u{00C3}",
        ];

        $violations = [];

        foreach ($paths as $path) {
            foreach (File::allFiles($path) as $file) {
                $content = File::get($file->getPathname());

                foreach ($brokenMarkers as $marker) {
                    if (str_contains($content, $marker)) {
                        $violations[] = str_replace(
                            base_path().DIRECTORY_SEPARATOR,
                            '',
                            $file->getPathname()
                        );
                        break;
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Broken UTF-8 text found in user-facing code:\n".implode("\n", $violations)
        );
    }
}
