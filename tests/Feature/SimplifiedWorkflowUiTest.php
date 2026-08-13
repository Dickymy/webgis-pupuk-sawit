<?php

namespace Tests\Feature;

use Tests\TestCase;

class SimplifiedWorkflowUiTest extends TestCase
{
    public function test_sidebar_only_contains_primary_workflow_destinations(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertSame(7, substr_count($layout, 'class="sidebar-link'));
        $this->assertStringContainsString('Data Kebun', $layout);
        $this->assertStringContainsString('Observasi', $layout);
        $this->assertStringContainsString('Rekomendasi Pupuk', $layout);
        $this->assertStringContainsString('Realisasi Pupuk', $layout);
        $this->assertStringNotContainsString("route('anggota.index') }}\"\n               class=\"sidebar-link", $layout);
        $this->assertStringNotContainsString("route('settings.index') }}\"\n               class=\"sidebar-link", $layout);
        $this->assertStringContainsString('Referensi', $layout);
        $this->assertStringContainsString('Rule Based', $layout);
        $this->assertStringContainsString("route('rule-base.index') }}\"\n               class=\"sidebar-link", $layout);
    }

    public function test_data_kebun_keeps_block_and_member_access_in_contextual_tabs(): void
    {
        $tabs = file_get_contents(resource_path('views/components/data-kebun-tabs.blade.php'));
        $blockIndex = file_get_contents(resource_path('views/blok_lahan/index.blade.php'));
        $memberIndex = file_get_contents(resource_path('views/anggota/index.blade.php'));

        $this->assertStringContainsString("route('blok-lahan.index')", $tabs);
        $this->assertStringContainsString("route('anggota.index')", $tabs);
        $this->assertStringContainsString("@include('components.data-kebun-tabs')", $blockIndex);
        $this->assertStringContainsString("@include('components.data-kebun-tabs')", $memberIndex);
    }

    public function test_data_entry_forms_use_the_full_content_width(): void
    {
        $blockCreate = file_get_contents(resource_path('views/blok_lahan/create.blade.php'));
        $blockEdit = file_get_contents(resource_path('views/blok_lahan/edit.blade.php'));
        $memberCreate = file_get_contents(resource_path('views/anggota/create.blade.php'));
        $memberEdit = file_get_contents(resource_path('views/anggota/edit.blade.php'));

        foreach ([$blockCreate, $blockEdit, $memberCreate, $memberEdit] as $view) {
            $this->assertStringContainsString('<div class="w-full">', $view);
        }
        $this->assertStringNotContainsString('max-w-4xl mx-auto', $blockCreate);
        $this->assertStringNotContainsString('max-w-4xl mx-auto', $blockEdit);
        $this->assertStringNotContainsString('max-w-2xl mx-auto', $memberCreate);
        $this->assertStringNotContainsString('max-w-2xl mx-auto', $memberEdit);

        $this->assertStringContainsString('lg:grid-cols-2', $blockCreate);
        $this->assertStringContainsString('lg:grid-cols-2', $blockEdit);
        $this->assertStringContainsString('lg:grid-cols-2', $memberCreate);
        $this->assertStringContainsString('lg:grid-cols-2', $memberEdit);
    }

    public function test_detail_analysis_places_schedule_before_optional_explanations(): void
    {
        $detail = file_get_contents(resource_path('views/rbs/partials/_detail_readable.blade.php'));

        $this->assertStringContainsString('IF (FAKTA DARI OBSERVASI LAPANGAN)', $detail);
        $this->assertStringContainsString('THEN (KESIMPULAN SISTEM)', $detail);
    }

    public function test_observation_form_uses_three_progressive_steps(): void
    {
        $create = file_get_contents(resource_path('views/kondisi_lahan/create.blade.php'));
        $edit = file_get_contents(resource_path('views/kondisi_lahan/edit.blade.php'));
        $form = file_get_contents(resource_path('views/kondisi_lahan/_form.blade.php'));
        $stepper = file_get_contents(resource_path('views/components/observation-stepper.blade.php'));

        foreach ([$create, $edit] as $view) {
            $this->assertStringContainsString("@include('kondisi_lahan._form')", $view);
            $this->assertStringContainsString('enctype="multipart/form-data"', $view);
        }

        $this->assertStringContainsString("@include('components.observation-stepper')", $form);
        $this->assertStringContainsString('data-observation-step="1"', $form);
        $this->assertStringContainsString('data-observation-step="2"', $form);
        $this->assertStringContainsString('data-observation-step="3"', $form);
        $this->assertStringContainsString('Foto pendukung', $form);
        $this->assertStringContainsString('1. Data hujan dan musim', $form);
        $this->assertStringContainsString(":'data_angka');", $form);
        $this->assertStringNotContainsString('pH tanah', $form);
        $this->assertStringNotContainsString('Kondisi pelepah', $form);
        $this->assertStringNotContainsString('Kondisi tandan', $form);
        $this->assertStringContainsString('Kondisi Tanaman', $stepper);
        $this->assertStringContainsString('Kesiapan Pupuk', $stepper);
        $this->assertStringNotContainsString('max-w-4xl mx-auto', $create.$edit.$form);
    }

