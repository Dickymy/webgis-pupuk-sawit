@extends('layouts.app')

@section('title', 'Realisasi Pemupukan')
@section('page-title', 'Realisasi Pemupukan')
@section('page-subtitle', 'Daftar seluruh realisasi pemupukan')

@section('content')

{{-- Filter --}}
<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm p-4 mb-5">
    <form method="GET" action="{{ route('realisasi-pemupukan.index') }}" class="flex flex-wrap gap-3 items-end" data-no-prevent-double="true">
        <div>
            <label class="text-[10px] text-slate-500 uppercase font-semibold block mb-1">Status</label>
            <select name="status_realisasi" class="text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg px-3 py-1.5">
                <option value="">Semua</option>
                <option value="SELESAI" {{ request('status_realisasi') === 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                <option value="SEBAGIAN" {{ request('status_realisasi') === 'SEBAGIAN' ? 'selected' : '' }}>Sebagian</option>
                <option value="BATAL" {{ request('status_realisasi') === 'BATAL' ? 'selected' : '' }}>Batal</option>
            </select>
        </div>
        <div>
            <label class="text-[10px] text-slate-500 uppercase font-semibold block mb-1">Tahun</label>
            <select name="tahun_program" class="text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg px-3 py-1.5">
                <option value="">Semua</option>
                @for($y = now()->year; $y >= 2024; $y--)
                <option value="{{ $y }}" {{ request('tahun_program') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button type="submit" class="px-4 py-1.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">Filter</button>
        @if(request()->hasAny(['status_realisasi', 'tahun_program', 'blok_lahan_id']))
        <a href="{{ route('realisasi-pemupukan.index') }}" class="px-3 py-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">Reset</a>
        @endif
    </form>
</div>

{{-- Tabel --}}
<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
    @if($realisasis->isEmpty())
    <div class="p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mx-auto mb-3">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada realisasi pemupukan yang tercatat.</p>
        <p class="text-xs text-slate-400 mt-1">Catat realisasi melalui halaman detail analisis RBS.</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700 border-b border-slate-200 dark:border-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Tanggal</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Blok</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-300">Tahap</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">Urea (kg)</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600 dark:text-slate-300">KCl (kg)</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-300">Status</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-300">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @foreach($realisasis as $r)
                @php
                    $statusColor = match($r->status_realisasi) {
                        'SELESAI' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                        'SEBAGIAN' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                        'BATAL' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                        default => 'bg-slate-100 text-slate-600',
                    };
                @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $r->tanggal_realisasi->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-800 dark:text-slate-200">{{ $r->blokLahan->nama_blok ?? '-' }}</p>
                        <p class="text-xs text-slate-400">{{ $r->blokLahan->anggota->nama ?? '-' }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ $r->tahap }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-slate-800 dark:text-slate-200">{{ number_format($r->urea_realisasi_kg, 1) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-slate-800 dark:text-slate-200">{{ number_format($r->kcl_realisasi_kg, 1) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusColor }}">{{ $r->label_status }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('realisasi-pemupukan.show', $r) }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium hover:underline">Detail</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">
        {{ $realisasis->links() }}
    </div>
    @endif
</div>

@endsection
