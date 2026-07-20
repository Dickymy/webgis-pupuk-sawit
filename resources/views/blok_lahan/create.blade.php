@extends('layouts.app')

@section('title', 'Tambah Blok Lahan')
@section('page-title', 'Tambah Blok Lahan')
@section('page-subtitle', 'Tambah data blok lahan baru')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
<style>
    /* ─── MAP WRAPPER — Mode Normal ─── */
    .map-wrapper {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    #draw-map {
        height: 450px;
        width: 100%;
        z-index: 1;
    }
    @media (max-width: 640px) { #draw-map { height: 300px; } }

    /* Tombol Perluas - centered, prominent */
    #btn-expand {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 500;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: rgba(255,255,255,0.92);
        border: 1.5px solid #d1d5db;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        backdrop-filter: blur(6px);
        transition: opacity 0.3s ease, transform 0.3s ease, background 0.15s;
    }
    #btn-expand:hover { background: #fff; border-color: #059669; color: #059669; }
    #btn-expand.is-hidden { opacity: 0; pointer-events: none; transform: translate(-50%, -50%) scale(0.9); }
    @media (max-width: 640px) { #btn-expand { padding: 6px 12px; font-size: 11px; gap: 4px; } }

    /* Bar atas fullscreen */
    .map-top-bar {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: rgba(255,255,255,0.97);
        backdrop-filter: blur(6px);
        padding: 6px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #e5e7eb;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .map-top-bar.hidden { display: none; }
    .map-info-luas {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        color: #374151;
    }
    .map-info-luas .icon-ha { width: 18px; height: 18px; color: #16a34a; }
    .map-info-luas strong { font-size: 18px; font-weight: 700; color: #16a34a; }
    #btn-kecilkan {
        background: #fee2e2;
        border: 1px solid #fca5a5;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 13px;
        font-weight: 500;
        color: #dc2626;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: background 0.15s;
        white-space: nowrap;
    }
    #btn-kecilkan:hover { background: #fecaca; }

    /* ─── MAP WRAPPER — Mode Fullscreen ─── */
    .map-wrapper.is-fullscreen {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        height: 100dvh !important;
        z-index: 9100 !important;
        border-radius: 0 !important;
        border: none !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
    }
    .map-wrapper.is-fullscreen #draw-map {
        flex: 1 !important;
        height: 100% !important;
        min-height: unset !important;
        margin-top: 0 !important;
    }
    .map-wrapper.is-fullscreen .leaflet-top {
        top: 54px !important;
    }
    .map-wrapper.is-fullscreen #btn-expand {
        display: none !important;
    }
    @supports (padding-bottom: env(safe-area-inset-bottom)) {
        .map-wrapper.is-fullscreen #draw-map {
            padding-bottom: env(safe-area-inset-bottom);
        }
    }
    @media (max-width: 640px) {
        .map-top-bar { padding: 6px 10px; }
        .map-info-luas strong { font-size: 16px; }
        #btn-kecilkan { font-size: 12px; padding: 5px 9px; }
    }

    /* Hide default Leaflet zoom control */
    .leaflet-control-zoom { display: none !important; }

    /* Custom Zoom Slider */
    .zoom-slider-container {
        position: absolute;
        bottom: 16px;
        right: 16px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
        background: rgba(255,255,255,0.96);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 6px 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.10);
        backdrop-filter: blur(6px);
    }
    .zoom-slider-container button {
        width: 28px;
        height: 28px;
        border: none;
        background: transparent;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        color: #374151;
        font-size: 16px;
        font-weight: 700;
        transition: background 0.15s, color 0.15s;
        user-select: none;
        line-height: 1;
    }
    .zoom-slider-container button:hover { background: #f0fdf4; color: #059669; }
    .zoom-slider-container button:active { background: #dcfce7; }
    .zoom-slider-container input[type="range"] {
        -webkit-appearance: none;
        appearance: none;
        width: 4px;
        height: 90px;
        background: #e2e8f0;
        border-radius: 4px;
        outline: none;
        writing-mode: vertical-lr;
        direction: rtl;
        margin: 4px 0;
        cursor: pointer;
    }
    .zoom-slider-container input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 14px;
        height: 14px;
        background: #059669;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        cursor: pointer;
        transition: transform 0.1s;
    }
    .zoom-slider-container input[type="range"]::-webkit-slider-thumb:hover { transform: scale(1.2); }
    .zoom-slider-container input[type="range"]::-moz-range-thumb {
        width: 14px;
        height: 14px;
        background: #059669;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        cursor: pointer;
    }
    .zoom-slider-container input[type="range"]::-moz-range-track {
        width: 4px;
        background: #e2e8f0;
        border-radius: 4px;
    }
    @media (max-width: 640px) {
        .zoom-slider-container { bottom: 10px; right: 10px; padding: 4px 4px; }
        .zoom-slider-container button { width: 24px; height: 24px; font-size: 14px; }
        .zoom-slider-container input[type="range"] { height: 60px; }
    }
    .map-wrapper.is-fullscreen .zoom-slider-container {
        bottom: 24px;
        right: 16px;
    }
    @media (max-width: 640px) {
        .map-wrapper.is-fullscreen .zoom-slider-container {
            bottom: calc(env(safe-area-inset-bottom) + 30px) !important;
            right: 14px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-4 sm:p-6">
        <form method="POST" action="{{ route('blok-lahan.store') }}" class="space-y-5" id="form-blok-lahan">
            @csrf

            {{-- Baris 1: Pemilik + Nama Blok --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    @include('components.searchable-select', [
                        'name' => 'anggota_id',
                        'label' => 'Pemilik Lahan',
                        'placeholder' => 'Cari nama anggota...',
                        'options' => $anggotas,
                        'displayField' => 'nama',
                        'selected' => old('anggota_id'),
                        'required' => true,
                        'error' => $errors->first('anggota_id'),
                        'helpText' => 'Belum ada? <a href="' . route('anggota.create') . '" class="text-emerald-600 font-medium hover:underline">Tambah anggota →</a>',
                    ])
                </div>
                <div>
                    <label for="nama_blok" class="block text-sm font-medium text-slate-700 mb-2">Nama Blok <span class="text-red-400">*</span></label>
                    <input type="text" id="nama_blok" name="nama_blok" value="{{ old('nama_blok') }}" required placeholder="contoh: Blok A, Blok Utara"
                        class="w-full px-4 py-3 bg-white border {{ $errors->has('nama_blok') ? 'border-red-400' : 'border-slate-300' }} rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                    @error('nama_blok') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Baris 2: SPH saja (luas otomatis dari peta) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="sph" class="block text-sm font-medium text-slate-700 mb-2">SPH (Standar Pohon/Ha) <span class="text-red-400">*</span></label>
                    <input type="number" id="sph" name="sph" value="{{ old('sph', 136) }}" min="1" required placeholder="contoh: 136"
                        class="w-full px-4 py-3 bg-white border {{ $errors->has('sph') ? 'border-red-400' : 'border-slate-300' }} rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                    @error('sph') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-slate-400">Umumnya 136 pohon/Ha untuk jarak tanam 9×9m</p>
                    <p class="mt-0.5 text-xs text-amber-600">⚠️ Nilai normal: 136–148 pohon/Ha</p>
                </div>
                <div></div>
            </div>

            {{-- Kriteria Agronomis --}}
            <div class="border-t border-slate-100 pt-5">
                <p class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">🌱</span>
                    Kriteria Agronomis
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="tahun_tanam" class="block text-sm font-medium text-slate-700 mb-2">Tahun Tanam <span class="text-red-400">*</span></label>
                        <input type="number" id="tahun_tanam" name="tahun_tanam" value="{{ old('tahun_tanam') }}" min="1990" max="{{ now()->year }}" required placeholder="2015"
                            class="w-full px-4 py-3 bg-white border {{ $errors->has('tahun_tanam') ? 'border-red-400' : 'border-slate-300' }} rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                        @error('tahun_tanam') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-emerald-600 font-semibold" id="umur-preview"></p>
                    </div>
                    <div>
                        @include('components.custom-select', [
                            'name'     => 'jenis_tanah',
                            'label'    => 'Jenis Tanah',
                            'required' => true,
                            'options'  => ['Tanah Lempung','Tanah Lempung Berpasir','Tanah Berpasir','Tanah Liat','Tanah Gambut','Tanah Aluvial','Tanah Podsolik Merah Kuning (PMK)','Tanah Laterit','Tanah Berbatu','Lainnya'],
                            'selected' => old('jenis_tanah'),
                            'placeholder' => '— Pilih Jenis Tanah —',
                            'error'    => $errors->first('jenis_tanah'),
                        ])
                    </div>
                    <div>
                        @include('components.custom-select', [
                            'name'     => 'topografi',
                            'label'    => 'Topografi',
                            'required' => true,
                            'options'  => ['Datar 0-15°','Bergelombang 15-30°','Curam >30°'],
                            'selected' => old('topografi'),
                            'placeholder' => '— Pilih Topografi —',
                            'error'    => $errors->first('topografi'),
                        ])
                    </div>
                </div>

                {{-- Fase Tanaman --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Fase Tanaman
                        <span class="text-xs text-slate-400 font-normal ml-1">(opsional — akan ditentukan otomatis dari umur jika tidak diisi)</span>
                    </label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 px-4 py-2.5 border rounded-xl cursor-pointer transition-colors {{ old('fase_tanaman') === 'TBM' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-300 hover:border-emerald-300' }}">
                            <input type="radio" name="fase_tanaman" value="TBM" {{ old('fase_tanaman') === 'TBM' ? 'checked' : '' }}
                                class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-medium text-slate-700">🌱 Tanaman Belum Menghasilkan</span>
                        </label>
                        <label class="flex items-center gap-2 px-4 py-2.5 border rounded-xl cursor-pointer transition-colors {{ old('fase_tanaman') === 'TM' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-300 hover:border-emerald-300' }}">
                            <input type="radio" name="fase_tanaman" value="TM" {{ old('fase_tanaman') === 'TM' ? 'checked' : '' }}
                                class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-medium text-slate-700">🌴 Tanaman Menghasilkan</span>
                        </label>
                    </div>
                    <div class="mt-1.5 text-xs text-slate-500 space-y-0.5">
                        <p><strong>Tanaman Belum Menghasilkan:</strong> tanaman yang belum memasuki fase produksi.</p>
                        <p><strong>Tanaman Menghasilkan:</strong> tanaman yang telah memasuki fase produksi tandan.</p>
                        <p class="text-slate-400 italic">Umur tanaman tepat tiga tahun dapat berada pada fase Tanaman Belum Menghasilkan atau Tanaman Menghasilkan. Pilih berdasarkan kondisi aktual di lapangan.</p>
                    </div>
                    @error('fase_tanaman') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Koordinat GeoJSON --}}
            <div class="border-t border-slate-100 pt-5">
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Koordinat Blok Lahan <span class="text-red-400">*</span>
                </label>

                <div class="flex gap-1 mb-3 bg-slate-100 p-1 rounded-xl">
                    <button type="button" id="tab-draw" onclick="switchTab('draw')"
                        class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium rounded-lg transition-all bg-white text-emerald-700 shadow-sm">
                        🗺️ Gambar di Peta
                    </button>
                    <button type="button" id="tab-upload" onclick="switchTab('upload')"
                        class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium rounded-lg transition-all text-slate-600 hover:text-slate-800">
                        📂 Upload File
                    </button>
                    <button type="button" id="tab-json" onclick="switchTab('json')"
                        class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium rounded-lg transition-all text-slate-600 hover:text-slate-800">
                        📝 GeoJSON Manual
                    </button>
                </div>

                <div id="panel-draw" class="space-y-2">
                    <div class="map-wrapper" id="draw-map-wrapper">
                        {{-- Top bar fullscreen (hidden by default) --}}
                        <div id="map-top-bar" class="map-top-bar hidden">
                            <div class="map-info-luas">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon-ha" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                                </svg>
                                <span>Luas: </span>
                                <strong id="luas-fullscreen">0.00</strong>
                                <span> ha</span>
                            </div>
                            <button type="button" id="btn-kecilkan" onclick="kecilkanPeta()">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                    <polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/>
                                    <line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/>
                                </svg>
                                Kecilkan Peta
                            </button>
                        </div>
                        {{-- Peta Leaflet --}}
                        <div id="draw-map"></div>
                        {{-- Zoom Slider --}}
                        <div class="zoom-slider-container" id="zoom-slider-container" style="display: none;">
                            <button type="button" id="zoom-in-btn" title="Zoom In">+</button>
                            <input type="range" id="zoom-slider" min="1" max="19" step="0.1" value="10" orient="vertical" title="Zoom Level">
                            <button type="button" id="zoom-out-btn" title="Zoom Out">−</button>
                        </div>
                        {{-- Tombol perluas (mode normal) - centered --}}
                        <button type="button" id="btn-expand" onclick="perluasPeta()">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                            Perluas Peta
                        </button>
                    </div>

                    {{-- Panduan Interaktif Alat Peta --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 mt-2">
                        <p class="text-xs font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Panduan Alat Peta
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <div class="flex items-start gap-2 p-2 bg-white rounded-lg border border-slate-100">
                                <div class="w-6 h-6 bg-emerald-100 rounded flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-slate-700">Gambar Polygon</p>
                                    <p class="text-[10px] text-slate-400 leading-tight">Klik titik-titik batas lahan</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 p-2 bg-white rounded-lg border border-slate-100">
                                <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-width="2"/></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-slate-700">Kotak (Rectangle)</p>
                                    <p class="text-[10px] text-slate-400 leading-tight">Drag untuk area persegi</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 p-2 bg-white rounded-lg border border-slate-100">
                                <div class="w-6 h-6 bg-amber-100 rounded flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-slate-700">Edit Titik</p>
                                    <p class="text-[10px] text-slate-400 leading-tight">Geser titik polygon</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 p-2 bg-white rounded-lg border border-slate-100">
                                <div class="w-6 h-6 bg-red-100 rounded flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-slate-700">Hapus Polygon</p>
                                    <p class="text-[10px] text-slate-400 leading-tight">Klik polygon lalu hapus</p>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2">💡 Area <span class="text-amber-600 font-semibold">kuning</span> = lahan terdaftar milik anggota lain. Luas otomatis terhitung setelah polygon selesai.</p>
                    </div>
                </div>

                <div id="panel-json" class="hidden">
                    <textarea id="textarea_geojson" rows="6"
                        placeholder='{"type":"Polygon","coordinates":[[[lng,lat],[lng,lat],...]]}'
                        class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors font-mono text-xs leading-relaxed resize-y">{{ old('koordinat_geojson') }}</textarea>
                    <p class="mt-1.5 text-xs text-slate-500">Paste GeoJSON lalu klik di luar textarea — luas akan otomatis terhitung.</p>

                    {{-- Preview Map untuk GeoJSON Manual --}}
                    <div id="json-preview-wrapper" class="hidden mt-3">
                        <div class="border border-emerald-200 rounded-xl overflow-hidden">
                            <div id="json-preview-map" style="height: 250px; width: 100%;"></div>
                        </div>
                        <div class="mt-2 p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21" stroke-width="2"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-800">Luas Lahan</p>
                                    <p class="text-xs text-emerald-600" id="json-preview-info">—</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-emerald-700" id="json-preview-luas">0.00</p>
                                <p class="text-xs text-emerald-600">Hektar</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Panel Upload SHP/GeoJSON --}}
                <div id="panel-upload" class="hidden">
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-emerald-400 transition-colors" id="upload-dropzone">
                        <div class="space-y-3">
                            <div class="mx-auto w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-700">Drag & drop file atau klik untuk memilih</p>
                                <p class="text-xs text-slate-400 mt-1">Format: <span class="font-semibold">.zip</span> (Shapefile) atau <span class="font-semibold">.geojson</span> — Maks. 10 MB</p>
                            </div>
                            <label for="geo_file_input" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg cursor-pointer transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Pilih File
                            </label>
                            <input type="file" id="geo_file_input" accept=".zip,.geojson,.json" class="hidden">
                        </div>
                    </div>

                    {{-- Upload Status --}}
                    <div id="upload-status" class="hidden mt-3">
                        <div id="upload-loading" class="hidden flex items-center gap-2 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                            <svg class="w-4 h-4 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-blue-700">Memproses file...</span>
                        </div>
                        <div id="upload-success" class="hidden p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                            <p class="text-sm text-emerald-700 font-medium" id="upload-success-msg"></p>
                            <p class="text-xs text-emerald-600 mt-1">Polygon telah dimuat ke peta. Anda dapat mengedit titik-titiknya di tab "Gambar di Peta".</p>
                        </div>
                        <div id="upload-error" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl">
                            <p class="text-sm text-red-700 font-medium" id="upload-error-msg"></p>
                        </div>
                    </div>

                    {{-- Preview Map setelah upload berhasil --}}
                    <div id="upload-preview-wrapper" class="hidden mt-3">
                        <div class="border border-emerald-200 rounded-xl overflow-hidden">
                            <div id="upload-preview-map" style="height: 250px; width: 100%;"></div>
                        </div>
                        <div class="mt-2 p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21" stroke-width="2"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-800">Luas Hasil Upload</p>
                                    <p class="text-xs text-emerald-600" id="upload-preview-info">—</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-emerald-700" id="upload-preview-luas">0.00</p>
                                <p class="text-xs text-emerald-600">Hektar</p>
                            </div>
                        </div>
                    </div>

                    {{-- Info format --}}
                    <div class="mt-3 bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <p class="text-xs font-semibold text-slate-700 mb-2">📋 Panduan Format File</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="p-2 bg-white rounded-lg border border-slate-100">
                                <p class="text-[11px] font-semibold text-slate-700">📦 Shapefile (.zip)</p>
                                <p class="text-[10px] text-slate-400 leading-tight mt-0.5">ZIP berisi minimal 3 file: .shp, .shx, .dbf. File .prj opsional.</p>
                            </div>
                            <div class="p-2 bg-white rounded-lg border border-slate-100">
                                <p class="text-[11px] font-semibold text-slate-700">🌍 GeoJSON (.geojson)</p>
                                <p class="text-[10px] text-slate-400 leading-tight mt-0.5">File JSON berisi geometri Polygon atau FeatureCollection.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="koordinat_geojson" id="koordinat_geojson" value="{{ old('koordinat_geojson') }}">
                @error('koordinat_geojson') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror

                {{-- Luas Lahan — langsung di bawah peta (hanya tampil di tab Gambar di Peta) --}}
                <div id="luas-lahan-wrapper" class="mt-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-emerald-800">Luas Lahan</p>
                        <p class="text-xs text-emerald-600" id="luas-info">Gambar polygon untuk menghitung luas otomatis</p>
                    </div>
                    <div class="text-right">
                        <input type="number" id="luas_ha" name="luas_ha" value="{{ old('luas_ha') }}" step="0.01" min="0.01" required readonly
                            class="w-24 px-3 py-2 bg-white border border-emerald-300 rounded-lg text-sm text-emerald-800 font-bold text-right cursor-not-allowed">
                        <p class="text-xs text-emerald-600 mt-0.5">Hektar</p>
                    </div>
                </div>
                @error('luas_ha') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Overlap Warning (Fitur 5) --}}
            <div id="overlap-warning" class="hidden bg-amber-50 border border-amber-300 rounded-xl p-4">
                <p class="text-sm text-amber-800 font-semibold mb-2" id="overlap-message"></p>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" id="overlap-confirm" class="mt-0.5 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                    <span class="text-xs text-amber-700">Saya memahami bahwa polygon ini bertumpuk dengan blok lain dan tetap ingin menyimpan.</span>
                </label>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl transition-all hover:shadow-lg hover:shadow-emerald-600/20">
                    Simpan Blok Lahan
                </button>
                <a href="{{ route('blok-lahan.index') }}"
                    class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-sm font-medium rounded-xl transition-colors text-center">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script>
var currentTab = 'draw';
var existingBloks = @json($existingBloks);

function switchTab(tab) {
    currentTab = tab;
    document.getElementById('panel-draw').classList.toggle('hidden', tab !== 'draw');
    document.getElementById('panel-json').classList.toggle('hidden', tab !== 'json');
    document.getElementById('panel-upload').classList.toggle('hidden', tab !== 'upload');
    var tabDraw = document.getElementById('tab-draw');
    var tabJson = document.getElementById('tab-json');
    var tabUpload = document.getElementById('tab-upload');
    var activeClass = 'flex-1 px-3 py-2 text-xs sm:text-sm font-medium rounded-lg transition-all bg-white text-emerald-700 shadow-sm';
    var inactiveClass = 'flex-1 px-3 py-2 text-xs sm:text-sm font-medium rounded-lg transition-all text-slate-600 hover:text-slate-800';
    tabDraw.className = tab === 'draw' ? activeClass : inactiveClass;
    tabJson.className = tab === 'json' ? activeClass : inactiveClass;
    tabUpload.className = tab === 'upload' ? activeClass : inactiveClass;
    // Luas Lahan bawah hanya tampil di tab draw
    var luasWrapper = document.getElementById('luas-lahan-wrapper');
    if (luasWrapper) luasWrapper.classList.toggle('hidden', tab !== 'draw');
    if (tab === 'draw') {
        setTimeout(function() { drawMap.invalidateSize(); }, 100);
    }
}

// ─── FUNGSI HITUNG LUAS POLYGON (Geodesic - Shoelace formula) ────
function extractPolygonGeometry(geojson) {
    if (!geojson || !geojson.type) return null;
    if (geojson.type === 'Polygon') return geojson;
    if (geojson.type === 'MultiPolygon') {
        return { type: 'Polygon', coordinates: geojson.coordinates[0] };
    }
    if (geojson.type === 'Feature' && geojson.geometry) {
        return extractPolygonGeometry(geojson.geometry);
    }
    if (geojson.type === 'FeatureCollection' && geojson.features && geojson.features.length > 0) {
        for (var i = 0; i < geojson.features.length; i++) {
            var result = extractPolygonGeometry(geojson.features[i]);
            if (result) return result;
        }
    }
    return null;
}

function calculateAreaHa(geojson) {
    try {
        var coords;
        if (geojson.type === 'Polygon') {
            coords = geojson.coordinates[0];
        } else if (geojson.type === 'Feature' && geojson.geometry.type === 'Polygon') {
            coords = geojson.geometry.coordinates[0];
        } else if (geojson.type === 'FeatureCollection' || geojson.type === 'MultiPolygon') {
            var extracted = extractPolygonGeometry(geojson);
            if (extracted) return calculateAreaHa(extracted);
            return 0;
        } else {
            return 0;
        }

        // Haversine-based area calculation (approximate for small polygons)
        var area = 0;
        var n = coords.length;
        for (var i = 0; i < n - 1; i++) {
            var j = (i + 1) % n;
            var xi = coords[i][0] * Math.PI / 180;
            var yi = coords[i][1] * Math.PI / 180;
            var xj = coords[j][0] * Math.PI / 180;
            var yj = coords[j][1] * Math.PI / 180;
            area += (xj - xi) * (2 + Math.sin(yi) + Math.sin(yj));
        }
        area = Math.abs(area * 6378137 * 6378137 / 2);
        // Convert m² to Ha (1 Ha = 10000 m²)
        return Math.round(area / 10000 * 100) / 100;
    } catch(e) {
        return 0;
    }
}

function updateLuas(geojson) {
    var ha = calculateAreaHa(geojson);
    var luasEl = document.getElementById('luas_ha');
    var infoEl = document.getElementById('luas-info');
    if (ha > 0) {
        luasEl.value = ha;
        infoEl.textContent = '✓ Luas terhitung: ' + ha + ' Ha dari polygon yang digambar';
        infoEl.className = 'mt-1 text-xs text-emerald-600 font-medium';
    } else {
        luasEl.value = '';
        infoEl.textContent = 'Luas dihitung otomatis saat polygon digambar';
        infoEl.className = 'mt-1 text-xs text-slate-400';
    }
}

// ─── MAP ─────────────────────────────────────────────────────────
var drawMap = L.map('draw-map', { center: [-1.5, 110.0], zoom: 10, zoomControl: false, zoomSnap: 0, zoomDelta: 0.25, wheelDebounceTime: 40, wheelPxPerZoomLevel: 120 });
var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OSM' });
var satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, maxNativeZoom: 17 }).addTo(drawMap);
L.control.layers({'🗺️ Peta': osmLayer, '🛰️ Satelit': satLayer}, null, {position: 'topright'}).addTo(drawMap);

// ─── ZOOM SLIDER (smooth continuous zoom on hold) ────────────────
(function(){
    var slider = document.getElementById('zoom-slider');
    var zoomInBtn = document.getElementById('zoom-in-btn');
    var zoomOutBtn = document.getElementById('zoom-out-btn');
    var isSliderDragging = false;
    var animFrameId = null;
    var zoomSpeed = 0.02;

    slider.min = drawMap.getMinZoom() || 1;
    slider.max = drawMap.getMaxZoom() || 19;
    slider.step = '0.1';
    slider.value = drawMap.getZoom();

    slider.addEventListener('input', function() {
        isSliderDragging = true;
        drawMap.setZoom(parseFloat(this.value));
    });
    slider.addEventListener('change', function() { isSliderDragging = false; });
    slider.addEventListener('pointerup', function() { isSliderDragging = false; });

    drawMap.on('zoomend zoom move', function() {
        if (!isSliderDragging) slider.value = drawMap.getZoom();
    });

    function startContinuousZoom(direction) {
        stopContinuousZoom();
        function frame() {
            var current = drawMap.getZoom();
            var next = current + (direction * zoomSpeed);
            var minZ = parseFloat(slider.min);
            var maxZ = parseFloat(slider.max);
            if (next < minZ) next = minZ;
            if (next > maxZ) next = maxZ;
            if ((direction > 0 && current < maxZ) || (direction < 0 && current > minZ)) {
                drawMap.setZoom(next);
                animFrameId = requestAnimationFrame(frame);
            }
        }
        animFrameId = requestAnimationFrame(frame);
    }

    function stopContinuousZoom() {
        if (animFrameId) { cancelAnimationFrame(animFrameId); animFrameId = null; }
    }

    zoomInBtn.addEventListener('mousedown', function(e) { e.preventDefault(); startContinuousZoom(1); });
    zoomOutBtn.addEventListener('mousedown', function(e) { e.preventDefault(); startContinuousZoom(-1); });
    document.addEventListener('mouseup', stopContinuousZoom);

    zoomInBtn.addEventListener('touchstart', function(e) { e.preventDefault(); startContinuousZoom(1); }, {passive:false});
    zoomOutBtn.addEventListener('touchstart', function(e) { e.preventDefault(); startContinuousZoom(-1); }, {passive:false});
    document.addEventListener('touchend', stopContinuousZoom);
    document.addEventListener('touchcancel', stopContinuousZoom);

    zoomInBtn.addEventListener('contextmenu', function(e) { e.preventDefault(); });
    zoomOutBtn.addEventListener('contextmenu', function(e) { e.preventDefault(); });
})();

existingBloks.forEach(function(blok) {
    if (!blok.geojson) return;
    L.geoJSON(blok.geojson, {
        style: { color: '#d97706', fillColor: '#fbbf24', fillOpacity: 0.25, weight: 1.5, dashArray: '4 4' }
    }).bindTooltip(blok.nama, { sticky: true, className: 'text-xs' }).addTo(drawMap);
});

var drawnItems = new L.FeatureGroup();
drawMap.addLayer(drawnItems);
drawMap.addControl(new L.Control.Draw({
    position: 'topleft',
    draw: {
        polygon: { allowIntersection: false, shapeOptions: { color: '#059669', fillColor: '#059669', fillOpacity: 0.3, weight: 2 } },
        rectangle: { shapeOptions: { color: '#059669', fillColor: '#059669', fillOpacity: 0.3, weight: 2 } },
        polyline: false, circle: false, circlemarker: false, marker: false
    },
    edit: { featureGroup: drawnItems, remove: true }
}));

drawMap.on(L.Draw.Event.CREATED, function(e) { drawnItems.clearLayers(); drawnItems.addLayer(e.layer); syncGeoJson(); });
drawMap.on(L.Draw.Event.EDITED, syncGeoJson);
drawMap.on(L.Draw.Event.DELETED, syncGeoJson);

// Auto-sync saat layer digeser/diubah (tanpa perlu klik Save di toolbar)
drawMap.on('draw:editvertex', syncGeoJson);
drawMap.on('draw:editmove', syncGeoJson);

// Tracking mode edit
var isEditingPolygon = false;
drawMap.on('draw:editstart', function() { isEditingPolygon = true; });
drawMap.on('draw:editstop', function() { isEditingPolygon = false; });

function syncGeoJson() {
    var layers = drawnItems.getLayers();
    if (layers.length > 0) {
        var geojson = layers[0].toGeoJSON().geometry;
        var geoStr = JSON.stringify(geojson);
        document.getElementById('koordinat_geojson').value = geoStr;
        document.getElementById('textarea_geojson').value = geoStr;
        updateLuas(geojson);
    } else {
        document.getElementById('koordinat_geojson').value = '';
        document.getElementById('textarea_geojson').value = '';
        updateLuas({});
    }
    syncLuasFullscreen();
}

// ─── TEXTAREA GeoJSON: hitung luas saat blur ─────────────────────
document.getElementById('textarea_geojson').addEventListener('blur', function() {
    var val = this.value.trim();
    var jsonPreviewWrapper = document.getElementById('json-preview-wrapper');
    if (!val) {
        updateLuas({});
        if (jsonPreviewWrapper) jsonPreviewWrapper.classList.add('hidden');
        return;
    }
    try {
        var parsed = JSON.parse(val);
        // Extract polygon geometry dari berbagai format (FeatureCollection, Feature, dll)
        var polygon = extractPolygonGeometry(parsed);
        if (polygon) {
            var geoStr = JSON.stringify(polygon);
            document.getElementById('koordinat_geojson').value = geoStr;
            updateLuas(polygon);
            // Render di peta utama juga
            drawnItems.clearLayers();
            L.geoJSON(polygon, { style: { color: '#059669', fillColor: '#059669', fillOpacity: 0.3, weight: 2 } })
                .eachLayer(function(l) { drawnItems.addLayer(l); });
            drawMap.fitBounds(drawnItems.getBounds().pad(0.2));

            // ─── Preview Map di panel GeoJSON ───
            if (jsonPreviewWrapper) {
                jsonPreviewWrapper.classList.remove('hidden');
                var ha = calculateAreaHa(polygon);
                document.getElementById('json-preview-luas').textContent = ha > 0 ? ha.toFixed(2) : '—';
                document.getElementById('json-preview-info').textContent = ha > 0 ? '✓ Luas terhitung: ' + ha.toFixed(2) + ' Ha' : 'Polygon dimuat';

                var previewMapEl = document.getElementById('json-preview-map');
                if (window._jsonPreviewMap) { window._jsonPreviewMap.remove(); }
                window._jsonPreviewMap = L.map(previewMapEl, { zoomControl: true, attributionControl: false, dragging: true, scrollWheelZoom: true });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(window._jsonPreviewMap);
                var previewLayer = L.geoJSON(polygon, {
                    style: { color: '#059669', fillColor: '#059669', fillOpacity: 0.35, weight: 2.5 }
                }).addTo(window._jsonPreviewMap);
                window._jsonPreviewMap.fitBounds(previewLayer.getBounds().pad(0.15));
            }
        } else {
            document.getElementById('koordinat_geojson').value = val;
            updateLuas(parsed);
            drawnItems.clearLayers();
            L.geoJSON(parsed, { style: { color: '#059669', fillColor: '#059669', fillOpacity: 0.3, weight: 2 } })
                .eachLayer(function(l) { drawnItems.addLayer(l); });
            drawMap.fitBounds(drawnItems.getBounds().pad(0.2));
            if (jsonPreviewWrapper) jsonPreviewWrapper.classList.add('hidden');
        }
    } catch(e) {
        updateLuas({});
        if (jsonPreviewWrapper) jsonPreviewWrapper.classList.add('hidden');
    }
});

// ─── FORM SUBMIT ─────────────────────────────────────────────────
document.getElementById('form-blok-lahan').addEventListener('submit', function() {
    // Force sync jika masih dalam mode edit
    if (isEditingPolygon) {
        syncGeoJson();
    }
    if (currentTab === 'json') {
        document.getElementById('koordinat_geojson').value = document.getElementById('textarea_geojson').value.trim();
    }
});

// ─── LOAD OLD VALUE ──────────────────────────────────────────────
var oldGeojson = document.getElementById('koordinat_geojson').value;
if (oldGeojson) {
    try {
        var parsed = JSON.parse(oldGeojson);
        L.geoJSON(parsed, { style: { color: '#059669', fillColor: '#059669', fillOpacity: 0.3, weight: 2 } })
            .eachLayer(function(l) { drawnItems.addLayer(l); });
        drawMap.fitBounds(drawnItems.getBounds().pad(0.2));
        document.getElementById('textarea_geojson').value = oldGeojson;
        updateLuas(parsed);
    } catch(e) {}
} else if (existingBloks.length > 0) {
    var allBounds = L.featureGroup();
    existingBloks.forEach(function(b) { if (b.geojson) L.geoJSON(b.geojson).eachLayer(function(l) { allBounds.addLayer(l); }); });
    if (allBounds.getLayers().length > 0) drawMap.fitBounds(allBounds.getBounds().pad(0.1));
} else if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(pos) { drawMap.setView([pos.coords.latitude, pos.coords.longitude], 14); });
}

// ─── PREVIEW UMUR ────────────────────────────────────────────────
document.getElementById('tahun_tanam').addEventListener('input', function() {
    var tahun = parseInt(this.value), sekarang = new Date().getFullYear();
    if (tahun >= 1990 && tahun <= sekarang) {
        var umur = sekarang - tahun;
        var kat = umur < 3 ? 'Belum Menghasilkan' : umur <= 8 ? 'Remaja' : umur <= 14 ? 'Menghasilkan Muda' : umur <= 25 ? 'Menghasilkan Tua' : 'Tua Renta';
        document.getElementById('umur-preview').textContent = 'Umur: ' + umur + ' tahun — ' + kat;
    } else { document.getElementById('umur-preview').textContent = ''; }
});

// ─── FULLSCREEN PETA DRAW ────────────────────────────────────────
var expandBtn = document.getElementById('btn-expand');
var drawDragTimer = null;

function perluasPeta() {
    var wrapper = document.getElementById('draw-map-wrapper');
    var topBar = document.getElementById('map-top-bar');
    var zoomSlider = document.getElementById('zoom-slider-container');
    wrapper.classList.add('is-fullscreen');
    topBar.classList.remove('hidden');
    if (zoomSlider) zoomSlider.style.display = 'flex';
    syncLuasFullscreen();
    setTimeout(function() { drawMap.invalidateSize(); }, 150);
    document.body.style.overflow = 'hidden';
}

function kecilkanPeta() {
    var wrapper = document.getElementById('draw-map-wrapper');
    var topBar = document.getElementById('map-top-bar');
    var zoomSlider = document.getElementById('zoom-slider-container');
    wrapper.classList.remove('is-fullscreen');
    topBar.classList.add('hidden');
    if (zoomSlider) zoomSlider.style.display = 'none';
    setTimeout(function() { drawMap.invalidateSize(); }, 150);
    document.body.style.overflow = '';
}

// Auto-hide perluas button saat drag/zoom peta
function hideExpandBtn() {
    if (expandBtn && !document.getElementById('draw-map-wrapper').classList.contains('is-fullscreen')) {
        expandBtn.classList.add('is-hidden');
    }
}
function showExpandBtn() {
    if (expandBtn && !document.getElementById('draw-map-wrapper').classList.contains('is-fullscreen')) {
        expandBtn.classList.remove('is-hidden');
    }
}

drawMap.on('movestart', function() { hideExpandBtn(); clearTimeout(drawDragTimer); });
drawMap.on('zoomstart', function() { hideExpandBtn(); clearTimeout(drawDragTimer); });
drawMap.on('moveend', function() { clearTimeout(drawDragTimer); drawDragTimer = setTimeout(showExpandBtn, 1200); });
drawMap.on('zoomend', function() { clearTimeout(drawDragTimer); drawDragTimer = setTimeout(showExpandBtn, 1200); });

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var wrapper = document.getElementById('draw-map-wrapper');
        if (wrapper && wrapper.classList.contains('is-fullscreen')) {
            kecilkanPeta();
        }
    }
});

