@extends('layouts.app')

@section('title', 'Dashboard WebGIS')
@section('page-title', 'Peta Lahan Kelapa Sawit')
@section('page-subtitle', 'WebGIS — Visualisasi Blok Lahan & Status Kondisi Tanaman')

@push('styles')
<style>
    #map { height: calc(100vh - 320px); min-height: 300px; border-radius: 12px; }
    @media (max-width: 640px) { #map { height: 300px; min-height: 250px; border-radius: 8px; } }
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
    .map-legend { position: absolute; bottom: 10px; right: 10px; z-index: 42; background: rgba(255,255,255,0.93); border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; backdrop-filter: blur(8px); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    @media (max-width: 640px) { .map-legend { bottom: 8px; right: 8px; left: auto; max-width: 220px; padding: 5px 8px; } .map-legend .legend-items { display: flex; flex-wrap: wrap; gap: 4px 10px; } }
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
    #map-container.is-fullscreen .map-legend { bottom: 24px; right: 16px; }
    #map-container.is-fullscreen .zoom-slider-container { bottom: 24px; left: 16px; }
    .scrollbar-none::-webkit-scrollbar { display: none !important; }
    .scrollbar-none { -ms-overflow-style: none !important; scrollbar-width: none !important; }
    .legend-item { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #64748b; padding: 1px 0; }
    .legend-dot { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }

    /* Filter status — berdasarkan status_kondisi_tanaman */
    .status-filter-btn { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; border: 1.5px solid; cursor: pointer; transition: all 0.15s; user-select: none; white-space: nowrap; line-height: 1.4; }
    @media (max-width: 640px) { .status-filter-btn { padding: 2.5px 7px; font-size: 9px; } }
    .status-filter-btn.active { opacity: 1; }
    .status-filter-btn.inactive { opacity: 0.35; }
    .status-filter-btn[data-status="GEJALA_BERAT"] { border-color: #fca5a5; background: #fee2e2; color: #991b1b; }
    .status-filter-btn[data-status="GEJALA_BERAT"].active { background: #dc2626; color: #fff; border-color: #dc2626; }
    .status-filter-btn[data-status="TERINDIKASI_DEFISIENSI"] { border-color: #fdba74; background: #ffedd5; color: #9a3412; }
    .status-filter-btn[data-status="TERINDIKASI_DEFISIENSI"].active { background: #f97316; color: #fff; border-color: #f97316; }
    .status-filter-btn[data-status="TERINDIKASI_DEFISIENSI_RINGAN"] { border-color: #fde68a; background: #fef9c3; color: #854d0e; }
    .status-filter-btn[data-status="TERINDIKASI_DEFISIENSI_RINGAN"].active { background: #eab308; color: #fff; border-color: #eab308; }
    .status-filter-btn[data-status="NORMAL_VISUAL"] { border-color: #86efac; background: #dcfce7; color: #166534; }
    .status-filter-btn[data-status="NORMAL_VISUAL"].active { background: #22c55e; color: #fff; border-color: #22c55e; }
    .status-filter-btn[data-status="BELUM_DIOBSERVASI"] { border-color: #cbd5e1; background: #f1f5f9; color: #475569; }
    .status-filter-btn[data-status="BELUM_DIOBSERVASI"].active { background: #475569; color: #fff; border-color: #475569; }
    /* Luas per status */
    .luas-status-item { display: flex; align-items: center; gap: 5px; padding: 5px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 10px; }
    .luas-status-item .luas-dot { width: 7px; height: 7px; border-radius: 2px; flex-shrink: 0; }
    .luas-status-item .luas-label { color: #64748b; white-space: nowrap; }
    .luas-status-item .luas-value { font-weight: 700; color: #1e293b; margin-left: auto; white-space: nowrap; }
    @media (max-width: 640px) { #stats-cards .stat-card { padding: 8px 10px; } #stats-cards .stat-value { font-size: 1.2rem; } #stats-cards .stat-label { font-size: 9px; } }
    #filter-pemilik, #filter-pemilik-mobile, #filter-blok, #filter-blok-mobile { -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") !important; background-repeat: no-repeat !important; background-position: right 8px center !important; background-size: 12px 12px !important; }
    select:disabled { opacity: 0.5; cursor: not-allowed; background: #f1f5f9; }
    .btn-map { display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.15s; white-space: nowrap; border: 1.5px solid; text-decoration: none; }
    .btn-map.expand { background: #eff6ff; color: #1d4ed8; border-color: #93c5fd; }
    .btn-map.expand:hover { background: #dbeafe; border-color: #60a5fa; }
    .btn-map.shrink { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
    .btn-map.tambah { background: #059669; color: #fff; border-color: #059669; }
    .btn-map.tambah:hover { background: #047857; border-color: #047857; }
    @media (max-width: 640px) { .btn-map { padding: 6px 10px; font-size: 10px; } }
</style>
@endpush

@section('content')

{{-- Stats Cards --}}
<div class="flex items-stretch gap-2.5 overflow-x-auto pb-2.5 sm:pb-0 scrollbar-none -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-3 lg:grid-cols-6 sm:gap-3 mb-3 sm:mb-4" id="stats-cards" style="-webkit-overflow-scrolling: touch;">
    <div class="stat-card flex-shrink-0 w-[130px] sm:w-auto bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm flex flex-col justify-between">
        <div>
            <p class="stat-label text-xs text-slate-500 mb-0.5">Anggota</p>
            <p class="stat-value text-xl sm:text-2xl font-bold text-slate-800">{{ $stats['total_anggota'] }}</p>
        </div>
        <p class="stat-sub text-xs text-slate-400 mt-1">terdaftar</p>
    </div>
    <div class="stat-card flex-shrink-0 w-[130px] sm:w-auto bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm flex flex-col justify-between">
        <div>
            <p class="stat-label text-xs text-slate-500 mb-0.5">Blok Lahan</p>
            <p class="stat-value text-xl sm:text-2xl font-bold text-slate-800" id="stat-total-blok">{{ $stats['total_blok'] }}</p>
        </div>
        <p class="stat-sub text-xs text-slate-400 mt-1" id="stat-total-luas">{{ number_format($stats['total_luas'], 2) }} Ha</p>
    </div>
    <div class="stat-card flex-shrink-0 w-[130px] sm:w-auto bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm border-l-4 border-l-amber-400 flex flex-col justify-between">
        <div>
            <p class="stat-label text-xs text-slate-500 mb-0.5">Belum Ada Kondisi</p>
            <p class="stat-value text-xl sm:text-2xl font-bold text-amber-600">{{ $stats['belum_kondisi'] }}</p>
        </div>
        <p class="stat-sub text-xs text-slate-400 mt-1">perlu observasi</p>
    </div>
    <div class="stat-card flex-shrink-0 w-[130px] sm:w-auto bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm border-l-4 border-l-emerald-500 flex flex-col justify-between">
        <div>
            <p class="stat-label text-xs text-slate-500 mb-0.5">Siap Dipupuk</p>
            <p class="stat-value text-xl sm:text-2xl font-bold text-emerald-600">{{ $stats['siap_dipupuk'] }}</p>
        </div>
        <p class="stat-sub text-xs text-slate-400 mt-1">blok</p>
    </div>
    <div class="stat-card flex-shrink-0 w-[130px] sm:w-auto bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm border-l-4 border-l-blue-400 flex flex-col justify-between">
        <div>
            <p class="stat-label text-xs text-slate-500 mb-0.5">Menunggu Interval</p>
            <p class="stat-value text-xl sm:text-2xl font-bold text-blue-600">{{ $stats['menunggu_interval'] }}</p>
        </div>
        <p class="stat-sub text-xs text-slate-400 mt-1">blok</p>
    </div>
    <div class="stat-card flex-shrink-0 w-[130px] sm:w-auto bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm border-l-4 border-l-green-600 flex flex-col justify-between">
        <div>
            <p class="stat-label text-xs text-slate-500 mb-0.5">Program Selesai</p>
            <p class="stat-value text-xl sm:text-2xl font-bold text-green-700">{{ $stats['program_selesai'] }}</p>
        </div>
        <p class="stat-sub text-xs text-slate-400 mt-1">tahun ini</p>
    </div>
</div>

{{-- Blok Perlu Tindakan --}}
@if($blokPerluTindakan->isNotEmpty())
<div class="mb-3 sm:mb-4 bg-amber-50 border border-amber-200 rounded-xl p-3 sm:p-4">
    <div class="flex items-center justify-between mb-2">
        <p class="text-xs font-bold text-amber-800 flex items-center gap-1.5"><span>⚠️</span> Perlu Tindakan — {{ $blokPerluTindakan->count() }} Blok</p>
        @if($blokPerluTindakan->count() > 5)
        <a href="{{ route('rbs.index') }}" class="text-[10px] text-amber-700 font-semibold hover:underline">Lihat semua →</a>
        @endif
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1.5">
        @foreach($blokPerluTindakan->take(6) as $bp)
        @php
            if (!$bp->kondisiTerbaru) {
                $keterangan = 'Belum ada kondisi lahan';
                $icon = '📋';
            } elseif (!$bp->rekomendasiRbsTerbaru) {
                $keterangan = 'Belum dianalisis';
                $icon = '🔬';
            } elseif ($bp->rekomendasiRbsTerbaru->tanggal_analisis->diffInDays(now()) > 90) {
                $keterangan = 'Analisis lebih dari 90 hari';
                $icon = '⏰';
            } elseif ($bp->rekomendasiRbsTerbaru->status_stage === 'TAHAP_1_SIAP') {
                $keterangan = 'Tahap 1 siap';
                $icon = '🟢';
            } elseif ($bp->rekomendasiRbsTerbaru->status_stage === 'TAHAP_1_SEBAGIAN') {
                $keterangan = 'Tahap 1 belum selesai';
                $icon = '🟡';
            } elseif ($bp->rekomendasiRbsTerbaru->status_stage === 'TAHAP_2_SIAP') {
                $keterangan = 'Tahap 2 siap';
                $icon = '🟢';
            } else {
                $keterangan = 'Perlu ditindaklanjuti';
                $icon = '📌';
            }
        @endphp
        <a href="{{ $bp->kondisiTerbaru ? route('rbs.detail', $bp) : route('kondisi-lahan.create', ['blok_lahan_id' => $bp->id]) }}" class="flex items-center gap-2 px-2.5 py-1.5 bg-white border border-amber-100 rounded-lg hover:bg-amber-100/50 transition-colors">
            <span class="text-sm flex-shrink-0">{{ $icon }}</span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-slate-800 truncate">{{ $bp->nama_blok }}</p>
                <p class="text-[9px] text-amber-600 truncate">{{ $keterangan }}</p>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Luas per Status Kondisi --}}
<div class="mb-3 sm:mb-4">
    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Luas Lahan per Kondisi Tanaman</p>
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none whitespace-nowrap -mx-3 px-3" style="-webkit-overflow-scrolling: touch;">
        <div class="luas-status-item flex-shrink-0"><div class="luas-dot" style="background:#dc2626;"></div><span class="luas-label">Gejala Berat</span><span class="luas-value" id="luas-gejala-berat">0 Ha</span></div>
        <div class="luas-status-item flex-shrink-0"><div class="luas-dot" style="background:#f97316;"></div><span class="luas-label">Def. Sedang</span><span class="luas-value" id="luas-terindikasi-defisiensi">0 Ha</span></div>
        <div class="luas-status-item flex-shrink-0"><div class="luas-dot" style="background:#eab308;"></div><span class="luas-label">Def. Ringan</span><span class="luas-value" id="luas-defisiensi-ringan">0 Ha</span></div>
        <div class="luas-status-item flex-shrink-0"><div class="luas-dot" style="background:#22c55e;"></div><span class="luas-label">Normal</span><span class="luas-value" id="luas-normal">0 Ha</span></div>
        <div class="luas-status-item flex-shrink-0"><div class="luas-dot" style="background:#475569;"></div><span class="luas-label">Belum Obs.</span><span class="luas-value" id="luas-belum">0 Ha</span></div>
    </div>
</div>

{{-- Map Container --}}
<div class="bg-white border border-slate-200 rounded-2xl overflow-hidden relative shadow-sm" id="map-container">
    {{-- HEADER BAR --}}
    <div class="px-3 sm:px-4 py-2 sm:py-2.5 border-b border-slate-100" id="map-header">
        {{-- Desktop layout --}}
        <div class="hidden sm:flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1.5" id="status-filter-buttons-desktop">
                <button type="button" class="status-filter-btn active" data-status="GEJALA_BERAT" onclick="toggleStatusFilter(this)">Gejala Berat</button>
                <button type="button" class="status-filter-btn active" data-status="TERINDIKASI_DEFISIENSI" onclick="toggleStatusFilter(this)">Def. Sedang</button>
                <button type="button" class="status-filter-btn active" data-status="TERINDIKASI_DEFISIENSI_RINGAN" onclick="toggleStatusFilter(this)">Def. Ringan</button>
                <button type="button" class="status-filter-btn active" data-status="NORMAL_VISUAL" onclick="toggleStatusFilter(this)">Normal</button>
                <button type="button" class="status-filter-btn active" data-status="BELUM_DIOBSERVASI" onclick="toggleStatusFilter(this)">Belum Obs.</button>
            </div>
            <div class="flex-1"></div>
            <div class="relative">
                <select id="filter-pemilik" class="min-w-[140px] pl-2.5 pr-7 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                    <option value="">Semua Pemilik</option>
                </select>
            </div>
            <div class="relative">
                <select id="filter-blok" disabled class="min-w-[130px] pl-2.5 pr-7 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                    <option value="">Semua Blok</option>
                </select>
            </div>
            <button type="button" onclick="toggleFullscreen()" class="btn-map expand" id="btn-fs-desktop"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg><span id="btn-fs-desktop-text">Perluas Peta</span></button>
            <a href="{{ route('blok-lahan.create') }}" class="btn-map tambah"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Tambah Blok</a>
        </div>

        {{-- Mobile layout --}}
        <div class="sm:hidden space-y-2">
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none whitespace-nowrap -mx-3 px-3" id="status-filter-buttons-mobile" style="-webkit-overflow-scrolling: touch;">
                <button type="button" class="status-filter-btn active flex-shrink-0" data-status="GEJALA_BERAT" onclick="toggleStatusFilter(this)">Gejala Berat</button>
                <button type="button" class="status-filter-btn active flex-shrink-0" data-status="TERINDIKASI_DEFISIENSI" onclick="toggleStatusFilter(this)">Def. Sedang</button>
                <button type="button" class="status-filter-btn active flex-shrink-0" data-status="TERINDIKASI_DEFISIENSI_RINGAN" onclick="toggleStatusFilter(this)">Def. Ringan</button>
                <button type="button" class="status-filter-btn active flex-shrink-0" data-status="NORMAL_VISUAL" onclick="toggleStatusFilter(this)">Normal</button>
                <button type="button" class="status-filter-btn active flex-shrink-0" data-status="BELUM_DIOBSERVASI" onclick="toggleStatusFilter(this)">Belum Obs.</button>
            </div>
            <div class="flex items-center gap-2" id="mobile-dropdown-filters">
                <div class="relative flex-1">
                    <select id="filter-pemilik-mobile" class="w-full pl-2.5 pr-6 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                        <option value="">Semua Pemilik</option>
                    </select>
                </div>
                <div class="relative flex-1">
                    <select id="filter-blok-mobile" disabled class="w-full pl-2.5 pr-6 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                        <option value="">Semua Blok</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('blok-lahan.create') }}" class="btn-map tambah flex-1" id="btn-tambah-mobile"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Tambah Blok</a>
                <button type="button" onclick="toggleFullscreen()" class="btn-map expand flex-1" id="btn-fs-mobile"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg><span id="btn-fs-mobile-text">Perluas Peta</span></button>
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
        {{-- Legend — berdasarkan status_kondisi_tanaman --}}
        <div class="map-legend">
            <p class="text-[9px] sm:text-[10px] font-semibold text-slate-600 mb-1">Kondisi Tanaman</p>
            <div class="legend-items">
                <div class="legend-item"><div class="legend-dot" style="background:#dc2626;"></div>Gejala Berat</div>
                <div class="legend-item"><div class="legend-dot" style="background:#f97316;"></div>Terindikasi Defisiensi</div>
                <div class="legend-item"><div class="legend-dot" style="background:#eab308;"></div>Defisiensi Ringan</div>
                <div class="legend-item"><div class="legend-dot" style="background:#22c55e;"></div>Normal Visual</div>
                <div class="legend-item"><div class="legend-dot" style="background:#475569;"></div>Belum Diobservasi</div>
            </div>
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

// Filter utama berdasarkan status_kondisi_tanaman (BUKAN status legacy)
var activeStatuses = ['GEJALA_BERAT', 'TERINDIKASI_DEFISIENSI', 'TERINDIKASI_DEFISIENSI_RINGAN', 'NORMAL_VISUAL', 'BELUM_DIOBSERVASI'];

var map = L.map('map', { center: [-2.5489, 118.0149], zoom: 5, zoomControl: false, zoomSnap: 0, zoomDelta: 0.25, wheelDebounceTime: 40, wheelPxPerZoomLevel: 120 });
var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' });
osm.addTo(map);
var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '&copy; Esri', maxZoom: 19, maxNativeZoom: 17 });
L.control.layers({'Peta': osm, 'Satelit': satellite}).addTo(map);

// Zoom slider
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

// Warna berdasarkan status_kondisi_tanaman
function getColorKondisi(s){
    return {'GEJALA_BERAT':'#dc2626','TERINDIKASI_DEFISIENSI':'#f97316','TERINDIKASI_DEFISIENSI_RINGAN':'#eab308','NORMAL_VISUAL':'#22c55e','PERLU_VERIFIKASI':'#7c3aed','BELUM_DIOBSERVASI':'#475569'}[s]||'#475569';
}
function getBadgeStyleKondisi(s){
    return {'GEJALA_BERAT':'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;','TERINDIKASI_DEFISIENSI':'background:#ffedd5;color:#9a3412;border:1px solid #fdba74;','TERINDIKASI_DEFISIENSI_RINGAN':'background:#fef9c3;color:#854d0e;border:1px solid #fde68a;','NORMAL_VISUAL':'background:#dcfce7;color:#166534;border:1px solid #86efac;','PERLU_VERIFIKASI':'background:#ede9fe;color:#5b21b6;border:1px solid #c4b5fd;','BELUM_DIOBSERVASI':'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;'}[s]||'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;';
}

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

// Luas per status_kondisi
function updateLuasPerStatus(data){
    var r={GEJALA_BERAT:0,TERINDIKASI_DEFISIENSI:0,TERINDIKASI_DEFISIENSI_RINGAN:0,NORMAL_VISUAL:0,BELUM_DIOBSERVASI:0};
    data.forEach(function(b){var s=b.status_kondisi||'BELUM_DIOBSERVASI';var h=b.luas_ha||0;if(r.hasOwnProperty(s))r[s]+=h;else r.BELUM_DIOBSERVASI+=h;});
    var el;
    el=document.getElementById('luas-gejala-berat');if(el)el.textContent=r.GEJALA_BERAT.toFixed(2)+' Ha';
    el=document.getElementById('luas-terindikasi-defisiensi');if(el)el.textContent=r.TERINDIKASI_DEFISIENSI.toFixed(2)+' Ha';
    el=document.getElementById('luas-defisiensi-ringan');if(el)el.textContent=r.TERINDIKASI_DEFISIENSI_RINGAN.toFixed(2)+' Ha';
    el=document.getElementById('luas-normal');if(el)el.textContent=r.NORMAL_VISUAL.toFixed(2)+' Ha';
    el=document.getElementById('luas-belum');if(el)el.textContent=r.BELUM_DIOBSERVASI.toFixed(2)+' Ha';
}

// Popup — menampilkan kondisi DAN kelayakan terpisah
function buildPopupContent(blok){
    var kondisi=blok.status_kondisi||'BELUM_DIOBSERVASI';
    var kondisiLabel=blok.status_kondisi_label||'Belum Diobservasi';
    var kelayakanLabel=blok.status_kelayakan_label||'-';
    var masalah=blok.masalah_rbs||[],pupuk=blok.pupuk_rbs||[],saran=blok.saran_rbs||'',tgl=blok.tgl_analisis_rbs||'-';
    var bs=getBadgeStyleKondisi(kondisi);
    var mh=masalah.length?masalah.slice(0,3).map(function(m){return'<span style="font-size:10px;color:#374151;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1px 5px;display:inline-block;margin:1px 2px 1px 0;">'+m+'</span>';}).join(''):'<span style="font-size:10px;color:#9ca3af;">Tidak ada masalah</span>';
    var ph=pupuk.length?pupuk.slice(0,2).map(function(p){return'<div style="font-size:10px;color:#15803d;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:5px;padding:2px 5px;margin-top:2px;">'+p.jenis_utama+(p.dosis?' — '+p.dosis:'')+'</div>';}).join(''):'';
    var sh=saran?'<div style="font-size:9px;color:#78350f;background:#fffbeb;border:1px solid #fde68a;border-radius:5px;padding:2px 5px;margin-top:3px;line-height:1.3;">'+saran.substring(0,70)+(saran.length>70?'...':'')+'</div>':'';
    return'<div style="min-width:170px;max-width:240px;font-family:system-ui,sans-serif;">'
        +'<div style="font-weight:700;font-size:12px;color:#0f172a;padding-bottom:4px;border-bottom:1px solid #f1f5f9;margin-bottom:4px;">'+blok.nama_blok+'</div>'
        +'<div style="font-size:10px;color:#64748b;margin-bottom:3px;">'+(blok.nama_pemilik||'-')+' · '+blok.luas_ha+' Ha'+(blok.umur_tanaman!==null?' · '+blok.umur_tanaman+' thn':'')+'</div>'
        +'<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;"><span style="font-size:9px;font-weight:700;color:#6b7280;text-transform:uppercase;">Kondisi</span><span style="'+bs+'font-size:10px;font-weight:700;padding:1px 6px;border-radius:9999px;">'+kondisiLabel+'</span></div>'
        +'<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;"><span style="font-size:9px;font-weight:700;color:#6b7280;text-transform:uppercase;">Kelayakan</span><span style="font-size:9px;color:#475569;">'+kelayakanLabel+'</span></div>'
        +'<div style="margin-bottom:3px;">'+mh+'</div>'+ph+sh
        +'<div style="display:flex;gap:6px;align-items:center;padding-top:4px;margin-top:3px;border-top:1px solid #f1f5f9;flex-wrap:wrap;">'
        +'<a href="/rbs/detail/'+blok.id+'" style="font-size:10px;color:#059669;font-weight:700;text-decoration:none;">Detail →</a>'
        +'<a href="/blok-lahan/'+blok.id+'/edit#koordinat" style="font-size:10px;color:#2563eb;font-weight:600;text-decoration:none;">✏️ Edit</a>'
        +'<span style="font-size:9px;color:#9ca3af;margin-left:auto;">'+tgl+'</span></div></div>';
}

function getSelectedPemilik(){return window.innerWidth<640?selectElMobile.value:selectEl.value;}
function getSelectedBlok(){return window.innerWidth<640?filterBlokElMobile.value:filterBlokEl.value;}

// Filter data — berdasarkan status_kondisi (BUKAN status_rbs legacy)
function getFilteredData(){
    var pemilik=getSelectedPemilik(),blokId=getSelectedBlok(),data=mapData;
    if(pemilik)data=data.filter(function(b){return b.nama_pemilik===pemilik;});
    if(blokId)data=data.filter(function(b){return b.id==blokId;});
    data=data.filter(function(b){return activeStatuses.indexOf(b.status_kondisi||'BELUM_DIOBSERVASI')!==-1;});
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
        var color=getColorKondisi(blok.status_kondisi||'BELUM_DIOBSERVASI');
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
        if(activeStatuses.indexOf(status)!==-1){b.classList.remove('inactive');b.classList.add('active');}
        else{b.classList.remove('active');b.classList.add('inactive');}
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
