@extends('layouts.app')

@section('title', 'Dashboard WebGIS')
@section('page-title', 'Dashboard WebGIS')
@section('page-subtitle', 'Ringkasan kondisi kebun dan pelaksanaan pemupukan')

@push('styles')
<style>
    #map { height: calc(100vh - 250px); min-height: 440px; border-radius: 12px; }
    @media (max-width: 640px) { #map { height: 360px; min-height: 300px; border-radius: 8px; } }
    @media (min-width: 1024px) { #map { min-height: 420px; } }

    .leaflet-tooltip-label { background: transparent !important; border: none !important; box-shadow: none !important; color: #1e293b; font-size: 10px; font-weight: 700; text-shadow: 0 0 3px #fff, 0 0 3px #fff, 0 0 3px #fff; padding: 0 !important; }
    .leaflet-popup-content-wrapper { background: #fff; border: 1px solid #e2e8f0; color: #1e293b; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .leaflet-popup-tip { background: #fff; }
    .leaflet-popup-content { margin: 10px 12px; max-height: 250px; overflow-y: auto; }
    .leaflet-popup-content div, .leaflet-popup-content span, .leaflet-popup-content p, .leaflet-popup-content a { color: inherit; }
    @media (max-width: 640px) {
        .leaflet-popup-content { margin: 8px 10px; max-height: 180px; font-size: 11px; }
        .leaflet-popup-content-wrapper { max-width: 260px !important; }
    }
    .leaflet-control-zoom { display: none !important; }
    .zoom-slider-container { position: absolute; bottom: 16px; left: 16px; z-index: 1000; display: flex; flex-direction: column; align-items: center; gap: 0; background: rgba(255,255,255,0.96); border: 1px solid #e2e8f0; border-radius: 10px; padding: 6px 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.10); backdrop-filter: blur(6px); }
    .zoom-slider-container button { width: 28px; height: 28px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 6px; color: #374151; font-size: 16px; font-weight: 700; transition: background 0.15s, color 0.15s; user-select: none; line-height: 1; }
    .zoom-slider-container button:hover { background: #f0fdf4; color: #059669; }
    .zoom-slider-container input[type="range"] { -webkit-appearance: none; appearance: none; width: 4px; height: 100px; background: #e2e8f0; border-radius: 4px; outline: none; writing-mode: vertical-lr; direction: rtl; margin: 4px 0; cursor: pointer; }
    .zoom-slider-container input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 14px; height: 14px; background: #059669; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.15); cursor: pointer; }
    .zoom-slider-container input[type="range"]::-moz-range-thumb { width: 14px; height: 14px; background: #059669; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.15); cursor: pointer; }
    @media (max-width: 640px) { .zoom-slider-container { bottom: 56px; left: 10px; padding: 4px 4px; } .zoom-slider-container button { width: 24px; height: 24px; font-size: 14px; } .zoom-slider-container input[type="range"] { height: 70px; } }
    #map-container.is-fullscreen { position: fixed !important; inset: 0 !important; z-index: 8000 !important; border-radius: 0 !important; margin: 0 !important; display: flex !important; flex-direction: column !important; height: 100vh !important; }
    #map-container.is-fullscreen #map-body { flex: 1 !important; min-height: 0 !important; display: flex !important; flex-direction: column !important; padding: 0 !important; }
    #map-container.is-fullscreen #map { flex: 1 !important; height: 100% !important; min-height: unset !important; border-radius: 0 !important; }
    #map-container.is-fullscreen .zoom-slider-container { bottom: 24px; left: 16px; }
    .scrollbar-none::-webkit-scrollbar { display: none !important; }
    .scrollbar-none { -ms-overflow-style: none !important; scrollbar-width: none !important; }

    /* Filter peta memakai warna hanya sebagai penanda tindakan berikutnya. */
    .status-filter-btn { display: inline-flex; min-height: 34px; align-items: center; justify-content: center; gap: 6px; padding: 5px 9px; border-radius: 8px; font-size: 10px; font-weight: 600; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer; transition: all 0.15s; user-select: none; white-space: nowrap; line-height: 1.25; }
    .status-filter-btn::before { content: ''; width: 7px; height: 7px; flex: 0 0 auto; border-radius: 9999px; background: #94a3b8; }
    .status-filter-btn.active { border-color: #059669; background: #f8fafc; color: #0f172a; box-shadow: inset 0 0 0 1px #059669; }
    .status-filter-btn.inactive { opacity: 0.48; box-shadow: none; }
    .status-filter-btn .status-area { margin-left: 1px; border-radius: 9999px; background: #f1f5f9; padding: 2px 5px; font-size: 9px; font-weight: 700; color: #475569; }
    .status-filter-btn[data-status="BELUM_DIPERIKSA"]::before { background: #64748b; }
    .status-filter-btn[data-status="ADA_GEJALA"]::before { background: #f97316; }
    .status-filter-btn[data-status="SIAP_DIPUPUK"]::before { background: #16a34a; }
    .status-filter-btn[data-status="DITUNDA"]::before { background: #2563eb; }
    .dark .status-filter-btn { border-color: #475569; background: #0f172a; color: #cbd5e1; }
    .dark .status-filter-btn.active { border-color: #34d399; background: #1e293b; color: #fff; box-shadow: inset 0 0 0 1px #34d399; }
    .dark .status-filter-btn .status-area { background: #334155; color: #e2e8f0; }
    @media (max-width: 640px) { .status-filter-btn { min-height: 40px; width: 100%; justify-content: flex-start; padding: 7px 9px; font-size: 10px; } }
    /* Luas per status */
    @media (max-width: 640px) { #stats-cards .stat-card { padding: 8px 10px; } #stats-cards .stat-value { font-size: 1.2rem; } #stats-cards .stat-label { font-size: 9px; } }
    #filter-pemilik, #filter-pemilik-mobile, #filter-blok, #filter-blok-mobile { -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") !important; background-repeat: no-repeat !important; background-position: right 8px center !important; background-size: 12px 12px !important; }
    select:disabled { opacity: 0.5; cursor: not-allowed; background: #f1f5f9; }
    .btn-map { display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.15s; white-space: nowrap; border: 1.5px solid; text-decoration: none; }
    .btn-map.expand { background: #fff; color: #047857; border-color: #a7f3d0; }
    .btn-map.expand:hover { background: #ecfdf5; border-color: #34d399; }
    .btn-map.shrink { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
    .btn-map.tambah { background: #059669; color: #fff; border-color: #059669; }
    .btn-map.tambah:hover { background: #047857; border-color: #047857; }
    @media (max-width: 640px) { .btn-map { padding: 6px 10px; font-size: 10px; } }
</style>
@endpush

@section('content')

{{-- Ringkasan utama: permukaan netral, warna hanya sebagai penanda status --}}
<section class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-5" id="stats-cards">
    <a href="{{ route('blok-lahan.index') }}" class="stat-card group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
        <p class="stat-label flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500"><span class="h-2 w-2 rounded-full bg-slate-500"></span>Blok Lahan</p>
        <div class="mt-2 flex items-end justify-between gap-2"><p class="stat-value text-2xl font-bold text-slate-900 dark:text-white" id="stat-total-blok">{{ $stats['total_blok'] }}</p><span class="text-[10px] text-slate-400" id="stat-total-luas">{{ number_format($stats['total_luas'], 2) }} Ha</span></div>
        <p class="mt-2 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">Buka data &rarr;</p>
    </a>
    <a href="{{ route('kondisi-lahan.index', ['status' => 'belum']) }}" class="stat-card group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
        <p class="stat-label flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Perlu Observasi</p>
        <div class="mt-2 flex items-end justify-between gap-2"><p class="stat-value text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['belum_kondisi'] }}</p><span class="text-right text-[10px] text-slate-400">blok belum lengkap</span></div>
        <p class="mt-2 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">Isi observasi &rarr;</p>
    </a>
    <a href="{{ route('realisasi-pemupukan.index', ['tab' => 'siap']) }}" class="stat-card group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
        <p class="stat-label flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Siap Dipupuk</p>
        <div class="mt-2 flex items-end justify-between gap-2"><p class="stat-value text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['siap_dipupuk'] }}</p><span class="text-right text-[10px] text-slate-400">dapat dilaksanakan</span></div>
        <p class="mt-2 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">Catat realisasi &rarr;</p>
    </a>
    <a href="{{ route('rbs.index', ['status' => 'menunggu-interval']) }}" class="stat-card group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
        <p class="stat-label flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500"><span class="h-2 w-2 rounded-full bg-indigo-500"></span>Menunggu Jarak Waktu</p>
        <div class="mt-2 flex items-end justify-between gap-2"><p class="stat-value text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['menunggu_interval'] }}</p><span class="text-right text-[10px] text-slate-400">menuju tahap berikutnya</span></div>
        <p class="mt-2 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">Lihat jadwal &rarr;</p>
    </a>
    <a href="{{ route('laporan.index', ['status_program' => 'SELESAI', 'tahun_program' => now()->year]) }}" class="stat-card group col-span-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 md:col-span-1">
        <p class="stat-label flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500"><span class="h-2 w-2 rounded-full bg-blue-500"></span>Program Selesai</p>
        <div class="mt-2 flex items-end justify-between gap-2"><p class="stat-value text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['program_selesai'] }}</p><span class="text-right text-[10px] text-slate-400">program tahun ini</span></div>
        <p class="mt-2 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">Buka laporan &rarr;</p>
    </a>
</section>
{{-- Prioritas hari ini --}}
@if($blokPerluTindakan->isNotEmpty())
<section class="mb-4 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="mb-2.5 flex items-center justify-between gap-3">
        <div>
            <p class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-white"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Prioritas Hari Ini</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ $blokPerluTindakan->count() }} blok memerlukan tindak lanjut</p>
        </div>
        <a href="{{ route('rbs.index', ['status' => 'perlu-tindakan']) }}" class="shrink-0 text-[10px] font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Lihat semua →</a>
    </div>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        @foreach($blokPerluTindakan->take(3) as $bp)
        @php
            $operationalStatus = $bp->operational_eligibility['status_stage'] ?? $bp->rekomendasiRbsTerbaru?->status_stage;
            if (!$bp->kondisiTerbaru) {
                $keterangan = 'Isi observasi lapangan';
                $dotClass = 'bg-slate-500';
                $actionUrl = route('kondisi-lahan.create', ['blok_lahan_id' => $bp->id]);
            } elseif (!$bp->rekomendasiRbsTerbaru) {
                $keterangan = 'Buat rekomendasi pupuk';
                $dotClass = 'bg-blue-500';
                $actionUrl = route('rbs.detail', $bp);
            } elseif (in_array($bp->rekomendasiRbsTerbaru->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
                || $bp->rekomendasiRbsTerbaru->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA') {
                $keterangan = 'Lengkapi observasi';
                $dotClass = 'bg-amber-500';
                $actionUrl = route('kondisi-lahan.edit', $bp->kondisiTerbaru);
            } elseif ($bp->kondisiTerbaru->updated_at->gt($bp->rekomendasiRbsTerbaru->updated_at)) {
                $keterangan = 'Perbarui rekomendasi';
                $dotClass = 'bg-violet-500';
                $actionUrl = route('rbs.detail', $bp);
            } elseif ($operationalStatus === 'TAHAP_1_SEBAGIAN') {
                $keterangan = 'Lanjutkan realisasi Tahap 1';
                $dotClass = 'bg-amber-500';
                $actionUrl = route('realisasi-pemupukan.create', $bp->rekomendasiRbsTerbaru);
            } elseif ($operationalStatus === 'TAHAP_2_SIAP') {
                $keterangan = 'Catat realisasi Tahap 2';
                $dotClass = 'bg-emerald-500';
                $actionUrl = route('realisasi-pemupukan.create', $bp->rekomendasiRbsTerbaru);
            } else {
                $keterangan = 'Catat realisasi Tahap 1';
                $dotClass = 'bg-emerald-500';
                $actionUrl = route('realisasi-pemupukan.create', $bp->rekomendasiRbsTerbaru);
            }
        @endphp
        <a href="{{ $actionUrl }}" class="flex min-h-14 min-w-0 items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 transition hover:border-emerald-300 hover:bg-white hover:shadow-sm dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-emerald-700">
            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $dotClass }}"></span>
            <span class="min-w-0">
                <span class="block truncate text-[11px] font-semibold text-slate-800 dark:text-slate-100">{{ $bp->nama_blok }}</span>
                <span class="block truncate text-[9px] text-slate-500 dark:text-slate-400">{{ $keterangan }} &middot; Buka &rarr;</span>
            </span>
        </a>
        @endforeach
    </div>
</section>
@endif
{{-- Map Container --}}
<div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800" id="map-container">
    {{-- HEADER BAR --}}
    <div class="border-b border-slate-100 px-3 py-2.5 dark:border-slate-700 sm:px-4" id="map-header">
        {{-- Desktop layout --}}
        <div class="hidden flex-col gap-2 sm:flex 2xl:flex-row 2xl:items-center">
            <div class="flex items-center gap-1.5" id="status-filter-buttons-desktop">
                <button type="button" class="status-filter-btn active" data-status="BELUM_DIPERIKSA" aria-label="Belum Diperiksa" aria-pressed="true" onclick="toggleStatusFilter(this)"><span aria-hidden="true">Belum Diperiksa</span><span class="status-area" id="luas-belum-diperiksa">0 Ha</span></button>
                <button type="button" class="status-filter-btn active" data-status="ADA_GEJALA" aria-label="Ditemukan Gejala" aria-pressed="true" onclick="toggleStatusFilter(this)"><span aria-hidden="true">Ditemukan Gejala</span><span class="status-area" id="luas-ada-gejala">0 Ha</span></button>
                <button type="button" class="status-filter-btn active" data-status="SIAP_DIPUPUK" aria-label="Siap Dipupuk" aria-pressed="true" onclick="toggleStatusFilter(this)"><span aria-hidden="true">Siap Dipupuk</span><span class="status-area" id="luas-siap-dipupuk">0 Ha</span></button>
                <button type="button" class="status-filter-btn active" data-status="DITUNDA" aria-label="Belum Saatnya Dipupuk" aria-pressed="true" onclick="toggleStatusFilter(this)"><span aria-hidden="true">Belum Saatnya Dipupuk</span><span class="status-area" id="luas-ditunda">0 Ha</span></button>
            </div>
            <div class="hidden flex-1 2xl:block"></div>
            <div class="flex items-center justify-end gap-2">
            <div class="relative">
                <select id="filter-pemilik" class="min-w-[140px] cursor-pointer rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-2.5 pr-7 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                    <option value="">Semua Pemilik</option>
                </select>
            </div>
            <div class="relative">
                <select id="filter-blok" disabled class="min-w-[130px] cursor-pointer rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-2.5 pr-7 text-xs font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                    <option value="">Semua Blok</option>
                </select>
            </div>
            <button type="button" onclick="toggleFullscreen()" class="btn-map expand" id="btn-fs-desktop"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg><span id="btn-fs-desktop-text">Perluas Peta</span></button>
            <a href="{{ route('blok-lahan.create') }}" class="btn-map tambah"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Tambah Blok</a>
            </div>
        </div>

        {{-- Mobile layout: filter lanjutan disembunyikan agar peta tetap menjadi fokus --}}
        <div class="space-y-2 sm:hidden">
            <details class="group rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50">
                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 px-3 py-2.5">
                    <span><span class="block text-xs font-semibold text-slate-800 dark:text-slate-100">Filter Peta</span><span class="block text-[9px] text-slate-400">Kondisi, pemilik, dan blok</span></span>
                    <svg class="h-4 w-4 shrink-0 text-slate-500 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="space-y-3 border-t border-slate-200 p-3 dark:border-slate-700">
                    <div class="grid grid-cols-1 gap-2" id="status-filter-buttons-mobile">
                        <button type="button" class="status-filter-btn active" data-status="BELUM_DIPERIKSA" aria-pressed="true" onclick="toggleStatusFilter(this)">Belum Diperiksa</button>
                        <button type="button" class="status-filter-btn active" data-status="ADA_GEJALA" aria-pressed="true" onclick="toggleStatusFilter(this)">Ditemukan Gejala</button>
                        <button type="button" class="status-filter-btn active" data-status="SIAP_DIPUPUK" aria-pressed="true" onclick="toggleStatusFilter(this)">Siap Dipupuk</button>
                        <button type="button" class="status-filter-btn active" data-status="DITUNDA" aria-pressed="true" onclick="toggleStatusFilter(this)">Belum Saatnya Dipupuk</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2" id="mobile-dropdown-filters">
                        <select id="filter-pemilik-mobile" aria-label="Filter pemilik" class="w-full cursor-pointer rounded-lg border border-slate-300 bg-white py-2.5 pl-2.5 pr-6 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"><option value="">Semua Pemilik</option></select>
                        <select id="filter-blok-mobile" aria-label="Filter blok" disabled class="w-full cursor-pointer rounded-lg border border-slate-300 bg-white py-2.5 pl-2.5 pr-6 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"><option value="">Semua Blok</option></select>
                    </div>
                </div>
            </details>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('blok-lahan.create') }}" class="btn-map tambah min-h-11" id="btn-tambah-mobile"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Tambah Blok</a>
                <button type="button" onclick="toggleFullscreen()" class="btn-map expand min-h-11" id="btn-fs-mobile"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg><span id="btn-fs-mobile-text">Perluas Peta</span></button>
            </div>
        </div>
    </div>

    {{-- PETA --}}
    <div class="p-1.5 sm:p-3 relative" id="map-body">
        <div id="map"></div>
        <div class="zoom-slider-container" id="zoom-slider-container" style="display: none;">
            <button type="button" id="zoom-in-btn" title="Zoom In">+</button>
            <input type="range" id="zoom-slider" min="1" max="19" step="0.1" value="5" orient="vertical" title="Zoom Level">
            <button type="button" id="zoom-out-btn" title="Zoom Out">−</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

var mapData = @json($mapData);

// Filter peta berdasarkan tindakan yang perlu dilakukan.
var activeStatuses = ['BELUM_DIPERIKSA', 'ADA_GEJALA', 'SIAP_DIPUPUK', 'DITUNDA'];

function getColorStatusPeta(status){
    return {'BELUM_DIPERIKSA':'#64748b','ADA_GEJALA':'#f97316','SIAP_DIPUPUK':'#16a34a','DITUNDA':'#2563eb'}[status]||'#64748b';
}
function getBadgeStyleStatusPeta(status){
    return {
        'BELUM_DIPERIKSA':'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;',
        'ADA_GEJALA':'background:#ffedd5;color:#9a3412;border:1px solid #fdba74;',
        'SIAP_DIPUPUK':'background:#dcfce7;color:#166534;border:1px solid #86efac;',
        'DITUNDA':'background:#dbeafe;color:#1d4ed8;border:1px solid #93c5fd;'
    }[status]||'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;';
}
var map = L.map('map', { center: [-2.5489, 118.0149], zoom: 5, zoomControl: false, zoomSnap: 0, zoomDelta: 0.25, wheelDebounceTime: 40, wheelPxPerZoomLevel: 120 });
var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' });
osm.addTo(map);
var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri', maxZoom: 19, maxNativeZoom: 17 });
L.control.layers({'Peta': osm, 'Satelit': satellite}).addTo(map);

// Kendali zoom tambahan untuk tampilan desktop dan layar sentuh.
(function(){
    var slider=document.getElementById('zoom-slider'),zoomInBtn=document.getElementById('zoom-in-btn'),zoomOutBtn=document.getElementById('zoom-out-btn'),isSliderDragging=false,animFrameId=null,zoomSpeed=0.02;
    slider.min=map.getMinZoom()||1;slider.max=map.getMaxZoom()||19;slider.step='0.1';slider.value=map.getZoom();
    slider.addEventListener('input',function(){isSliderDragging=true;map.setZoom(parseFloat(this.value));});
    slider.addEventListener('change',function(){isSliderDragging=false;});
    map.on('zoomend zoom move',function(){if(!isSliderDragging)slider.value=map.getZoom();});
    function startZoom(d){stopZoom();function f(){var c=map.getZoom(),n=c+(d*zoomSpeed),mn=parseFloat(slider.min),mx=parseFloat(slider.max);if(n<mn)n=mn;if(n>mx)n=mx;if((d>0&&c<mx)||(d<0&&c>mn)){map.setZoom(n);animFrameId=requestAnimationFrame(f);}}animFrameId=requestAnimationFrame(f);}
    function stopZoom(){if(animFrameId){cancelAnimationFrame(animFrameId);animFrameId=null;}}
    zoomInBtn.addEventListener('mousedown',function(e){e.preventDefault();startZoom(1);});
    zoomOutBtn.addEventListener('mousedown',function(e){e.preventDefault();startZoom(-1);});
    document.addEventListener('mouseup',stopZoom);
    zoomInBtn.addEventListener('touchstart',function(e){e.preventDefault();startZoom(1);},{passive:false});
    zoomOutBtn.addEventListener('touchstart',function(e){e.preventDefault();startZoom(-1);},{passive:false});
    document.addEventListener('touchend',stopZoom);document.addEventListener('touchcancel',stopZoom);
    zoomInBtn.addEventListener('contextmenu',function(e){e.preventDefault();});
    zoomOutBtn.addEventListener('contextmenu',function(e){e.preventDefault();});
})();
// Populate filters
var selectEl=document.getElementById('filter-pemilik'),selectElMobile=document.getElementById('filter-pemilik-mobile'),filterBlokEl=document.getElementById('filter-blok'),filterBlokElMobile=document.getElementById('filter-blok-mobile');
var pemilikSet={},pemilikList=[];
mapData.forEach(function(b){if(b.nama_pemilik&&!pemilikSet[b.nama_pemilik]){pemilikSet[b.nama_pemilik]=true;pemilikList.push(b.nama_pemilik);}});
pemilikList.sort();
pemilikList.forEach(function(p){var o1=document.createElement('option');o1.value=p;o1.textContent=p;selectEl.appendChild(o1);var o2=document.createElement('option');o2.value=p;o2.textContent=p;selectElMobile.appendChild(o2);});

var mapLayers=[];

// Statistik dinamis — update hanya elemen yang ada
function updateStats(data){
    var t=0,l=0;
    data.forEach(function(b){t++;l+=(b.luas_ha||0);});
    var el1=document.getElementById('stat-total-blok');if(el1)el1.textContent=t;
    var el2=document.getElementById('stat-total-luas');if(el2)el2.textContent=l.toFixed(2)+' Ha';
}

// Luas per tindakan pada peta.
function updateLuasPerStatus(data){
    var r={BELUM_DIPERIKSA:0,ADA_GEJALA:0,SIAP_DIPUPUK:0,DITUNDA:0};
    data.forEach(function(b){var s=b.status_peta||'BELUM_DIPERIKSA';var h=b.luas_ha||0;r[s]=(r[s]||0)+h;});
    var el;
    el=document.getElementById('luas-belum-diperiksa');if(el)el.textContent=r.BELUM_DIPERIKSA.toFixed(2)+' Ha';
    el=document.getElementById('luas-ada-gejala');if(el)el.textContent=r.ADA_GEJALA.toFixed(2)+' Ha';
    el=document.getElementById('luas-siap-dipupuk');if(el)el.textContent=r.SIAP_DIPUPUK.toFixed(2)+' Ha';
    el=document.getElementById('luas-ditunda');if(el)el.textContent=r.DITUNDA.toFixed(2)+' Ha';
}

// Popup menampilkan status peta, kondisi tanaman, dan kesiapan pemupukan secara terpisah
function buildPopupContent(blok){
    var statusPeta=blok.status_peta||'BELUM_DIPERIKSA';
    var statusPetaLabel=blok.status_peta_label||'Belum Diperiksa';
    var kondisiLabel=blok.status_kondisi_label||'Belum Diperiksa';
    var kelayakanLabel=blok.status_kelayakan_label||'-';
    var masalah=blok.masalah_rbs||[],pupuk=blok.pupuk_rbs||[],saran=blok.saran_rbs||'',tgl=blok.tgl_analisis_rbs||'-';
    var bs=getBadgeStyleStatusPeta(statusPeta);
    var mh=masalah.length?masalah.slice(0,3).map(function(m){return'<span style="font-size:10px;color:#374151;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1px 5px;display:inline-block;margin:1px 2px 1px 0;">'+m+'</span>';}).join(''):'<span style="font-size:10px;color:#9ca3af;">Tidak ada temuan tambahan</span>';
    var ph=pupuk.length?pupuk.slice(0,2).map(function(p){return'<div style="font-size:10px;color:#15803d;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:5px;padding:2px 5px;margin-top:2px;">'+p.jenis_utama+(p.dosis?' — '+p.dosis:'')+'</div>';}).join(''):'';
    var sh=saran?'<div style="font-size:9px;color:#78350f;background:#fffbeb;border:1px solid #fde68a;border-radius:5px;padding:2px 5px;margin-top:3px;line-height:1.3;">'+saran.substring(0,70)+(saran.length>70?'...':'')+'</div>':'';
    var actionUrl='/rbs/detail/'+blok.id,actionLabel='Lihat Rekomendasi';
    if(blok.belum_ada_kondisi){
        actionUrl='/kondisi-lahan/create?blok_lahan_id='+blok.id;
        actionLabel='Isi Observasi';
    }else if(blok.data_belum_cukup){
        actionUrl='/kondisi-lahan/'+blok.kondisi_id+'/edit';
        actionLabel='Lengkapi Observasi';
    }else if(!blok.rekomendasi_id){
        actionLabel='Buat Rekomendasi';
    }else if(['TAHAP_1_SIAP','TAHAP_1_SEBAGIAN','TAHAP_2_SIAP'].includes(blok.status_stage)){
        actionUrl='/realisasi-pemupukan/create/'+blok.rekomendasi_id;
        actionLabel='Catat Realisasi';
    }else if(blok.status_stage==='SELESAI_TAHUNAN'){
        actionUrl='/laporan/'+blok.rekomendasi_id;
        actionLabel='Lihat Laporan';
    }else if(['MENUNGGU_INTERVAL','MENUNGGU_KELAYAKAN'].includes(blok.status_stage)){
        actionLabel='Lihat Detail';
    }
    return'<div style="min-width:170px;max-width:240px;font-family:system-ui,sans-serif;">'
        +'<div style="font-weight:700;font-size:12px;color:#0f172a;padding-bottom:4px;border-bottom:1px solid #f1f5f9;margin-bottom:4px;">'+blok.nama_blok+'</div>'
        +'<div style="font-size:10px;color:#64748b;margin-bottom:3px;">'+(blok.nama_pemilik||'-')+' · '+blok.luas_ha+' Ha'+(blok.umur_tanaman!==null?' · '+blok.umur_tanaman+' thn':'')+'</div>'
        +'<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;"><span style="font-size:9px;font-weight:700;color:#6b7280;text-transform:uppercase;">Status Peta</span><span style="'+bs+'font-size:10px;font-weight:700;padding:1px 6px;border-radius:9999px;">'+statusPetaLabel+'</span></div>'
        +'<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;"><span style="font-size:9px;font-weight:700;color:#6b7280;text-transform:uppercase;">Kondisi Tanaman</span><span style="font-size:9px;color:#475569;text-align:right;">'+kondisiLabel+'</span></div>'
        +'<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;"><span style="font-size:9px;font-weight:700;color:#6b7280;text-transform:uppercase;">Kesiapan Pupuk</span><span style="font-size:9px;color:#475569;text-align:right;">'+kelayakanLabel+'</span></div>'
        +'<div style="margin-bottom:3px;">'+mh+'</div>'+ph+sh
        +'<div style="display:flex;gap:6px;align-items:center;padding-top:4px;margin-top:3px;border-top:1px solid #f1f5f9;flex-wrap:wrap;">'
        +'<a href="'+actionUrl+'" style="font-size:10px;color:#059669;font-weight:700;text-decoration:none;">'+actionLabel+' →</a>'
        +'<a href="/blok-lahan/'+blok.id+'/edit#koordinat" style="font-size:10px;color:#2563eb;font-weight:600;text-decoration:none;">Edit Peta</a>'
        +'<span style="font-size:9px;color:#9ca3af;margin-left:auto;">'+tgl+'</span></div></div>';
}
function getSelectedPemilik(){return window.innerWidth<640?selectElMobile.value:selectEl.value;}
function getSelectedBlok(){return window.innerWidth<640?filterBlokElMobile.value:filterBlokEl.value;}

// Filter data berdasarkan status tindakan pada peta
function getFilteredData(){
    var pemilik=getSelectedPemilik(),blokId=getSelectedBlok(),data=mapData;
    if(pemilik)data=data.filter(function(b){return b.nama_pemilik===pemilik;});
    if(blokId)data=data.filter(function(b){return b.id==blokId;});
    data=data.filter(function(b){
        var status=b.status_peta||'BELUM_DIPERIKSA';
        return activeStatuses.indexOf(status)!==-1;
    });
    return data;
}

function renderMapLayers(){
    mapLayers.forEach(function(item){map.removeLayer(item.layer);});
    mapLayers=[];
    var filteredData=getFilteredData();
    var pemilik=getSelectedPemilik(),blokId=getSelectedBlok();
    var statsData=mapData;
    if(pemilik)statsData=statsData.filter(function(b){return b.nama_pemilik===pemilik;});
    if(blokId)statsData=statsData.filter(function(b){return b.id==blokId;});
    updateStats(statsData);
    updateLuasPerStatus(statsData);
    var activeLayers=[];
    filteredData.forEach(function(blok){
        if(!blok.geojson)return;
        var color=getColorStatusPeta(blok.status_peta||'BELUM_DIPERIKSA');
        var layer=L.geoJSON(blok.geojson,{style:{fillColor:color,fillOpacity:0.45,color:color,weight:2,opacity:0.9}});
        layer.bindPopup(buildPopupContent(blok),{maxWidth:240,autoPanPaddingTopLeft:[10,10],autoPanPaddingBottomRight:[10,50]});
        layer.bindTooltip(blok.nama_blok,{permanent:true,direction:'center',className:'leaflet-tooltip-label'});
        layer.on('mouseover',function(e){e.target.setStyle({fillOpacity:0.7,weight:3});});
        layer.on('mouseout',function(e){e.target.setStyle({fillOpacity:0.45,weight:2});});
        layer.addTo(map);
        mapLayers.push({id:blok.id,layer:layer});
        activeLayers.push(layer);
    });
    if(activeLayers.length>0){map.fitBounds(L.featureGroup(activeLayers).getBounds().pad(0.1));}
    // Empty state: tampilkan pesan jika tidak ada polygon
    var emptyEl=document.getElementById('map-empty-state');
    if(activeLayers.length===0){
        if(!emptyEl){
            emptyEl=document.createElement('div');
            emptyEl.id='map-empty-state';
            emptyEl.style.cssText='position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:45;text-align:center;padding:16px 24px;background:rgba(255,255,255,0.95);border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.06);';
            emptyEl.innerHTML='<p style="font-size:13px;font-weight:600;color:#475569;">Belum ada batas blok lahan yang dapat ditampilkan pada peta.</p><p style="font-size:11px;color:#94a3b8;margin-top:4px;">Tambahkan polygon GeoJSON pada data blok lahan.</p>';
            document.getElementById('map-body').appendChild(emptyEl);
        }
        emptyEl.style.display='';
    } else {
        if(emptyEl)emptyEl.style.display='none';
    }
}

renderMapLayers();

// Filter status — sync desktop + mobile
function toggleStatusFilter(btn){
    var status=btn.getAttribute('data-status');
    var idx=activeStatuses.indexOf(status);
    if(idx!==-1){if(activeStatuses.length<=1)return;activeStatuses.splice(idx,1);}
    else{activeStatuses.push(status);}
    document.querySelectorAll('.status-filter-btn[data-status="'+status+'"]').forEach(function(b){
        if(activeStatuses.indexOf(status)!==-1){b.classList.remove('inactive');b.classList.add('active');b.setAttribute('aria-pressed','true');}
        else{b.classList.remove('active');b.classList.add('inactive');b.setAttribute('aria-pressed','false');}
    });
    renderMapLayers();
}

// Filter pemilik + blok
function handlePemilikChange(pemilik,blokSelect){
    blokSelect.innerHTML='<option value="">Semua Blok</option>';
    if(pemilik){blokSelect.disabled=false;mapData.filter(function(b){return b.nama_pemilik===pemilik;}).forEach(function(b){var o=document.createElement('option');o.value=b.id;o.textContent=b.nama_blok;blokSelect.appendChild(o);});}
    else{blokSelect.disabled=true;}
    renderMapLayers();
}
selectEl.addEventListener('change',function(){selectElMobile.value=selectEl.value;handlePemilikChange(selectEl.value,filterBlokEl);filterBlokElMobile.innerHTML=filterBlokEl.innerHTML;filterBlokElMobile.disabled=filterBlokEl.disabled;});
filterBlokEl.addEventListener('change',function(){filterBlokElMobile.value=filterBlokEl.value;renderMapLayers();});
selectElMobile.addEventListener('change',function(){selectEl.value=selectElMobile.value;handlePemilikChange(selectElMobile.value,filterBlokElMobile);filterBlokEl.innerHTML=filterBlokElMobile.innerHTML;filterBlokEl.disabled=filterBlokElMobile.disabled;});
filterBlokElMobile.addEventListener('change',function(){filterBlokEl.value=filterBlokElMobile.value;renderMapLayers();});

// Fullscreen
var isFullscreen=false;
function toggleFullscreen(){
    var container=document.getElementById('map-container'),sidebar=document.getElementById('sidebar'),zoomSlider=document.getElementById('zoom-slider-container');
    isFullscreen=!isFullscreen;
    if(isFullscreen){container.classList.add('is-fullscreen');document.body.style.overflow='hidden';if(sidebar)sidebar.style.display='none';if(zoomSlider)zoomSlider.style.display='flex';document.getElementById('btn-fs-desktop-text').textContent='Kecilkan';document.getElementById('btn-fs-mobile-text').textContent='Kecilkan';document.getElementById('btn-fs-desktop').classList.replace('expand','shrink');document.getElementById('btn-fs-mobile').classList.replace('expand','shrink');}
    else{container.classList.remove('is-fullscreen');document.body.style.overflow='';if(sidebar)sidebar.style.display='';if(zoomSlider)zoomSlider.style.display='none';document.getElementById('btn-fs-desktop-text').textContent='Perluas Peta';document.getElementById('btn-fs-mobile-text').textContent='Perluas Peta';document.getElementById('btn-fs-desktop').classList.replace('shrink','expand');document.getElementById('btn-fs-mobile').classList.replace('shrink','expand');}
    setTimeout(function(){map.invalidateSize();},200);
}
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&isFullscreen)toggleFullscreen();});
window.addEventListener('resize',function(){map.invalidateSize();});
</script>
@endpush
