@extends('layouts.app')

@section('title', 'Laporan Pemupukan')
@section('page-title', 'Laporan Pemupukan')
@section('page-subtitle', 'Rekapitulasi kebutuhan pupuk per anggota Kelompok Tani Suluh Tani')

@section('content')
{{-- Nilai operasional menyegarkan snapshot urea_aplikasi_saat_ini dan kcl_aplikasi_saat_ini untuk program aktif. --}}
<div class="space-y-4 sm:space-y-5">

    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-4">
        <div>
            <p class="text-xs font-semibold text-slate-700">Program pemupukan {{ $programStats['tahun'] }}</p>
            <p class="text-[10px] text-slate-400">Pilih ringkasan program tanpa mengatur banyak filter.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('laporan.index', ['tahun_program' => $programStats['tahun']]) }}"
               class="rounded-lg border px-3 py-2 text-[10px] font-semibold transition {{ request('tahun_program') && !request('status_program') ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-emerald-300' }}">
                Semua Program ({{ $programStats['semua'] }})
            </a>
            <a href="{{ route('laporan.index', ['status_program' => 'AKTIF', 'tahun_program' => $programStats['tahun']]) }}"
               class="rounded-lg border px-3 py-2 text-[10px] font-semibold transition {{ request('status_program') === 'AKTIF' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-blue-300' }}">
                Aktif ({{ $programStats['aktif'] }})
            </a>
            <a href="{{ route('laporan.index', ['status_program' => 'SELESAI', 'tahun_program' => $programStats['tahun']]) }}"
               class="rounded-lg border px-3 py-2 text-[10px] font-semibold transition {{ request('status_program') === 'SELESAI' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-emerald-300' }}">
                Selesai ({{ $programStats['selesai'] }})
            </a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm">
            <p class="text-xs text-slate-500 font-medium mb-0.5">Total Anggota</p>
            <p class="text-xl sm:text-2xl font-extrabold text-slate-900">{{ $laporanPerAnggota->count() }}</p>
            <p class="text-[10px] text-slate-400">{{ $isCompletedProgramView ? 'memiliki program selesai' : 'memiliki rekomendasi' }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm border-l-4 border-l-amber-400">
            <p class="text-xs text-slate-500 font-medium mb-0.5">{{ $isCompletedProgramView ? 'Urea Terealisasi' : 'Kebutuhan Urea Saat Ini' }}</p>
            <p class="text-xl sm:text-2xl font-extrabold text-amber-700">{{ number_format($totalUrea, 0) }} <span class="text-xs font-normal">kg</span></p>
            <p class="text-[10px] text-slate-400">setara {{ $karungUrea }} karung</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm border-l-4 border-l-emerald-500">
            <p class="text-xs text-slate-500 font-medium mb-0.5">{{ $isCompletedProgramView ? 'KCl Terealisasi' : 'Kebutuhan KCl Saat Ini' }}</p>
            <p class="text-xl sm:text-2xl font-extrabold text-emerald-700">{{ number_format($totalKcl, 0) }} <span class="text-xs font-normal">kg</span></p>
            <p class="text-[10px] text-slate-400">setara {{ $karungKcl }} karung</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm">
            <p class="text-xs text-slate-500 font-medium mb-0.5">{{ $isCompletedProgramView ? 'Program Selesai' : 'Blok Siap Dipupuk' }}</p>
            <p class="text-xl sm:text-2xl font-extrabold text-blue-600">{{ $blokRingkasanTotal }}</p>
            <p class="text-[10px] text-slate-400">{{ $isCompletedProgramView ? 'program telah dituntaskan' : 'dari '.$rekap->count().' blok dianalisis' }}</p>
        </div>
    </div>

    {{-- Keterangan --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-xs text-blue-800">
        <span class="font-semibold">ℹ Catatan:</span>
        @if($isCompletedProgramView)
            Total dihitung dari realisasi Urea dan KCl yang aktif pada program selesai; catatan yang dibatalkan tidak ikut dihitung.
        @else
            Total menunjukkan kebutuhan aplikasi saat ini dari blok yang <strong>siap dipupuk</strong>.
        @endif
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-slate-200 rounded-xl p-3 sm:p-4 shadow-sm relative z-20">
        <form method="GET" action="{{ route('laporan.index') }}" id="laporan-filter-form" data-no-prevent-double="true" class="flex flex-col sm:flex-row flex-wrap items-start sm:items-end gap-2 sm:gap-3">
            @if(request('status_program'))<input type="hidden" name="status_program" value="{{ request('status_program') }}">@endif
            @if(request('tahun_program'))<input type="hidden" name="tahun_program" value="{{ request('tahun_program') }}">@endif
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
                        class="custom-select w-full sm:w-auto pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:min-w-[140px] cursor-pointer">
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

            {{-- Pahan v2.8: Filter berdasarkan status baru --}}
            <div class="w-full sm:w-auto relative">
                <label class="block text-xs text-slate-500 font-semibold mb-1">Kondisi Tanaman</label>
                <div class="relative">
                    <select name="status_kondisi_tanaman" onchange="this.form.submit()"
                        class="custom-select w-full sm:w-auto pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:min-w-[150px] cursor-pointer">
                        <option value="">Semua Kondisi</option>
                        @foreach(['TERINDIKASI_DEFISIENSI' => 'Ditemukan Gejala pada Daun', 'NORMAL_VISUAL' => 'Tidak Ditemukan Gejala pada Daun', 'PERLU_VERIFIKASI' => 'Data Pemeriksaan Belum Lengkap'] as $val => $label)
                            <option value="{{ $val }}" {{ request('status_kondisi_tanaman') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <div class="w-full sm:w-auto relative">
                <label class="block text-xs text-slate-500 font-semibold mb-1">Status Tahap</label>
                <div class="relative">
                    <select name="status_stage" onchange="this.form.submit()"
                        class="custom-select w-full sm:w-auto pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:min-w-[160px] cursor-pointer">
                        <option value="">Semua Tahap</option>
                        @foreach(['TAHAP_1_SIAP' => 'Tahap 1 Siap', 'TAHAP_1_SEBAGIAN' => 'Tahap 1 Sebagian', 'MENUNGGU_INTERVAL' => 'Menunggu '.config('fertilization.window.min_interval_days', 120).' Hari', 'TAHAP_2_SIAP' => 'Tahap 2 Siap', 'SELESAI_TAHUNAN' => 'Selesai Tahunan'] as $val => $label)
                            <option value="{{ $val }}" {{ request('status_stage') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto sm:ml-auto pt-1 sm:pt-0">
                @if(request()->hasAny(['status_kondisi_tanaman', 'status_stage', 'status_program', 'anggota_id', 'blok_lahan_id']))
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
                <span class="text-amber-700 font-bold">{{ $isCompletedProgramView ? 'Urea terealisasi' : 'Urea saat ini' }}: {{ number_format($group['subtotal_urea'], 1) }} kg</span>
                @endif
                @if($group['subtotal_kcl'] > 0)
                <span class="text-cyan-700 font-bold">{{ $isCompletedProgramView ? 'KCl terealisasi' : 'KCl saat ini' }}: {{ number_format($group['subtotal_kcl'], 1) }} kg</span>
                @endif
                @if($group['subtotal_urea'] == 0 && $group['subtotal_kcl'] == 0)
                <span class="text-slate-400 font-medium">{{ $isCompletedProgramView ? 'Belum ada realisasi tercatat' : 'Belum ada kebutuhan saat ini' }}</span>
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
                        <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-400 uppercase">{{ $isCompletedProgramView ? 'Urea Realisasi' : 'Urea Saat Ini' }} (kg)</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-400 uppercase">{{ $isCompletedProgramView ? 'KCl Realisasi' : 'KCl Saat Ini' }} (kg)</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-400 uppercase">Tanggal</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($items as $r)
                    <tr class="block-row hover:bg-slate-50/50" data-nama-blok="{{ strtolower($r->blokLahan->nama_blok ?? '') }}">
                        <td class="px-4 py-2.5 font-medium text-slate-800 text-xs">{{ $r->blokLahan->nama_blok }}</td>
                        <td class="px-4 py-2.5 text-xs text-slate-600">{{ number_format($r->blokLahan->luas_ha, 2) }} Ha</td>
                        <td class="px-4 py-2.5">
                            <x-recommendation-status
                                :recommendation="$r"
                                :stage-status="$r->status_stage_operasional"
                                :show-feasibility="! $isCompletedProgramView"
                                compact
                            />
                        </td>
                        @php
                            $ureaRingkasan = $isCompletedProgramView ? ($r->urea_terealisasi ?? 0) : ($r->urea_operasional ?? 0);
                            $kclRingkasan = $isCompletedProgramView ? ($r->kcl_terealisasi ?? 0) : ($r->kcl_operasional ?? 0);
                        @endphp
                        <td class="px-4 py-2.5 text-right text-xs font-semibold {{ $ureaRingkasan > 0 ? 'text-amber-700' : 'text-slate-300' }}">
                            {{ $ureaRingkasan > 0 ? number_format($ureaRingkasan, 1) : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right text-xs font-semibold {{ $kclRingkasan > 0 ? 'text-cyan-700' : 'text-slate-300' }}">
                            {{ $kclRingkasan > 0 ? number_format($kclRingkasan, 1) : '—' }}
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
                @if($group['blok_ringkasan'] > 0)
                <tfoot>
                    <tr class="border-t border-slate-200 bg-slate-50/50">
                        <td colspan="3" class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase">{{ $isCompletedProgramView ? 'Subtotal realisasi' : 'Subtotal kebutuhan saat ini' }} ({{ $group['blok_ringkasan'] }} blok)</td>
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
                $ureaRingkasan = $isCompletedProgramView ? ($r->urea_terealisasi ?? 0) : ($r->urea_operasional ?? 0);
                $kclRingkasan = $isCompletedProgramView ? ($r->kcl_terealisasi ?? 0) : ($r->kcl_operasional ?? 0);
            @endphp
            <div class="block-row px-4 py-3" data-nama-blok="{{ strtolower($r->blokLahan->nama_blok ?? '') }}">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <p class="font-semibold text-slate-800 text-xs">{{ $r->blokLahan->nama_blok }} <span class="font-normal text-slate-400">· {{ number_format($r->blokLahan->luas_ha, 2) }} Ha</span></p>
                    <x-recommendation-status
                        :recommendation="$r"
                        :stage-status="$r->status_stage_operasional"
                        :show-stage="$isCompletedProgramView"
                        :show-feasibility="! $isCompletedProgramView"
                        compact
                        class="justify-end"
                    />
                </div>
                <div class="flex items-center justify-between gap-2">
                    <div class="flex flex-wrap gap-x-3 text-[10px] text-slate-600">
                        @if($ureaRingkasan > 0)
                        <span class="text-amber-700 font-semibold">Urea: {{ number_format($ureaRingkasan, 1) }} kg</span>
                        @endif
                        @if($kclRingkasan > 0)
                        <span class="text-cyan-700 font-semibold">KCl: {{ number_format($kclRingkasan, 1) }} kg</span>
                        @endif
                        @if($ureaRingkasan <= 0 && $kclRingkasan <= 0)
                        <span class="text-slate-400">{{ $isCompletedProgramView ? 'Belum ada realisasi tercatat' : $r->label_kelayakan }}</span>
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
            @if($group['blok_ringkasan'] > 0)
            <div class="px-4 py-2.5 bg-slate-50 flex items-center justify-between text-[10px]">
                <span class="font-bold text-slate-500 uppercase">{{ $isCompletedProgramView ? 'Realisasi' : 'Subtotal' }}</span>
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
        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="text-slate-600 text-sm font-medium mb-1">Belum ada data laporan.</p>
        <p class="text-slate-400 text-xs mb-3">Jalankan analisis RBS pada blok lahan terlebih dahulu agar laporan dapat dibuat.</p>
        <a href="{{ route('rbs.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Jalankan Analisis RBS
        </a>
    </div>
    @endforelse

</div>

@endsection
