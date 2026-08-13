@extends('layouts.app')

@section('title', 'Detail ' . $blokLahan->nama_blok)
@section('page-title', $blokLahan->nama_blok)
@section('page-subtitle', 'Detail informasi blok lahan')

@section('content')
<div class="space-y-5">

    {{-- Breadcrumb / Konteks Anggota --}}
    <nav class="flex items-center gap-2 text-sm" aria-label="Breadcrumb">
        <a href="{{ route('blok-lahan.index') }}" class="text-slate-500 hover:text-emerald-600 transition-colors">Data Kebun</a>
        <svg class="w-3.5 h-3.5 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @if($blokLahan->anggota)
            <span class="text-slate-500">{{ $blokLahan->anggota->nama }}</span>
            <svg class="w-3.5 h-3.5 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @endif
        <span class="font-semibold text-slate-800">{{ $blokLahan->nama_blok }}</span>
    </nav>

    {{-- Tombol Aksi --}}
    <div class="flex gap-3">
        <a href="{{ route('blok-lahan.index') }}" class="flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-sm rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
        <a href="{{ route('blok-lahan.edit', $blokLahan) }}" class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm rounded-xl transition-colors shadow-sm shadow-emerald-600/10">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </a>
    </div>

    {{-- Banner Anggota + Blok Saudara Cepat --}}
    @if($blokLahan->anggota)
    <div class="flex flex-col gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 sm:flex-row sm:items-center">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">
                {{ strtoupper(substr($blokLahan->anggota->nama, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-xs text-emerald-600 font-semibold">Pemilik Lahan</p>
                <p class="font-bold text-slate-800 text-sm">{{ $blokLahan->anggota->nama }}</p>
                @if($blokLahan->anggota->no_hp)
                    <p class="text-xs text-slate-500">{{ $blokLahan->anggota->no_hp }}</p>
                @endif
            </div>
        </div>
        @if($siblingBloks->isNotEmpty())
        <div class="sm:ml-auto">
            <p class="text-[10px] text-slate-400 uppercase tracking-wide mb-1.5">Blok lain milik anggota ini</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($siblingBloks as $sibling)
                    <a href="{{ route('blok-lahan.show', $sibling) }}"
                       class="inline-flex items-center gap-1 rounded-lg border border-white bg-white px-2.5 py-1 text-[10px] font-semibold text-slate-600 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 transition-colors shadow-sm">
                        {{ $sibling->nama_blok }}
                        <span class="text-slate-400">· {{ number_format($sibling->luas_ha, 2) }} Ha</span>
                    </a>
                @endforeach
                <a href="{{ route('blok-lahan.create', ['anggota_id' => $blokLahan->anggota_id]) }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-dashed border-emerald-300 bg-white/70 px-2.5 py-1 text-[10px] font-semibold text-emerald-600 hover:bg-emerald-50 transition-colors">
                    + Tambah Blok
                </a>
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-emerald-600 mb-4">Info Lahan</h3>
            <div class="space-y-3">
                <div><p class="text-xs text-slate-400">Nama Blok</p><p class="text-sm font-semibold text-slate-900">{{ $blokLahan->nama_blok }}</p></div>
                <div><p class="text-xs text-slate-400">Nama Pemilik Lahan</p><p class="text-sm font-semibold text-slate-900">{{ $blokLahan->anggota?->nama ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-400">Luas Lahan</p><p class="text-sm font-semibold text-slate-900">{{ number_format($blokLahan->luas_ha, 2) }} Ha</p></div>
                @if(!$blokLahan->jumlah_pohon)
                    <div><p class="text-xs text-slate-400">SPH</p><p class="text-sm font-semibold text-slate-900">{{ number_format($blokLahan->sph) }} pohon/Ha</p></div>
                @endif
                <div><p class="text-xs text-slate-400">Total Pohon</p>
                    <p class="text-sm font-semibold text-slate-900">
                        {{ number_format($blokLahan->jumlah_pokok_aktual) }} pohon
                        @if($blokLahan->jumlah_pohon)
                            <span class="text-xs text-emerald-600 ml-1">(Aktual)</span>
                        @else
                            <span class="text-xs text-amber-600 ml-1">(Estimasi Luas × SPH)</span>
                        @endif
                    </p>
                </div>
                @if($siblingBloks->isNotEmpty())
                <div class="pt-2 border-t border-slate-100">
                    <p class="text-xs text-slate-400">Total luas semua blok anggota ini</p>
                    <p class="text-sm font-semibold text-slate-900">
                        {{ number_format($siblingBloks->sum('luas_ha') + $blokLahan->luas_ha, 2) }} Ha
                        <span class="text-xs text-slate-400 font-normal">({{ $siblingBloks->count() + 1 }} blok)</span>
                    </p>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-emerald-600 mb-4">Kriteria Agronomis</h3>
            @if($blokLahan->tahun_tanam)
            <div class="space-y-3">
                <div><p class="text-xs text-slate-400">Tahun Tanam</p><p class="text-sm font-semibold text-slate-900">{{ $blokLahan->tahun_tanam }}</p></div>
                <div><p class="text-xs text-slate-400">Umur Tanaman</p><p class="text-sm font-semibold text-emerald-600">{{ $blokLahan->umur_tanaman }} tahun</p></div>
                <div><p class="text-xs text-slate-400">Kategori Umur</p><p class="text-sm font-semibold text-slate-900">{{ $blokLahan->kategori_umur }}</p></div>
                <div><p class="text-xs text-slate-400">Topografi</p><p class="text-sm font-semibold text-slate-900">{{ $blokLahan->topografi }}</p></div>
            </div>
            @else
            <p class="text-sm text-slate-500">Kriteria belum diisi. <a href="{{ route('blok-lahan.edit', $blokLahan) }}" class="text-emerald-600 hover:underline font-semibold">Edit sekarang</a></p>
            @endif
        </div>

        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-emerald-600 mb-4">Kebutuhan Pupuk</h3>
            @if($rbs = $blokLahan->rekomendasiRbsTerbaru)
            <div class="space-y-3">
                <div><p class="text-xs text-slate-400">Tanggal Analisis</p><p class="text-sm font-semibold text-slate-900">{{ $rbs->tanggal_analisis->format('d/m/Y') }}</p></div>
                @if($rbs->dosis_urea)
                <div><p class="text-xs text-slate-400">Dosis Urea</p><p class="text-sm font-semibold text-amber-700">{{ $rbs->dosis_urea }} kg/pokok</p></div>
                <div><p class="text-xs text-slate-400">Dosis KCl</p><p class="text-sm font-semibold text-cyan-700">{{ $rbs->dosis_kcl }} kg/pokok</p></div>
                <div><p class="text-xs text-slate-400">Total Urea</p><p class="text-sm font-semibold text-slate-900">{{ number_format($rbs->total_urea, 1) }} kg ({{ $rbs->karung_urea }} karung)</p></div>
                <div><p class="text-xs text-slate-400">Total KCl</p><p class="text-sm font-semibold text-slate-900">{{ number_format($rbs->total_kcl, 1) }} kg ({{ $rbs->karung_kcl }} karung)</p></div>
                @endif
                <div><p class="text-xs text-slate-400">Status</p>
                    <x-recommendation-status :recommendation="$rbs" />
                </div>
            </div>
            @else
            <p class="text-sm text-slate-500">Belum ada analisis. <a href="{{ route('rbs.index') }}" class="text-emerald-600 hover:underline font-semibold">Analisis sekarang</a></p>
            @endif
        </div>
    </div>

    {{-- Hasil Analisis RBS --}}
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Hasil Analisis Rule-Based System
            </h3>
            <a href="{{ route('kondisi-lahan.create', ['blok_lahan_id' => $blokLahan->id]) }}"
               class="text-xs text-emerald-600 hover:underline font-medium">
                + Input Kondisi Baru
            </a>
        </div>
        @include('rbs.partials._hasil_rbs', ['blokLahan' => $blokLahan])
    </div>
</div>
@endsection
