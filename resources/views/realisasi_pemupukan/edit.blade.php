@extends('layouts.app')

@section('title', 'Edit Realisasi Pemupukan')
@section('page-title', 'Edit Realisasi')
@section('page-subtitle', ($blok->nama_blok ?? '-') . ' · Tahap ' . $realisasiPemupukan->tahap)

@section('content')

<div class="mb-4">
    <a href="{{ route('realisasi-pemupukan.show', $realisasiPemupukan) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

{{-- Form Edit --}}
<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm p-4 sm:p-6">
    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4">✏️ Edit Realisasi — {{ $blok->nama_blok }} (Tahap {{ $realisasiPemupukan->tahap }})</h3>

    <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <p class="text-xs text-blue-700 dark:text-blue-300">
            <strong>Rencana:</strong> Urea {{ number_format($realisasiPemupukan->urea_rencana_kg, 2) }} kg · KCl {{ number_format($realisasiPemupukan->kcl_rencana_kg, 2) }} kg
        </p>
    </div>

    <form method="POST" action="{{ route('realisasi-pemupukan.update', $realisasiPemupukan) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Tanggal Realisasi --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Realisasi</label>
                <input type="date" name="tanggal_realisasi" value="{{ old('tanggal_realisasi', $realisasiPemupukan->tanggal_realisasi->toDateString()) }}" max="{{ now()->toDateString() }}"
                    class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('tanggal_realisasi') border-red-500 @enderror">
                @error('tanggal_realisasi')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Status Realisasi --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Realisasi</label>
                <select name="status_realisasi" class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('status_realisasi') border-red-500 @enderror">
                    <option value="SELESAI" {{ old('status_realisasi', $realisasiPemupukan->status_realisasi) === 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                    <option value="SEBAGIAN" {{ old('status_realisasi', $realisasiPemupukan->status_realisasi) === 'SEBAGIAN' ? 'selected' : '' }}>Sebagian</option>
                </select>
                @error('status_realisasi')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Urea Realisasi --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Urea Realisasi (kg)</label>
                <input type="number" name="urea_realisasi_kg" value="{{ old('urea_realisasi_kg', $realisasiPemupukan->urea_realisasi_kg) }}" step="0.01" min="0"
                    class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('urea_realisasi_kg') border-red-500 @enderror">
                @error('urea_realisasi_kg')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- KCl Realisasi --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">KCl Realisasi (kg)</label>
                <input type="number" name="kcl_realisasi_kg" value="{{ old('kcl_realisasi_kg', $realisasiPemupukan->kcl_realisasi_kg) }}" step="0.01" min="0"
                    class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('kcl_realisasi_kg') border-red-500 @enderror">
                @error('kcl_realisasi_kg')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Catatan --}}
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Pelaksana</label>
                <textarea name="catatan_pelaksana" rows="2" class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg">{{ old('catatan_pelaksana', $realisasiPemupukan->catatan_pelaksana) }}</textarea>
                @error('catatan_pelaksana')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Override sections --}}
        <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg" id="over-plan-edit" style="display:none;">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="confirmed_over_plan" value="1" {{ old('confirmed_over_plan', $realisasiPemupukan->confirmed_over_plan) ? 'checked' : '' }} class="rounded border-amber-300">
                <span class="text-xs text-amber-700">Konfirmasi realisasi melebihi rencana tahap.</span>
            </label>
            @error('confirmed_over_plan')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg" id="override-edit" style="display:none;">
            <label class="flex items-center gap-2 mb-2">
                <input type="checkbox" name="override_annual_limit" value="1" {{ old('override_annual_limit', $realisasiPemupukan->override_annual_limit) ? 'checked' : '' }} class="rounded border-red-300">
                <span class="text-xs text-red-700">Override batas kebutuhan tahunan.</span>
            </label>
            <textarea name="override_reason" rows="2" class="w-full text-sm border-red-300 rounded-lg" placeholder="Alasan override...">{{ old('override_reason', $realisasiPemupukan->override_reason) }}</textarea>
            @error('override_annual_limit')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            @error('override_reason')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                💾 Simpan Perubahan
            </button>
            <a href="{{ route('realisasi-pemupukan.show', $realisasiPemupukan) }}" class="px-4 py-2.5 text-sm text-slate-600 hover:text-slate-800 transition-colors">Batal</a>
        </div>
    </form>
</div>

@endsection