    public function test_recommendation_page_exposes_rbs_reference_contextually(): void
    {
        $view = file_get_contents(resource_path('views/rbs/index.blade.php'));

        $this->assertStringContainsString('Pahan (2013)', $view);
        $this->assertStringContainsString("route('rule-base.index')", $view);
        $this->assertStringContainsString('Rule Based', $view);
        $this->assertStringContainsString('Perbarui Rekomendasi', $view);
        $this->assertStringNotContainsString('Tindakan massal', $view);
        $this->assertStringContainsString('Lihat Detail', $view);
    }

    public function test_layout_keeps_tailwind_width_limits_and_search_filter_can_reset(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $filter = file_get_contents(resource_path('views/components/filter-searchable.blade.php'));

        $this->assertStringNotContainsString('*, *::before, *::after { max-width: 100%; }', $layout);
        $this->assertStringContainsString('max-width: calc(100vw - 32px) !important;', $layout);
        $this->assertStringContainsString('position: fixed !important;', $layout);
        $this->assertStringContainsString('closeDropdown(true);', $filter);
        $this->assertStringContainsString('matchedOptions === 0', $filter);
        $this->assertStringContainsString("empty.classList.toggle('hidden'", $filter);
        $this->assertStringContainsString("input.setAttribute('aria-expanded', 'false')", $filter);
    }

    public function test_supporting_pages_use_full_width_and_clear_language(): void
    {
        $settings = file_get_contents(resource_path('views/settings/index.blade.php'));
        $realization = file_get_contents(resource_path('views/realisasi_pemupukan/index.blade.php'));

        $this->assertStringContainsString('<div class="w-full space-y-4">', $settings);
        $this->assertStringContainsString('Ganti Kata Sandi', $settings);
        $this->assertStringContainsString('Tema Aplikasi', $settings);
        $this->assertStringNotContainsString('max-w-3xl mx-auto', $settings);
        $this->assertStringContainsString('Siap Dipupuk', $realization);
        $this->assertStringContainsString('Riwayat Pemupukan', $realization);
        $this->assertStringNotContainsString('Belum Bisa Dilaksanakan', $realization);
    }

    public function test_mobile_workflow_has_clear_navigation_and_touch_targets(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $action = file_get_contents(resource_path('views/components/next-block-action.blade.php'));
        $observation = file_get_contents(resource_path('views/kondisi_lahan/index.blade.php'));
        $recommendation = file_get_contents(resource_path('views/rbs/index.blade.php'));
        $realization = file_get_contents(resource_path('views/realisasi_pemupukan/index.blade.php'));
        $dashboard = file_get_contents(resource_path('views/dashboard/index.blade.php'));

        $this->assertStringContainsString('mobile-bottom-nav', $layout);
        $this->assertStringContainsString('Navigasi utama ponsel', $layout);
        $this->assertStringContainsString('mobile-safe-main', $layout);
        $this->assertStringContainsString('min-h-11', $action);
        $this->assertStringContainsString('bg-emerald-600', $action);
        $this->assertStringContainsString('Kontrol daftar observasi', $observation);
        $this->assertStringContainsString('grid grid-cols-2 gap-1 rounded-xl', $observation);
        $this->assertStringContainsString('sm:grid-cols-4', $observation);
        $this->assertStringContainsString('Buka &rarr;', $recommendation);
        $this->assertStringContainsString('grid grid-cols-3 gap-1', $realization);
        $this->assertStringNotContainsString('flex min-w-max gap-1', $realization);
        $this->assertStringContainsString('Filter Peta', $dashboard);
        $this->assertStringContainsString('<details class="group', $dashboard);
    }
}
