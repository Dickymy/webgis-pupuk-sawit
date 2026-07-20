@extends('layouts.app')

@section('title', 'Detail Analisis — ' . $blokLahan->nama_blok)
@section('page-title', 'Detail Analisis')
@section('page-subtitle', $blokLahan->nama_blok . ' · ' . $blokLahan->nama_pemilik)

@section('content')

<div class="mb-4">
    <a href="{{ route('rbs.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali
    </a>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SECTION 1: Ringkasan Blok (compact) --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-5 mb-5">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 text-center">
        <div>
            <p class="text-[10px] text-slate-400 uppercase font-semibold">Luas</p>
            <p class="text-sm font-bold text-slate-800">{{ $blokLahan->luas_ha }} Ha</p>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 uppercase font-semibold">SPH</p>
            <p class="text-sm font-bold text-slate-800">{{ $blokLahan->sph }} pokok/Ha</p>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 uppercase font-semibold">Umur</p>
            <p class="text-sm font-bold text-slate-800">{{ $blokLahan->umur_tanaman ?? '—' }} tahun</p>
            @if($blokLahan->fase_tanaman)
            <p class="text-[9px] text-emerald-600 font-medium">{{ $blokLahan->fase_tanaman === 'TBM' ? '🌱' : '🌴' }} {{ $blokLahan->fase_label }}</p>
            @elseif($blokLahan->kategori_umur)
            <p class="text-[9px] text-emerald-600 font-medium">{{ $blokLahan->kategori_umur }}</p>
            @endif
        </div>
        <div>
            <p class="text-[10px] text-slate-400 uppercase font-semibold">Jenis Tanah</p>
            <p class="text-xs font-bold text-slate-800 leading-tight">{{ $blokLahan->jenis_tanah ?? '—' }}</p>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SECTION 2: Hasil Analisis RBS (komponen utama) --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@include('rbs.partials._hasil_rbs', ['blokLahan' => $blokLahan])

