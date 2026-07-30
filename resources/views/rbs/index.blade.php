@extends('layouts.app')

@section('title', 'Rekomendasi Pupuk')
@section('page-title', 'Rekomendasi Pupuk')
@section('page-subtitle', 'Lihat hasil analisis dan langkah selanjutnya untuk setiap blok')

@section('content')
@php
    $belumKondisi = $stats['belum_kondisi'];
    $statusOptions = [
        'semua' => 'Semua Status',
        'perlu-tindakan' => 'Perlu Tindakan',
        'belum-observasi' => 'Belum Diperiksa',
        'perlu-rekomendasi' => 'Perlu Dianalisis',
        'siap-realisasi' => 'Siap Dipupuk',
        'menunggu-interval' => 'Menunggu Jarak Waktu',
        'menunggu' => 'Menunggu Lainnya',
        'selesai' => 'Program Selesai',
    ];
@endphp

<div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-[10px] text-slate-500 dark:text-slate-400 [&>div>p:first-child]:hidden [&>div>p:last-child]:mt-0 [&>div>p:last-child]:text-[10px] [&>div>p:last-child]:text-slate-500">
    <div>
        <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Fakta observasi → aturan RBS → rekomendasi pupuk</p>
        <p class="mt-0.5 text-xs text-emerald-700 dark:text-emerald-400">Dosis Urea dan KCl mengacu pada Iyung Pahan (2013).</p>
    </div>
    <a href="{{ route('rule-base.index') }}" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-semibold text-slate-600 transition-colors hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
        Rule Based
        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
    </a>
</div>

<div class="mb-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
    <a href="{{ route('rbs.index', ['status' => 'perlu-rekomendasi']) }}" class="flex min-h-20 items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
        <div class="min-w-0"><p class="flex items-center gap-2 text-xs font-semibold text-slate-800 dark:text-slate-100"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Perlu Dianalisis</p><p class="mt-1 text-[10px] text-slate-400">Observasi baru atau berubah</p></div>
        <div class="shrink-0 text-right"><p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $stats['perlu_rekomendasi'] }}</p><p class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">Buka &rarr;</p></div>
    </a>
    <a href="{{ route('rbs.index', ['status' => 'siap-realisasi']) }}" class="flex min-h-20 items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
        <div class="min-w-0"><p class="flex items-center gap-2 text-xs font-semibold text-slate-800 dark:text-slate-100"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Siap Dipupuk</p><p class="mt-1 text-[10px] text-slate-400">Dapat dicatat sekarang</p></div>
        <div class="shrink-0 text-right"><p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $stats['siap_realisasi'] }}</p><p class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">Buka &rarr;</p></div>
    </a>
    <a href="{{ route('rbs.index', ['status' => 'belum-observasi']) }}" class="flex min-h-20 items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
        <div class="min-w-0"><p class="flex items-center gap-2 text-xs font-semibold text-slate-800 dark:text-slate-100"><span class="h-2 w-2 rounded-full bg-slate-400"></span>Belum Diperiksa</p><p class="mt-1 text-[10px] text-slate-400">Isi fakta lapangan dahulu</p></div>
        <div class="shrink-0 text-right"><p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $belumKondisi }}</p><p class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">Buka &rarr;</p></div>
    </a>
