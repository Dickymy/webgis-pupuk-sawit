<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NoManualVerificationFeatureTest extends TestCase
{
    public function test_no_verification_route(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringNotContainsString('verifikasi', $routes);
        $this->assertStringNotContainsString('verification', $routes);
    }

    public function test_no_verification_menu_in_sidebar(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringNotContainsString('Verifikasi', $layout);
        $this->assertStringNotContainsString('verifikasi', strtolower($layout));
    }

    public function test_no_validasi_ahli_in_active_weights(): void
    {
        $weights = config('fertilization.reliability_weights');

        $this->assertArrayNotHasKey('validasi_ahli', $weights);
    }

    public function test_reliability_service_no_validasi_ahli(): void
    {
        $content = file_get_contents(app_path('Services/RecommendationReliabilityService.php'));

        $this->assertStringNotContainsString("'validasi_ahli'", $content);
    }

    public function test_no_status_verifikasi_gejala_in_active_form(): void
    {
        // Check that the kondisi lahan create/edit form doesn't show verification status
        $viewPaths = [
            resource_path('views/kondisi_lahan/create.blade.php'),
            resource_path('views/kondisi_lahan/edit.blade.php'),
        ];

        foreach ($viewPaths as $path) {
            if (File::exists($path)) {
                $content = File::get($path);
                $this->assertStringNotContainsString(
                    'status_verifikasi_gejala',
                    $content,
                    "Form masih menampilkan status_verifikasi_gejala: {$path}"
                );
            }
        }
    }
}
