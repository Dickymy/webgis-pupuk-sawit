@extends('layouts.app')

@section('title', 'Analisis RBS')
@section('page-title', 'Analisis Rule-Based System')
@section('page-subtitle', 'Evaluasi kondisi lahan & rekomendasi pemupukan')

@section('content')
@php
    $totalBlok     = $stats['total'];
    $sudahAnalisis = $stats['sudah_analisis'];
    $darurat       = $stats['darurat'];
    $segera        = $stats['segera'];
    $belumKondisi  = $stats['belum_kondisi'];
@endphp

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-4">
    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm">
        <p class="text-[11px] text-slate-500 mb-0.5">Total Blok</p>
        <p class="text-xl sm:text-2xl font-bold text-slate-900">{{ $totalBlok }}</p>
        <p class="text-[10px] text-slate-400">terdaftar</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm">
        <p class="text-[11px] text-slate-500 mb-0.5">Sudah Dianalisis</p>
        <p class="text-xl sm:text-2xl font-bold text-blue-600">{{ $sudahAnalisis }}</p>
        <p class="text-[10px] text-slate-400">dari {{ $totalBlok }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm border-l-4 border-l-red-500">
        <p class="text-[11px] text-slate-500 mb-0.5">Def. Berat</p>
        <p class="text-xl sm:text-2xl font-bold text-red-600">{{ $darurat }}</p>
        <p class="text-[10px] text-slate-400">penanganan</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm border-l-4 border-l-orange-400">
        <p class="text-[11px] text-slate-500 mb-0.5">Perlu Pupuk</p>
        <p class="text-xl sm:text-2xl font-bold text-orange-500">{{ $segera }}</p>
        <p class="text-[10px] text-slate-400">kurang hara</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm">
        <p class="text-[11px] text-slate-500 mb-0.5">Belum Ada Kondisi</p>
        <p class="text-xl sm:text-2xl font-bold text-slate-400">{{ $belumKondisi }}</p>
        <p class="text-[10px] text-slate-400">perlu input</p>
    </div>
</div>

{{-- Filter & Action --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
    <form method="GET" action="{{ route('rbs.index') }}" id="rbs-filter-form" data-no-prevent-double="true" class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
        <div class="sm:min-w-[180px]">
            @include('components.filter-searchable', [
                'name' => 'anggota_id',
                'placeholder' => 'Cari anggota...',
                'options' => $anggotas,
                'displayField' => 'nama',
                'selected' => request('anggota_id'),
                'formId' => 'rbs-filter-form',
            ])
        </div>

        @if($blokFilter->isNotEmpty())
        <select name="blok_lahan_id" onchange="this.form.submit()"
            class="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-slate-700 font-medium focus:outline-none focus:ring-1 focus:ring-emerald-500 w-full sm:w-auto sm:min-w-[150px]">
            <option value="">Semua Blok</option>
            @foreach($blokFilter as $bf)
                <option value="{{ $bf->id }}" {{ request('blok_lahan_id') == $bf->id ? 'selected' : '' }}>{{ $bf->nama_blok }}</option>
            @endforeach
        </select>
        @endif

        @if(request()->hasAny(['anggota_id', 'blok_lahan_id']))
            <a href="{{ route('rbs.index') }}" class="text-xs text-slate-500 hover:text-slate-700 font-medium">Reset</a>
        @endif
    </form>

    <form action="{{ route('rbs.analisisSemua') }}" method="POST" id="form-analisis-semua" class="w-full sm:w-auto">
        @csrf
        <button type="button" onclick="showConfirm('Jalankan analisis RBS untuk semua blok yang memiliki data kondisi?', function(){ document.getElementById('form-analisis-semua').submit(); })"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition-colors shadow-sm w-full sm:w-auto justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Analisis Semua Blok
        </button>
    </form>
</div>

@if($belumKondisi > 0)
<div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs text-amber-700 mb-4">
    ⚠ {{ $belumKondisi }} blok belum memiliki data kondisi lahan. <a href="{{ route('kondisi-lahan.create') }}" class="font-semibold underline">Input sekarang →</a>
</div>
@endif

{{-- Grouped by Anggota --}}
@forelse($grouped as $group)
@php
    $anggota = $group['anggota'];
    $bloks = $group['bloks'];
@endphp
<div class="anggota-group-card bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-4" data-nama-anggota="{{ strtolower($anggota->nama ?? '') }}">
    {{-- Header anggota --}}
    <div class="px-4 sm:px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
            {{ strtoupper(substr($anggota->nama ?? '?', 0, 1)) }}
        </div>
        <div>
            <p class="font-bold text-slate-800 text-sm">{{ $anggota->nama ?? 'Tidak Diketahui' }}</p>
            <p class="text-[10px] text-slate-500">{{ $bloks->count() }} blok</p>
        </div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Blok Lahan</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Umur</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Kondisi Terakhir</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Status</th>
                    <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-400 uppercase">Confidence</th>
                    <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-400 uppercase">Rule</th>
                    <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-400 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($bloks as $blok)
                @php
                    $rbs = $blok->rekomendasiRbsTerbaru;
                    $kondisi = $blok->kondisiTerbaru;
                    $perluAnalisisUlang = $kondisi && $rbs && $kondisi->updated_at->gt($rbs->updated_at);
                    $dataBlokKurang = !$blok->tahun_tanam || !$blok->jenis_tanah || !$blok->topografi;
                    $statusConfig = match($rbs?->status_kebutuhan_dominan) {
                        'Darurat' => ['bg' => 'bg-red-50 text-red-700', 'label' => 'Defisiensi Berat'],
                        'Segera'  => ['bg' => 'bg-orange-50 text-orange-700', 'label' => 'Perlu Pupuk'],
                        'Normal'  => ['bg' => 'bg-emerald-50 text-emerald-700', 'label' => 'Sehat'],
                        'Tunda'   => ['bg' => 'bg-slate-100 text-slate-600', 'label' => 'Tunda Pupuk'],
                        default   => ['bg' => 'bg-blue-50 text-blue-700', 'label' => 'Belum Dicek'],
                    };
                @endphp
                <tr class="block-row hover:bg-slate-50/50" data-nama-blok="{{ strtolower($blok->nama_blok ?? '') }}">
                    <td class="px-4 py-2.5">
                        <p class="font-semibold text-slate-800 text-xs">{{ $blok->nama_blok }}</p>
                        <p class="text-[10px] text-slate-400">{{ number_format($blok->luas_ha, 2) }} Ha · SPH {{ $blok->sph }}</p>
                        @if($dataBlokKurang)
                        <span class="text-[9px] text-red-500 font-medium">⚠️ Data blok belum lengkap</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs text-slate-600">
                        @if($blok->umur_tanaman !== null)
                            {{ $blok->umur_tanaman }} thn
                            <span class="text-[10px] text-slate-400 block">{{ $blok->kategori_umur }}</span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs">
                        @if($kondisi)
                            <span class="text-slate-700">{{ $kondisi->tanggal_observasi->format('d/m/Y') }}</span>
                        @else
                            <span class="text-amber-600 font-medium">Belum ada</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusConfig['bg'] }}">{{ $statusConfig['label'] }}</span>
                        @if($perluAnalisisUlang)
                        <span class="block mt-0.5 text-[9px] text-amber-600 font-medium">⚠️ Data berubah</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-center text-xs">
                        @if($rbs)
                            @php $confColor = match($rbs->confidence_label) { 'Tinggi' => 'text-green-600', 'Sedang' => 'text-blue-600', default => 'text-amber-600' }; @endphp
                            <span class="font-semibold {{ $confColor }}">{{ $rbs->confidence_score }}%</span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-center text-xs text-slate-600">
                        {{ $rbs ? $rbs->jumlah_rule_terpicu : '—' }}
                    </td>
                    <td class="px-4 py-2.5 text-right">
                        <div class="flex items-center gap-1.5 justify-end">
                            @if(!$kondisi)
                                <a href="{{ route('kondisi-lahan.create', ['blok_lahan_id' => $blok->id]) }}" class="inline-flex items-center gap-1 px-2 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-medium rounded-md hover:bg-amber-100 transition-colors">
                                    Input Kondisi
                                </a>
                            @else
                                <form action="{{ route('rbs.analisis', $blok) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-medium rounded-md hover:bg-emerald-100 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        Analisis
                                    </button>
                                </form>
                                @if($rbs)
                                @if($rbs->is_tahap_siap && ($rbs->urea_aplikasi_saat_ini > 0 || $rbs->kcl_aplikasi_saat_ini > 0))
                                <a href="{{ route('realisasi-pemupukan.create', $rbs) }}" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-medium rounded-md hover:bg-blue-100 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Realisasi
                                </a>
                                @endif
                                <a href="{{ route('rbs.detail', $blok) }}" class="inline-flex items-center gap-1 px-2 py-1 bg-slate-50 text-slate-600 border border-slate-200 text-[10px] font-medium rounded-md hover:bg-slate-100 transition-colors">
                                    Detail
                                </a>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="sm:hidden divide-y divide-slate-100">
        @foreach($bloks as $blok)
        @php
            $rbs = $blok->rekomendasiRbsTerbaru;
            $kondisi = $blok->kondisiTerbaru;
            $perluAnalisisUlang = $kondisi && $rbs && $kondisi->updated_at->gt($rbs->updated_at);
            $dataBlokKurang = !$blok->tahun_tanam || !$blok->jenis_tanah || !$blok->topografi;
            $statusConfig = match($rbs?->status_kebutuhan_dominan) {
                'Darurat' => ['bg' => 'bg-red-100 text-red-800', 'label' => 'Defisiensi Berat'],
                'Segera'  => ['bg' => 'bg-orange-100 text-orange-800', 'label' => 'Perlu Pupuk'],
                'Normal'  => ['bg' => 'bg-emerald-100 text-emerald-800', 'label' => 'Sehat'],
                'Tunda'   => ['bg' => 'bg-slate-100 text-slate-700', 'label' => 'Tunda Pupuk'],
                default   => ['bg' => 'bg-blue-50 text-blue-700', 'label' => 'Belum Dicek'],
            };
        @endphp
        <div class="block-row px-4 py-3" data-nama-blok="{{ strtolower($blok->nama_blok ?? '') }}">
            {{-- Row 1: Nama blok + Status --}}
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <div>
                    <p class="font-semibold text-slate-800 text-xs">{{ $blok->nama_blok }}</p>
                    <p class="text-[10px] text-slate-400">{{ number_format($blok->luas_ha, 2) }} Ha · {{ $blok->umur_tanaman !== null ? $blok->umur_tanaman.' thn' : '—' }}</p>
                    @if($dataBlokKurang)
                    <span class="text-[8px] text-red-500 font-medium">⚠️ Data blok belum lengkap</span>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-0.5 flex-shrink-0">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-semibold {{ $statusConfig['bg'] }}">{{ $statusConfig['label'] }}</span>
                    @if($perluAnalisisUlang)
                    <span class="text-[8px] text-amber-600 font-medium">⚠️ Data berubah</span>
                    @endif
                </div>
            </div>

            {{-- Row 2: Info + Aksi --}}
            <div class="flex items-center justify-between gap-2">
                <div class="text-[10px] text-slate-500">
                    @if($kondisi)
                        Kondisi: {{ $kondisi->tanggal_observasi->format('d/m/Y') }}
                        @if($rbs) · {{ $rbs->jumlah_rule_terpicu }} rule @endif
                    @else
                        <span class="text-amber-600">Belum ada data kondisi</span>
                    @endif
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    @if(!$kondisi)
                        <a href="{{ route('kondisi-lahan.create', ['blok_lahan_id' => $blok->id]) }}" class="px-2 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-[9px] font-medium rounded-md">Input</a>
                    @else
                        <form action="{{ route('rbs.analisis', $blok) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-medium rounded-md">Analisis</button>
                        </form>
                        @if($rbs)
                        @if($rbs->is_tahap_siap && ($rbs->urea_aplikasi_saat_ini > 0 || $rbs->kcl_aplikasi_saat_ini > 0))
                        <a href="{{ route('realisasi-pemupukan.create', $rbs) }}" class="px-2 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-[9px] font-medium rounded-md">Realisasi</a>
                        @endif
                        <a href="{{ route('rbs.detail', $blok) }}" class="px-2 py-1 bg-slate-50 text-slate-600 border border-slate-200 text-[9px] font-medium rounded-md">Detail</a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@empty
<div class="bg-white border border-slate-200 rounded-xl p-8 sm:p-12 text-center shadow-sm">
    <p class="text-slate-400 text-sm mb-2">Tidak ada data blok lahan yang sesuai filter.</p>
    <a href="{{ route('blok-lahan.create') }}" class="text-emerald-600 text-sm font-semibold hover:underline">Tambah blok lahan →</a>
</div>
@endforelse

@endsection
