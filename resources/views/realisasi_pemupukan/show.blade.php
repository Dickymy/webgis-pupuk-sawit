@extends('layouts.app')

@section('title', 'Detail Realisasi Pemupukan')
@section('page-title', 'Detail Realisasi')
@section('page-subtitle', ($realisasiPemupukan->blokLahan->nama_blok ?? '-') . ' · Tahap ' . $realisasiPemupukan->tahap)

@section('content')

<div class="mb-4">
    <a href="{{ route('realisasi-pemupukan.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
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
    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-3">📜 Histori Operasional</h3>
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
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-5 mt-5">
    <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
        <span class="text-base">🎯</span>
        Tindakan Berikutnya
    </h3>
    <div class="p-3 rounded-xl {{ $isSiap ? 'bg-emerald-50 border border-emerald-200' : 'bg-blue-50 border border-blue-200' }}">
        <p class="text-sm font-semibold {{ $isSiap ? 'text-emerald-800' : 'text-blue-800' }}">
            @switch($statusStage)
                @case(\App\Services\CurrentApplicationCalculator::TAHAP_1_SIAP)
                    Catat realisasi Tahap 1.
                    @break
                @case(\App\Services\CurrentApplicationCalculator::TAHAP_1_SEBAGIAN)
                    Lanjutkan realisasi Tahap 1 (sisa belum terpenuhi).
                    @break
                @case(\App\Services\CurrentApplicationCalculator::MENUNGGU_INTERVAL)
                    Tahap 1 selesai. Tahap 2 dapat dilakukan mulai {{ $rekomendasi->tanggal_minimum_tahap_berikutnya ? $rekomendasi->tanggal_minimum_tahap_berikutnya->format('d M Y') : '—' }}.
                    @break
                @case(\App\Services\CurrentApplicationCalculator::MENUNGGU_KELAYAKAN)
                    Pemupukan ditunda karena kondisi kelayakan belum terpenuhi.
                    @break
                @case(\App\Services\CurrentApplicationCalculator::TAHAP_2_SIAP)
                    Catat realisasi Tahap 2.
                    @break
                @case(\App\Services\CurrentApplicationCalculator::SELESAI_TAHUNAN)
                    Program pemupukan tahun ini telah selesai.
                    @break
                @default
                    {{ $rekomendasi->alasan_tahap ?? 'Jalankan analisis ulang untuk status terkini.' }}
            @endswitch
        </p>
    </div>
</div>
@endif

@endsection
