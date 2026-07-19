@extends('layouts.app')

@section('title', 'Input Kondisi Lahan')
@section('page-title', 'Input Kondisi Lahan')
@section('page-subtitle', 'Data observasi visual tanaman & lingkungan untuk analisis RBS')

@section('content')

<div class="w-full max-w-4xl mx-auto">

    <form action="{{ route('kondisi-lahan.store') }}" method="POST" class="space-y-4 sm:space-y-6">
        @csrf

        {{-- SEKSI 1: Identifikasi Blok --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-6">
            <h2 class="text-sm sm:text-base font-semibold text-slate-800 mb-4 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">1</span>
                Identifikasi Blok Lahan
            </h2>

            {{-- Tanggal di atas — mobile-first --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Tanggal Observasi <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_observasi"
                        value="{{ old('tanggal_observasi', now()->format('Y-m-d')) }}"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors @error('tanggal_observasi') border-red-400 @enderror"
                        required>
                    @error('tanggal_observasi')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Tanggal Pemupukan Terakhir <span class="text-xs text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input type="date" name="tanggal_pemupukan_terakhir"
                        value="{{ old('tanggal_pemupukan_terakhir') }}"
                        max="{{ now()->format('Y-m-d') }}"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                    <p class="mt-1 text-xs text-slate-400">Interval &lt;60 hari → aplikasi ditunda. &gt;120 hari → perlu dijadwalkan segera.</p>
                </div>
            </div>

            {{-- Pemilik + Blok berdampingan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-start">
                {{-- Kolom kiri: Pilih Pemilik --}}
                <div>
                    @include('components.searchable-select', [
                        'name' => '_anggota_filter',
                        'label' => 'Pemilik Lahan',
                        'placeholder' => 'Cari nama pemilik...',
                        'options' => $anggotas,
                        'displayField' => 'nama',
                        'selected' => old('_anggota_filter', $selectedBlokId ? ($bloks->firstWhere('id', $selectedBlokId)?->anggota_id) : ''),
                        'required' => false,
                        'error' => null,
                        'helpText' => null,
                    ])
                </div>

                {{-- Kolom kanan: Pilih Blok (muncul setelah pilih pemilik) --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Blok Lahan <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" name="blok_lahan_id" id="blok-lahan-id-value" value="{{ old('blok_lahan_id', $selectedBlokId) }}">

                    <div id="blok-list-container" class="min-h-[44px]">
                        <p id="blok-hint" class="text-xs text-slate-400 py-2.5 px-3 bg-slate-50 rounded-xl border border-dashed border-slate-300">⬅️ Pilih pemilik lahan terlebih dahulu</p>
                        <div id="blok-list" class="grid grid-cols-1 gap-2 hidden"></div>
                    </div>
                    @error('blok_lahan_id')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Banner Info TBM (Tanaman Belum Menghasilkan) --}}
        <div id="banner-tbm" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-3 sm:p-4">
            <div class="flex items-start gap-2.5">
                <span class="text-lg flex-shrink-0">🌱</span>
                <div>
                    <p class="text-xs font-bold text-blue-800">Tanaman Belum Menghasilkan (TBM)</p>
                    <p class="text-xs text-blue-700 mt-0.5 leading-relaxed">Blok ini berusia &lt;3 tahun dan belum berbuah. Kondisi tandan otomatis diset "Tidak Ada Tandan". Dosis pupuk akan dihitung dengan formula khusus TBM (lebih rendah).</p>
                </div>
            </div>
        </div>

        {{-- SEKSI 2: Kondisi Tanah --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-6">
            <h2 class="text-base font-semibold text-slate-800 mb-1 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">2</span>
                Kondisi Tanah
            </h2>
            <p class="text-xs text-slate-400 mb-4 ml-0 sm:ml-8">Data keasaman dan kelembaban tanah untuk menentukan efektivitas penyerapan pupuk.</p>
            {{-- Stack di mobile, 3 kolom di desktop --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">pH Tanah</label>
                    <input type="number" name="ph_tanah" id="ph_tanah_input" value="{{ old('ph_tanah') }}"
                        step="0.1" min="3" max="8" placeholder="Contoh: 5.2"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors @error('ph_tanah') border-red-400 @enderror">
                    <p id="ph-warning-alert" class="mt-1 text-xs text-red-600 font-semibold hidden">⚠️ Nilai pH di luar skala normal (3.0 - 8.0)!</p>
                    <p class="mt-1 text-xs text-slate-400">Skala 3.0–8.0 · Optimal sawit: 5.5–6.5</p>
                    <p class="mt-0.5 text-xs text-amber-600">⚡ pH &lt; 4.5 = pupuk tidak efektif, perlu kapur dulu</p>
                    @error('ph_tanah') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    @include('components.custom-select', [
                        'name'    => 'kelembaban_tanah',
                        'label'   => 'Kelembaban Tanah',
                        'options' => ['Sangat Kering','Kering','Normal','Lembab','Sangat Lembab'],
                        'selected'=> old('kelembaban_tanah'),
                        'placeholder' => '— Pilih —',
                        'helpText' => 'Pengaruh: pupuk butuh kelembaban untuk terlarut dan diserap akar',
                    ])
                </div>
                <div>
                    @include('components.custom-select', [
                        'name'    => 'kondisi_drainase',
                        'label'   => 'Kondisi Drainase',
                        'options' => ['Baik','Cukup','Buruk — Tergenang'],
                        'selected'=> old('kondisi_drainase'),
                        'placeholder' => '— Pilih —',
                        'helpText' => 'Buruk = tergenang air, pupuk tanah akan terbuang sia-sia',
                    ])
                </div>
            </div>
        </div>

        {{-- SEKSI 3: Kondisi Iklim --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-6">
            <h2 class="text-base font-semibold text-slate-800 mb-1 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-bold">3</span>
                Kondisi Iklim
            </h2>
            <p class="text-xs text-slate-400 mb-4 ml-0 sm:ml-8">Musim dan curah hujan mempengaruhi kapan waktu terbaik aplikasi pupuk.</p>

            {{-- Tombol Ambil Data Cuaca Otomatis --}}
            <div class="mb-4 p-3 sm:p-4 rounded-xl" id="cuaca-auto-section" style="background:#e0f2fe; border:2px solid #7dd3fc;">
                <div class="flex items-start gap-2.5 mb-3">
                    <span class="text-xl flex-shrink-0">🌦️</span>
                    <div>
                        <p class="text-sm font-bold" style="color:#075985;">Data Cuaca Otomatis</p>
                        <p class="text-xs mt-0.5" style="color:#0369a1;" id="cuaca-auto-hint">Pilih blok lahan terlebih dahulu untuk mengambil data cuaca dari lokasi blok.</p>
                    </div>
                </div>

                {{-- Placeholder saat blok belum dipilih --}}
                <div id="cuaca-btn-placeholder" class="text-center py-3 px-4 rounded-xl" style="background:#f1f5f9; border:2px dashed #94a3b8;">
                    <p class="text-xs font-medium" style="color:#64748b;">⬆️ Pilih blok lahan di atas untuk mengaktifkan tombol cuaca</p>
                </div>

                {{-- Tombol aktif — hidden sampai blok dipilih --}}
                <button type="button" id="btn-fetch-cuaca" onclick="fetchCuacaOtomatis()"
                    class="hidden w-full rounded-xl"
                    style="display:none; min-height:52px; max-width:none!important; padding:14px 20px; background:#0284c7; color:#ffffff; font-size:15px; font-weight:700; border:2px solid #0369a1; border-radius:12px; cursor:pointer; box-shadow:0 4px 12px rgba(2,132,199,0.3); transition:all 0.15s; text-align:center;">
                    <span id="btn-fetch-cuaca-inner" style="display:flex; align-items:center; justify-content:center; gap:10px; max-width:none!important;">
                        <svg id="cuaca-icon-normal" style="width:20px; height:20px; max-width:none!important; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg id="cuaca-icon-loading" style="width:20px; height:20px; max-width:none!important; flex-shrink:0; display:none; animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span id="btn-fetch-cuaca-text">🔄 Ambil Data Cuaca Otomatis</span>
                    </span>
                </button>

                {{-- Result info --}}
                <div id="cuaca-result" style="display:none; margin-top:12px; padding:12px; background:#ecfdf5; border:1px solid #6ee7b7; border-radius:12px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <span style="font-size:18px;">✅</span>
                        <span style="font-size:13px; font-weight:700; color:#047857;">Data cuaca berhasil diambil!</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" style="font-size:11px;">
                        <div style="background:#fff; border-radius:8px; padding:8px; text-align:center; border:1px solid #d1fae5;">
                            <p style="color:#94a3b8; font-size:10px;">Rata-rata</p>
                            <p style="font-weight:700; color:#1e293b;" id="cuaca-rata2">-</p>
                        </div>
                        <div style="background:#fff; border-radius:8px; padding:8px; text-align:center; border:1px solid #d1fae5;">
                            <p style="color:#94a3b8; font-size:10px;">Total 30 hari</p>
                            <p style="font-weight:700; color:#1e293b;" id="cuaca-total">-</p>
                        </div>
                        <div style="background:#fff; border-radius:8px; padding:8px; text-align:center; border:1px solid #d1fae5;">
                            <p style="color:#94a3b8; font-size:10px;">Kategori</p>
                            <p style="font-weight:700; color:#0369a1;" id="cuaca-kategori">-</p>
                        </div>
                        <div style="background:#fff; border-radius:8px; padding:8px; text-align:center; border:1px solid #d1fae5;">
                            <p style="color:#94a3b8; font-size:10px;">Musim</p>
                            <p style="font-weight:700; color:#0369a1;" id="cuaca-musim">-</p>
                        </div>
                    </div>
                    <p style="font-size:10px; color:#047857; margin-top:8px; font-weight:500;" id="cuaca-periode"></p>
                </div>
                {{-- Error info --}}
                <div id="cuaca-error" style="display:none; margin-top:12px; padding:12px; background:#fef2f2; border:1px solid #fca5a5; border-radius:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:18px;">⚠️</span>
                        <p style="font-size:12px; font-weight:500; color:#b91c1c;" id="cuaca-error-msg"></p>
                    </div>
                    <p style="font-size:10px; color:#dc2626; margin-top:6px;">Anda tetap bisa mengisi data musim dan curah hujan secara manual di bawah.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    @include('components.custom-select', [
                        'name'    => 'musim_saat_ini',
                        'id'      => 'select-musim',
                        'label'   => 'Musim Saat Ini',
                        'options' => ['Musim Hujan','Musim Kemarau','Peralihan'],
                        'selected'=> old('musim_saat_ini'),
                        'placeholder' => '— Pilih —',
                        'helpText' => 'Musim hujan = waktu optimal pemupukan; Kemarau = pupuk kurang efektif',
                    ])
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Intensitas Curah Hujan</label>
                    {{-- Curah hujan dikelola oleh JS (berubah berdasarkan musim) --}}
                    <div style="position:relative; min-width:0;" id="curah-hujan-cs-wrapper">
                        <input type="hidden" name="curah_hujan_kategori" id="select-curah-hujan-val" value="{{ old('curah_hujan_kategori') }}">
                        <button type="button" id="select-curah-hujan-btn"
                            onclick="cuahToggle()"
                            style="width:100%; min-width:0; box-sizing:border-box; display:flex; align-items:center; justify-content:space-between; gap:8px; padding:10px 12px; border:1px solid transparent; border-radius:12px; font-size:14px; cursor:pointer; text-align:left;"
                            class="cs-btn-trigger">
                            <span id="select-curah-hujan-display" style="flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; opacity:0.6;">— Pilih —</span>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="select-curah-hujan-cs-arrow" style="flex-shrink:0; max-width:none!important; color:#94a3b8; transition:transform 0.2s;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        {{-- Panel dipindahkan ke body via JS (portal pattern) --}}
                        <div id="select-curah-hujan-cs-panel"
                            class="cs-panel"
                            style="display:none; position:fixed; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.16); z-index:99999; overflow:hidden;">
                            {{-- Panah atas — muncul saat bisa scroll ke atas --}}
                            <div id="curah-arrow-up" class="cs-arrow-up" style="display:none; align-items:center; justify-content:center; gap:4px; padding:5px 12px; cursor:pointer; user-select:none;"
                                onclick="document.getElementById('curah-options-scroll').scrollBy({top:-80,behavior:'smooth'})">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="max-width:none!important; flex-shrink:0; color:#64748b;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                                </svg>
                                <span style="font-size:10px; color:#64748b; font-weight:600;">Scroll ke atas</span>
                            </div>
                            {{-- Scrollable options --}}
                            <div id="curah-options-scroll" style="max-height:220px; overflow-y:auto; overscroll-behavior:contain;"></div>
                            {{-- Panah bawah — muncul saat masih ada konten di bawah --}}
                            <div id="curah-arrow-down" class="cs-arrow-down" style="display:none; align-items:center; justify-content:center; gap:4px; padding:5px 12px; cursor:pointer; user-select:none;"
                                onclick="document.getElementById('curah-options-scroll').scrollBy({top:80,behavior:'smooth'})">
                                <span style="font-size:10px; color:#64748b; font-weight:600;">Scroll ke bawah</span>
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="max-width:none!important; flex-shrink:0; color:#64748b;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-slate-400" id="curah-hujan-info">Pilih musim terlebih dahulu</p>
                </div>
            </div>
        </div>

        {{-- SEKSI 4: Gejala Visual Tanaman --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-6">
            <h2 class="text-base font-semibold text-slate-800 mb-1 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold">4</span>
                Gejala Visual Tanaman
            </h2>
            <p class="text-xs text-slate-400 mb-4 ml-0 sm:ml-8">Pengamatan fisik daun, pelepah, dan tandan untuk mendeteksi kekurangan unsur hara.</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
                <div>
                    @include('components.custom-select', [
                        'name'    => 'warna_daun',
                        'label'   => 'Warna Daun',
                        'options' => ['Hijau Normal','Hijau Pucat','Kuning Merata','Kuning Tepi','Kuning Antar Tulang','Oranye/Kemerahan','Coklat Ujung','Bercak Nekrotik'],
                        'selected'=> old('warna_daun'),
                        'placeholder' => '— Pilih —',
                    ])
                </div>
                <div>
                    @include('components.custom-select', [
                        'name'    => 'kondisi_pelepah',
                        'label'   => 'Kondisi Pelepah',
                        'options' => ['Normal','Patah/Menggantung','Kering Prematur','Pertumbuhan Terhambat'],
                        'selected'=> old('kondisi_pelepah'),
                        'placeholder' => '— Pilih —',
                    ])
                </div>
                <div>
                    <p class="block text-sm font-medium text-slate-700 mb-1.5">Kondisi Tandan / Buah</p>
                    <div id="kondisi-tandan-wrapper">
                        @include('components.custom-select', [
                            'name'    => 'kondisi_tandan',
                            'id'      => 'kondisi-tandan-cs',
                            'options' => ['Normal','Kecil','Rontok Prematur','Busuk Pangkal','Tidak Ada Tandan'],
                            'selected'=> old('kondisi_tandan'),
                            'placeholder' => '— Pilih —',
                        ])
                    </div>
                    <p id="tandan-tbm-note" class="hidden mt-1 text-[10px] text-blue-600 font-medium">🌱 Terkunci — tanaman belum menghasilkan (belum berbuah)</p>
                </div>
            </div>

            {{-- Dugaan Unsur Hara yang Kurang (Fitur 8) --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Dugaan Unsur Hara yang Kurang
                    <span class="text-xs text-slate-400 font-normal">(opsional, boleh pilih lebih dari satu)</span>
                </label>
                <p class="text-xs text-slate-400 mb-3">Opsional. Pilih unsur hara yang diduga kurang berdasarkan gejala visual daun. Jika tidak yakin, kosongkan pilihan ini. Field ini bukan hasil uji lab, tetapi dugaan awal berdasarkan pengamatan visual di lapangan.</p>

                <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-7 gap-1.5 sm:gap-2" id="defisiensi-grid">
                    @foreach(['N','P','K','Mg','B','Fe','Zn'] as $def)
                    @php
                        $defLabel = match($def) {
                            'N'  => 'Nitrogen',
                            'P'  => 'Fosfor',
                            'K'  => 'Kalium',
                            'Mg' => 'Magnesium',
                            'B'  => 'Boron',
                            'Fe' => 'Besi',
                            'Zn' => 'Seng',
                            default => $def,
                        };
                        $defHint = match($def) {
                            'N'  => 'Daun kuning merata',
                            'P'  => 'Ujung daun coklat',
                            'K'  => 'Daun oranye/tepi kuning',
                            'Mg' => 'Kuning antar tulang',
                            'B'  => 'Pucuk kerdil/bengkok',
                            'Fe' => 'Daun muda pucat',
                            'Zn' => 'Daun muda kecil',
                            default => '',
                        };
                        $checked = in_array($def, old('gejala_defisiensi', []));
                    @endphp
                    <label class="def-label flex flex-col items-center gap-1 p-2 sm:p-2.5 border rounded-xl cursor-pointer transition-all {{ $checked ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500' : 'border-slate-200 hover:bg-emerald-50 hover:border-emerald-400' }}">
                        <input type="checkbox" name="gejala_defisiensi[]" value="{{ $def }}"
                            {{ $checked ? 'checked' : '' }}
                            class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <span class="text-sm sm:text-base font-bold text-slate-800">{{ $def }}</span>
                        <span class="text-[10px] text-slate-500 text-center leading-tight">{{ $defLabel }}</span>
                        <span class="text-[9px] text-slate-400 text-center leading-tight italic hidden sm:block">{{ $defHint }}</span>
                    </label>
                    @endforeach
                </div>

                <div class="mt-3 p-3 rounded-xl bg-blue-50 border border-blue-100 text-xs text-blue-800">
                    <p class="font-semibold mb-1">💡 Panduan memilih dugaan unsur:</p>
                    <ul class="space-y-0.5 text-blue-700">
                        <li>• <strong>N (Nitrogen)</strong> — biasanya terkait daun hijau pucat atau kuning merata</li>
                        <li>• <strong>K (Kalium)</strong> — sering berkaitan dengan tepi daun kuning/oranye</li>
                        <li>• <strong>Mg (Magnesium)</strong> — sering berkaitan dengan kuning antar tulang daun</li>
                        <li>• <strong>B (Boron)</strong> — dapat berkaitan dengan pertumbuhan terhambat atau daun muda abnormal</li>
                        <li>• <strong>P (Fosfor)</strong> — dapat berkaitan dengan pertumbuhan lemah atau ujung daun mengering</li>
                        <li>• <strong>Fe (Besi)</strong> — dapat berkaitan dengan klorosis pada daun muda</li>
                        <li>• <strong>Zn (Seng)</strong> — dapat berkaitan dengan pertumbuhan tidak normal</li>
                    </ul>
                </div>
            </div>

            {{-- Toggle Kondisi Khusus --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="toggle-label flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition-all {{ old('ada_serangan_hama') ? 'bg-red-50 border-red-400' : 'border-slate-200 hover:bg-red-50 hover:border-red-300' }}">
                    <input type="checkbox" name="ada_serangan_hama" value="1"
                        {{ old('ada_serangan_hama') ? 'checked' : '' }}
                        class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500">
                    <div>
                        <span class="text-sm font-medium text-slate-800">Ada Serangan Hama / Penyakit</span>
                        <p class="text-xs text-slate-400 mt-0.5">Terlihat gejala serangan fisik atau bercak penyakit</p>
                    </div>
                </label>
                <label class="toggle-label flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition-all {{ old('ada_gulma_dominan') ? 'bg-amber-50 border-amber-400' : 'border-slate-200 hover:bg-amber-50 hover:border-amber-300' }}">
                    <input type="checkbox" name="ada_gulma_dominan" value="1"
                        {{ old('ada_gulma_dominan') ? 'checked' : '' }}
                        class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-medium text-slate-800">Ada Gulma Dominan</span>
                        <p class="text-xs text-slate-400 mt-0.5">Gulma menutupi piringan atau gawangan secara masif</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- SEKSI 5: Catatan --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-6">
            <h2 class="text-sm sm:text-base font-semibold text-slate-800 mb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-bold">5</span>
                Catatan Observasi
            </h2>
            <textarea name="catatan_observasi" rows="3"
                placeholder="Catatan tambahan dari petugas lapangan (opsional)..."
                class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors resize-none">{{ old('catatan_observasi') }}</textarea>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2 sm:gap-3 pb-2">
            <a href="{{ route('kondisi-lahan.index') }}"
               class="px-5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-700 hover:bg-slate-50 transition-colors font-medium text-center">
                Batal
            </a>
            <button type="submit"
                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm shadow-emerald-600/20">
                Simpan Data Kondisi
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
// Toggle visual state untuk defisiensi checkboxes
document.querySelectorAll('#defisiensi-grid .def-label input[type="checkbox"]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var label = this.closest('.def-label');
        if (this.checked) {
            label.classList.remove('border-slate-200', 'hover:bg-emerald-50', 'hover:border-emerald-400');
            label.classList.add('bg-emerald-50', 'border-emerald-500', 'ring-2', 'ring-emerald-500');
        } else {
            label.classList.remove('bg-emerald-50', 'border-emerald-500', 'ring-2', 'ring-emerald-500');
            label.classList.add('border-slate-200', 'hover:bg-emerald-50', 'hover:border-emerald-400');
        }
    });
});

// Toggle visual state untuk hama/gulma checkboxes
document.querySelectorAll('.toggle-label input[type="checkbox"]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var label = this.closest('.toggle-label');
        var isHama = this.name === 'ada_serangan_hama';
        var activeClasses = isHama ? ['bg-red-50', 'border-red-400'] : ['bg-amber-50', 'border-amber-400'];
        var inactiveClasses = isHama
            ? ['border-slate-200', 'hover:bg-red-50', 'hover:border-red-300']
            : ['border-slate-200', 'hover:bg-amber-50', 'hover:border-amber-300'];

        if (this.checked) {
            inactiveClasses.forEach(function(c) { label.classList.remove(c); });
            activeClasses.forEach(function(c) { label.classList.add(c); });
        } else {
            activeClasses.forEach(function(c) { label.classList.remove(c); });
            inactiveClasses.forEach(function(c) { label.classList.add(c); });
        }
    });
});

// ─── MUSIM → CURAH HUJAN DEPENDENCY ──────────────────────────────
var oldCurah = '{{ old("curah_hujan_kategori") }}';

var curahOptions = {
    'Musim Hujan':  ['Normal','Tinggi','Sangat Tinggi'],
    'Musim Kemarau':['Sangat Rendah','Rendah','Normal'],
    'Peralihan':    ['Sangat Rendah','Rendah','Normal','Tinggi','Sangat Tinggi'],
};

var curahHints = {
    'Musim Hujan':  'Saat musim hujan, curah hujan umumnya Normal hingga Sangat Tinggi',
    'Musim Kemarau':'Saat kemarau, curah hujan umumnya Sangat Rendah hingga Normal',
    'Peralihan':    'Saat peralihan musim, curah hujan bisa bervariasi',
};

function updateCurahOptions(musimVal, autoSelectVal) {
    var panel     = document.getElementById('select-curah-hujan-cs-panel');
    var scrollEl  = document.getElementById('curah-options-scroll');
    var valEl     = document.getElementById('select-curah-hujan-val');
    var display   = document.getElementById('select-curah-hujan-display');
    var infoEl    = document.getElementById('curah-hujan-info');

    if (!panel || !scrollEl) return;

    // Tutup panel dulu saat opsi berubah
    cuahClose();
    scrollEl.innerHTML = '';

    if (!musimVal || !curahOptions[musimVal]) {
        if (display) { display.textContent = '— Pilih —'; display.style.color = ''; display.style.opacity = '0.6'; }
        if (valEl) valEl.value = '';
        if (infoEl) { infoEl.textContent = 'Pilih musim terlebih dahulu'; infoEl.className = 'mt-1 text-xs text-slate-400'; }
        return;
    }

    var opts = curahOptions[musimVal];

    opts.forEach(function(opt) {
        var dk = document.documentElement.classList.contains('dark');
        var div = document.createElement('div');
        div.className = 'cs-option';
        div.setAttribute('data-value', opt);
        div.setAttribute('data-label', opt);
        div.style.cssText = 'padding:11px 14px; cursor:pointer; font-size:13px; border-bottom:1px solid transparent; word-break:break-word; line-height:1.4;';
        div.textContent = opt;
        div.addEventListener('click', function() {
            var dk = document.documentElement.classList.contains('dark');
            if (valEl) valEl.value = opt;
            if (display) { display.textContent = opt; display.style.color = ''; display.style.opacity = '1'; }
            scrollEl.querySelectorAll('.cs-option').forEach(function(o) {
                var isMe = o.getAttribute('data-value') === opt;
                o.style.background = isMe ? (dk ? '#064e3b' : '#f0fdf4') : '';
                o.style.color      = isMe ? (dk ? '#6ee7b7' : '#065f46') : '';
                o.style.fontWeight = isMe ? '600' : '400';
            });
            cuahClose();
        });
        div.addEventListener('mouseenter', function() { var dk = document.documentElement.classList.contains('dark'); if (!this.style.background || this.style.background === '') this.style.background = dk ? '#334155' : '#f8fafc'; });
        div.addEventListener('mouseleave', function() { var dk = document.documentElement.classList.contains('dark'); var hoverBg = dk ? 'rgb(51, 65, 85)' : 'rgb(248, 250, 252)'; if (this.style.background === hoverBg) this.style.background = ''; });
        scrollEl.appendChild(div);
    });

    if (infoEl) { infoEl.textContent = curahHints[musimVal] || ''; infoEl.className = 'mt-1 text-xs text-sky-600'; }

    // Auto-select jika ada nilai sebelumnya
    var toSelect = autoSelectVal || (valEl && valEl.value);
    if (toSelect && opts.includes(toSelect)) {
        if (display) { display.textContent = toSelect; display.style.color = ''; display.style.opacity = '1'; }
        if (valEl) valEl.value = toSelect;
    }
}

// Update visibilitas panah atas/bawah curah hujan
function cuahUpdateArrows() {
    var scrollEl  = document.getElementById('curah-options-scroll');
    var arrowUp   = document.getElementById('curah-arrow-up');
    var arrowDown = document.getElementById('curah-arrow-down');
    if (!scrollEl) return;
    var atTop    = scrollEl.scrollTop <= 2;
    var atBottom = scrollEl.scrollTop + scrollEl.clientHeight >= scrollEl.scrollHeight - 2;
    if (arrowUp)   arrowUp.style.display  = atTop    ? 'none' : 'flex';
    if (arrowDown) arrowDown.style.display = atBottom ? 'none' : 'flex';
}

// Pasang scroll listener pada curah-options-scroll
document.addEventListener('DOMContentLoaded', function() {
    var curahScroll = document.getElementById('curah-options-scroll');
    if (curahScroll) {
        curahScroll.addEventListener('scroll', cuahUpdateArrows);
    }
});

// ─── Portal toggle functions untuk curah hujan dropdown ──────────
function cuahPositionPanel() {
    var btn   = document.getElementById('select-curah-hujan-btn');
    var panel = document.getElementById('select-curah-hujan-cs-panel');
    var scrollEl = document.getElementById('curah-options-scroll');
    if (!btn || !panel) return;

    var rect    = btn.getBoundingClientRect();
    var viewH   = window.innerHeight;
    var viewW   = window.innerWidth;
    var isMobile = viewW < 640;

    if (isMobile) {
        panel.style.left  = '8px';
        panel.style.right = '8px';
        panel.style.width = 'auto';
    } else {
        panel.style.left  = rect.left + 'px';
        panel.style.width = rect.width + 'px';
        panel.style.right = 'auto';
    }

    var spaceBelow = viewH - rect.bottom - 10;
    var spaceAbove = rect.top - 10;
    var maxH       = Math.min(220, Math.max(spaceBelow, spaceAbove));
    if (scrollEl) scrollEl.style.maxHeight = maxH + 'px';

    if (spaceBelow >= Math.min(160, panel.offsetHeight) || spaceBelow >= spaceAbove) {
        panel.style.top    = (rect.bottom + 4) + 'px';
        panel.style.bottom = 'auto';
    } else {
        panel.style.bottom = (viewH - rect.top + 4) + 'px';
        panel.style.top    = 'auto';
    }
}

var _cuahOpen = false;
var _cuahRAF  = null;

function cuahToggle() {
    if (_cuahOpen) { cuahClose(); } else { cuahOpenPanel(); }
}

function cuahOpenPanel() {
    var panel = document.getElementById('select-curah-hujan-cs-panel');
    var arrow = document.getElementById('select-curah-hujan-cs-arrow');
    var btn   = document.getElementById('select-curah-hujan-btn');
    if (!panel) return;

    // Portal: pindahkan ke body
    if (panel.parentElement !== document.body) document.body.appendChild(panel);
    panel.style.display = 'block';
    _cuahOpen = true;
    cuahPositionPanel();
    cuahUpdateArrows();
    if (arrow) arrow.style.transform = 'rotate(180deg)';
    if (btn)   btn.style.borderColor = '#10b981';
}

function cuahClose() {
    var panel = document.getElementById('select-curah-hujan-cs-panel');
    var arrow = document.getElementById('select-curah-hujan-cs-arrow');
    var btn   = document.getElementById('select-curah-hujan-btn');
    if (!panel) return;
    panel.style.display = 'none';
    _cuahOpen = false;
    if (arrow) arrow.style.transform = '';
    if (btn)   btn.style.borderColor = '';
}

// Reposisi saat scroll/resize
window.addEventListener('scroll', function() {
    if (!_cuahOpen) return;
    if (_cuahRAF) cancelAnimationFrame(_cuahRAF);
    _cuahRAF = requestAnimationFrame(cuahPositionPanel);
}, { passive: true, capture: true });

window.addEventListener('resize', function() {
    if (!_cuahOpen) return;
    if (_cuahRAF) cancelAnimationFrame(_cuahRAF);
    _cuahRAF = requestAnimationFrame(cuahPositionPanel);
}, { passive: true });

// Tutup saat klik di luar
document.addEventListener('click', function(e) {
    if (!_cuahOpen) return;
    var btn   = document.getElementById('select-curah-hujan-btn');
    var panel = document.getElementById('select-curah-hujan-cs-panel');
    if (btn   && btn.contains(e.target))   return;
    if (panel && panel.contains(e.target)) return;
    cuahClose();
}, true);

// Listen ke custom select musim via change event pada hidden input
var musimValEl = document.querySelector('[name="_cs_musim_saat_ini"], [id$="-val"][id*="musim_saat_ini"]');
// Fallback: watch via MutationObserver pada cs-wrapper musim
document.addEventListener('change', function(e) {
    if (e.target && e.target.name === 'musim_saat_ini') {
        updateCurahOptions(e.target.value);
    }
});

// Init
(function() {
    var musimHidden = document.querySelector('input[name="musim_saat_ini"][type="hidden"]');
    if (musimHidden && musimHidden.value) {
        updateCurahOptions(musimHidden.value, oldCurah);
    }
})();

// ─── CASCADING: PEMILIK → BLOK LAHAN ─────────────────────────────
var bloksData = @json($bloksJson);
var blokListEl = document.getElementById('blok-list');
var blokHintEl = document.getElementById('blok-hint');
var blokValueEl = document.getElementById('blok-lahan-id-value');
var selectedBlokId = '{{ old("blok_lahan_id", $selectedBlokId) }}';

// Listen for pemilik selection (from searchable-select component)
var anggotaHidden = document.querySelector('input[name="_anggota_filter"]');
if (anggotaHidden) {
    // Use MutationObserver to detect value changes on hidden input
    var observer = new MutationObserver(function() { renderBlokList(anggotaHidden.value); });
    observer.observe(anggotaHidden, { attributes: true, attributeFilter: ['value'] });

    // Also poll for changes (backup since hidden input value change doesn't trigger mutation)
    var lastAnggotaVal = anggotaHidden.value;
    setInterval(function() {
        if (anggotaHidden.value !== lastAnggotaVal) {
            lastAnggotaVal = anggotaHidden.value;
            renderBlokList(anggotaHidden.value);
        }
    }, 200);

    // Initial render if already selected
    if (anggotaHidden.value) renderBlokList(anggotaHidden.value);
}

function renderBlokList(anggotaId) {
    if (!anggotaId) {
        blokListEl.classList.add('hidden');
        blokHintEl.classList.remove('hidden');
        blokHintEl.textContent = 'Pilih pemilik lahan terlebih dahulu untuk menampilkan daftar blok.';
        return;
    }

    var filtered = bloksData.filter(function(b) { return b.anggota_id == anggotaId; });
    // Sort: terbaru dulu
    filtered.sort(function(a, b) { return b.updated_at - a.updated_at; });

    if (filtered.length === 0) {
        blokListEl.classList.add('hidden');
        blokHintEl.classList.remove('hidden');
        blokHintEl.textContent = 'Pemilik ini belum memiliki blok lahan.';
        return;
    }

    blokHintEl.classList.add('hidden');
    blokListEl.classList.remove('hidden');
    blokListEl.innerHTML = '';

    filtered.forEach(function(blok, idx) {
        var isSelected = blokValueEl.value == blok.id;
        var isNew = idx === 0; // terbaru
        var card = document.createElement('div');
        card.className = 'blok-card relative flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition-all '
            + (isSelected ? 'bg-emerald-50 border-emerald-500 ring-1 ring-emerald-500' : 'bg-white border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/30');
        card.dataset.blokId = blok.id;

        card.innerHTML = '<div class="flex-1 min-w-0">'
            + '<p class="font-semibold text-slate-800 text-sm truncate">' + blok.nama_blok + '</p>'
            + '<p class="text-[11px] text-slate-500 mt-0.5">' + blok.luas_ha + ' Ha · ' + blok.kategori + '</p>'
            + '</div>'
            + (isNew ? '<span class="text-[9px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-full font-semibold flex-shrink-0">Terbaru</span>' : '')
            + (isSelected ? '<svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' : '');

        card.addEventListener('click', function() {
            blokValueEl.value = blok.id;
            handleBlokSelected(blok);
            renderBlokList(anggotaId); // re-render to update selection
        });

        blokListEl.appendChild(card);
    });

    // If already selected (e.g. from old input), trigger banner check
    if (selectedBlokId) {
        var sel = bloksData.find(function(b) { return b.id == selectedBlokId; });
        if (sel) handleBlokSelected(sel);
    }
}

function handleBlokSelected(blok) {
    var bannerEl = document.getElementById('banner-tbm');
    var tandanNote = document.getElementById('tandan-tbm-note');

    if (blok.kategori === 'Belum Menghasilkan') {
        bannerEl.classList.remove('hidden');
        // Paksa kondisi tandan = "Tidak Ada Tandan" via custom select
        var valEl = document.querySelector('input[name="kondisi_tandan"][type="hidden"]');
        var displayEl = document.getElementById('kondisi-tandan-cs-display');
        var btnEl     = document.getElementById('kondisi-tandan-cs-btn');
        if (valEl)    { valEl.value = 'Tidak Ada Tandan'; }
        if (displayEl){ displayEl.textContent = 'Tidak Ada Tandan'; displayEl.style.color = '#1e293b'; }
        if (btnEl)    { btnEl.style.opacity = '0.6'; btnEl.style.pointerEvents = 'none'; }
        if (tandanNote) tandanNote.classList.remove('hidden');
        // Tambah hidden input sebagai fallback
        var existing = document.getElementById('tandan-hidden-tbm');
        if (!existing) {
            var hi = document.createElement('input');
            hi.type = 'hidden';
            hi.name = 'kondisi_tandan_override';
            hi.value = 'Tidak Ada Tandan';
            hi.id = 'tandan-hidden-tbm';
            document.querySelector('form').appendChild(hi);
        }
    } else {
        bannerEl.classList.add('hidden');
        var btnEl = document.getElementById('kondisi-tandan-cs-btn');
        if (btnEl) { btnEl.style.opacity = ''; btnEl.style.pointerEvents = ''; }
        if (tandanNote) tandanNote.classList.add('hidden');
        var existing = document.getElementById('tandan-hidden-tbm');
        if (existing) existing.remove();
    }

    // Enable/disable tombol cuaca otomatis berdasarkan blok yang dipilih
    updateCuacaButton(blok);
}

// ─── CUACA OTOMATIS (Open-Meteo API) ─────────────────────────────
function updateCuacaButton(blok) {
    var btn = document.getElementById('btn-fetch-cuaca');
    var hint = document.getElementById('cuaca-auto-hint');
    var placeholder = document.getElementById('cuaca-btn-placeholder');

    if (blok && blok.centroid_lat && blok.centroid_lng) {
        // Tampilkan tombol, sembunyikan placeholder
        btn.style.display = 'block';
        placeholder.style.display = 'none';
        hint.textContent = 'Blok "' + blok.nama_blok + '" dipilih — klik tombol biru di bawah untuk mengisi otomatis.';
        showToast('info', '📍 Blok "' + blok.nama_blok + '" dipilih. Klik tombol cuaca untuk mengisi otomatis.');
    } else if (blok) {
        // Blok dipilih tapi tidak ada koordinat
        btn.style.display = 'none';
        placeholder.style.display = 'block';
        placeholder.innerHTML = '<p style="font-size:12px; color:#d97706; font-weight:500;">⚠️ Blok "' + blok.nama_blok + '" belum memiliki koordinat peta. Silakan isi data iklim secara manual.</p>';
        hint.textContent = 'Blok belum memiliki koordinat peta.';
        showToast('warning', '⚠️ Blok ini belum punya koordinat. Isi data iklim secara manual.');
    } else {
        // Belum ada blok dipilih
        btn.style.display = 'none';
        placeholder.style.display = 'block';
        placeholder.innerHTML = '<p style="font-size:12px; color:#64748b; font-weight:500;">⬆️ Pilih blok lahan di atas untuk mengaktifkan tombol cuaca</p>';
        hint.textContent = 'Pilih blok lahan terlebih dahulu untuk mengambil data cuaca dari lokasi blok.';
    }
}

// ─── TOAST ALERT SYSTEM ──────────────────────────────────────────
// showToast sudah tersedia global dari layouts/app.blade.php

function fetchCuacaOtomatis() {
    var blokId = document.getElementById('blok-lahan-id-value').value;
    if (!blokId) return;

    var blok = bloksData.find(function(b) { return b.id == blokId; });
    if (!blok || !blok.centroid_lat || !blok.centroid_lng) return;

    var btn = document.getElementById('btn-fetch-cuaca');
    var btnText = document.getElementById('btn-fetch-cuaca-text');

    // Loading state
    btn.style.opacity = '0.7';
    btn.style.pointerEvents = 'none';
    btnText.textContent = '⏳ Mengambil data cuaca...';
    document.getElementById('cuaca-icon-normal').style.display = 'none';
    document.getElementById('cuaca-icon-loading').style.display = 'inline-block';
    document.getElementById('cuaca-result').style.display = 'none';
    document.getElementById('cuaca-error').style.display = 'none';

    // AbortController untuk timeout 30 detik (ngrok + API bisa lambat)
    var controller = new AbortController();
    var timeoutId = setTimeout(function() { controller.abort(); }, 30000);

    fetch('{{ route("api.cuaca.fetch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'ngrok-skip-browser-warning': 'true',
        },
        body: JSON.stringify({ lat: blok.centroid_lat, lng: blok.centroid_lng }),
        signal: controller.signal
    })
    .then(function(response) {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error('Server response: ' + response.status);
        }
        // Pastikan response adalah JSON (bukan HTML dari ngrok interstitial/login redirect)
        var contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error('Response bukan JSON (kemungkinan session expired atau ngrok interstitial). Refresh halaman dan coba lagi.');
        }
        return response.json();
    })
    .then(function(data) {
        // Reset loading state
        btn.style.opacity = '1';
        btn.style.pointerEvents = 'auto';
        btnText.textContent = '🔄 Ambil Data Cuaca Otomatis';
        document.getElementById('cuaca-icon-normal').style.display = 'inline-block';
        document.getElementById('cuaca-icon-loading').style.display = 'none';

        if (data.success) {
            // Show result info
            document.getElementById('cuaca-result').style.display = 'block';
            document.getElementById('cuaca-error').style.display = 'none';
            document.getElementById('cuaca-rata2').textContent = data.detail.rata_rata_harian_mm + ' mm/hari';
            document.getElementById('cuaca-total').textContent = (data.detail.total_curah_hujan_mm || data.detail.total_30_hari_mm) + ' mm';
            document.getElementById('cuaca-kategori').textContent = data.curah_hujan_kategori;
            document.getElementById('cuaca-musim').textContent = data.musim_saat_ini;
            document.getElementById('cuaca-periode').textContent = data.detail.analisis || '';

            // Toast sukses
            showToast('success', '🌤️ Data cuaca berhasil diambil! Musim: ' + data.musim_saat_ini + ', Curah Hujan: ' + data.curah_hujan_kategori, 5000);

            // Auto-fill musim menggunakan custom-select API (csSelect)
            // ID komponen musim adalah 'select-musim', sehingga hidden input = 'select-musim-val'
            var musimValEl    = document.getElementById('select-musim-val');
            var musimDisplayEl = document.getElementById('select-musim-display');
            var musimBtnEl    = document.getElementById('select-musim-btn');
            var musimPanelEl  = document.getElementById('select-musim-panel');

            if (musimValEl) {
                // Set nilai pada hidden input
                musimValEl.value = data.musim_saat_ini;

                // Update tampilan display span
                if (musimDisplayEl) {
                    musimDisplayEl.textContent = data.musim_saat_ini;
                    musimDisplayEl.style.color = '#1e293b';
                }

                // Update active state pada panel options
                if (musimPanelEl) {
                    musimPanelEl.querySelectorAll('.cs-option').forEach(function(opt) {
                        var isMe = opt.getAttribute('data-value') === data.musim_saat_ini;
                        opt.style.background = isMe ? '#f0fdf4' : '#fff';
                        opt.style.color      = isMe ? '#065f46' : '#374151';
                        opt.style.fontWeight = isMe ? '600' : '400';
                    });
                }

                // Dispatch change event agar updateCurahOptions() terpanggil
                musimValEl.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Setelah options curah hujan diupdate oleh updateCurahOptions(), set nilainya
            setTimeout(function() {
                updateCurahOptions(data.musim_saat_ini, data.curah_hujan_kategori);

                var infoEl = document.getElementById('curah-hujan-info');
                if (infoEl) {
                    infoEl.textContent = '✓ Terisi otomatis dari data cuaca — Anda tetap bisa mengubahnya';
                    infoEl.className = 'mt-1 text-xs text-emerald-600 font-medium';
                }
            }, 100);
        } else {
            // Show error
            document.getElementById('cuaca-error').style.display = 'block';
            document.getElementById('cuaca-result').style.display = 'none';
            document.getElementById('cuaca-error-msg').textContent = data.message || 'Gagal mengambil data cuaca.';
            showToast('warning', '⚠️ ' + (data.message || 'Gagal mengambil data cuaca. Isi manual.'), 5000);
        }
    })
    .catch(function(err) {
        clearTimeout(timeoutId);
        btn.style.opacity = '1';
        btn.style.pointerEvents = 'auto';
        btnText.textContent = '🔄 Ambil Data Cuaca Otomatis';
        document.getElementById('cuaca-icon-normal').style.display = 'inline-block';
        document.getElementById('cuaca-icon-loading').style.display = 'none';
        document.getElementById('cuaca-error').style.display = 'block';
        document.getElementById('cuaca-result').style.display = 'none';

        var msg = 'Tidak dapat terhubung ke server.';
        if (err.name === 'AbortError') {
            msg = 'Request timeout (>30 detik). Koneksi ke server terlalu lambat. Silakan isi form secara manual.';
        } else if (err.message) {
            msg = 'Gagal terhubung: ' + err.message + '. Silakan isi form cuaca secara manual.';
        }
        document.getElementById('cuaca-error-msg').textContent = msg;
        showToast('error', '❌ ' + msg, 6000);
    });
}

// pH Input Warning Validation
(function() {
    var phInput = document.getElementById('ph_tanah_input');
    var warning = document.getElementById('ph-warning-alert');
    if (phInput) {
        phInput.addEventListener('input', function() {
            var val = parseFloat(this.value);
            if (this.value !== '' && (val < 3.0 || val > 8.0)) {
                if (warning) warning.classList.remove('hidden');
            } else {
                if (warning) warning.classList.add('hidden');
            }
        });
        phInput.addEventListener('blur', function() {
            var val = parseFloat(this.value);
            if (this.value !== '' && (val < 3.0 || val > 8.0)) {
                showToast('error', '⚠️ Peringatan: Nilai pH tanah (' + this.value + ') berada di luar skala normal (3.0 - 8.0). Silakan masukkan nilai antara 3.0 hingga 8.0.', 7000);
                this.value = '';
                if (warning) warning.classList.add('hidden');
            }
        });
    }
})();
</script>
@endpush
