@extends('layouts.app')

@section('title', 'Pelaksanaan Pemupukan')
@section('page-title', 'Pelaksanaan Pemupukan')
@section('page-subtitle', 'Lihat blok yang siap dipupuk, masih menunggu, dan riwayat pemupukan')

@section('content')
<div class="space-y-4">
    @php
        $tabs = [
            'siap' => ['label' => 'Siap Dipupuk', 'short' => 'Siap', 'count' => $workflowStats['siap']],
            'menunggu' => ['label' => 'Belum Siap Dipupuk', 'short' => 'Belum Siap', 'count' => $workflowStats['menunggu']],
            'riwayat' => ['label' => 'Riwayat Pemupukan', 'short' => 'Riwayat', 'count' => $workflowStats['riwayat']],
        ];
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800" aria-label="Kontrol pelaksanaan pemupukan">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center">
            <nav class="min-w-0 flex-1" aria-label="Tahap pelaksanaan pemupukan">
                <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-700/70">
                    @foreach($tabs as $tabKey => $tabData)
                        <a href="{{ route('realisasi-pemupukan.index', array_filter(['tab' => $tabKey, 'anggota_id' => request('anggota_id')])) }}"
                           class="inline-flex min-h-10 min-w-0 items-center justify-center gap-1 rounded-lg px-2 py-2 text-center text-[11px] font-semibold transition-colors sm:gap-2 sm:px-3 sm:text-xs {{ $tab === $tabKey ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-700' }}"
                           @if($tab === $tabKey) aria-current="page" @endif>
                            <span class="truncate sm:hidden">{{ $tabData['short'] }}</span>
                            <span class="hidden truncate sm:inline">{{ $tabData['label'] }}</span>
                            <span class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] {{ $tab === $tabKey ? 'bg-white/20 text-white' : 'bg-white text-slate-500 dark:bg-slate-600 dark:text-slate-200' }}">{{ $tabData['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </nav>

            <form method="GET" action="{{ route('realisasi-pemupukan.index') }}" id="realisasi-filter-form" data-no-prevent-double="true" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center xl:w-auto xl:shrink-0">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="w-full min-w-0 sm:w-[230px]">
                    @include('components.filter-searchable', [
                        'name' => 'anggota_id',
                        'placeholder' => 'Cari pemilik...',
                        'options' => $anggotas,
                        'displayField' => 'nama',
                        'selected' => request('anggota_id'),
                        'formId' => 'realisasi-filter-form',
                    ])
                </div>
                @if($tab === 'riwayat')
                    <select name="status_realisasi" onchange="this.form.submit()" class="custom-select min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 sm:w-[145px]">
                        <option value="">Semua Status</option>
                        <option value="SELESAI" {{ request('status_realisasi') === 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                        <option value="SEBAGIAN" {{ request('status_realisasi') === 'SEBAGIAN' ? 'selected' : '' }}>Sebagian</option>
                        <option value="BATAL" {{ request('status_realisasi') === 'BATAL' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                @endif
                @if(request()->hasAny(['anggota_id', 'status_realisasi']))
                    <a href="{{ route('realisasi-pemupukan.index', ['tab' => $tab]) }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-lg px-3 py-2 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700">Hapus Filter</a>
                @endif
            </form>
        </div>
        <p class="mt-2 border-t border-slate-100 px-1 pt-2 text-[11px] text-slate-500 dark:border-slate-700 dark:text-slate-400">
            @if($tab === 'siap')
                Blok pada daftar ini sudah memenuhi syarat dan dapat dipupuk sekarang.
            @elseif($tab === 'menunggu')
                Lihat alasan dan tanggal paling awal pemupukan pada setiap blok.
            @else
                Catatan pemupukan yang telah dilakukan tersimpan pada daftar ini.
            @endif
        </p>
    </section>
    @if($tab === 'siap')

        @forelse($groupedSiap as $group)
            @php $anggota = $group['anggota']; @endphp
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/80">
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $anggota->nama ?? 'Tidak Diketahui' }}</p>
                    <p class="text-[10px] text-slate-500">{{ $group['items']->count() }} blok siap dipupuk</p>
                </div>
                <div class="grid gap-3 p-3" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 22rem), 1fr));">
                    @foreach($group['items'] as $item)
                        @php
                            $rbs = $item['rekomendasi'];
                            $eligibility = $item['eligibility'];
                            $blok = $rbs->blokLahan;
                            $needsObservation = in_array($rbs->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
                                || $rbs->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA';
                        @endphp
                        <article class="rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 dark:border-slate-700 dark:bg-slate-900/30">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $blok->nama_blok }}</p>
                                    <p class="text-[10px] text-slate-500">Tahap {{ $eligibility['active_stage'] }} · {{ number_format($blok->luas_ha, 2) }} Ha</p>
                                </div>
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-[9px] font-bold text-emerald-700">SIAP</span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div class="rounded-lg border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                                    <p class="text-[9px] uppercase text-slate-400">Urea tahap ini</p>
                                    <p class="text-sm font-bold text-amber-700">{{ number_format($eligibility['urea_rencana_kg'], 1) }} kg</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                                    <p class="text-[9px] uppercase text-slate-400">KCl tahap ini</p>
                                    <p class="text-sm font-bold text-cyan-700">{{ number_format($eligibility['kcl_rencana_kg'], 1) }} kg</p>
                                </div>
                            </div>
                            <p class="mt-2 text-[10px] leading-relaxed text-slate-500">{{ $eligibility['reason'] }}</p>
                            <a href="{{ route('realisasi-pemupukan.create', $rbs) }}" class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-emerald-600 px-3 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">Catat Realisasi</a>
                        </article>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Belum ada blok yang siap dipupuk.</p>
                <p class="mt-1 text-xs text-slate-400">Periksa tab Belum Siap Dipupuk atau lengkapi observasi blok.</p>
                <a href="{{ route('rbs.index', ['status' => 'perlu-tindakan']) }}" class="mt-3 inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white">Lihat daftar tindakan</a>
            </div>
        @endforelse
    @elseif($tab === 'menunggu')

        @forelse($groupedMenunggu as $group)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/80">
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $group['anggota']->nama ?? 'Tidak Diketahui' }}</p>
                    <p class="text-[10px] text-slate-500">{{ $group['items']->count() }} blok belum siap dipupuk</p>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($group['items'] as $item)
                        @php
                            $rbs = $item['rekomendasi'];
                            $eligibility = $item['eligibility'];
                            $blok = $rbs->blokLahan;
                            $needsObservation = in_array($rbs->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
                                || $rbs->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA';
                        @endphp
                        <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $blok->nama_blok }}</p>
                                <p class="mt-0.5 text-[10px] font-medium text-blue-700">{{ \App\Services\CurrentApplicationCalculator::labelStatusStage($eligibility['status_stage']) }}</p>
                                <p class="mt-1 text-[10px] text-slate-500">{{ $eligibility['reason'] }}</p>
                                @if($rbs->tanggal_minimum_tahap_berikutnya)
                                    <p class="mt-1 text-[10px] font-semibold text-slate-600">Tanggal minimum: {{ $rbs->tanggal_minimum_tahap_berikutnya->format('d/m/Y') }}</p>
                                @endif
                            </div>
                            @if($needsObservation)
                                <a href="{{ route('kondisi-lahan.edit', $blok->kondisiTerbaru) }}" class="inline-flex min-h-11 w-full shrink-0 items-center justify-center rounded-lg bg-emerald-600 px-3 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 sm:w-auto">Lengkapi Observasi</a>
                            @else
                                <a href="{{ route('rbs.detail', $blok) }}" class="inline-flex min-h-11 w-full shrink-0 items-center justify-center gap-1.5 rounded-lg border border-emerald-300 bg-white px-3 py-2.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:bg-slate-800 dark:text-emerald-300 sm:w-auto">Lihat Detail <span aria-hidden="true">&rarr;</span></a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Tidak ada blok yang sedang menunggu.</p>
            </div>
        @endforelse
    @else
        @forelse($grouped as $group)
            @php $anggota = $group['anggota']; $items = $group['items']; @endphp
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/80 sm:px-5">
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $anggota->nama ?? 'Tidak Diketahui' }}</p>
                    <p class="text-[10px] text-slate-500">{{ $items->where('status_realisasi', '!=', 'BATAL')->count() }} catatan pemupukan</p>
                </div>
                <div class="hidden overflow-x-auto sm:block">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-slate-100 dark:border-slate-700">
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Tanggal</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase text-slate-400">Blok</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-semibold uppercase text-slate-400">Tahap</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase text-slate-400">Urea</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase text-slate-400">KCl</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-semibold uppercase text-slate-400">Status</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase text-slate-400">Aksi</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($items as $realisasi)
                                <tr class="{{ $realisasi->status_realisasi === 'BATAL' ? 'opacity-50' : '' }} hover:bg-slate-50/70 dark:hover:bg-slate-700/40">
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ $realisasi->tanggal_realisasi->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $realisasi->blokLahan->nama_blok ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center text-xs">Tahap {{ $realisasi->tahap }}</td>
                                    <td class="px-4 py-3 text-right text-xs font-semibold">{{ number_format($realisasi->urea_realisasi_kg, 1) }} kg</td>
                                    <td class="px-4 py-3 text-right text-xs font-semibold">{{ number_format($realisasi->kcl_realisasi_kg, 1) }} kg</td>
                                    <td class="px-4 py-3 text-center"><span class="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-semibold text-slate-700">{{ $realisasi->label_status }}</span></td>
                                    <td class="px-4 py-3 text-right"><a href="{{ route('realisasi-pemupukan.show', $realisasi) }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-semibold text-slate-700 transition-colors hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">Lihat Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-700 sm:hidden">
                    @foreach($items as $realisasi)
                        <a href="{{ route('realisasi-pemupukan.show', $realisasi) }}" class="block px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/40">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $realisasi->blokLahan->nama_blok ?? '-' }} · Tahap {{ $realisasi->tahap }}</p>
                                <span class="text-[9px] font-semibold text-slate-500">{{ $realisasi->label_status }}</span>
                            </div>
                            <p class="mt-1 text-[10px] text-slate-500">{{ $realisasi->tanggal_realisasi->format('d/m/Y') }} · Urea {{ number_format($realisasi->urea_realisasi_kg, 1) }} kg · KCl {{ number_format($realisasi->kcl_realisasi_kg, 1) }} kg</p>
                            <p class="mt-2 text-[10px] font-semibold text-emerald-700">Lihat detail <span aria-hidden="true">&rarr;</span></p>

                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800"><p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Belum ada riwayat pemupukan.</p></div>
        @endforelse
    @endif
</div>
@endsection