function syncLuasFullscreen() {
    var inputLuas = document.getElementById('luas_ha');
    var luasFullscreen = document.getElementById('luas-fullscreen');
    if (inputLuas && luasFullscreen) {
        var nilai = parseFloat(inputLuas.value) || 0;
        luasFullscreen.textContent = nilai.toFixed(2);
    }
}

// Listen for sidebar toggle
document.addEventListener('sidebarToggled', function() {
    if (typeof drawMap !== 'undefined') {
        drawMap.invalidateSize();
    }
});

// ─── FITUR 5: DETEKSI OVERLAP POLYGON ───────────────────────────────
(function() {
    var overlapWarning = document.getElementById('overlap-warning');
    var overlapCheckbox = document.getElementById('overlap-confirm');
    var submitBtn = document.querySelector('button[type="submit"]');

    function checkOverlapOnChange() {
        var geojsonInput = document.getElementById('koordinat_geojson');
        if (!geojsonInput || !geojsonInput.value) return;
        try {
            var newGeojson = JSON.parse(geojsonInput.value);
            detectOverlap(newGeojson);
        } catch(e) {}
    }

    function detectOverlap(newGeojson) {
        if (!overlapWarning) return;
        var overlaps = [];

        existingBloks.forEach(function(blok) {
            if (!blok.geojson) return;
            try {
                if (polygonsIntersect(newGeojson, blok.geojson)) {
                    overlaps.push(blok.nama);
                }
            } catch(e) {}
        });

        if (overlaps.length > 0) {
            var msg = 'Peringatan: Area blok yang digambar bertumpuk dengan ' + overlaps.join(', ') + '. Silakan sesuaikan polygon agar tidak menimpa blok lain.';
            document.getElementById('overlap-message').textContent = msg;
            overlapWarning.classList.remove('hidden');
            if (submitBtn) submitBtn.disabled = true;
        } else {
            overlapWarning.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    // Simple polygon intersection check using bounding box + point-in-polygon
    function polygonsIntersect(geojsonA, geojsonB) {
        var coordsA = getCoords(geojsonA);
        var coordsB = getCoords(geojsonB);
        if (!coordsA || !coordsB) return false;

        // Check if any point of A is inside B or vice versa
        for (var i = 0; i < coordsA.length - 1; i++) {
            if (pointInPolygon(coordsA[i], coordsB)) return true;
        }
        for (var j = 0; j < coordsB.length - 1; j++) {
            if (pointInPolygon(coordsB[j], coordsA)) return true;
        }
        return false;
    }

    function getCoords(geojson) {
        if (geojson.type === 'Polygon') return geojson.coordinates[0];
        if (geojson.type === 'Feature' && geojson.geometry) return geojson.geometry.coordinates[0];
        if (geojson.type === 'FeatureCollection' && geojson.features && geojson.features.length > 0) {
            return geojson.features[0].geometry.coordinates[0];
        }
        return null;
    }

    function pointInPolygon(point, polygon) {
        var x = point[0], y = point[1];
        var inside = false;
        for (var i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            var xi = polygon[i][0], yi = polygon[i][1];
            var xj = polygon[j][0], yj = polygon[j][1];
            var intersect = ((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
            if (intersect) inside = !inside;
        }
        return inside;
    }

    // Observe geojson input changes
    var geojsonInput = document.getElementById('koordinat_geojson');
    if (geojsonInput) {
        var observer = new MutationObserver(checkOverlapOnChange);
        observer.observe(geojsonInput, { attributes: true, attributeFilter: ['value'] });
        geojsonInput.addEventListener('change', checkOverlapOnChange);
        geojsonInput.addEventListener('input', checkOverlapOnChange);
    }

    // Checkbox enable submit
    if (overlapCheckbox) {
        overlapCheckbox.addEventListener('change', function() {
            if (submitBtn) submitBtn.disabled = !this.checked && !overlapWarning.classList.contains('hidden');
        });
    }

    // Hook into Leaflet draw events to trigger overlap check
    if (typeof drawnItems !== 'undefined') {
        drawMap.on(L.Draw.Event.CREATED, function() {
            setTimeout(checkOverlapOnChange, 200);
        });
        drawMap.on(L.Draw.Event.EDITED, function() {
            setTimeout(checkOverlapOnChange, 200);
        });
    }
})();

// ─── UPLOAD FILE SHP/GEOJSON ─────────────────────────────────────
(function() {
    var fileInput = document.getElementById('geo_file_input');
    var dropzone = document.getElementById('upload-dropzone');
    var statusDiv = document.getElementById('upload-status');
    var loadingDiv = document.getElementById('upload-loading');
    var successDiv = document.getElementById('upload-success');
    var errorDiv = document.getElementById('upload-error');
    var successMsg = document.getElementById('upload-success-msg');
    var errorMsg = document.getElementById('upload-error-msg');

    if (!fileInput || !dropzone) return;

    // File input change
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            uploadFile(this.files[0]);
        }
    });

    // Drag & drop
    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('border-emerald-400', 'bg-emerald-50');
    });
    dropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-emerald-400', 'bg-emerald-50');
    });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-emerald-400', 'bg-emerald-50');
        if (e.dataTransfer.files.length > 0) {
            uploadFile(e.dataTransfer.files[0]);
        }
    });

    function showStatus(type) {
        statusDiv.classList.remove('hidden');
        loadingDiv.classList.toggle('hidden', type !== 'loading');
        successDiv.classList.toggle('hidden', type !== 'success');
        errorDiv.classList.toggle('hidden', type !== 'error');
    }

    function uploadFile(file) {
        // Validate extension
        var ext = file.name.split('.').pop().toLowerCase();
        if (!['zip', 'geojson', 'json'].includes(ext)) {
            showStatus('error');
            errorMsg.textContent = 'Format file tidak didukung. Gunakan .zip (Shapefile) atau .geojson.';
            return;
        }

        // Validate size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            showStatus('error');
            errorMsg.textContent = 'Ukuran file terlalu besar. Maksimal 10 MB.';
            return;
        }

        showStatus('loading');

        var formData = new FormData();
        formData.append('geo_file', file);
        formData.append('_token', document.querySelector('input[name="_token"]').value);

        fetch('{{ route("api.geo.upload") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showStatus('success');
                successMsg.textContent = '✓ ' + data.message;

                // Load polygon to map
                var geojson = data.geojson;
                var geoStr = JSON.stringify(geojson);
                document.getElementById('koordinat_geojson').value = geoStr;
                document.getElementById('textarea_geojson').value = geoStr;

                // Render on main draw map
                drawnItems.clearLayers();
                L.geoJSON(geojson, {
                    style: { color: '#059669', fillColor: '#059669', fillOpacity: 0.3, weight: 2 }
                }).eachLayer(function(l) { drawnItems.addLayer(l); });

                drawMap.fitBounds(drawnItems.getBounds().pad(0.2));
                updateLuas(geojson);
                syncLuasFullscreen();

                // ─── Preview Map di panel upload ───
                var previewWrapper = document.getElementById('upload-preview-wrapper');
                var previewLuas = document.getElementById('upload-preview-luas');
                var previewInfo = document.getElementById('upload-preview-info');
                previewWrapper.classList.remove('hidden');

                // Hitung luas
                var ha = calculateAreaHa(geojson);
                previewLuas.textContent = ha > 0 ? ha.toFixed(2) : '—';
                previewInfo.textContent = ha > 0 ? '✓ Polygon berhasil dimuat dari file' : 'Polygon dimuat';

                // Init preview map (destroy old one if exists)
                var previewMapEl = document.getElementById('upload-preview-map');
                if (window._uploadPreviewMap) {
                    window._uploadPreviewMap.remove();
                }
                window._uploadPreviewMap = L.map(previewMapEl, { zoomControl: true, attributionControl: false, dragging: true, scrollWheelZoom: true });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(window._uploadPreviewMap);

                var previewLayer = L.geoJSON(geojson, {
                    style: { color: '#059669', fillColor: '#059669', fillOpacity: 0.35, weight: 2.5 }
                }).addTo(window._uploadPreviewMap);

                window._uploadPreviewMap.fitBounds(previewLayer.getBounds().pad(0.15));

                // Trigger overlap check
                setTimeout(function() {
                    if (typeof checkOverlapOnChange === 'function') {
                        checkOverlapOnChange();
                    }
                    var evt = new Event('change', { bubbles: true });
                    document.getElementById('koordinat_geojson').dispatchEvent(evt);
                }, 300);
            } else {
                showStatus('error');
                errorMsg.textContent = data.message || 'Gagal memproses file.';
                // Hide preview on error
                document.getElementById('upload-preview-wrapper').classList.add('hidden');
            }
        })
        .catch(function(err) {
            showStatus('error');
            errorMsg.textContent = 'Terjadi kesalahan jaringan. Silakan coba lagi.';
            document.getElementById('upload-preview-wrapper').classList.add('hidden');
            console.error('Upload error:', err);
        });
    }
})();
</script>
@endpush
