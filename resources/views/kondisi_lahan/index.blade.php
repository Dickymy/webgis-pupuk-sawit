@extends('layouts.app')

@section('title', 'Observasi')
@section('page-title', 'Observasi Lahan')
@section('page-subtitle', 'Pilih blok dan lengkapi data yang diperlukan')

@section('content')
<div class="space-y-3">
    @php
        $tabs = [
            'semua' => ['label' => 'Semua Blok', 'count' => $stats['semua']],
            'belum' => ['label' => 'Perlu Observasi', 'count' => $stats['belum']],
            'perlu-rekomendasi' => ['label' => 'Perlu Dianalisis', 'count' => $stats['perlu_rekomendasi']],
            'sudah' => ['label' => 'Sudah Diobservasi', 'count' => $stats['sudah']],
        ];
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800" aria-label="Kontrol daftar observasi">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center">
            <nav class="min-w-0 flex-1" aria-label="Status observasi">
                <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-700/70 sm:grid-cols-4">
                    @foreach($tabs as $tabKey => $tabData)
                        <a href="{{ route('kondisi-lahan.index', array_filter(['status' => $tabKey, 'anggota_id' => request('anggota_id')])) }}"
                           class="inline-flex min-h-10 min-w-0 items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-center text-[11px] font-semibold transition-colors sm:px-3 sm:text-xs {{ $status === $tabKey ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-700' }}"
                           @if($status === $tabKey) aria-current="page" @endif>
                            <span class="truncate">{{ $tabData['label'] }}</span>
                            <span class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] {{ $status === $tabKey ? 'bg-white/20 text-white' : 'bg-white text-slate-500 dark:bg-slate-600 dark:text-slate-200' }}">{{ $tabData['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </nav>

            <form method="GET" action="{{ route('kondisi-lahan.index') }}" id="kondisi-filter-form" data-no-prevent-double="true" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center xl:w-auto xl:shrink-0">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="w-full min-w-0 sm:w-[230px]">
                    @include('components.filter-searchable', [
                        'name' => 'anggota_id',
                        'placeholder' => 'Cari pemilik...',
                        'options' => $anggotas,
                        'displayField' => 'nama',
                        'selected' => request('anggota_id'),
                        'formId' => 'kondisi-filter-form',
                    ])
                </div>
                @if(request()->filled('anggota_id'))
                    <a href="{{ route('kondisi-lahan.index', ['status' => $status]) }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-lg px-3 py-2 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700">Hapus Filter</a>
                @endif
            </form>

            <a href="{{ route('kondisi-lahan.create') }}"
               class="inline-flex min-h-10 w-full shrink-0 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 sm:w-auto">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Observasi Baru
            </a>
        </div>
    </section>
    @forelse($grouped as $group)
        @php $anggota = $group['anggota']; $bloks = $group['bloks']; @endphp
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center gap-2.5 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/80 sm:px-5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                    {{ strtoupper(substr($anggota->nama ?? '?', 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $anggota->nama ?? 'Tidak Diketahui' }}</p>
                    <p class="text-[10px] text-slate-500">{{ $bloks->count() }} blok pada daftar ini</p>
                </div>
            </div>

            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700">
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Blok</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Observasi Terakhir</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Ringkasan Lapangan</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Status Rekomendasi</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase text-slate-400">Langkah Selanjutnya</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($bloks as $blok)
                            @php
                                $kondisi = $blok->kondisiTerbaru;
                                $rbs = $blok->rekomendasiRbsTerbaru;
                                $perluAnalisisUlang = $kondisi && $rbs && $kondisi->updated_at->gt($rbs->updated_at);
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40">
                                <td class="px-4 py-3">
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $blok->nama_blok }}</p>
                                    <p class="text-[10px] text-slate-400">{{ number_format($blok->luas_ha, 2) }} Ha · {{ $blok->anggota?->nama }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if($kondisi)
                                        <p class="font-medium text-slate-700 dark:text-slate-200">{{ $kondisi->tanggal_observasi->format('d/m/Y') }}</p>
                                        <a href="{{ route('kondisi-lahan.edit', $kondisi) }}" class="text-[10px] font-medium text-blue-600 hover:underline">Edit observasi</a>
                                    @else
                                        <span class="font-semibold text-amber-600">Belum diobservasi</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                    @if($kondisi)
                                        <p>{{ $kondisi->warna_daun ?? 'Warna daun belum dicatat' }}</p>
                                        <p class="text-[10px] text-slate-400">Drainase: {{ $kondisi->kondisi_drainase ?? 'belum dicatat' }}</p>
                                    @else
                                        <span class="text-slate-400">Isi fakta lapangan terlebih dahulu.</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-recommendation-status :recommendation="$rbs" :show-stage="false" compact />
                                    @if($perluAnalisisUlang)
                                        <span class="mt-1 block text-[9px] font-semibold text-amber-600">Observasi berubah</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-next-block-action :blok="$blok" compact />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
        </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-700 sm:hidden">
                @foreach($bloks as $blok)
                    @php
                        $kondisi = $blok->kondisiTerbaru;
                        $rbs = $blok->rekomendasiRbsTerbaru;
                        $dataBelumCukup = $rbs && (
                            ! $rbs->data_cukup
                            || in_array($rbs->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
                            || $rbs->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA'
                        );
                    @endphp
                    <article class="space-y-3 px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $blok->nama_blok }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">{{ number_format($blok->luas_ha, 2) }} Ha &middot; {{ $kondisi?->tanggal_observasi?->format('d/m/Y') ?? 'belum diobservasi' }}</p>
                            </div>
                            <x-recommendation-status :recommendation="$rbs" :show-stage="false" compact />
                        </div>
                        <div class="grid gap-2">
                            <x-next-block-action :blok="$blok" class="w-full" compact />
                            @if($kondisi && ! $dataBelumCukup)
                                <a href="{{ route('kondisi-lahan.edit', $kondisi) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-[11px] font-semibold text-slate-700 hover:border-emerald-400 hover:text-emerald-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                    Edit data observasi
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
    </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Tidak ada blok pada status ini.</p>
            <p class="mt-1 text-xs text-slate-400">Pilih tab lain atau ubah filter pemilik.</p>
        </div>
    @endforelse
</div>
@endsection