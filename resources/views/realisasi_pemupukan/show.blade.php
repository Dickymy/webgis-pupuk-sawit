@extends('layouts.app')

@section('title', 'Detail Realisasi Pemupukan')
@section('page-title', 'Detail Realisasi')
@section('page-subtitle', ($realisasiPemupukan->blokLahan->nama_blok ?? '-') . ' · Tahap ' . $realisasiPemupukan->tahap)

@section('content')

<div class="mb-4">
    <a href="{{ route('realisasi-pemupukan.index', ['tab' => 'riwayat']) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Daftar
    </a>
</div>

@php
    $statusColor = match($realisasiPemupukan->status_realisasi) {
        'SELESAI' => 'bg-green-100 text-green-800 border-green-200',
        'SEBAGIAN' => 'bg-amber-100 text-amber-800 border-amber-200',
        'BATAL' => 'bg-red-100 text-red-800 border-red-200',
        default => 'bg-slate-100 text-slate-600 border-slate-200',
    };
@endphp

{{-- Status Banner --}}
<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm p-5 mb-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">{{ $realisasiPemupukan->blokLahan->nama_blok ?? '-' }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">Pemilik: {{ $realisasiPemupukan->blokLahan->anggota->nama ?? '-' }}</p>
        </div>
        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border {{ $statusColor }}">
            {{ $realisasiPemupukan->label_status }}
        </span>
    </div>
</div>

{{-- Detail --}}
<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm p-5 mb-5">
    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4">📊 Detail Realisasi</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
        <div>
            <span class="text-slate-400 block">Tanggal Realisasi</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $realisasiPemupukan->tanggal_realisasi->format('d/m/Y') }}</span>
        </div>
        <div>
            <span class="text-slate-400 block">Tahap</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">Tahap {{ $realisasiPemupukan->tahap }}</span>
        </div>
        <div>
            <span class="text-slate-400 block">Tahun Program</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $realisasiPemupukan->tahun_program ?? '-' }}</span>
        </div>
        <div>
            <span class="text-slate-400 block">Dicatat Oleh</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $realisasiPemupukan->admin->nama_lengkap ?? '-' }}</span>
        </div>
    </div>

    {{-- Rencana vs Realisasi --}}
    <div class="grid grid-cols-2 gap-4 mt-5">
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
            <p class="text-[10px] text-amber-600 uppercase font-bold mb-2">Urea</p>
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs">
                    <span class="text-slate-500">Rencana:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ number_format($realisasiPemupukan->urea_rencana_kg, 2) }} kg</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-slate-500">Realisasi:</span>
                    <span class="font-bold text-amber-800">{{ number_format($realisasiPemupukan->urea_realisasi_kg, 2) }} kg</span>
                </div>
            </div>
        </div>
        <div class="bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-xl p-4">
            <p class="text-[10px] text-cyan-600 uppercase font-bold mb-2">KCl</p>
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs">
                    <span class="text-slate-500">Rencana:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ number_format($realisasiPemupukan->kcl_rencana_kg, 2) }} kg</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-slate-500">Realisasi:</span>
                    <span class="font-bold text-cyan-800">{{ number_format($realisasiPemupukan->kcl_realisasi_kg, 2) }} kg</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Catatan --}}
    @if($realisasiPemupukan->catatan_pelaksana)
    <div class="mt-4 p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
        <p class="text-[10px] text-slate-400 uppercase font-semibold mb-1">Catatan Pelaksana</p>
        <p class="text-xs text-slate-700 dark:text-slate-300">{{ $realisasiPemupukan->catatan_pelaksana }}</p>
    </div>
    @endif

    {{-- Override Info --}}
    @if($realisasiPemupukan->confirmed_over_plan)
    <div class="mt-3 p-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 rounded-lg">
        <p class="text-[10px] text-amber-700 font-semibold">⚠️ Realisasi melebihi rencana tahap — dikonfirmasi oleh admin.</p>
    </div>
    @endif
    @if($realisasiPemupukan->override_annual_limit)
    <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 rounded-lg">
        <p class="text-[10px] text-red-700 font-semibold">🚨 Override batas kebutuhan tahunan aktif.</p>
        @if($realisasiPemupukan->override_reason)
        <p class="text-[10px] text-red-600 mt-0.5">Alasan: {{ $realisasiPemupukan->override_reason }}</p>
        @endif
    </div>
    @endif
