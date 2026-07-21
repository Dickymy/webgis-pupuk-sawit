@extends('layouts.app')

@section('title', 'Realisasi Pemupukan')
@section('page-title', 'Realisasi Pemupukan')
@section('page-subtitle', 'Riwayat pelaksanaan pemupukan per anggota')

@section('content')
<div class="space-y-4">

    {{-- Filter --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <form method="GET" action="{{ route('realisasi-pemupukan.index') }}" id="realisasi-filter-form" data-no-prevent-double="true" class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <div class="flex-1 sm:max-w-[240px]">
                @include('components.filter-searchable', [
                    'name' => 'anggota_id',
                    'placeholder' => 'Cari pemilik...',
                    'options' => $anggotas,
                    'displayField' => 'nama',
                    'selected' => request('anggota_id'),
                    'formId' => 'realisasi-filter-form',
                ])
            </div>
            <div class="relative">
                <select name="status_realisasi" onchange="this.form.submit()"
                    class="w-full sm:w-auto pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="SELESAI" {{ request('status_realisasi') === 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                    <option value="SEBAGIAN" {{ request('status_realisasi') === 'SEBAGIAN' ? 'selected' : '' }}>Sebagian</option>
                    <option value="BATAL" {{ request('status_realisasi') === 'BATAL' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            @if(request()->hasAny(['anggota_id', 'status_realisasi']))
                <a href="{{ route('realisasi-pemupukan.index') }}" class="text-xs text-slate-500 hover:text-slate-700 font-medium px-2 py-1.5">Reset</a>
            @endif
        </form>
    </div>

    {{-- Grouped by Anggota --}}
    @forelse($grouped as $group)
    @php $anggota = $group['anggota']; $items = $group['items']; @endphp
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        {{-- Header anggota --}}
        <div class="px-4 sm:px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    {{ strtoupper(substr($anggota->nama ?? '?', 0, 1)) }}
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-sm">{{ $anggota->nama ?? 'Tidak Diketahui' }}</p>
                    @php
                        $aktif = $items->where('status_realisasi', '!=', 'BATAL');
                    @endphp
                    <p class="text-[10px] text-slate-500">{{ $aktif->count() }} realisasi aktif · Urea {{ number_format($aktif->sum('urea_realisasi_kg'), 1) }} kg · KCl {{ number_format($aktif->sum('kcl_realisasi_kg'), 1) }} kg</p>
                </div>
            </div>
        </div>

        {{-- Desktop Table --}}
        <div class="hidden sm:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-400 uppercase">Tanggal</th>
                        <th class="px-4 py-2 text-left text-[10px] font-semibold text-slate-400 uppercase">Blok Lahan</th>
                        <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-400 uppercase">Tahap</th>
                        <th class="px-4 py-2 text-right text-[10px] font-semibold text-slate-400 uppercase">Urea (kg)</th>
                        <th class="px-4 py-2 text-right text-[10px] font-semibold text-slate-400 uppercase">KCl (kg)</th>
                        <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-400 uppercase">Status</th>
                        <th class="px-4 py-2 text-right text-[10px] font-semibold text-slate-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($items as $realisasi)
                    @php
                        $statusStyle = match($realisasi->status_realisasi) {
                            'SELESAI' => 'bg-green-100 text-green-800',
                            'SEBAGIAN' => 'bg-amber-100 text-amber-800',
                            'BATAL' => 'bg-red-100 text-red-800',
                            default => 'bg-slate-100 text-slate-600',
                        };
                        $isBatal = $realisasi->status_realisasi === 'BATAL';
                        $tahapLabel = $realisasi->tahap === 1 ? 'Tahap 1' : 'Tahap 2';
                        $tahapColor = $realisasi->tahap === 1 ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800';
                    @endphp
                    <tr class="hover:bg-slate-50/50 {{ $isBatal ? 'opacity-50' : '' }}">
                        <td class="px-4 py-2.5 text-xs text-slate-600 whitespace-nowrap">{{ $realisasi->tanggal_realisasi->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5">
                            <p class="text-xs font-semibold text-slate-800">{{ $realisasi->blokLahan->nama_blok ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tahapColor }}">{{ $tahapLabel }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-800 whitespace-nowrap">{{ number_format($realisasi->urea_realisasi_kg, 1) }}</td>
                        <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-800 whitespace-nowrap">{{ number_format($realisasi->kcl_realisasi_kg, 1) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusStyle }}">
                                {{ $realisasi->label_status }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <a href="{{ route('realisasi-pemupukan.show', $realisasi) }}" class="inline-flex items-center px-2.5 py-1 bg-slate-50 border border-slate-200 text-slate-600 hover:text-blue-700 hover:bg-blue-50 hover:border-blue-200 text-[10px] font-medium rounded-lg transition-colors">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="sm:hidden divide-y divide-slate-100">
            @foreach($items as $realisasi)
            @php
                $statusStyle = match($realisasi->status_realisasi) {
                    'SELESAI' => 'bg-green-100 text-green-800',
                    'SEBAGIAN' => 'bg-amber-100 text-amber-800',
                    'BATAL' => 'bg-red-100 text-red-800',
                    default => 'bg-slate-100 text-slate-600',
                };
                $isBatal = $realisasi->status_realisasi === 'BATAL';
                $tahapLabel = $realisasi->tahap === 1 ? 'Tahap 1' : 'Tahap 2';
                $tahapColor = $realisasi->tahap === 1 ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800';
            @endphp
            <a href="{{ route('realisasi-pemupukan.show', $realisasi) }}" class="block px-4 py-3 hover:bg-slate-50 transition-colors {{ $isBatal ? 'opacity-50' : '' }}">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold {{ $tahapColor }}">{{ $tahapLabel }}</span>
                        <span class="text-xs font-semibold text-slate-800">{{ $realisasi->blokLahan->nama_blok ?? '-' }}</span>
                    </div>
                    <span class="inline-flex px-1.5 py-0.5 rounded-full text-[9px] font-semibold {{ $statusStyle }} flex-shrink-0">{{ $realisasi->label_status }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 text-[11px]">
                        <span class="text-slate-500">Urea: <strong class="text-slate-800">{{ number_format($realisasi->urea_realisasi_kg, 1) }} kg</strong></span>
                        <span class="text-slate-500">KCl: <strong class="text-slate-800">{{ number_format($realisasi->kcl_realisasi_kg, 1) }} kg</strong></span>
                    </div>
                    <span class="text-[10px] text-slate-400">{{ $realisasi->tanggal_realisasi->format('d/m/Y') }}</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @empty
    <div class="bg-white border border-slate-200 rounded-xl p-8 sm:p-12 text-center shadow-sm">
        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <p class="text-slate-600 text-sm font-medium mb-1">Belum ada realisasi pemupukan.</p>
        <p class="text-slate-400 text-xs mb-3">Catat realisasi setelah rekomendasi analisis tersedia dan tahap siap dilaksanakan.</p>
        <a href="{{ route('rbs.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Buka Analisis Pemupukan
        </a>
    </div>
    @endforelse
</div>
@endsection