@if($rbs = $blokLahan->rekomendasiRbsTerbaru)

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SECTION 3: Kebutuhan Pupuk (angka besar, mudah dipahami) --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if($rbs->urea_estimasi_kg_per_pokok_tahun !== null || $rbs->kcl_estimasi_kg_per_pokok_tahun !== null || $rbs->total_urea || $rbs->total_kcl)
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-5 mt-5">
    <h3 class="text-sm font-bold text-slate-800 mb-3">🧮 Kebutuhan Pupuk Tahunan</h3>

    {{-- Rentang Referensi Pahan --}}
    @if($rbs->urea_min_kg_per_pokok_tahun)
    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-xl">
        <p class="text-[10px] text-blue-600 uppercase font-bold mb-2">📖 Rentang Referensi (Pahan, 2013 — Tabel 9.13 & 9.14)</p>
        <div class="grid grid-cols-2 gap-3 text-xs">
            <div>
                <span class="text-slate-500">Urea:</span>
                <span class="font-bold text-slate-800">{{ number_format($rbs->urea_min_kg_per_pokok_tahun, 2) }}–{{ number_format($rbs->urea_max_kg_per_pokok_tahun, 2) }}</span>
                <span class="text-slate-400">kg/pokok/tahun</span>
            </div>
            <div>
                <span class="text-slate-500">KCl:</span>
                <span class="font-bold text-slate-800">{{ number_format($rbs->kcl_min_kg_per_pokok_tahun, 2) }}–{{ number_format($rbs->kcl_max_kg_per_pokok_tahun, 2) }}</span>
                <span class="text-slate-400">kg/pokok/tahun</span>
            </div>
        </div>
        @if($rbs->fase_tanaman_snapshot)
        <p class="text-[10px] text-blue-500 mt-1.5">Fase: {{ $rbs->label_fase }} · Umur saat observasi: {{ $rbs->umur_tanaman_snapshot }} tahun · Strategi: {{ $rbs->strategi_estimasi_dosis ?? 'midpoint' }}</p>
        @endif
    </div>
    @endif

    {{-- Estimasi Dosis Kerja --}}
    @php
        $jumlahPokokRbs = $rbs->jumlah_pokok_snapshot ?? 0;
        $ureaEstTahunanRbs = $rbs->urea_estimasi_kg_per_pokok_tahun ? round($rbs->urea_estimasi_kg_per_pokok_tahun * $jumlahPokokRbs, 1) : null;
        $kclEstTahunanRbs = $rbs->kcl_estimasi_kg_per_pokok_tahun ? round($rbs->kcl_estimasi_kg_per_pokok_tahun * $jumlahPokokRbs, 1) : null;
        $karungUreaRbs = $ureaEstTahunanRbs ? (int) ceil($ureaEstTahunanRbs / 50) : 0;
        $karungKclRbs = $kclEstTahunanRbs ? (int) ceil($kclEstTahunanRbs / 50) : 0;
        $isDitundaRbs = $rbs->status_kelayakan_aplikasi && !in_array($rbs->status_kelayakan_aplikasi, ['LAYAK_DIJADWALKAN', 'TERLAMBAT_PERLU_DIJADWALKAN']);
    @endphp
    <p class="text-[10px] text-slate-400 uppercase font-bold mb-2">Estimasi Dosis Kerja Sistem</p>
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-center">
            <p class="text-[10px] text-amber-600 uppercase font-semibold mb-1">Urea</p>
            <p class="text-xl sm:text-2xl font-extrabold text-amber-800">{{ $ureaEstTahunanRbs ? number_format($ureaEstTahunanRbs, 0) : ($rbs->total_urea ? number_format($rbs->total_urea, 0) : '—') }}</p>
            <p class="text-xs text-amber-600">kg ({{ $karungUreaRbs ?: $rbs->karung_urea }} karung)</p>
            @if($rbs->urea_estimasi_kg_per_pokok_tahun)
            <p class="text-[9px] text-amber-500 mt-1">{{ $rbs->urea_estimasi_kg_per_pokok_tahun }} kg/pokok/tahun</p>
            @elseif($rbs->dosis_urea)
            <p class="text-[9px] text-amber-500 mt-1">{{ $rbs->dosis_urea }} kg/pokok</p>
            @endif
        </div>
        <div class="bg-cyan-50 border border-cyan-200 rounded-xl p-3 text-center">
            <p class="text-[10px] text-cyan-600 uppercase font-semibold mb-1">KCl</p>
            <p class="text-xl sm:text-2xl font-extrabold text-cyan-800">{{ $kclEstTahunanRbs ? number_format($kclEstTahunanRbs, 0) : ($rbs->total_kcl ? number_format($rbs->total_kcl, 0) : '—') }}</p>
            <p class="text-xs text-cyan-600">kg ({{ $karungKclRbs ?: $rbs->karung_kcl }} karung)</p>
            @if($rbs->kcl_estimasi_kg_per_pokok_tahun)
            <p class="text-[9px] text-cyan-500 mt-1">{{ $rbs->kcl_estimasi_kg_per_pokok_tahun }} kg/pokok/tahun</p>
            @elseif($rbs->dosis_kcl)
            <p class="text-[9px] text-cyan-500 mt-1">{{ $rbs->dosis_kcl }} kg/pokok</p>
            @endif
        </div>
    </div>

    @if($isDitundaRbs)
    <div class="mt-2 bg-amber-50/80 border border-amber-200 rounded-lg p-2">
        <p class="text-[10px] text-amber-700 font-medium">⏸️ Aplikasi saat ini: <strong>0 kg</strong> — {{ \App\Enums\ApplicationFeasibilityStatus::labelFromValue($rbs->status_kelayakan_aplikasi) }}. Kebutuhan tahunan tetap tercatat di atas.</p>
    </div>
    @elseif(($rbs->urea_aplikasi_saat_ini ?? 0) > 0 || ($rbs->kcl_aplikasi_saat_ini ?? 0) > 0)
    <div class="mt-2 bg-emerald-50/80 border border-emerald-200 rounded-lg p-2">
        <p class="text-[10px] text-emerald-800 font-bold">✅ Tahap Aktif{{ $rbs->active_stage ? ' (Tahap ' . $rbs->active_stage . ')' : '' }}:</p>
        <p class="text-xs text-emerald-700 font-semibold mt-0.5">Urea: {{ number_format($rbs->urea_aplikasi_saat_ini, 1) }} kg · KCl: {{ number_format($rbs->kcl_aplikasi_saat_ini, 1) }} kg</p>
        @if($rbs->alasan_tahap)
        <p class="text-[10px] text-emerald-600 mt-0.5">{{ $rbs->alasan_tahap }}</p>
        @endif
    </div>
    @endif

    {{-- Kelayakan Aplikasi --}}
    @if($rbs->status_kelayakan_aplikasi && $rbs->status_kelayakan_aplikasi !== 'LAYAK_DIJADWALKAN')
    <div class="mt-3 bg-amber-50/60 border border-amber-200 rounded-xl p-3 text-xs text-amber-800">
        <p class="font-bold">⏸️ Status Aplikasi: {{ \App\Services\FertilizationWindowService::labelStatus($rbs->status_kelayakan_aplikasi) }}</p>
        @if($rbs->alasan_kelayakan)
        <p class="mt-1 text-[11px] text-amber-700">{{ $rbs->alasan_kelayakan }}</p>
        @endif
        <p class="mt-1 text-[10px] text-amber-600 italic">Kebutuhan tahunan tetap tercatat. Dosis akan diaplikasikan setelah kondisi memenuhi syarat.</p>
    </div>
    @endif

    {{-- Disclaimer --}}
    <div class="mt-3 p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
        <p class="text-[10px] text-slate-500 leading-relaxed">
            ⚠️ <strong>Disclaimer:</strong> Rekomendasi ini merupakan estimasi awal berbasis data blok, observasi visual, dan basis aturan. Hasil ini bukan pengganti analisis laboratorium tanah/daun atau keputusan ahli agronomi. Perhitungan kuantitatif dibatasi pada Urea dan MOP/KCl.
        </p>
    </div>