</div>

{{-- Aksi --}}
<div class="flex items-center gap-3 flex-wrap">
    @if($realisasiPemupukan->status_realisasi !== 'BATAL')
    <a href="{{ route('realisasi-pemupukan.edit', $realisasiPemupukan) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
        ✏️ Edit Realisasi
    </a>
    <form method="POST" action="{{ route('realisasi-pemupukan.cancel', $realisasiPemupukan) }}" class="inline" onsubmit="return false;">
        @csrf
        @method('PATCH')
        <button type="button" onclick="confirmDelete(this.closest('form'), 'realisasi ini')" class="inline-flex items-center gap-2 px-4 py-2.5 border border-red-200 text-red-600 text-sm font-medium rounded-xl hover:bg-red-50 transition-colors">
            ❌ Batalkan Realisasi
        </button>
    </form>
    @endif
    @if($realisasiPemupukan->rekomendasiRbs?->blokLahan)
    <a href="{{ route('rbs.detail', $realisasiPemupukan->rekomendasiRbs->blokLahan) }}" class="inline-flex items-center gap-2 px-4 py-2.5 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
        📋 Lihat Analisis RBS
    </a>
    @endif
</div>

{{-- Histori Operasional (Pahan v2.7) --}}
@if(isset($historiOperasional) && $historiOperasional->count() > 0)
<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm p-5 mt-5">
    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-3">Riwayat Perubahan</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-slate-300">Waktu</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-slate-300">Event</th>
                    <th class="px-3 py-2 text-center font-semibold text-slate-600 dark:text-slate-300">Tahap</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-600 dark:text-slate-300">Status</th>
                    <th class="px-3 py-2 text-right font-semibold text-slate-600 dark:text-slate-300">Sisa Urea</th>
                    <th class="px-3 py-2 text-right font-semibold text-slate-600 dark:text-slate-300">Sisa KCl</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @foreach($historiOperasional as $h)
                <tr>
                    <td class="px-3 py-2 text-slate-500">{{ $h->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-3 py-2 font-medium text-slate-800 dark:text-slate-200">{{ $h->label_event }}</td>
                    <td class="px-3 py-2 text-center">{{ $h->active_stage ?? '-' }}</td>
                    <td class="px-3 py-2 text-slate-600 dark:text-slate-400">{{ \App\Services\CurrentApplicationCalculator::labelStatusStage($h->status_stage) }}</td>
                    <td class="px-3 py-2 text-right">{{ $h->urea_sisa_tahunan !== null ? number_format($h->urea_sisa_tahunan, 1) : '-' }}</td>
                    <td class="px-3 py-2 text-right">{{ $h->kcl_sisa_tahunan !== null ? number_format($h->kcl_sisa_tahunan, 1) : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Pahan v2.8: Tindakan Berikutnya --}}
@if($rekomendasi = $realisasiPemupukan->rekomendasiRbs)
@php
    $statusStage = $rekomendasi->status_stage;
    $isSiap = in_array($statusStage, [
        \App\Services\CurrentApplicationCalculator::TAHAP_1_SIAP,
        \App\Services\CurrentApplicationCalculator::TAHAP_1_SEBAGIAN,
        \App\Services\CurrentApplicationCalculator::TAHAP_2_SIAP,
    ]);
@endphp
<div class="mt-8 mb-5">
    <div class="relative overflow-hidden rounded-3xl border {{ $isSiap ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/30' : 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/30' }} shadow-sm">
        {{-- Background icon (decorative) --}}
        <div class="absolute -right-8 -top-8 opacity-10 pointer-events-none">
            @if($isSiap)
            <svg class="w-48 h-48 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            @else
            <svg class="w-48 h-48 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            @endif
        </div>
        
        <div class="p-6 sm:p-8 relative z-10">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <div class="flex-shrink-0 w-14 h-14 flex items-center justify-center rounded-2xl shadow-sm {{ $isSiap ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-800 dark:text-emerald-300' : 'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-300' }}">
                    @if($isSiap)
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                
                <div class="flex-1">
                    <h3 class="text-xs font-bold uppercase tracking-widest mb-1 {{ $isSiap ? 'text-emerald-700 dark:text-emerald-400' : 'text-blue-700 dark:text-blue-400' }}">
                        Langkah Selanjutnya
                    </h3>
                    
                    <p class="text-xl sm:text-2xl font-extrabold {{ $isSiap ? 'text-emerald-900 dark:text-emerald-100' : 'text-blue-900 dark:text-blue-100' }}">
                        @switch($statusStage)
                            @case(\App\Services\CurrentApplicationCalculator::TAHAP_1_SIAP)
                                Catat Realisasi Tahap 1
                                @break
                            @case(\App\Services\CurrentApplicationCalculator::TAHAP_1_SEBAGIAN)
                                Lanjutkan Realisasi Tahap 1
                                @break
                            @case(\App\Services\CurrentApplicationCalculator::MENUNGGU_INTERVAL)
                                Tunggu hingga {{ $rekomendasi->tanggal_minimum_tahap_berikutnya ? $rekomendasi->tanggal_minimum_tahap_berikutnya->format('d M Y') : 'Jadwal Berikutnya' }}
                                @break
                            @case(\App\Services\CurrentApplicationCalculator::MENUNGGU_KELAYAKAN)
                                Blok Belum Memenuhi Syarat
                                @break
                            @case(\App\Services\CurrentApplicationCalculator::TAHAP_2_SIAP)
                                Catat Realisasi Tahap 2
                                @break
                            @case(\App\Services\CurrentApplicationCalculator::SELESAI_TAHUNAN)
                                Program Pemupukan Selesai
                                @break
                            @default
                                Periksa Status Terbaru
                        @endswitch
                    </p>
                    
                    <p class="mt-2 text-sm font-medium leading-relaxed {{ $isSiap ? 'text-emerald-800 dark:text-emerald-300' : 'text-blue-800 dark:text-blue-300' }}">
                        @switch($statusStage)
                            @case(\App\Services\CurrentApplicationCalculator::TAHAP_1_SIAP)
                            @case(\App\Services\CurrentApplicationCalculator::TAHAP_2_SIAP)
                                Blok ini siap untuk dipupuk. Silakan lakukan pencatatan realisasi.
                                @break
                            @case(\App\Services\CurrentApplicationCalculator::TAHAP_1_SEBAGIAN)
                                Sebagian dosis pada tahap 1 belum terpenuhi. Lanjutkan realisasi untuk memenuhinya.
                                @break
                            @case(\App\Services\CurrentApplicationCalculator::MENUNGGU_INTERVAL)
                                Tahap sebelumnya telah selesai. Tahap berikutnya dapat dilakukan setelah jarak waktu minimal terpenuhi.
                                @break
                            @case(\App\Services\CurrentApplicationCalculator::MENUNGGU_KELAYAKAN)
                                Kondisi lapangan (misalnya drainase tergenang atau curah hujan berlebihan) menyebabkan blok ini belum dapat dipupuk.
                                @break
                            @case(\App\Services\CurrentApplicationCalculator::SELESAI_TAHUNAN)
                                Kebutuhan Urea dan KCl tahun ini sudah terpenuhi. Tidak ada pemupukan lanjutan di blok ini.
                                @break
                            @default
                                Silakan jalankan ulang analisis RBS untuk mendapatkan status terkini.
                        @endswitch
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
