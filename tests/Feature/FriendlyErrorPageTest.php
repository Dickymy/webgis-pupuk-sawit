<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FriendlyErrorPageTest extends TestCase
{
    public function test_404_page_exists(): void
    {
        $this->assertTrue(File::exists(resource_path('views/errors/404.blade.php')));
    }

    public function test_500_page_exists(): void
    {
        $this->assertTrue(File::exists(resource_path('views/errors/500.blade.php')));
    }

    public function test_419_page_exists(): void
    {
        $this->assertTrue(File::exists(resource_path('views/errors/419.blade.php')));
    }

    public function test_403_page_exists(): void
    {
        $this->assertTrue(File::exists(resource_path('views/errors/403.blade.php')));
    }

    public function test_503_page_exists(): void
    {
        $this->assertTrue(File::exists(resource_path('views/errors/503.blade.php')));
    }

    public function test_error_pages_no_stack_trace(): void
    {
        $errorViews = [
            '403.blade.php',
            '404.blade.php',
            '419.blade.php',
            '422.blade.php',
            '500.blade.php',
            '503.blade.php',
        ];

        foreach ($errorViews as $view) {
            $path = resource_path("views/errors/{$view}");
            if (File::exists($path)) {
                $content = File::get($path);
                $this->assertStringNotContainsString('stack', strtolower($content), "Error page {$view} should not show stack trace");
                $this->assertStringNotContainsString('exception', strtolower($content), "Error page {$view} should not show exception details");
            }
        }
    }

    public function test_404_returns_friendly_page(): void
    {
        $response = $this->get('/halaman-yang-tidak-ada-sama-sekali');
        $response->assertStatus(404);
    }
}
