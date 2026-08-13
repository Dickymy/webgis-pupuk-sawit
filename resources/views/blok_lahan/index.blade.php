@extends('layouts.app')

@section('title', 'Data Kebun')
@section('page-title', 'Data Kebun')
@section('page-subtitle', 'Kelola blok lahan dan anggota kelompok tani')

@section('content')
<div class="space-y-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800" aria-label="Kontrol data blok lahan">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center">
            @include('components.data-kebun-tabs')

            <p class="shrink-0 px-1 text-xs text-slate-500 dark:text-slate-400">
                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $totalBlok }}</span> blok terdaftar
            </p>

            <form method="GET" action="{{ route('blok-lahan.index') }}" id="blok-filter-form" data-no-prevent-double="true" class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center xl:justify-end">
                <div class="w-full min-w-0 sm:w-[220px] sm:flex-none">
                    @include('components.filter-searchable', [
                        'name' => 'anggota_id',
                        'placeholder' => 'Cari pemilik...',
                        'options' => $anggotas,
                        'displayField' => 'nama',
                        'selected' => request('anggota_id'),
                        'formId' => 'blok-filter-form',
                    ])
                </div>
                <div class="relative w-full shrink-0 sm:w-[170px]">
                    <select name="status" onchange="this.form.submit()"
                        class="custom-select min-h-10 w-full rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-medium text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                        <option value="">Semua Status</option>
                        @foreach(['TERINDIKASI_DEFISIENSI' => 'Ditemukan Gejala pada Daun', 'NORMAL_VISUAL' => 'Tidak Ditemukan Gejala pada Daun', 'PERLU_VERIFIKASI' => 'Data Pemeriksaan Belum Lengkap', 'Belum' => 'Belum Dianalisis'] as $val => $label)
                            <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
                @if(request()->hasAny(['anggota_id','status']))
                    <a href="{{ route('blok-lahan.index') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-lg px-3 py-2 text-xs font-medium text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700">Hapus Filter</a>
                @endif
            </form>

            <a href="{{ route('blok-lahan.create') }}"
               class="inline-flex min-h-10 w-full shrink-0 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 sm:w-auto">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Blok
            </a>
        </div>
    </section>
    {{-- Grouped by Anggota --}}
    @forelse($grouped as $group)
    @php $anggota = $group['anggota']; $bloks = $group['bloks']; @endphp
    <div class="anggota-group-card bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden" data-nama-anggota="{{ strtolower($anggota->nama ?? '') }}">
        {{-- Header --}}
        <div class="px-4 sm:px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                {{ strtoupper(substr($anggota->nama ?? '?', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-slate-800 text-sm">{{ $anggota->nama ?? 'Tidak Diketahui' }}</p>
                <p class="text-[10px] text-slate-500">{{ $bloks->count() }} blok · {{ number_format($bloks->sum('luas_ha'), 2) }} Ha total</p>
            </div>
            <a href="{{ route('blok-lahan.create', ['anggota_id' => $anggota->id]) }}"
               class="flex-shrink-0 inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700 transition hover:bg-emerald-50"
               title="Tambah blok baru untuk {{ $anggota->nama }}">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Blok
            </a>
        </div>

        {{-- Desktop Table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Nama Blok</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Luas</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Umur</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Topografi</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Status</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($bloks as $blok)
                    @php $rbs = $blok->rekomendasiRbsTerbaru; @endphp
                    <tr class="block-row hover:bg-slate-50/50" data-nama-blok="{{ strtolower($blok->nama_blok ?? '') }}">
                        <td class="px-4 py-2.5 font-semibold text-slate-800 text-xs">{{ $blok->nama_blok }}</td>
                        <td class="px-4 py-2.5 text-xs text-slate-600">{{ number_format($blok->luas_ha, 2) }} Ha</td>
                        <td class="px-4 py-2.5 text-xs text-slate-600">
                            @if($blok->umur_tanaman !== null)
                                {{ $blok->umur_tanaman }} thn <span class="text-[10px] text-slate-400">({{ $blok->kategori_umur }})</span>
                            @else <span class="text-slate-300">—</span> @endif
                        </td>
                        <td class="px-4 py-2.5 text-xs text-slate-600">
                            {{ $blok->topografi ? $blok->topografi : '—' }}
                        </td>
                        <td class="px-4 py-2.5">
                            <x-recommendation-status :recommendation="$rbs" :show-stage="false" compact />
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <div class="flex items-center gap-1 justify-end">
                                <a href="{{ route('blok-lahan.show', $blok) }}" class="p-1 rounded-md bg-slate-50 border border-slate-200 text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all" title="Lihat">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('blok-lahan.edit', $blok) }}" class="p-1 rounded-md bg-slate-50 border border-slate-200 text-slate-500 hover:text-blue-700 hover:bg-blue-50 transition-all" title="Edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('blok-lahan.destroy', $blok) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="confirmDelete(this.closest('form'))" class="p-1 rounded-md bg-slate-50 border border-slate-200 text-slate-500 hover:text-red-600 hover:bg-red-50 transition-all" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
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
            @php $rbs = $blok->rekomendasiRbsTerbaru; @endphp
            <div class="block-row px-4 py-3" data-nama-blok="{{ strtolower($blok->nama_blok ?? '') }}">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <p class="font-semibold text-slate-800 text-xs">{{ $blok->nama_blok }} <span class="font-normal text-slate-400">· {{ number_format($blok->luas_ha, 2) }} Ha</span></p>
                    <x-recommendation-status :recommendation="$rbs" :show-stage="false" compact />
                </div>
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] text-slate-500">
                        {{ $blok->umur_tanaman !== null ? $blok->umur_tanaman.' thn' : '' }}
                    </p>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <a href="{{ route('blok-lahan.show', $blok) }}" class="px-2 py-1 bg-slate-50 border border-slate-200 text-slate-600 text-[9px] font-medium rounded-md">Lihat</a>
                        <a href="{{ route('blok-lahan.edit', $blok) }}" class="px-2 py-1 bg-blue-50 border border-blue-200 text-blue-700 text-[9px] font-medium rounded-md">Edit</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="bg-white border border-slate-200 rounded-xl p-8 text-center shadow-sm">
        <p class="text-slate-400 text-sm mb-2">Tidak ada data blok lahan.</p>
        <a href="{{ route('blok-lahan.create') }}" class="text-emerald-600 text-sm font-semibold hover:underline">Tambah blok lahan →</a>
    </div>
    @endforelse
</div>
@endsection
