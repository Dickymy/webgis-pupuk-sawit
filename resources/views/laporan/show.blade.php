@extends('layouts.app')

@section('title', 'Detail Laporan')
@section('page-title', 'Detail Laporan Rekomendasi')
@section('page-subtitle', $rekomendasiRbs->blokLahan->nama_blok . ' — ' . $rekomendasiRbs->tanggal_analisis->format('d F Y'))

@section('content')
<div class="space-y-5 max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <a href="{{ route('laporan.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('laporan.pdf', $rekomendasiRbs) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download PDF
            </a>
        </div>
    </div>

    {{-- Status Banner --}}
    @php $sc = match($rekomendasiRbs->status_kebutuhan_dominan) {
        'Darurat' => 'from-red-50 to-rose-50/30 border-red-200 text-red-950',
        'Segera'  => 'from-orange-50 to-amber-50/30 border-orange-200 text-orange-950',
        'Normal'  => 'from-emerald-50 to-green-50/30 border-emerald-200 text-emerald-950',
        'Tunda'   => 'from-slate-50 to-slate-100/50 border-slate-200 text-slate-900',
        default   => 'from-slate-50 to-slate-100/50 border-slate-200 text-slate-900'
    }; @endphp
    <div class="bg-gradient-to-r {{ $sc }} border rounded-2xl p-5 shadow-sm">
        <p class="text-xs text-slate-500 font-semibold tracking-wider uppercase">Rekomendasi Rule-Based System</p>
        <p class="text-xl font-extrabold mt-0.5">{{ \App\Models\RekomendasiRbs::labelStatus($rekomendasiRbs->status_kebutuhan_dominan) }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">
            {{ $rekomendasiRbs->blokLahan->nama_blok }} · {{ $rekomendasiRbs->blokLahan->nama_pemilik }}
            · {{ $rekomendasiRbs->tanggal_analisis->format('d F Y') }}
            · Oleh: <span class="font-semibold text-slate-700">{{ $rekomendasiRbs->admin->nama_lengkap }}</span>
        </p>
        {{-- Badges Validitas + Confidence --}}
        <div class="flex flex-wrap items-center gap-2 mt-3">
            @php
                $validitasColor = match($rekomendasiRbs->validitas_rekomendasi) {
                    'Cukup Kuat'    => 'bg-blue-100 text-blue-800',
                    'Terverifikasi' => 'bg-green-100 text-green-800',
                    default         => 'bg-amber-100 text-amber-800',
                };
                $confColor = match($rekomendasiRbs->confidence_label) {
                    'Tinggi' => 'bg-green-100 text-green-800',
                    'Sedang' => 'bg-blue-100 text-blue-800',
                    default  => 'bg-amber-100 text-amber-800',
                };
            @endphp
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $validitasColor }}">{{ $rekomendasiRbs->validitas_rekomendasi }}</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $confColor }}">Keandalan: {{ $rekomendasiRbs->confidence_label }} ({{ $rekomendasiRbs->confidence_score }}%)</span>
            @if(!$rekomendasiRbs->data_cukup)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-800">⚠ Data Belum Cukup</span>
            @endif
        </div>
    </div>

    {{-- Notifikasi Data (Fitur 7) --}}
    @if(!$rekomendasiRbs->data_cukup && $rekomendasiRbs->notifikasi_data)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
        <p class="font-semibold mb-1">⚠️ Data Observasi Belum Cukup</p>
        <p>{{ $rekomendasiRbs->notifikasi_data }}</p>
    </div>
    @endif

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-50 pb-2">Info Lahan</h3>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Luas</span><span class="text-slate-800 font-bold">{{ number_format($rekomendasiRbs->blokLahan->luas_ha, 2) }} Ha</span></div>
                <div class="flex justify-between"><span class="text-slate-500">SPH</span><span class="text-slate-800 font-medium">{{ number_format($rekomendasiRbs->blokLahan->sph) }} ph/Ha</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Total Pohon</span><span class="text-slate-900 font-bold">{{ number_format($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha) }}</span></div>
            </div>
        </div>

        @if($rekomendasiRbs->blokLahan->tahun_tanam)
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-50 pb-2">Kriteria Agronomis</h3>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Umur</span><span class="text-emerald-700 font-bold">{{ $rekomendasiRbs->blokLahan->umur_tanaman }} tahun</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Kategori</span><span class="text-slate-800 font-semibold">{{ $rekomendasiRbs->blokLahan->kategori_umur }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Jenis Tanah</span><span class="text-slate-800 font-medium text-xs">{{ $rekomendasiRbs->blokLahan->jenis_tanah }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Topografi</span><span class="text-slate-800 font-medium">{{ $rekomendasiRbs->blokLahan->topografi }}</span></div>
            </div>
        </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-50 pb-2">Kebutuhan Pupuk Standar</h3>
            @if($rekomendasiRbs->dosis_urea)
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-500 font-medium">Dosis Urea</p>
                    <p class="text-lg font-extrabold text-amber-700">{{ $rekomendasiRbs->dosis_urea }} <span class="text-[10px] font-semibold text-slate-500">kg/pk</span></p>
                </div>
                <div class="flex items-center justify-between border-t border-slate-50 pt-2">
                    <p class="text-xs text-slate-500 font-medium">Dosis KCl</p>
                    <p class="text-lg font-extrabold text-cyan-700">{{ $rekomendasiRbs->dosis_kcl }} <span class="text-[10px] font-semibold text-slate-500">kg/pk</span></p>
                </div>
            </div>
            @else
            <p class="text-sm text-slate-400">Data kriteria lahan belum tersedia untuk perhitungan dosis.</p>
            @endif
        </div>
    </div>

    {{-- Logistik --}}
    @if($rekomendasiRbs->urea_estimasi_kg_per_pokok_tahun !== null || $rekomendasiRbs->kcl_estimasi_kg_per_pokok_tahun !== null || $rekomendasiRbs->total_urea)
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <h3 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Kebutuhan Logistik Pupuk
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-amber-50/60 border border-amber-100/80 rounded-xl p-4 text-center shadow-sm">
                <p class="text-xs text-amber-800 font-semibold mb-1">Total Urea</p>
                <p class="text-2xl font-extrabold text-amber-700">{{ number_format($rekomendasiRbs->total_urea, 1) }}</p>
                <p class="text-[10px] text-slate-400 font-medium uppercase mt-0.5">kilogram</p>
            </div>
            <div class="bg-amber-50/60 border border-amber-100/80 rounded-xl p-4 text-center shadow-sm">
                <p class="text-xs text-amber-800 font-semibold mb-1">Karung Urea</p>
                <p class="text-2xl font-extrabold text-amber-800">{{ $rekomendasiRbs->karung_urea }}</p>
                <p class="text-[10px] text-slate-400 font-medium uppercase mt-0.5">karung</p>
            </div>
            <div class="bg-cyan-50/60 border border-cyan-100/80 rounded-xl p-4 text-center shadow-sm">
                <p class="text-xs text-cyan-800 font-semibold mb-1">Total KCl</p>
                <p class="text-2xl font-extrabold text-cyan-700">{{ number_format($rekomendasiRbs->total_kcl, 1) }}</p>
                <p class="text-[10px] text-slate-400 font-medium uppercase mt-0.5">kilogram</p>
            </div>
            <div class="bg-cyan-50/60 border border-cyan-100/80 rounded-xl p-4 text-center shadow-sm">
                <p class="text-xs text-cyan-800 font-semibold mb-1">Karung KCl</p>
                <p class="text-2xl font-extrabold text-cyan-700">{{ $rekomendasiRbs->karung_kcl }}</p>
                <p class="text-[10px] text-slate-400 font-medium uppercase mt-0.5">karung</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Catatan Dosis Kontekstual --}}
    @if($rekomendasiRbs->catatan_dosis)
    @php
        $catatanStyle = match($rekomendasiRbs->status_kebutuhan_dominan) {
            'Darurat' => 'bg-red-50 border-red-200 text-red-900',
            'Tunda'   => 'bg-amber-50 border-amber-200 text-amber-900',
            'Segera'  => 'bg-blue-50 border-blue-200 text-blue-900',
            default   => 'bg-emerald-50 border-emerald-200 text-emerald-900',
        };
    @endphp
    <div class="{{ $catatanStyle }} border rounded-2xl p-5 shadow-sm">
        <h3 class="text-sm font-bold mb-2 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Catatan Aplikasi Dosis
        </h3>
        <p class="text-sm leading-relaxed">{{ $rekomendasiRbs->catatan_dosis }}</p>
    </div>
    @endif

    {{-- Jadwal Pemupukan Per Tahap (Fitur 2) --}}
    @if($rekomendasiRbs->jadwal_pemupukan && count($rekomendasiRbs->jadwal_pemupukan) > 0)
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <h3 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center gap-2">
            📅 Jadwal Pemupukan Per Tahap
        </h3>
        {{-- Desktop Version: Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[10px] text-slate-500 uppercase tracking-wider">
                        <th class="px-3 py-2 text-left font-semibold">Tahap</th>
                        <th class="px-3 py-2 text-left font-semibold">Waktu Aplikasi</th>
                        <th class="px-3 py-2 text-left font-semibold">Jenis Pupuk</th>
                        <th class="px-3 py-2 text-right font-semibold">Dosis/Pokok</th>
                        <th class="px-3 py-2 text-right font-semibold">Total Blok</th>
                        <th class="px-3 py-2 text-left font-semibold">Cara Aplikasi & Petunjuk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @foreach($rekomendasiRbs->jadwal_pemupukan as $jadwal)
                    @php
                        $hasUrea = isset($jadwal['urea_kg']) && $jadwal['urea_kg'] > 0;
                        $hasKcl = isset($jadwal['kcl_kg']) && $jadwal['kcl_kg'] > 0;
                        
                        $isCombined = $hasUrea && $hasKcl;
                        
                        $jenisPupuk = '-';
                        $dosisPokok = '-';
                        $totalKg = '-';
                        $colorClass = 'text-slate-700';
                        $bgClass = '';
                        
                        $dosisU = 0;
                        $dosisK = 0;
                        if ($hasUrea) {
                            $dosisU = isset($jadwal['urea_per_pokok']) ? $jadwal['urea_per_pokok'] : ($jadwal['urea_kg'] / max(1, ($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha)));
                        }
                        if ($hasKcl) {
                            $dosisK = isset($jadwal['kcl_per_pokok']) ? $jadwal['kcl_per_pokok'] : ($jadwal['kcl_kg'] / max(1, ($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha)));
                        }
                        
                        if ($isCombined) {
                            $jenisPupuk = 'Urea & KCl';
                            $dosisPokok = 'Urea: ' . number_format($dosisU, 2) . ' kg | KCl: ' . number_format($dosisK, 2) . ' kg';
                            $totalKg = 'Urea: ' . number_format($jadwal['urea_kg'], 1) . ' kg | KCl: ' . number_format($jadwal['kcl_kg'], 1) . ' kg';
                            $colorClass = 'text-slate-800 font-semibold';
                            $bgClass = 'bg-slate-50';
                        } elseif ($hasUrea) {
                            $jenisPupuk = 'Urea (N)';
                            $dosisPokok = number_format($dosisU, 2) . ' kg';
                            $totalKg = number_format($jadwal['urea_kg'], 1) . ' kg';
                            $colorClass = 'text-amber-800 font-bold';
                            $bgClass = 'bg-amber-50/20';
                        } elseif ($hasKcl) {
                            $jenisPupuk = 'KCl (K)';
                            $dosisPokok = number_format($dosisK, 2) . ' kg';
                            $totalKg = number_format($jadwal['kcl_kg'], 1) . ' kg';
                            $colorClass = 'text-cyan-800 font-bold';
                            $bgClass = 'bg-cyan-50/20';
                        }
                    @endphp
                    <tr class="{{ $bgClass }}">
                        <td class="px-3 py-3 font-semibold text-slate-800">{{ $jadwal['nama_tahap'] }}</td>
                        <td class="px-3 py-3 text-slate-600 font-medium">{{ $jadwal['estimasi_waktu'] }}</td>
                        <td class="px-3 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $hasUrea && !$isCombined ? 'bg-amber-100 text-amber-800' : ($hasKcl && !$isCombined ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-800') }}">
                                {{ $jenisPupuk }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-right {{ $colorClass }}">{{ $dosisPokok }}</td>
                        <td class="px-3 py-3 text-right {{ $colorClass }}">{{ $totalKg }}</td>
                        <td class="px-3 py-3">
                            <div class="max-w-md">
                                <p class="leading-relaxed">{{ $jadwal['metode_aplikasi'] }}</p>
                                @if(!empty($jadwal['catatan']))
                                <p class="text-[10px] text-amber-600 font-medium italic mt-1">⚠️ {{ $jadwal['catatan'] }}</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Version: Vertical Timeline --}}
        <div class="block md:hidden">
            <div class="relative border-l-2 border-slate-100 ml-2.5 pl-0.5 space-y-6">
                @foreach($rekomendasiRbs->jadwal_pemupukan as $jadwal)
                @php
                    $hasUrea = isset($jadwal['urea_kg']) && $jadwal['urea_kg'] > 0;
                    $hasKcl = isset($jadwal['kcl_kg']) && $jadwal['kcl_kg'] > 0;
                    
                    $dosisU = 0;
                    $dosisK = 0;
                    if ($hasUrea) {
                        $dosisU = isset($jadwal['urea_per_pokok']) ? $jadwal['urea_per_pokok'] : ($jadwal['urea_kg'] / max(1, ($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha)));
                    }
                    if ($hasKcl) {
                        $dosisK = isset($jadwal['kcl_per_pokok']) ? $jadwal['kcl_per_pokok'] : ($jadwal['kcl_kg'] / max(1, ($rekomendasiRbs->blokLahan->sph * $rekomendasiRbs->blokLahan->luas_ha)));
                    }
                @endphp
                <div class="relative pb-2 pl-6">
                    <!-- Bullet point -->
                    <div class="absolute top-1.5 w-4 h-4 rounded-full border-2 border-emerald-500 bg-white flex items-center justify-center" style="left: -9px;">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                    </div>
                    
                    <div class="flex flex-col gap-1 mb-2">
                        <h4 class="text-xs font-extrabold text-slate-800 leading-snug">
                            {{ $jadwal['nama_tahap'] }}
                        </h4>
                        <span class="inline-flex self-start text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 font-bold">
                            🗓️ {{ $jadwal['estimasi_waktu'] }}
                        </span>
                    </div>

                    <!-- Info Dosis Card -->
                    @if($hasUrea || $hasKcl)
                    <div class="space-y-2 mb-2.5">
                        @if($hasUrea)
                        <div class="bg-amber-50/60 border border-amber-100 rounded-xl p-3 flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700 font-bold text-[10px] flex-shrink-0">
                                N
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] text-amber-700 font-semibold uppercase leading-none">Urea</p>
                                <p class="text-xs text-slate-700 font-medium mt-1">
                                    Dosis: <span class="font-extrabold text-slate-900">{{ number_format($dosisU, 2) }}</span> kg/pokok
                                </p>
                                <p class="text-[10px] text-slate-500 mt-0.5">
                                    Total Blok: {{ number_format($jadwal['urea_kg'], 1) }} kg (±{{ ceil($jadwal['urea_kg'] / 50) }} Karung)
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($hasKcl)
                        <div class="bg-cyan-50/60 border border-cyan-100 rounded-xl p-3 flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-cyan-100 flex items-center justify-center text-cyan-700 font-bold text-[10px] flex-shrink-0">
                                K
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] text-cyan-700 font-semibold uppercase leading-none">KCl</p>
                                <p class="text-xs text-slate-700 font-medium mt-1">
                                    Dosis: <span class="font-extrabold text-slate-900">{{ number_format($dosisK, 2) }}</span> kg/pokok
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
                            <p class="text-[10px] text-slate-400 font-semibold uppercase leading-none">Cara Penaburan</p>
                            <p class="text-xs text-slate-700 mt-1 leading-relaxed">{{ $jadwal['metode_aplikasi'] }}</p>
                        </div>
                        @if(!empty($jadwal['catatan']))
                        <div class="pt-2 border-t border-slate-200/60">
                            <p class="text-[10px] text-amber-600 font-semibold uppercase leading-none">⚠️ Petunjuk Penting</p>
                            <p class="text-xs text-slate-600 mt-1 italic leading-relaxed">{{ $jadwal['catatan'] }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Masalah & Rekomendasi --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <h3 class="text-sm font-extrabold text-slate-800 mb-4">Masalah Teridentifikasi & Rekomendasi</h3>

        @if($rekomendasiRbs->masalah_teridentifikasi)
        <div class="mb-4">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Masalah</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($rekomendasiRbs->masalah_teridentifikasi as $masalah)
                <span class="inline-flex items-center px-2.5 py-1 bg-slate-50 border border-slate-200 text-slate-700 text-xs rounded-full">{{ $masalah }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if($rekomendasiRbs->rekomendasi_pupuk)
        <div class="mb-4">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Rekomendasi Pupuk Spesifik</p>
            <div class="space-y-2">
                @foreach($rekomendasiRbs->rekomendasi_pupuk as $pupuk)
                <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-3">
                    <p class="font-semibold text-emerald-700 text-sm">🌿 {{ $pupuk['jenis_utama'] ?? '' }}</p>
                    @if(!empty($pupuk['dosis']))<p class="text-xs text-slate-600 mt-1"><strong>Dosis:</strong> {{ $pupuk['dosis'] }}</p>@endif
                    @if(!empty($pupuk['metode']))<p class="text-xs text-slate-600"><strong>Metode:</strong> {{ $pupuk['metode'] }}</p>@endif
                    @if(!empty($pupuk['waktu']))<p class="text-xs text-slate-500"><strong>Waktu:</strong> {{ $pupuk['waktu'] }}</p>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($rekomendasiRbs->saran_tindakan_utama)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
            <p class="text-xs font-semibold text-amber-800 uppercase tracking-wider mb-1">Saran Tindakan</p>
            <p class="text-sm text-amber-900 leading-relaxed">{{ $rekomendasiRbs->saran_tindakan_utama }}</p>
        </div>
        @endif
    </div>

    {{-- Info Analisis --}}
    <div class="text-xs text-slate-400 text-right">
        {{ $rekomendasiRbs->jumlah_rule_terpicu }} rule terpicu · Dianalisis {{ $rekomendasiRbs->tanggal_analisis->diffForHumans() }}
    </div>

    {{-- Button Kembali di bawah --}}
    <div class="pt-2 pb-4">
        <a href="{{ route('laporan.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Laporan
        </a>
    </div>
</div>
@endsection
