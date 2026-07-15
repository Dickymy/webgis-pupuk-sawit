@extends('layouts.app')

@section('title', 'Laporan Pemupukan')
@section('page-title', 'Laporan & Rekap Pemupukan')
@section('page-subtitle', 'Rekapitulasi kebutuhan pupuk per anggota Kelompok Tani Suluh Tani')

@section('content')
<div class="space-y-4 sm:space-y-5">

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm">
            <p class="text-xs text-slate-500 font-medium mb-0.5">Total Anggota</p>
            <p class="text-xl sm:text-2xl font-extrabold text-slate-900">{{ $laporanPerAnggota->count() }}</p>
            <p class="text-[10px] text-slate-400">memiliki rekomendasi</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm border-l-4 border-l-amber-400">
            <p class="text-xs text-slate-500 font-medium mb-0.5">Total Urea</p>
            <p class="text-xl sm:text-2xl font-extrabold text-amber-700">{{ number_format($totalUrea, 0) }} <span class="text-xs font-normal">kg</span></p>
            <p class="text-[10px] text-slate-400">{{ $karungUrea }} karung</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm border-l-4 border-l-emerald-500">
            <p class="text-xs text-slate-500 font-medium mb-0.5">Total KCl</p>
            <p class="text-xl sm:text-2xl font-extrabold text-emerald-700">{{ number_format($totalKcl, 0) }} <span class="text-xs font-normal">kg</span></p>
            <p class="text-[10px] text-slate-400">{{ $karungKcl }} karung</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm">
            <p class="text-xs text-slate-500 font-medium mb-0.5">Blok Layak Pupuk</p>
            <p class="text-xl sm:text-2xl font-extrabold text-blue-600">{{ $blokLayakTotal }}</p>
            <p class="text-[10px] text-slate-400">dari {{ $rekap->count() }} blok dianalisis</p>
        </div>
    </div>

    {{-- Keterangan --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-xs text-blue-800">
        <span class="font-semibold">ℹ Catatan:</span> Blok layak pupuk = berstatus <strong>Sehat</strong> dan <strong>Perlu Pupuk</strong>. Blok Defisiensi Berat dan Tunda perlu penanganan masalah terlebih dahulu.
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm relative z-20">
        <form method="GET" action="{{ route('laporan.index') }}" id="laporan-filter-form" data-no-prevent-double="true" class="flex flex-col sm:flex-row flex-wrap items-start sm:items-end gap-2 sm:gap-3">
            <div class="w-full sm:w-auto sm:min-w-[180px] relative z-30">
                <label class="block text-xs text-slate-500 font-semibold mb-1">Pemilik</label>
                @include('components.filter-searchable', [
                    'name' => 'anggota_id',
                    'placeholder' => 'Cari anggota...',
                    'options' => $anggotas,
                    'displayField' => 'nama',
                    'selected' => request('anggota_id'),
                    'formId' => 'laporan-filter-form',
                ])
            </div>

            @if($blokFilter->isNotEmpty())
            <div class="w-full sm:w-auto relative">
                <label class="block text-xs text-slate-500 font-semibold mb-1">Blok</label>
                <div class="relative">
                    <select name="blok_lahan_id" onchange="this.form.submit()"
                        style="background-image: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important;"
                        class="w-full sm:w-auto pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:min-w-[140px] cursor-pointer">
                        <option value="">Semua Blok</option>
                        @foreach($blokFilter as $bf)
                            <option value="{{ $bf->id }}" {{ request('blok_lahan_id') == $bf->id ? 'selected' : '' }}>{{ $bf->nama_blok }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
            @endif

            <div class="w-full sm:w-auto relative">
                <label class="block text-xs text-slate-500 font-semibold mb-1">Status</label>
                <div class="relative">
                    <select name="status_kebutuhan_dominan" onchange="this.form.submit()"
                        style="background-image: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important;"
                        class="w-full sm:w-auto pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:min-w-[160px] cursor-pointer">
                        <option value="">Semua Status</option>
                        @foreach(['Darurat' => 'Defisiensi Berat', 'Segera' => 'Perlu Pupuk', 'Normal' => 'Sehat', 'Tunda' => 'Tunda Pupuk'] as $val => $label)
                            <option value="{{ $val }}" {{ request('status_kebutuhan_dominan') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto sm:ml-auto pt-1 sm:pt-0">
                @if(request()->hasAny(['status_kebutuhan_dominan', 'anggota_id', 'blok_lahan_id']))
                <a href="{{ route('laporan.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-medium rounded-lg transition-colors">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Laporan per Anggota --}}
    @forelse($laporanPerAnggota as $group)
    @php
        $anggota = $group['anggota'];
        $items = $group['items'];
    @endphp
    <div class="anggota-group-card bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden" data-nama-anggota="{{ strtolower($anggota->nama ?? '') }}">
        {{-- Header anggota --}}
        <div class="px-4 sm:px-5 py-3 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr($anggota->nama ?? '?', 0, 1)) }}
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-sm">{{ $anggota->nama ?? 'Tidak Diketahui' }}</p>
                    <p class="text-[10px] text-slate-500">{{ $group['jumlah_blok'] }} blok · {{ number_format($group['total_luas'], 2) }} Ha</p>
                </div>
            </div>
            <div class="flex items-center gap-3 text-xs">
                @if($group['subtotal_urea'] > 0)
                <span class="text-amber-700 font-bold">Urea: {{ number_format($group['subtotal_urea'], 1) }} kg</span>
                @endif
                @if($group['subtotal_kcl'] > 0)
                <span class="text-cyan-700 font-bold">KCl: {{ number_format($group['subtotal_kcl'], 1) }} kg</span>
                @endif
                @if($group['subtotal_urea'] == 0 && $group['subtotal_kcl'] == 0)
                <span class="text-slate-400 font-medium">Belum ada kebutuhan</span>
                @endif
            </div>
        </div>

        {{-- Desktop Table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Blok</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Luas</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Status</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-400 uppercase">Urea (kg)</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-400 uppercase">KCl (kg)</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Tanggal</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($items as $r)
                    @php
                        $sc = match($r->status_kebutuhan_dominan) {
                            'Darurat' => 'bg-red-50 text-red-700',
                            'Segera'  => 'bg-orange-50 text-orange-700',
                            'Normal'  => 'bg-emerald-50 text-emerald-700',
                            default   => 'bg-slate-100 text-slate-600',
                        };
                        $layak = in_array($r->status_kebutuhan_dominan, ['Normal', 'Segera']);
                    @endphp
                    <tr class="block-row hover:bg-slate-50/50" data-nama-blok="{{ strtolower($r->blokLahan->nama_blok ?? '') }}">
                        <td class="px-4 py-2.5 font-medium text-slate-800 text-xs">{{ $r->blokLahan->nama_blok }}</td>
                        <td class="px-4 py-2.5 text-xs text-slate-600">{{ number_format($r->blokLahan->luas_ha, 2) }} Ha</td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $sc }}">{{ \App\Models\RekomendasiRbs::labelStatus($r->status_kebutuhan_dominan) }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right text-xs font-semibold {{ $layak ? 'text-amber-700' : 'text-slate-300' }}">
                            {{ $layak && $r->total_urea ? number_format($r->total_urea, 1) : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right text-xs font-semibold {{ $layak ? 'text-cyan-700' : 'text-slate-300' }}">
                            {{ $layak && $r->total_kcl ? number_format($r->total_kcl, 1) : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-xs text-slate-500">{{ $r->tanggal_analisis->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5 text-right">
                            <div class="flex items-center gap-1 justify-end">
                                <a href="{{ route('laporan.show', $r) }}" class="p-1 rounded-md bg-slate-50 border border-slate-200 text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all" title="Detail">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('laporan.pdf', $r) }}" class="p-1 rounded-md bg-slate-50 border border-slate-200 text-slate-500 hover:text-red-600 hover:bg-red-50 transition-all" title="PDF">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                {{-- Subtotal row --}}
                @if($group['blok_layak'] > 0)
                <tfoot>
                    <tr class="border-t border-slate-200 bg-slate-50/50">
                        <td colspan="3" class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase">Subtotal ({{ $group['blok_layak'] }} blok layak)</td>
                        <td class="px-4 py-2 text-right text-xs font-bold text-amber-700">{{ number_format($group['subtotal_urea'], 1) }}</td>
                        <td class="px-4 py-2 text-right text-xs font-bold text-cyan-700">{{ number_format($group['subtotal_kcl'], 1) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="sm:hidden divide-y divide-slate-100">
            @foreach($items as $r)
            @php
                $sc = match($r->status_kebutuhan_dominan) {
                    'Darurat' => 'bg-red-100 text-red-800',
                    'Segera'  => 'bg-orange-100 text-orange-800',
                    'Normal'  => 'bg-emerald-100 text-emerald-800',
                    default   => 'bg-slate-100 text-slate-700',
                };
                $layak = in_array($r->status_kebutuhan_dominan, ['Normal', 'Segera']);
            @endphp
            <div class="block-row px-4 py-3" data-nama-blok="{{ strtolower($r->blokLahan->nama_blok ?? '') }}">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <p class="font-semibold text-slate-800 text-xs">{{ $r->blokLahan->nama_blok }} <span class="font-normal text-slate-400">· {{ number_format($r->blokLahan->luas_ha, 2) }} Ha</span></p>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-semibold {{ $sc }} flex-shrink-0">{{ \App\Models\RekomendasiRbs::labelStatus($r->status_kebutuhan_dominan) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <div class="flex flex-wrap gap-x-3 text-[10px] text-slate-600">
                        @if($layak && $r->total_urea)
                        <span class="text-amber-700 font-semibold">Urea: {{ number_format($r->total_urea, 1) }} kg</span>
                        @endif
                        @if($layak && $r->total_kcl)
                        <span class="text-cyan-700 font-semibold">KCl: {{ number_format($r->total_kcl, 1) }} kg</span>
                        @endif
                        @if(!$layak)
                        <span class="text-slate-400">{{ $r->status_kebutuhan_dominan === 'Darurat' ? 'Tangani masalah dulu' : 'Ditunda' }}</span>
                        @endif
                        <span class="text-slate-400">{{ $r->tanggal_analisis->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <a href="{{ route('laporan.show', $r) }}" class="p-1 rounded-md bg-slate-50 border border-slate-200 text-slate-500 text-[10px]">Detail</a>
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Mobile subtotal --}}
            @if($group['blok_layak'] > 0)
            <div class="px-4 py-2.5 bg-slate-50 flex items-center justify-between text-[10px]">
                <span class="font-bold text-slate-500 uppercase">Subtotal</span>
                <div class="flex gap-3">
                    <span class="text-amber-700 font-bold">Urea: {{ number_format($group['subtotal_urea'], 1) }} kg</span>
                    <span class="text-cyan-700 font-bold">KCl: {{ number_format($group['subtotal_kcl'], 1) }} kg</span>
                </div>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white border border-slate-200 rounded-xl p-8 sm:p-12 text-center shadow-sm">
        <p class="text-slate-400 text-sm mb-2">Belum ada data laporan.</p>
        <a href="{{ route('rbs.index') }}" class="text-emerald-600 text-sm font-semibold hover:underline">Jalankan analisis RBS terlebih dahulu →</a>
    </div>
    @endforelse

</div>

@endsection
