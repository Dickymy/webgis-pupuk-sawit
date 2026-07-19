@extends('layouts.app')

@section('title', 'Edit Kondisi Lahan')
@section('page-title', 'Edit Kondisi Lahan')
@section('page-subtitle', 'Perbarui data observasi visual tanaman & lingkungan')

@section('content')

<div class="w-full max-w-4xl mx-auto">

    <form action="{{ route('kondisi-lahan.update', $kondisiLahan) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- SEKSI 1: Identifikasi Blok --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">1</span>
                Identifikasi Blok Lahan
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    @php
                        $blokOptions = $bloks->map(function($b) {
                            $b->display_label = $b->nama_blok . ' — ' . ($b->anggota?->nama ?? '-');
                            return $b;
                        });
                    @endphp
                    @include('components.searchable-select', [
                        'name' => 'blok_lahan_id',
                        'label' => 'Blok Lahan',
                        'placeholder' => 'Cari blok lahan atau pemilik...',
                        'options' => $blokOptions,
                        'displayField' => 'display_label',
                        'selected' => old('blok_lahan_id', $kondisiLahan->blok_lahan_id),
                        'required' => true,
                        'error' => $errors->first('blok_lahan_id'),
                    ])
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Tanggal Observasi <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_observasi"
                        value="{{ old('tanggal_observasi', $kondisiLahan->tanggal_observasi->format('Y-m-d')) }}"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                        required>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Tanggal Pemupukan Terakhir <span class="text-xs text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input type="date" name="tanggal_pemupukan_terakhir"
                        value="{{ old('tanggal_pemupukan_terakhir', $kondisiLahan->tanggal_pemupukan_terakhir?->format('Y-m-d')) }}"
                        max="{{ now()->format('Y-m-d') }}"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                    <p class="mt-1 text-xs text-slate-400">Interval &lt;60 hari → aplikasi ditunda. &gt;120 hari → perlu dijadwalkan segera.</p>
                </div>
                <div></div>
            </div>
        </div>

        {{-- Banner Info TBM (Tanaman Belum Menghasilkan) --}}
        <div id="banner-tbm" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-3 sm:p-4">
            <div class="flex items-start gap-2.5">
                <span class="text-lg flex-shrink-0">🌱</span>
                <div>
                    <p class="text-xs font-bold text-blue-800">Tanaman Belum Menghasilkan (TBM)</p>
                    <p class="text-xs text-blue-700 mt-0.5 leading-relaxed">Blok ini berusia &lt;3 tahun dan belum berbuah. Kondisi tandan otomatis diset "Tidak Ada Tandan".</p>
                </div>
            </div>
        </div>

        {{-- SEKSI 2: Kondisi Tanah --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">2</span>
                Kondisi Tanah
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        pH Tanah
                        <span class="text-xs text-slate-400 font-normal">(skala 3.0–8.0)</span>
                    </label>
                    <input type="number" name="ph_tanah" id="ph_tanah_input"
                        value="{{ old('ph_tanah', $kondisiLahan->ph_tanah) }}"
                        step="0.1" min="3" max="8" placeholder="Contoh: 5.2"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                    <p id="ph-warning-alert" class="mt-1 text-xs text-red-600 font-semibold hidden">⚠️ Nilai pH di luar skala normal (3.0 - 8.0)!</p>
                    <p class="mt-1 text-xs text-slate-400">Optimal sawit: 5.5–6.5</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kelembaban Tanah</label>
                    <select name="kelembaban_tanah"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors appearance-auto">
                        <option value="">— Pilih —</option>
                        @foreach(['Sangat Kering','Kering','Normal','Lembab','Sangat Lembab'] as $opt)
                            <option value="{{ $opt }}" {{ old('kelembaban_tanah', $kondisiLahan->kelembaban_tanah) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kondisi Drainase</label>
                    <select name="kondisi_drainase"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors appearance-auto">
                        <option value="">— Pilih —</option>
                        @foreach(['Baik','Cukup','Buruk — Tergenang'] as $opt)
                            <option value="{{ $opt }}" {{ old('kondisi_drainase', $kondisiLahan->kondisi_drainase) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- SEKSI 3: Kondisi Iklim --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-bold">3</span>
                Kondisi Iklim
            </h2>

            {{-- Tombol Ambil Data Cuaca Otomatis --}}
            <div class="mb-4 p-3 sm:p-4 rounded-xl" id="cuaca-auto-section" style="background:#e0f2fe; border:2px solid #7dd3fc;">
                <div class="flex items-start gap-2.5 mb-3">
                    <span class="text-xl flex-shrink-0">🌦️</span>
                    <div>
                        <p class="text-sm font-bold" style="color:#075985;">Data Cuaca Otomatis</p>
                        <p class="text-xs mt-0.5" style="color:#0369a1;" id="cuaca-auto-hint">Klik tombol biru di bawah untuk mengambil data cuaca terkini dari lokasi blok.</p>
                    </div>
                </div>
                {{-- Tombol — langsung terlihat di halaman edit karena blok sudah ada --}}
                <button type="button" id="btn-fetch-cuaca" onclick="fetchCuacaOtomatis()"
                    style="display:block; width:100%; min-height:52px; max-width:none!important; padding:14px 20px; background:#0284c7; color:#ffffff; font-size:15px; font-weight:700; border:2px solid #0369a1; border-radius:12px; cursor:pointer; box-shadow:0 4px 12px rgba(2,132,199,0.3); transition:all 0.15s; text-align:center;">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Musim Saat Ini</label>
                    <select name="musim_saat_ini" id="select-musim"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                        <option value="">— Pilih —</option>
                        @foreach(['Musim Hujan','Musim Kemarau','Peralihan'] as $opt)
                            <option value="{{ $opt }}" {{ old('musim_saat_ini', $kondisiLahan->musim_saat_ini) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Intensitas Curah Hujan</label>
                    <select name="curah_hujan_kategori" id="select-curah-hujan"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                        <option value="">— Pilih —</option>
                        @foreach(['Sangat Rendah','Rendah','Normal','Tinggi','Sangat Tinggi'] as $opt)
                            <option value="{{ $opt }}" {{ old('curah_hujan_kategori', $kondisiLahan->curah_hujan_kategori) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- SEKSI 4: Gejala Visual --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold">4</span>
                Gejala Visual Tanaman
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Warna Daun</label>
                    <select name="warna_daun"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                        <option value="">— Pilih —</option>
                        @foreach(['Hijau Normal','Hijau Pucat','Kuning Merata','Kuning Tepi','Kuning Antar Tulang','Oranye/Kemerahan','Coklat Ujung','Bercak Nekrotik'] as $opt)
                            <option value="{{ $opt }}" {{ old('warna_daun', $kondisiLahan->warna_daun) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kondisi Pelepah</label>
                    <select name="kondisi_pelepah"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                        <option value="">— Pilih —</option>
                        @foreach(['Normal','Patah/Menggantung','Kering Prematur','Pertumbuhan Terhambat'] as $opt)
                            <option value="{{ $opt }}" {{ old('kondisi_pelepah', $kondisiLahan->kondisi_pelepah) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kondisi Tandan / Buah</label>
                    <select name="kondisi_tandan" id="kondisi-tandan-select"
                        class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                        <option value="">— Pilih —</option>
                        @foreach(['Normal','Kecil','Rontok Prematur','Busuk Pangkal','Tidak Ada Tandan'] as $opt)
                            <option value="{{ $opt }}" {{ old('kondisi_tandan', $kondisiLahan->kondisi_tandan) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <p id="tandan-tbm-note" class="hidden mt-1 text-[10px] text-blue-600 font-medium">🌱 Terkunci — tanaman belum menghasilkan (belum berbuah)</p>
                </div>
            </div>

            {{-- Dugaan Unsur Hara yang Kurang (Fitur 8) --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Dugaan Unsur Hara yang Kurang
                    <span class="text-xs text-slate-400 font-normal">(opsional, boleh pilih lebih dari satu)</span>
                </label>
                @php $defAktif = old('gejala_defisiensi', $kondisiLahan->gejala_defisiensi ?? []); @endphp
                <div class="grid grid-cols-4 sm:grid-cols-7 gap-2" id="defisiensi-grid">
                    @foreach(['N','P','K','Mg','B','Fe','Zn'] as $def)
                    @php
                        $defLabel = match($def) {
                            'N'  => 'Nitrogen', 'P'  => 'Fosfor', 'K'  => 'Kalium',
                            'Mg' => 'Magnesium', 'B'  => 'Boron', 'Fe' => 'Besi', 'Zn' => 'Seng',
                            default => $def,
                        };
                        $checked = in_array($def, $defAktif);
                    @endphp
                    <label class="def-label flex flex-col items-center gap-1.5 p-2.5 border rounded-xl cursor-pointer transition-all {{ $checked ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500' : 'border-slate-200 hover:bg-emerald-50 hover:border-emerald-400' }}">
                        <input type="checkbox" name="gejala_defisiensi[]" value="{{ $def }}"
                            {{ $checked ? 'checked' : '' }}
                            class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <span class="text-base font-bold text-slate-800">{{ $def }}</span>
                        <span class="text-xs text-slate-400 text-center leading-tight">{{ $defLabel }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Toggle Kondisi Khusus --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @php
                    $hamaChecked = old('ada_serangan_hama', $kondisiLahan->ada_serangan_hama);
                    $gulmaChecked = old('ada_gulma_dominan', $kondisiLahan->ada_gulma_dominan);
                @endphp
                <label class="toggle-label flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition-all {{ $hamaChecked ? 'bg-red-50 border-red-400' : 'border-slate-200 hover:bg-red-50 hover:border-red-300' }}">
                    <input type="checkbox" name="ada_serangan_hama" value="1"
                        {{ $hamaChecked ? 'checked' : '' }}
                        class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500">
                    <div>
                        <span class="text-sm font-medium text-slate-800">Ada Serangan Hama / Penyakit</span>
                        <p class="text-xs text-slate-400 mt-0.5">Terlihat gejala serangan fisik atau bercak penyakit</p>
                    </div>
                </label>
                <label class="toggle-label flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition-all {{ $gulmaChecked ? 'bg-amber-50 border-amber-400' : 'border-slate-200 hover:bg-amber-50 hover:border-amber-300' }}">
                    <input type="checkbox" name="ada_gulma_dominan" value="1"
                        {{ $gulmaChecked ? 'checked' : '' }}
                        class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-medium text-slate-800">Ada Gulma Dominan</span>
                        <p class="text-xs text-slate-400 mt-0.5">Gulma menutupi piringan atau gawangan secara masif</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- SEKSI 5: Catatan --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-slate-800 mb-3 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-bold">5</span>
                Catatan Observasi
            </h2>
            <textarea name="catatan_observasi" rows="3"
                placeholder="Catatan tambahan dari petugas lapangan (opsional)..."
                class="w-full border border-slate-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors resize-none">{{ old('catatan_observasi', $kondisiLahan->catatan_observasi) }}</textarea>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 pb-2">
            <a href="{{ route('kondisi-lahan.index') }}"
               class="px-5 py-2.5 border border-slate-300 rounded-xl text-sm text-slate-700 hover:bg-slate-50 transition-colors font-medium">
                Batal
            </a>
            <button type="submit"
                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm shadow-emerald-600/20">
                Simpan Perubahan
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

// TBM: Lock kondisi tandan jika blok belum menghasilkan
(function() {
    var kategoriUmur = @json($kondisiLahan->blokLahan->kategori_umur);
    if (kategoriUmur === 'Belum Menghasilkan') {
        var bannerEl = document.getElementById('banner-tbm');
        var tandanSelect = document.getElementById('kondisi-tandan-select');
        var tandanNote = document.getElementById('tandan-tbm-note');

        if (bannerEl) bannerEl.classList.remove('hidden');
        if (tandanSelect) {
            tandanSelect.value = 'Tidak Ada Tandan';
            tandanSelect.disabled = true;
            tandanSelect.classList.add('opacity-50', 'cursor-not-allowed');
            // Hidden input agar nilai terkirim
            var hi = document.createElement('input');
            hi.type = 'hidden';
            hi.name = 'kondisi_tandan';
            hi.value = 'Tidak Ada Tandan';
            hi.id = 'tandan-hidden-tbm';
            tandanSelect.parentNode.appendChild(hi);
        }
        if (tandanNote) tandanNote.classList.remove('hidden');
    }
})();

// ─── CUACA OTOMATIS (Open-Meteo API) ─────────────────────────────
(function() {
    var bloksData = @json($bloksJson);
    var blokId = {{ $kondisiLahan->blok_lahan_id }};
    var blok = bloksData.find(function(b) { return b.id == blokId; });
    var btn = document.getElementById('btn-fetch-cuaca');
    var hint = document.getElementById('cuaca-auto-hint');

    if (blok && blok.centroid_lat && blok.centroid_lng) {
        btn.disabled = false;
        hint.textContent = 'Blok "' + blok.nama_blok + '" — klik untuk mengambil data cuaca terkini dari lokasi blok.';
    } else {
        btn.disabled = true;
        hint.textContent = 'Blok ini belum memiliki koordinat peta. Silakan input cuaca manual.';
    }

    window.fetchCuacaOtomatis = function() {
        if (!blok || !blok.centroid_lat || !blok.centroid_lng) return;

        var btnText = document.getElementById('btn-fetch-cuaca-text');

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
            var contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error('Response bukan JSON (kemungkinan session expired). Refresh halaman dan coba lagi.');
            }
            return response.json();
        })
        .then(function(data) {
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
            btnText.textContent = '🔄 Ambil Data Cuaca Otomatis';
            document.getElementById('cuaca-icon-normal').style.display = 'inline-block';
            document.getElementById('cuaca-icon-loading').style.display = 'none';

            if (data.success) {
                document.getElementById('cuaca-result').style.display = 'block';
                document.getElementById('cuaca-error').style.display = 'none';
                document.getElementById('cuaca-rata2').textContent = data.detail.rata_rata_harian_mm + ' mm/hari';
                document.getElementById('cuaca-total').textContent = (data.detail.total_curah_hujan_mm || data.detail.total_30_hari_mm) + ' mm';
                document.getElementById('cuaca-kategori').textContent = data.curah_hujan_kategori;
                document.getElementById('cuaca-musim').textContent = data.musim_saat_ini;
                document.getElementById('cuaca-periode').textContent = data.detail.analisis || '';

                // Auto-fill fields
                var musimEl = document.getElementById('select-musim');
                var curahEl = document.getElementById('select-curah-hujan');
                if (musimEl) musimEl.value = data.musim_saat_ini;
                if (curahEl) curahEl.value = data.curah_hujan_kategori;
            } else {
                document.getElementById('cuaca-error').style.display = 'block';
                document.getElementById('cuaca-result').style.display = 'none';
                document.getElementById('cuaca-error-msg').textContent = data.message || 'Gagal mengambil data cuaca.';
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
                msg = 'Request timeout (>30 detik). Koneksi terlalu lambat. Silakan isi form secara manual.';
            } else if (err.message) {
                msg = 'Gagal terhubung: ' + err.message + '. Silakan isi form cuaca secara manual.';
            }
            document.getElementById('cuaca-error-msg').textContent = msg;
        });
    };
})();

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