</div>
<div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
    <form method="GET" action="{{ route('rbs.index') }}" id="rbs-filter-form" data-no-prevent-double="true" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative sm:min-w-[180px]">
            <select name="status" onchange="this.form.submit()" class="custom-select w-full rounded-lg border border-slate-300 bg-white pl-3 pr-8 py-2 text-xs font-medium text-slate-700 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 sm:w-auto sm:min-w-[180px]">
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div class="sm:min-w-[200px]">
            @include('components.filter-searchable', [
                'name' => 'anggota_id',
                'placeholder' => 'Cari pemilik...',
                'options' => $anggotas,
                'displayField' => 'nama',
                'selected' => request('anggota_id'),
                'formId' => 'rbs-filter-form',
            ])
        </div>
        @if($blokFilter->isNotEmpty())
            <div class="relative sm:min-w-[150px]">
                <select name="blok_lahan_id" onchange="this.form.submit()" class="custom-select w-full rounded-lg border border-slate-300 bg-white pl-3 pr-8 py-2 text-xs font-medium text-slate-700 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 sm:w-auto sm:min-w-[150px]">
                    <option value="">Semua Blok</option>
                    @foreach($blokFilter as $bf)
                        <option value="{{ $bf->id }}" {{ request('blok_lahan_id') == $bf->id ? 'selected' : '' }}>{{ $bf->nama_blok }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        @endif
        @if(request()->hasAny(['anggota_id', 'blok_lahan_id']) || $status !== 'semua')
            <a href="{{ route('rbs.index') }}" class="px-2 py-1.5 text-xs font-medium text-slate-500 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition-colors">Reset</a>
        @endif
    </form>

    <form action="{{ route('rbs.analisisSemua') }}" method="POST" id="form-analisis-semua">
        @csrf
        <button type="button"
            onclick="showConfirm('Hitung ulang rekomendasi untuk semua blok yang sudah diobservasi?', function(){ document.getElementById('form-analisis-semua').submit(); })"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-emerald-300 bg-white px-3 py-2 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-50 dark:border-emerald-700 dark:bg-slate-800 dark:text-emerald-300">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Perbarui Rekomendasi
        </button>
    </form>
</div>

@if($status !== 'semua')
    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
        Menampilkan filter: <strong>{{ $statusOptions[$status] }}</strong> · {{ $grouped->sum(fn ($group) => $group['bloks']->count()) }} blok
    </div>
@endif

@forelse($grouped as $group)
    @php $anggota = $group['anggota']; $bloks = $group['bloks']; @endphp
    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="flex items-center gap-2.5 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/80 sm:px-5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">{{ strtoupper(substr($anggota->nama ?? '?', 0, 1)) }}</div>
            <div>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $anggota->nama ?? 'Tidak Diketahui' }}</p>
                <p class="text-[10px] text-slate-500">{{ $bloks->count() }} blok</p>
            </div>
    </div>

        <div class="hidden overflow-x-auto sm:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700">
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Blok Lahan</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Observasi</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Hasil RBS</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Tahap</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase text-slate-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($bloks as $blok)
                        @php
                            $rbs = $blok->rekomendasiRbsTerbaru;
                            $kondisi = $blok->kondisiTerbaru;
                            $perluAnalisisUlang = $kondisi && $rbs && $kondisi->updated_at->gt($rbs->updated_at);
                            $operationalStatus = $blok->operational_eligibility['status_stage'] ?? $rbs?->status_stage;
                            $observasiBelumLengkap = $rbs && (
                                ! $rbs->data_cukup
                                || in_array($rbs->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
                                || $rbs->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA'
                            );
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40">
                            <td class="px-4 py-3">
                                @if($rbs)
                                    <a href="{{ route('rbs.detail', $blok) }}" class="text-xs font-semibold text-slate-800 hover:text-emerald-700 hover:underline dark:text-slate-100 dark:hover:text-emerald-400">{{ $blok->nama_blok }}</a>
                                @else
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $blok->nama_blok }}</p>
                                @endif
                                <p class="text-[10px] text-slate-400">{{ number_format($blok->luas_ha, 2) }} Ha · SPH {{ $blok->sph }} · {{ $blok->umur_tanaman ?? '—' }} thn</p>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($kondisi)
                                    <p class="font-medium text-slate-700 dark:text-slate-200">{{ $kondisi->tanggal_observasi->format('d/m/Y') }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $kondisi->warna_daun ?? 'Gejala belum lengkap' }}</p>
                                @else
                                    <span class="font-semibold text-amber-600">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-recommendation-status :recommendation="$rbs" :show-stage="false" compact />
                                @if($rbs)
                                    <p class="mt-1 text-[10px] text-slate-400">{{ $rbs->jumlah_rule_terpicu }} aturan sesuai</p>
                                @endif
                                @if($perluAnalisisUlang)
                                    <p class="mt-1 text-[9px] font-semibold text-amber-600">Observasi berubah, rekomendasi perlu diperbarui</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                @if($rbs)
                                    <p class="font-medium">{{ $observasiBelumLengkap ? 'Observasi belum lengkap' : \App\Services\CurrentApplicationCalculator::labelStatusStage($operationalStatus) }}</p>
                                    @if(! $observasiBelumLengkap && $rbs->tanggal_minimum_tahap_berikutnya)
                                        <p class="text-[10px] text-slate-400">Mulai {{ $rbs->tanggal_minimum_tahap_berikutnya->format('d/m/Y') }}</p>
                                    @endif
                                @else
                                    <span class="text-slate-400">Belum dihitung</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    @if($rbs)
                                        <a href="{{ route('rbs.detail', $blok) }}" class="inline-flex items-center justify-center gap-1 rounded-lg border border-emerald-300 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700 transition-colors hover:bg-emerald-50 dark:border-emerald-700 dark:bg-slate-800 dark:text-emerald-300">
                                            Lihat Detail
                                        </a>
                                    @endif
                                    <x-next-block-action :blok="$blok" :hide-detail-fallback="true" compact />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-700 sm:hidden">
            @foreach($bloks as $blok)
                @php
                    $rbs = $blok->rekomendasiRbsTerbaru;
                    $kondisi = $blok->kondisiTerbaru;
                    $operationalStatus = $blok->operational_eligibility['status_stage'] ?? $rbs?->status_stage;
                    $observasiBelumLengkap = $rbs && (
                        ! $rbs->data_cukup
                        || in_array($rbs->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
                        || $rbs->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA'
                    );
                @endphp
                <div class="space-y-2.5 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            @if($rbs)
                                <a href="{{ route('rbs.detail', $blok) }}" class="text-xs font-semibold text-slate-800 hover:text-emerald-700 hover:underline dark:text-slate-100 dark:hover:text-emerald-400">{{ $blok->nama_blok }}</a>
                            @else
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $blok->nama_blok }}</p>
                            @endif
                            <p class="text-[10px] text-slate-400">{{ number_format($blok->luas_ha, 2) }} Ha &middot; {{ $kondisi?->tanggal_observasi?->format('d/m/Y') ?? 'belum diobservasi' }}</p>
                        </div>
                        <x-recommendation-status :recommendation="$rbs" :show-stage="false" compact />
                    </div>
                    @if($rbs)
                        <p class="text-[10px] text-slate-500">{{ $observasiBelumLengkap ? 'Observasi belum lengkap' : \App\Services\CurrentApplicationCalculator::labelStatusStage($operationalStatus) }} · {{ $rbs->jumlah_rule_terpicu }} aturan sesuai</p>
                    @endif
                    <div class="grid gap-2">
                        <x-next-block-action :blok="$blok" :hide-detail-fallback="true" class="w-full" compact />
                        @if($rbs)
                            <a href="{{ route('rbs.detail', $blok) }}" class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg border border-emerald-300 bg-white px-3 py-2.5 text-[11px] font-semibold text-emerald-700 transition-colors hover:bg-emerald-50 dark:border-emerald-700 dark:bg-slate-800 dark:text-emerald-300">
                                Lihat Detail <span aria-hidden="true">&rarr;</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Tidak ada blok yang sesuai filter.</p>
        <p class="mt-1 text-xs text-slate-400">Pilih status lain atau reset filter.</p>
    </div>
@endforelse
@endsection
