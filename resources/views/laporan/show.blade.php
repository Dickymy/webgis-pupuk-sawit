@extends('layouts.app')

@section('title', 'Detail Laporan')
@section('page-title', 'Detail Laporan Rekomendasi')
@section('page-subtitle', $rekomendasiRbs->blokLahan->nama_blok . ' — ' . $rekomendasiRbs->tanggal_analisis->format('d F Y'))

@section('content')
<div class="space-y-4 sm:space-y-5">

    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <a href="{{ route('laporan.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-xl transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
        <a href="{{ route('laporan.pdf', $rekomendasiRbs) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-all shadow-sm shadow-red-600/10">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Download PDF
        </a>
    </div>

    {{-- Status Banner --}}
    @php
        $sc = match($rekomendasiRbs->status_kondisi_tanaman) {
            'GEJALA_BERAT' => 'from-red-50 to-rose-50/30 border-red-200 dark:from-red-950/40 dark:to-red-900/20 dark:border-red-800/60',
            'TERINDIKASI_DEFISIENSI' => 'from-orange-50 to-amber-50/30 border-orange-200 dark:from-orange-950/40 dark:to-amber-900/20 dark:border-orange-800/60',
            'NORMAL_VISUAL' => 'from-emerald-50 to-green-50/30 border-emerald-200 dark:from-emerald-950/40 dark:to-green-900/20 dark:border-emerald-800/60',
            'PERLU_VERIFIKASI' => 'from-yellow-50 to-yellow-50/30 border-yellow-200 dark:from-yellow-950/40 dark:to-yellow-900/20 dark:border-yellow-800/60',
            default => 'from-slate-50 to-slate-100/50 border-slate-200 dark:from-slate-800/60 dark:to-slate-800/30 dark:border-slate-700'
        };
        $scText = match($rekomendasiRbs->status_kondisi_tanaman) {
            'GEJALA_BERAT' => 'text-red-950 dark:text-red-200',
            'TERINDIKASI_DEFISIENSI' => 'text-orange-950 dark:text-orange-200',
            'NORMAL_VISUAL' => 'text-emerald-950 dark:text-emerald-200',
            'PERLU_VERIFIKASI' => 'text-yellow-950 dark:text-yellow-200',
            default => 'text-slate-900 dark:text-slate-200'
        };
    @endphp
    <div class="bg-gradient-to-r {{ $sc }} border rounded-2xl p-5 shadow-sm">
        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold tracking-wider uppercase mb-1">Rekomendasi Rule-Based System</p>
        <p class="text-lg sm:text-xl font-extrabold {{ $scText }}">{{ $rekomendasiRbs->label_kondisi_tanaman }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
            {{ $rekomendasiRbs->blokLahan->nama_blok }} · {{ $rekomendasiRbs->blokLahan->nama_pemilik }}
            · {{ $rekomendasiRbs->tanggal_analisis->format('d F Y') }}
            · Oleh: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $rekomendasiRbs->admin->nama_lengkap }}</span>
        </p>
        @php
            $currentDataReady = $observationCompleteness['can_run_diagnosis'] ?? $rekomendasiRbs->data_cukup;
            $dataPendukungKurang = collect($observationCompleteness['missing_fields'] ?? $rekomendasiRbs->data_kurang ?? [])->filter()->values();
        @endphp
        <div class="flex flex-wrap items-center gap-2 mt-3">
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $currentDataReady ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                {{ $currentDataReady ? 'Data analisis tersedia' : 'Data analisis belum lengkap' }}
            </span>
            @if($dataPendukungKurang->isNotEmpty())
                <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-700 px-2.5 py-1 text-[10px] font-semibold text-slate-700 dark:text-slate-300">Data pendukung perlu dilengkapi</span>
            @endif
        </div>
    </div>

    {{-- Notifikasi Data --}}
    @if(!$rekomendasiRbs->data_cukup && $rekomendasiRbs->notifikasi_data)
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-xl p-4 text-sm text-amber-800 dark:text-amber-300">
        <p class="font-semibold mb-1">⚠️ Data Observasi Belum Cukup</p>
        <p>{{ $rekomendasiRbs->notifikasi_data }}</p>
    </div>
    @endif

    {{-- Info Cards — 3 columns --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Informasi Lahan --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
            <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 dark:border-slate-700">
                Informasi Lahan <span class="text-[9px] text-slate-400 dark:text-slate-500 normal-case font-normal">(data saat analisis)</span>
            </h3>
            @php
                $luasDisplay = $rekomendasiRbs->luas_ha_snapshot ?? $rekomendasiRbs->blokLahan->luas_ha;
                $sphDisplay = $rekomendasiRbs->sph_snapshot ?? $rekomendasiRbs->blokLahan->sph;
                $pokokDisplay = $rekomendasiRbs->jumlah_pokok_snapshot ?? $rekomendasiRbs->blokLahan->jumlah_pokok_aktual;
            @endphp
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Luas</span>
                    <span class="text-slate-800 dark:text-slate-200 font-bold">{{ number_format($luasDisplay, 2) }} Ha</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">SPH</span>
                    <span class="text-slate-800 dark:text-slate-200 font-medium">{{ number_format($sphDisplay) }} ph/Ha</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Total Pohon</span>
                    <span class="text-slate-900 dark:text-slate-100 font-bold">{{ number_format($pokokDisplay) }}</span>
                </div>
            </div>
        </div>

        {{-- Kriteria Agronomis --}}
        @if($rekomendasiRbs->blokLahan->tahun_tanam)
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
            <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 dark:border-slate-700">
                Kriteria Agronomis <span class="text-[9px] text-slate-400 dark:text-slate-500 normal-case font-normal">(data saat analisis)</span>
            </h3>
            @php
                $umurDisplay = $rekomendasiRbs->umur_tanaman_snapshot ?? $rekomendasiRbs->blokLahan->umur_tanaman;
                $faseDisplay = $rekomendasiRbs->fase_tanaman_snapshot
                    ? \App\Enums\PlantPhase::labelFromValue($rekomendasiRbs->fase_tanaman_snapshot)
                    : ($rekomendasiRbs->blokLahan->fase_label ?? '-');
            @endphp
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Umur (saat analisis)</span>
                    <span class="text-emerald-700 dark:text-emerald-400 font-bold">{{ $umurDisplay ?? '-' }} tahun</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Fase</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ $faseDisplay }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Topografi</span>
                    <span class="text-slate-800 dark:text-slate-200 font-medium">{{ $rekomendasiRbs->blokLahan->topografi }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Kebutuhan Pupuk Standar --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
            <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 dark:border-slate-700">
                Kebutuhan Pupuk Standar
            </h3>
            @if($rekomendasiRbs->dosis_urea)
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Dosis Urea</p>
                    <p class="text-lg font-extrabold text-amber-700 dark:text-amber-400">{{ $rekomendasiRbs->dosis_urea }} <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">kg/pk</span></p>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-700 pt-3">
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Dosis KCl</p>
                    <p class="text-lg font-extrabold text-cyan-700 dark:text-cyan-400">{{ $rekomendasiRbs->dosis_kcl }} <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">kg/pk</span></p>
                </div>
            </div>
            @else
            <p class="text-sm text-slate-400 dark:text-slate-500">Data kriteria lahan belum tersedia untuk perhitungan dosis.</p>
            @endif
        </div>
    </div>

    {{-- Grid 2 Kolom: Kebutuhan Pupuk Tahunan + Jadwal Pemupukan --}}
    @php
        $jumlahPokok = $rekomendasiRbs->jumlah_pokok_snapshot ?? 0;
        $ureaEstTahunan = $rekomendasiRbs->urea_estimasi_kg_per_pokok_tahun ? round($rekomendasiRbs->urea_estimasi_kg_per_pokok_tahun * $jumlahPokok, 1) : null;
        $kclEstTahunan = $rekomendasiRbs->kcl_estimasi_kg_per_pokok_tahun ? round($rekomendasiRbs->kcl_estimasi_kg_per_pokok_tahun * $jumlahPokok, 1) : null;
        $karungUreaTahunan = $ureaEstTahunan ? (int) ceil($ureaEstTahunan / 50) : 0;
        $karungKclTahunan = $kclEstTahunan ? (int) ceil($kclEstTahunan / 50) : 0;
        $isDitunda = $rekomendasiRbs->status_kelayakan_aplikasi && !in_array($rekomendasiRbs->status_kelayakan_aplikasi, ['LAYAK_DIJADWALKAN', 'TERLAMBAT_PERLU_DIJADWALKAN']);
        $hasLogistik = $ureaEstTahunan !== null || $kclEstTahunan !== null || $rekomendasiRbs->total_urea;
        $hasJadwal = $rekomendasiRbs->jadwal_pemupukan && count($rekomendasiRbs->jadwal_pemupukan) > 0;
    @endphp
    @if($hasLogistik || $hasJadwal)
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">

        {{-- KIRI: Kebutuhan Pupuk Tahunan --}}
        @if($hasLogistik)
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Kebutuhan Pupuk Tahunan
            </h3>

            {{-- 4 stat boxes dalam 2x2 --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-amber-50/70 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/40 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-amber-700 dark:text-amber-400 font-semibold uppercase tracking-wider mb-1">Total Urea</p>
                    <p class="text-xl font-extrabold text-amber-700 dark:text-amber-400">{{ number_format($ureaEstTahunan ?? $rekomendasiRbs->total_urea, 1) }}</p>
                    <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium uppercase mt-0.5">kg / tahun</p>
                </div>
                <div class="bg-amber-50/70 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/40 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-amber-700 dark:text-amber-400 font-semibold uppercase tracking-wider mb-1">Karung Urea</p>
                    <p class="text-xl font-extrabold text-amber-800 dark:text-amber-300">{{ $karungUreaTahunan ?: $rekomendasiRbs->karung_urea }}</p>
                    <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium uppercase mt-0.5">karung @50kg</p>
                </div>
                <div class="bg-cyan-50/70 dark:bg-cyan-900/20 border border-cyan-100 dark:border-cyan-800/40 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-cyan-700 dark:text-cyan-400 font-semibold uppercase tracking-wider mb-1">Total KCl</p>
                    <p class="text-xl font-extrabold text-cyan-700 dark:text-cyan-400">{{ number_format($kclEstTahunan ?? $rekomendasiRbs->total_kcl, 1) }}</p>
                    <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium uppercase mt-0.5">kg / tahun</p>
                </div>
                <div class="bg-cyan-50/70 dark:bg-cyan-900/20 border border-cyan-100 dark:border-cyan-800/40 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-cyan-700 dark:text-cyan-400 font-semibold uppercase tracking-wider mb-1">Karung KCl</p>
                    <p class="text-xl font-extrabold text-cyan-700 dark:text-cyan-400">{{ $karungKclTahunan ?: $rekomendasiRbs->karung_kcl }}</p>
                    <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium uppercase mt-0.5">karung @50kg</p>
                </div>
            </div>

            {{-- Status Tahap Aktif --}}
            @if($isDitunda)
            <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-xl p-3">
                <p class="text-xs font-bold text-amber-800 dark:text-amber-300">⏸️ Aplikasi Saat Ini: 0 kg</p>
                <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">{{ \App\Enums\ApplicationFeasibilityStatus::labelFromValue($rekomendasiRbs->status_kelayakan_aplikasi) }}. Kebutuhan tahunan tetap tercatat di atas.</p>
            </div>
            @elseif(($rekomendasiRbs->urea_aplikasi_saat_ini ?? 0) > 0 || ($rekomendasiRbs->kcl_aplikasi_saat_ini ?? 0) > 0)
            <div class="mt-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl p-4">
                <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300">✅ Tahap Aktif{{ $rekomendasiRbs->active_stage ? ' (Tahap ' . $rekomendasiRbs->active_stage . ')' : '' }}: Siap Diaplikasikan</p>
                <div class="grid grid-cols-2 gap-2 mt-3">
                    <div class="text-center bg-white/60 dark:bg-slate-800/60 rounded-lg p-2.5">
                        <p class="text-base font-extrabold text-emerald-700 dark:text-emerald-400">{{ number_format($rekomendasiRbs->urea_aplikasi_saat_ini, 1) }} kg</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Urea — tahap aktif</p>
                    </div>
                    <div class="text-center bg-white/60 dark:bg-slate-800/60 rounded-lg p-2.5">
                        <p class="text-base font-extrabold text-emerald-700 dark:text-emerald-400">{{ number_format($rekomendasiRbs->kcl_aplikasi_saat_ini, 1) }} kg</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">KCl — tahap aktif</p>
                    </div>
                </div>
                @if($rekomendasiRbs->alasan_tahap)
                <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-2">{{ $rekomendasiRbs->alasan_tahap }}</p>
                @endif
            </div>
            @endif

            {{-- Catatan Dosis (masuk ke dalam kolom kiri agar tidak jadi baris sendiri) --}}
            @if($rekomendasiRbs->catatan_dosis)
            @php
                $catatanStyle = match($rekomendasiRbs->status_kondisi_tanaman) {
                    'GEJALA_BERAT' => 'bg-red-50 border-red-200 text-red-900 dark:bg-red-900/20 dark:border-red-800/50 dark:text-red-200',
                    'TERINDIKASI_DEFISIENSI' => 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-900/20 dark:border-amber-800/50 dark:text-amber-200',
                    'PERLU_VERIFIKASI' => 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-900/20 dark:border-blue-800/50 dark:text-blue-200',
                    default => 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-900/20 dark:border-emerald-800/50 dark:text-emerald-200',
                };
            @endphp
            <div class="{{ $catatanStyle }} border rounded-xl p-3.5 mt-4">
                <h4 class="text-xs font-bold mb-1.5 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Catatan Aplikasi Dosis
                </h4>
                <p class="text-xs leading-relaxed">{{ $rekomendasiRbs->catatan_dosis }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- KANAN: Kolom Kanan (Jadwal & Rekomendasi) --}}
        <div class="flex flex-col gap-4">
            
            {{-- Jadwal Pemupukan Per Tahap --}}
            @if($hasJadwal)
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                    📅 Jadwal Pemupukan Per Tahap
                </h3>

                {{-- Card per tahap (compact, cocok di kolom sempit) --}}
                <div class="space-y-3">
                    @foreach($rekomendasiRbs->jadwal_pemupukan as $index => $jadwal)
                    @php
                        $hasUrea = isset($jadwal['urea_kg']) && $jadwal['urea_kg'] > 0;
                        $hasKcl  = isset($jadwal['kcl_kg'])  && $jadwal['kcl_kg']  > 0;
                        $dosisU  = 0; $dosisK = 0;
                        if ($hasUrea) $dosisU = $jadwal['urea_per_pokok'] ?? ($jadwal['urea_kg'] / max(1, $rekomendasiRbs->blokLahan->jumlah_pokok_aktual));
                        if ($hasKcl)  $dosisK = $jadwal['kcl_per_pokok']  ?? ($jadwal['kcl_kg']  / max(1, $rekomendasiRbs->blokLahan->jumlah_pokok_aktual));
                        $stepNum = $index + 1;
                    @endphp
                    <div class="border border-slate-200 dark:border-slate-600 rounded-xl overflow-hidden">
                        {{-- Header tahap --}}
                        <div class="flex items-center gap-3 px-4 py-2.5 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-600">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-[10px] font-extrabold flex items-center justify-center flex-shrink-0">{{ $stepNum }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $jadwal['nama_tahap'] }}</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">🗓️ {{ $jadwal['estimasi_waktu'] }}</p>
                            </div>
                            @if($hasUrea && $hasKcl)
                            <span class="flex-shrink-0 text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-300">Urea + KCl</span>
                            @elseif($hasUrea)
                            <span class="flex-shrink-0 text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">Urea</span>
                            @elseif($hasKcl)
                            <span class="flex-shrink-0 text-[9px] font-bold px-1.5 py-0.5 rounded bg-cyan-100 dark:bg-cyan-900/40 text-cyan-800 dark:text-cyan-300">KCl</span>
                            @endif
                        </div>

                        {{-- Dosis baris --}}
                        @if($hasUrea || $hasKcl)
                        <div class="px-4 py-2.5 flex flex-wrap gap-x-6 gap-y-1.5 border-b border-slate-100 dark:border-slate-700">
                            @if($hasUrea)
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-5 h-5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 flex items-center justify-center text-[9px] font-bold flex-shrink-0">N</span>
                                <div>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Urea</p>
                                    <p class="text-xs font-bold text-amber-700 dark:text-amber-400">{{ number_format($dosisU, 2) }} kg/pk <span class="font-normal text-slate-500 dark:text-slate-400">· {{ number_format($jadwal['urea_kg'], 1) }} kg total</span></p>
                                </div>
                            </div>
                            @endif
                            @if($hasKcl)
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-5 h-5 rounded bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-400 flex items-center justify-center text-[9px] font-bold flex-shrink-0">K</span>
                                <div>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">KCl</p>
                                    <p class="text-xs font-bold text-cyan-700 dark:text-cyan-400">{{ number_format($dosisK, 2) }} kg/pk <span class="font-normal text-slate-500 dark:text-slate-400">· {{ number_format($jadwal['kcl_kg'], 1) }} kg total</span></p>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- Cara aplikasi & catatan --}}
                        <div class="px-4 py-2.5 space-y-1.5">
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ $jadwal['metode_aplikasi'] }}</p>
                            @if(!empty($jadwal['catatan']))
                            <p class="text-[10px] text-amber-700 dark:text-amber-400 italic leading-relaxed">⚠️ {{ $jadwal['catatan'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Masalah & Rekomendasi --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-4">Temuan dan Rekomendasi</h3>

                @if($rekomendasiRbs->masalah_teridentifikasi)
                <div class="mb-4">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Masalah</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($rekomendasiRbs->masalah_teridentifikasi as $masalah)
                        <span class="inline-flex items-center px-2.5 py-1 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-xs rounded-full">{{ $masalah }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($rekomendasiRbs->rekomendasi_pupuk)
                <div class="mb-4">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Rekomendasi Pupuk Spesifik</p>
                    <div class="space-y-2">
                        @foreach($rekomendasiRbs->rekomendasi_pupuk as $pupuk)
                        <div class="bg-emerald-50/50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/40 rounded-xl p-3">
                            <p class="font-semibold text-emerald-700 dark:text-emerald-400 text-sm">🌿 {{ $pupuk['jenis_utama'] ?? '' }}</p>
                            @if(!empty($pupuk['dosis']))<p class="text-xs text-slate-600 dark:text-slate-400 mt-1"><strong>Dosis:</strong> {{ $pupuk['dosis'] }}</p>@endif
                            @if(!empty($pupuk['metode']))<p class="text-xs text-slate-600 dark:text-slate-400"><strong>Metode:</strong> {{ $pupuk['metode'] }}</p>@endif
                            @if(!empty($pupuk['waktu']))<p class="text-xs text-slate-500 dark:text-slate-400"><strong>Waktu:</strong> {{ $pupuk['waktu'] }}</p>@endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($rekomendasiRbs->saran_tindakan_utama)
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-xl p-3">
                    <p class="text-xs font-semibold text-amber-800 dark:text-amber-300 uppercase tracking-wider mb-1">Saran Tindakan</p>
                    <p class="text-sm text-amber-900 dark:text-amber-200 leading-relaxed">{{ $rekomendasiRbs->saran_tindakan_utama }}</p>
                </div>
                @endif
            </div>

        </div>

    </div>
    @endif

    {{-- Aturan RBS yang Terpicu --}}
    @if($rekomendasiRbs->rules_terpicu && count($rekomendasiRbs->rules_terpicu) > 0)
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-slate-700">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Jejak Penalaran Forward Chaining</p>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                    Aturan yang Terpicu
                    <span class="ml-2 rounded-full bg-indigo-100 dark:bg-indigo-900/50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 dark:text-indigo-300">
                        {{ $rekomendasiRbs->jumlah_rule_terpicu }} aturan
                    </span>
                </h3>
            </div>
            <span class="text-[10px] text-slate-400 dark:text-slate-500">Versi mesin: {{ $rekomendasiRbs->versi_mesin_rekomendasi ?? '-' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 dark:bg-slate-700/60">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">Kode Rule</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">Jenis</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">Tahap</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-slate-500 dark:text-slate-400">Indikasi / Kesimpulan</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">Pupuk Terkait</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($rekomendasiRbs->rules_terpicu as $r)
                    @php
                        $jenisColor = match($r['jenis_rule'] ?? '') {
                            'DIAGNOSIS_VISUAL'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                            'PEMBATAS_APLIKASI' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                            'KONDISI_LAHAN'     => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
                            'PENENTU_DOSIS'     => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300',
                            'PENENTU_METODE'    => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
                            default             => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                        };
                        $jenisLabel = match($r['jenis_rule'] ?? '') {
                            'DIAGNOSIS_VISUAL'  => 'Visual',
                            'PEMBATAS_APLIKASI' => 'Waktu',
                            'KONDISI_LAHAN'     => 'Lahan',
                            'PENENTU_DOSIS'     => 'Dosis',
                            'PENENTU_METODE'    => 'Metode',
                            default             => $r['jenis_rule'] ?? '-',
                        };
                        $statusColor = match($r['status'] ?? '') {
                            'Darurat' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                            'Segera'  => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
                            'Tunda'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                            default   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="font-mono text-[11px] font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/40 px-2 py-0.5 rounded">
                                {{ $r['kode_rule'] ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $jenisColor }}">
                                {{ $jenisLabel }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-center text-slate-500 dark:text-slate-400 font-medium">
                            {{ $r['tahap_eksekusi'] ?? 1 }}
                        </td>
                        <td class="px-4 py-2.5 text-slate-700 dark:text-slate-300 leading-relaxed">
                            {{ $r['indikasi'] ?? '-' }}
                        </td>
                        <td class="px-4 py-2.5 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                            {{ $r['pupuk'] ?? '-' }}
                        </td>
                        <td class="px-4 py-2.5 text-center whitespace-nowrap">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusColor }}">
                                {{ \App\Models\RekomendasiRbs::labelStatus($r['status'] ?? '') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="px-5 py-2.5 text-[10px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-700">
            Tahap: 1 = Diagnosis Kondisi · 2 = Penentuan Dosis · 3 = Penyesuaian/Pembatas.
            Kode rule dapat ditelusuri di <a href="{{ route('rule-base.index') }}" class="text-indigo-500 hover:underline">menu Rule Base</a> untuk melihat sumber literatur dan kondisi IF-THEN lengkap.
        </p>
    </div>
    @endif

    {{-- Data Observasi Lapangan --}}
    @php
        $kondisi = $rekomendasiRbs->kondisiLahan;
        $sumberHujanLabel = match($kondisi?->sumber_curah_hujan) {
            'alat_ukur'  => 'Alat ukur di kebun',
            'open-meteo' => 'Open-Meteo (perkiraan lokasi)',
            'manual'     => 'Catatan kelompok tani',
            'lainnya'    => 'Sumber lainnya',
            default      => null,
        };
    @endphp
    @if($kondisi)
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
        <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 dark:border-slate-700 flex items-center gap-2">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Data Observasi Lapangan
            <span class="font-normal normal-case text-[9px]">{{ $kondisi->tanggal_observasi?->format('d M Y') }}</span>
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-x-6 gap-y-3 text-sm">

            {{-- Kondisi daun --}}
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Kondisi Daun</p>
                <p class="text-slate-800 dark:text-slate-200 font-medium text-xs">
                    {{ config('observation.leaf_condition_labels.' . $kondisi->warna_daun, $kondisi->warna_daun ?? '-') }}
                </p>
            </div>

            {{-- Curah hujan --}}
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Curah Hujan</p>
                <p class="text-slate-800 dark:text-slate-200 font-medium text-xs">
                    @if($kondisi->curah_hujan_mm_bulanan !== null)
                        {{ number_format($kondisi->curah_hujan_mm_bulanan, 1) }} mm/bulan
                    @elseif($kondisi->curah_hujan_kategori)
                        {{ $kondisi->curah_hujan_kategori }} (perkiraan)
                    @else
                        <span class="text-slate-400 dark:text-slate-500">Tidak tersedia</span>
                    @endif
                </p>
            </div>

            {{-- Sumber curah hujan (baru) --}}
            @if($sumberHujanLabel || $kondisi->periode_curah_hujan)
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Sumber Data Hujan</p>
                <p class="text-slate-800 dark:text-slate-200 font-medium text-xs">
                    {{ $sumberHujanLabel ?? '-' }}
                    @if($kondisi->periode_curah_hujan)
                        <span class="text-slate-400 dark:text-slate-500"> · {{ $kondisi->periode_curah_hujan }}</span>
                    @endif
                </p>
            </div>
            @endif

            {{-- Musim --}}
            @if($kondisi->musim_saat_ini)
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Musim</p>
                <p class="text-slate-800 dark:text-slate-200 font-medium text-xs">{{ $kondisi->musim_saat_ini }}</p>
            </div>
            @endif

            {{-- Kelembaban --}}
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Kelembapan Tanah</p>
                <p class="text-slate-800 dark:text-slate-200 font-medium text-xs">{{ $kondisi->kelembaban_tanah ?? '-' }}</p>
            </div>

            {{-- Drainase --}}
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Drainase</p>
                <p class="text-slate-800 dark:text-slate-200 font-medium text-xs">{{ $kondisi->kondisi_drainase ?? '-' }}</p>
            </div>

            {{-- Gulma & hama --}}
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Gulma / Hama</p>
                <p class="text-slate-800 dark:text-slate-200 font-medium text-xs">
                    {{ $kondisi->ada_gulma_dominan ? 'Ada gulma' : 'Tidak ada gulma' }}
                    · {{ $kondisi->ada_serangan_hama ? 'Ada hama' : 'Tidak ada hama' }}
                </p>
            </div>

            {{-- Tanggal pemupukan terakhir --}}
            @if($kondisi->tanggal_pemupukan_terakhir)
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Pemupukan Terakhir</p>
                <p class="text-slate-800 dark:text-slate-200 font-medium text-xs">{{ $kondisi->tanggal_pemupukan_terakhir->format('d M Y') }}</p>
            </div>
            @endif

        </div>

        {{-- Catatan observasi (baru) --}}
        @if($kondisi->catatan_observasi)
        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Catatan Lapangan</p>
            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-900/40 rounded-lg px-3 py-2.5 border border-slate-200 dark:border-slate-700">{{ $kondisi->catatan_observasi }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Info Analisis --}}
    <div class="text-xs text-slate-400 dark:text-slate-500 text-right">
        {{ $rekomendasiRbs->jumlah_rule_terpicu }} aturan sesuai · Dianalisis {{ $rekomendasiRbs->tanggal_analisis->diffForHumans() }}
    </div>

    {{-- Button Kembali di bawah --}}
    <div class="pt-2 pb-4">
        <a href="{{ route('laporan.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-xl transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Laporan
        </a>
    </div>
</div>
@endsection