</div>
@else
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-5 mt-5">
    <h3 class="text-sm font-bold text-slate-800 mb-2">🧮 Kebutuhan Pupuk Kimia</h3>
    <div class="bg-amber-50/60 border border-amber-200 rounded-xl p-3.5 flex items-start gap-3">
        <span class="text-xl">⏸️</span>
        <div>
            <p class="text-xs font-bold text-amber-800">Aplikasi Pupuk Kimia Ditunda</p>
            <p class="text-[11px] text-amber-700 mt-0.5 leading-relaxed">
                @if($rbs->status_kebutuhan_dominan === 'Darurat')
                    Status <strong>Defisiensi Berat / Darurat</strong> terdeteksi pada lahan ini. Pemupukan Urea &amp; KCl ditangguhkan sementara. Sangat disarankan untuk memprioritaskan tindakan koreksi (seperti pengapuran dengan Dolomit) sesuai petunjuk jadwal di bawah sebelum mengaplikasikan pupuk kimia utama.
                @else
                    @php
                        $masalahStr = implode(' ', $rbs->masalah_teridentifikasi ?? []);
                        $pesanTunda = 'Kondisi pembatas lahan saat ini (genangan air atau kekeringan ekstrem) tidak ideal untuk pemupukan. Pemupukan kimia standar ditunda sementara waktu guna mencegah pemborosan pupuk akibat pencucian (leaching) atau penguapan (volatilisasi).';
                        if (str_contains($masalahStr, 'Waterlogging') || str_contains($masalahStr, 'tergenang') || str_contains($masalahStr, 'drainase') || str_contains($masalahStr, 'Drainase')) {
                            $pesanTunda = '<strong>Lahan Tergenang (Waterlogging)</strong>: Pemupukan tanah ditunda sementara. Air tergenang akan mencuci bersih pupuk (leaching) dan membuat akar kelapa sawit kekurangan oksigen untuk menyerap hara secara efektif. Disarankan untuk memprioritaskan perbaikan parit drainase terlebih dahulu.';
                        } elseif (str_contains($masalahStr, 'Kekeringan') || str_contains($masalahStr, 'kering') || str_contains($masalahStr, 'Kemarau')) {
                            $pesanTunda = '<strong>Cekaman Kekeringan</strong>: Pemupukan ditunda sementara. Tanah yang terlalu kering membuat pupuk tidak dapat larut untuk diserap akar, serta berisiko membakar akar rambut kelapa sawit. Disarankan fokus pada pemberian mulsing organik (seperti janjang kosong) untuk menjaga kelembaban dan menunggu hingga curah hujan cukup.';
                        } elseif (str_contains($masalahStr, 'Tua Renta') || str_contains($masalahStr, 'Tua')) {
                            $pesanTunda = '<strong>Tanaman Tua Renta</strong>: Pemupukan standar ditangguhkan untuk analisis ekonomi. Pohon berusia di atas 25 tahun memiliki efisiensi penyerapan hara yang sangat rendah. Disarankan mengevaluasi kelayakan replanting (peremajaan lahan) dibandingkan biaya pemeliharaan pupuk.';
                        }
                    @endphp
                    {!! $pesanTunda !!}
                @endif
            </p>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SECTION 4: Info Tambahan (Validitas + Confidence) --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-5 mt-5">
    <h3 class="text-sm font-bold text-slate-800 mb-3">📊 Tingkat Kelengkapan & Keandalan Data</h3>
    <div class="flex flex-wrap items-center gap-2 mb-3">
        {{-- Confidence --}}
        @php
            $confColor = match($rbs->confidence_label) {
                'Tinggi' => 'bg-green-100 text-green-800 border-green-200',
                'Sedang' => 'bg-blue-100 text-blue-800 border-blue-200',
                default  => 'bg-amber-100 text-amber-800 border-amber-200',
            };
            $validitasColor = match($rbs->validitas_rekomendasi) {
                'Cukup Kuat'    => 'bg-blue-100 text-blue-800 border-blue-200',
                'Terverifikasi' => 'bg-green-100 text-green-800 border-green-200',
                default         => 'bg-amber-100 text-amber-800 border-amber-200',
            };
        @endphp
        <span class="inline-flex items-center gap-1 px-2.5 py-1 border rounded-full text-xs font-semibold {{ $confColor }}">
            Keandalan: {{ $rbs->confidence_label }} ({{ $rbs->confidence_score }}%)
        </span>
        <span class="inline-flex items-center gap-1 px-2.5 py-1 border rounded-full text-xs font-semibold {{ $validitasColor }}">
            {{ $rbs->validitas_rekomendasi }}
        </span>
        @if($rbs->data_cukup)
        <span class="inline-flex items-center gap-1 px-2.5 py-1 border rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border-emerald-200">✓ Data Cukup</span>
        @else
        <span class="inline-flex items-center gap-1 px-2.5 py-1 border rounded-full text-xs font-semibold bg-red-50 text-red-700 border-red-200">⚠️ Data Belum Lengkap</span>
        @endif
    </div>
    @if($rbs->catatan_confidence)
    <p class="text-xs text-slate-500 italic">{{ $rbs->catatan_confidence }}</p>
    @endif
    @if(!$rbs->data_cukup && $rbs->notifikasi_data)
    <div class="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-xs text-amber-800">
        {{ $rbs->notifikasi_data }}
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SECTION 5: Jadwal Pemupukan --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if($rbs->jadwal_pemupukan && count($rbs->jadwal_pemupukan) > 0)
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-5">
    <div class="px-4 sm:px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-bold text-slate-800">📅 Jadwal Pemupukan Tahap demi Tahap</h3>
        <p class="text-xs text-slate-400 mt-0.5">Rencana aplikasi dosis per pokok dan total kebutuhan blok</p>
    </div>
    <div class="px-4 sm:px-5 py-5">
        @if($blokLahan->kondisiTerbaru?->ada_gulma_dominan || $blokLahan->kondisiTerbaru?->ada_serangan_hama)
        <div class="mb-5 bg-amber-50/70 border border-amber-200 rounded-xl p-3.5 text-xs text-amber-800 flex items-start gap-2.5">
            <span class="text-base flex-shrink-0">⚠️</span>
            <div>
                <p class="font-bold">Perlu Tindakan Sebelum Pemupukan Kimia:</p>
                <ul class="list-disc pl-4 mt-1.5 space-y-1 text-[11px] leading-relaxed text-amber-700">
                    @if($blokLahan->kondisiTerbaru?->ada_gulma_dominan)
                    <li><strong>Pembersihan Gulma</strong>: Lakukan pengendalian gulma (penyiangan piringan / ring weeding) secara manual atau menggunakan herbisida sebelum melakukan pemupukan. Gulma yang dominan akan merebut unsur hara yang ditaburkan sehingga pemupukan menjadi tidak efektif.</li>
                    @endif
                    @if($blokLahan->kondisiTerbaru?->ada_serangan_hama)
                    <li><strong>Pengendalian Hama &amp; Penyakit</strong>: Tangani serangan hama aktif dengan insektisida/fungisida yang tepat terlebih dahulu. Pemupukan kimia tanah hanya boleh diaplikasikan jika serangan hama telah terkendali agar tanaman dapat fokus pada pemulihan vegetatif.</li>
                    @endif
                </ul>
            </div>
        </div>
        @endif
        
        <div class="relative border-l-2 border-slate-100 ml-3 space-y-6">
            @foreach($rbs->jadwal_pemupukan as $index => $jadwal)
            <div class="relative pb-2" style="padding-left: 32px;">
                <!-- Bullet point -->
                <div class="absolute top-1.5 w-4 h-4 rounded-full border-2 border-emerald-500 bg-white flex items-center justify-center" style="left: -9px;">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                </div>
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                    <h4 class="text-xs font-bold text-slate-800 flex items-center gap-2">
                        {{ $jadwal['nama_tahap'] }}
                    </h4>
                    <span class="inline-flex self-start sm:self-auto text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 font-medium">
                        🗓️ {{ $jadwal['estimasi_waktu'] }}
                    </span>
                </div>

                <!-- Info Dosis Card -->
                @if((isset($jadwal['urea_kg']) && $jadwal['urea_kg'] > 0) || (isset($jadwal['kcl_kg']) && $jadwal['kcl_kg'] > 0))
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2 mb-2.5">
                    @if(isset($jadwal['urea_kg']) && $jadwal['urea_kg'] > 0)
                    <div class="bg-amber-50/60 border border-amber-100 rounded-xl p-3 flex items-start gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700 font-bold text-[10px] flex-shrink-0">
                            N
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-amber-700 font-semibold uppercase">Urea</p>
                            <p class="text-xs text-slate-700 font-medium mt-0.5">
                                Dosis: <span class="font-bold text-slate-900">{{ isset($jadwal['urea_per_pokok']) ? number_format($jadwal['urea_per_pokok'], 2) : number_format($jadwal['urea_kg'] / max(1, ($blokLahan->sph * $blokLahan->luas_ha)), 2) }}</span> kg/pokok
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                Total Blok: {{ number_format($jadwal['urea_kg'], 1) }} kg (±{{ ceil($jadwal['urea_kg'] / 50) }} Karung)
                            </p>
                        </div>
                    </div>
                    @endif

                    @if(isset($jadwal['kcl_kg']) && $jadwal['kcl_kg'] > 0)
                    <div class="bg-cyan-50/60 border border-cyan-100 rounded-xl p-3 flex items-start gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-cyan-100 flex items-center justify-center text-cyan-700 font-bold text-[10px] flex-shrink-0">
                            K
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-cyan-700 font-semibold uppercase">KCl</p>
                            <p class="text-xs text-slate-700 font-medium mt-0.5">
                                Dosis: <span class="font-bold text-slate-900">{{ isset($jadwal['kcl_per_pokok']) ? number_format($jadwal['kcl_per_pokok'], 2) : number_format($jadwal['kcl_kg'] / max(1, ($blokLahan->sph * $blokLahan->luas_ha)), 2) }}</span> kg/pokok
                            </p>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                Total Blok: {{ number_format($jadwal['kcl_kg'], 1) }} kg (±{{ ceil($jadwal['kcl_kg'] / 50) }} Karung)
                            </p>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Cara & Catatan -->
                <div class="bg-slate-50 rounded-xl p-3 space-y-2 border border-slate-100">
                    <div>
                        <p class="text-[10px] text-slate-400 font-semibold uppercase">Cara Penaburan</p>
                        <p class="text-xs text-slate-700 mt-0.5 leading-relaxed">{{ $jadwal['metode_aplikasi'] }}</p>
                    </div>
                    @if(!empty($jadwal['catatan']))
                    <div class="pt-2 border-t border-slate-200/60">
                        <p class="text-[10px] text-amber-600 font-semibold uppercase">⚠️ Petunjuk Penting</p>
                        <p class="text-xs text-slate-600 mt-0.5 italic leading-relaxed">{{ $jadwal['catatan'] }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SECTION 6: Kondisi Observasi (collapsible) --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if($kondisi = $blokLahan->kondisiTerbaru)
<details class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-5 group">
    <summary class="px-4 sm:px-5 py-4 cursor-pointer hover:bg-slate-50 transition-colors flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-slate-800">🔍 Data Kondisi Observasi</h3>
            <span class="text-xs text-slate-400">{{ $kondisi->tanggal_observasi->format('d M Y') }}</span>
        </div>
        <svg class="w-4 h-4 text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </summary>
    <div class="px-4 sm:px-5 pb-4 border-t border-slate-100 pt-3">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
            @if($kondisi->warna_daun)
            <div><span class="text-slate-400 block">Warna Daun</span><span class="font-semibold text-slate-800">{{ $kondisi->warna_daun }}</span></div>
            @endif
            @if($kondisi->ph_tanah)
            <div><span class="text-slate-400 block">pH Tanah</span><span class="font-semibold text-slate-800">{{ $kondisi->ph_tanah }} ({{ $kondisi->label_ph }})</span></div>
            @endif
            @if($kondisi->kelembaban_tanah)
            <div><span class="text-slate-400 block">Kelembaban</span><span class="font-semibold text-slate-800">{{ $kondisi->kelembaban_tanah }}</span></div>
            @endif
            @if($kondisi->musim_saat_ini)
            <div><span class="text-slate-400 block">Musim</span><span class="font-semibold text-slate-800">{{ $kondisi->musim_saat_ini }}</span></div>
            @endif
            @if($kondisi->curah_hujan_kategori)
            <div><span class="text-slate-400 block">Curah Hujan</span><span class="font-semibold text-slate-800">{{ $kondisi->curah_hujan_kategori }}</span></div>
            @endif
            @if($kondisi->kondisi_drainase)
            <div><span class="text-slate-400 block">Drainase</span><span class="font-semibold text-slate-800">{{ $kondisi->kondisi_drainase }}</span></div>
            @endif
            @if($kondisi->kondisi_pelepah)
            <div><span class="text-slate-400 block">Pelepah</span><span class="font-semibold text-slate-800">{{ $kondisi->kondisi_pelepah }}</span></div>
            @endif
            @if($kondisi->kondisi_tandan)
            <div><span class="text-slate-400 block">Tandan</span><span class="font-semibold text-slate-800">{{ $kondisi->kondisi_tandan }}</span></div>
            @endif
            @if($kondisi->ada_serangan_hama)
            <div><span class="text-slate-400 block">Hama</span><span class="font-semibold text-red-600">🐛 Ada</span></div>
            @endif
            @if($kondisi->ada_gulma_dominan)
            <div><span class="text-slate-400 block">Gulma</span><span class="font-semibold text-amber-600">🌿 Ada</span></div>
            @endif
        </div>
        @if(!empty($kondisi->gejala_defisiensi))
        <div class="mt-3 pt-3 border-t border-slate-100">
            <span class="text-slate-400 text-xs block mb-1">Dugaan Defisiensi:</span>
            <div class="flex flex-wrap gap-1">
                @foreach($kondisi->gejala_defisiensi as $def)
                <span class="px-1.5 py-0.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded font-bold">{{ $def }}</span>
                @endforeach
            </div>
        </div>
        @endif
        <div class="mt-3 pt-3 border-t border-slate-100 flex gap-3">
            <a href="{{ route('kondisi-lahan.edit', $kondisi) }}" class="text-xs text-blue-600 hover:underline font-medium">Edit kondisi →</a>
            <a href="{{ route('kondisi-lahan.create', ['blok_lahan_id' => $blokLahan->id]) }}" class="text-xs text-emerald-600 hover:underline font-medium">+ Observasi baru</a>
        </div>
    </div>
</details>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SECTION 7: Detail Rules Terpicu (collapsible) --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if($rbs->jumlah_rule_terpicu > 0)
<details class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-5 group">
    <summary class="px-4 sm:px-5 py-4 cursor-pointer hover:bg-slate-50 transition-colors flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-slate-800">⚙️ Rules yang Terpicu</h3>
            <span class="text-xs text-slate-400">({{ $rbs->jumlah_rule_terpicu }} aturan)</span>
        </div>
        <svg class="w-4 h-4 text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </summary>
    <div class="border-t border-slate-100 divide-y divide-slate-50">
        @foreach($rbs->rules_terpicu as $i => $rule)
        @php
            $ruleColor = match($rule['status']) {
                'Darurat' => 'bg-red-50 text-red-700',
                'Segera'  => 'bg-orange-50 text-orange-700',
                'Normal'  => 'bg-emerald-50 text-emerald-700',
                default   => 'bg-slate-100 text-slate-600',
            };
        @endphp
        <div class="px-4 sm:px-5 py-3 flex items-start gap-3">
            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-bold flex items-center justify-center mt-0.5">{{ $i + 1 }}</span>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-slate-800">{{ $rule['indikasi'] }}</p>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-semibold {{ $ruleColor }}">{{ \App\Models\RekomendasiRbs::labelStatus($rule['status']) }}</span>
                    <span class="text-[10px] text-slate-400">Pupuk: {{ $rule['pupuk'] }}</span>
                    <span class="text-[10px] text-slate-400">Prioritas: {{ $rule['prioritas'] }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</details>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SECTION 8: Aksi --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="flex items-center gap-3 flex-wrap mt-5">
    @if($blokLahan->kondisiTerbaru)
    <form action="{{ route('rbs.analisis', $blokLahan) }}" method="POST">
        @csrf
        <button type="submit"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Jalankan Ulang Analisis
        </button>
    </form>
    @endif
    <a href="{{ route('laporan.show', $rbs) }}" class="inline-flex items-center gap-2 px-4 py-2.5 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">
        📄 Lihat Laporan
    </a>
    <a href="{{ route('laporan.pdf', $rbs) }}" class="inline-flex items-center gap-2 px-4 py-2.5 border border-red-200 text-red-600 text-sm font-medium rounded-xl hover:bg-red-50 transition-colors">
        📥 Download PDF
    </a>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SECTION 9: Histori (hanya tampil jika ada perubahan nyata) --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if(isset($historiRekomendasi) && $historiRekomendasi->count() > 0)
<details class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mt-5 group">
    <summary class="px-4 sm:px-5 py-4 cursor-pointer hover:bg-slate-50 transition-colors flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-slate-800">📜 Histori Analisis Sebelumnya</h3>
            <span class="text-xs text-slate-400">({{ $historiRekomendasi->count() }})</span>
        </div>
        <svg class="w-4 h-4 text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </summary>
    <div class="border-t border-slate-100 divide-y divide-slate-50">
        @foreach($historiRekomendasi as $hist)
        @php
            $hColor = match($hist->status_kebutuhan_dominan) {
                'Darurat' => 'bg-red-50 text-red-700',
                'Segera'  => 'bg-orange-50 text-orange-700',
                'Normal'  => 'bg-emerald-50 text-emerald-700',
                default   => 'bg-slate-100 text-slate-600',
            };
        @endphp
        <div class="px-4 sm:px-5 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="text-xs text-slate-400 font-mono flex-shrink-0">#{{ $hist->nomor_analisis }}</span>
                <div class="min-w-0">
                    <p class="text-xs text-slate-700">{{ $hist->tanggal_analisis->format('d/m/Y') }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-semibold {{ $hColor }}">{{ \App\Models\RekomendasiRbs::labelStatus($hist->status_kebutuhan_dominan) }}</span>
                        <span class="text-[10px] text-slate-400">{{ $hist->jumlah_rule_terpicu }} rule · {{ $hist->confidence_score ?? 0 }}%</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('laporan.show', $hist) }}" class="text-[10px] text-blue-600 hover:underline font-medium flex-shrink-0">Detail</a>
        </div>
        @endforeach
    </div>
</details>
@endif

@endif {{-- end if $rbs --}}

@endsection
