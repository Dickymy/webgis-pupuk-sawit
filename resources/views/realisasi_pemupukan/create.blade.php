@extends('layouts.app')

@section('title', 'Catat Realisasi Pemupukan')
@section('page-title', 'Catat Realisasi Pemupukan')
@section('page-subtitle', $blok->nama_blok . ' · ' . ($blok->anggota->nama ?? '-'))

@section('content')

<div class="mb-4">
    <a href="{{ route('rbs.detail', $blok) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Detail Analisis
    </a>
</div>

{{-- Info Rekomendasi --}}
<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm p-4 sm:p-5 mb-5">
    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-3">📋 Informasi Rekomendasi</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <span class="text-slate-400 block">Tanggal Analisis</span>
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $rekomendasiRbs->tanggal_analisis->format('d/m/Y') }}</span>
        </div>
        <div>
            <span class="text-slate-400 block">Fase Snapshot</span>
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $rekomendasiRbs->label_fase }}</span>
        </div>
        <div>
            <span class="text-slate-400 block">Umur Snapshot</span>
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $rekomendasiRbs->umur_tanaman_snapshot ?? '-' }} tahun</span>
        </div>
        <div>
            <span class="text-slate-400 block">Tahap Aktif</span>
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ \App\Services\CurrentApplicationCalculator::labelStatusStage($currentApp['status_stage'] ?? '') }}</span>
        </div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
        <div>
            <span class="text-slate-400 block">Kebutuhan Tahunan Urea</span>
            <span class="font-bold text-amber-700">{{ number_format($rekomendasiRbs->urea_total_estimasi_tahunan ?? 0, 1) }} kg</span>
        </div>
        <div>
            <span class="text-slate-400 block">Kebutuhan Tahunan KCl</span>
            <span class="font-bold text-cyan-700">{{ number_format($rekomendasiRbs->kcl_total_estimasi_tahunan ?? 0, 1) }} kg</span>
        </div>
        <div>
            <span class="text-slate-400 block">Total Realisasi Urea</span>
            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ number_format($realizationSummary['total_urea_realisasi'], 1) }} kg</span>
        </div>
        <div>
            <span class="text-slate-400 block">Total Realisasi KCl</span>
            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ number_format($realizationSummary['total_kcl_realisasi'], 1) }} kg</span>
        </div>
    </div>
    @if($currentApp['tanggal_minimum_tahap_berikutnya'])
    <div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <p class="text-[11px] text-blue-700 dark:text-blue-300">🕐 Tanggal minimum tahap berikutnya: <strong>{{ \Carbon\Carbon::parse($currentApp['tanggal_minimum_tahap_berikutnya'])->format('d/m/Y') }}</strong></p>
    </div>
    @endif
</div>

{{-- Form --}}
<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm p-4 sm:p-6">
    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4">✏️ Form Realisasi Pemupukan</h3>

    <form method="POST" action="{{ route('realisasi-pemupukan.store') }}">
        @csrf
        <input type="hidden" name="rekomendasi_rbs_id" value="{{ $rekomendasiRbs->id }}">
        <input type="hidden" name="tahun_program" value="{{ now()->year }}">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Tahap --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahap Pemupukan</label>
                <select name="tahap" class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('tahap') border-red-500 @enderror">
                    <option value="1" {{ old('tahap', $tahapDefault) == 1 ? 'selected' : '' }}>Tahap 1</option>
                    <option value="2" {{ old('tahap', $tahapDefault) == 2 ? 'selected' : '' }}>Tahap 2</option>
                </select>
                @error('tahap')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Tanggal Realisasi --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Realisasi</label>
                <input type="date" name="tanggal_realisasi" value="{{ old('tanggal_realisasi', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                    class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('tanggal_realisasi') border-red-500 @enderror">
                @error('tanggal_realisasi')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Urea Rencana --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Urea Rencana (kg)</label>
                <input type="number" name="urea_rencana_kg" value="{{ old('urea_rencana_kg', number_format($ureaRencana, 2, '.', '')) }}" step="0.01" min="0"
                    class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('urea_rencana_kg') border-red-500 @enderror" readonly>
                @error('urea_rencana_kg')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- KCl Rencana --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">KCl Rencana (kg)</label>
                <input type="number" name="kcl_rencana_kg" value="{{ old('kcl_rencana_kg', number_format($kclRencana, 2, '.', '')) }}" step="0.01" min="0"
                    class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('kcl_rencana_kg') border-red-500 @enderror" readonly>
                @error('kcl_rencana_kg')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Urea Realisasi --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Urea Realisasi (kg) <span class="text-red-500">*</span></label>
                <input type="number" name="urea_realisasi_kg" value="{{ old('urea_realisasi_kg', number_format($ureaRencana, 2, '.', '')) }}" step="0.01" min="0"
                    class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('urea_realisasi_kg') border-red-500 @enderror">
                @error('urea_realisasi_kg')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- KCl Realisasi --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">KCl Realisasi (kg) <span class="text-red-500">*</span></label>
                <input type="number" name="kcl_realisasi_kg" value="{{ old('kcl_realisasi_kg', number_format($kclRencana, 2, '.', '')) }}" step="0.01" min="0"
                    class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('kcl_realisasi_kg') border-red-500 @enderror">
                @error('kcl_realisasi_kg')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Status Realisasi --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Realisasi</label>
                <select name="status_realisasi" class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('status_realisasi') border-red-500 @enderror">
                    <option value="SELESAI" {{ old('status_realisasi') === 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                    <option value="SEBAGIAN" {{ old('status_realisasi') === 'SEBAGIAN' ? 'selected' : '' }}>Sebagian</option>
                </select>
                @error('status_realisasi')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Catatan --}}
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Pelaksana</label>
                <textarea name="catatan_pelaksana" rows="2" class="w-full text-sm border-slate-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg @error('catatan_pelaksana') border-red-500 @enderror" placeholder="Catatan tambahan (opsional)...">{{ old('catatan_pelaksana') }}</textarea>
                @error('catatan_pelaksana')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Konfirmasi Over Plan --}}
        <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg hidden" id="over-plan-section">
            <p class="text-xs text-amber-800 dark:text-amber-300 font-semibold mb-2">⚠️ Realisasi melebihi rencana tahap</p>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="confirmed_over_plan" value="1" {{ old('confirmed_over_plan') ? 'checked' : '' }} class="rounded border-amber-300">
                <span class="text-xs text-amber-700 dark:text-amber-300">Saya mengonfirmasi bahwa realisasi melebihi rencana tahap dengan alasan yang tercatat.</span>
            </label>
            @error('confirmed_over_plan')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Override Annual Limit --}}
        <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg hidden" id="override-annual-section">
            <p class="text-xs text-red-800 dark:text-red-300 font-semibold mb-2">🚨 Total realisasi akan melebihi kebutuhan tahunan</p>
            <label class="flex items-center gap-2 mb-2">
                <input type="checkbox" name="override_annual_limit" value="1" {{ old('override_annual_limit') ? 'checked' : '' }} class="rounded border-red-300">
                <span class="text-xs text-red-700 dark:text-red-300">Saya mengotorisasi override batas kebutuhan tahunan.</span>
            </label>
            <textarea name="override_reason" rows="2" class="w-full text-sm border-red-300 dark:border-red-600 dark:bg-red-900/10 rounded-lg" placeholder="Alasan override (wajib)...">{{ old('override_reason') }}</textarea>
            @error('override_annual_limit')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            @error('override_reason')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Tombol Simpan --}}
        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                💾 Simpan Realisasi
            </button>
            <a href="{{ route('rbs.detail', $blok) }}" class="px-4 py-2.5 text-sm text-slate-600 dark:text-slate-400 hover:text-slate-800 transition-colors">Batal</a>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ureaRencana = parseFloat(document.querySelector('[name="urea_rencana_kg"]').value) || 0;
    const kclRencana = parseFloat(document.querySelector('[name="kcl_rencana_kg"]').value) || 0;
    const totalTahunanUrea = {{ $rekomendasiRbs->urea_total_estimasi_tahunan ?? 0 }};
    const totalTahunanKcl = {{ $rekomendasiRbs->kcl_total_estimasi_tahunan ?? 0 }};
    const existingUrea = {{ $realizationSummary['total_urea_realisasi'] }};
    const existingKcl = {{ $realizationSummary['total_kcl_realisasi'] }};

    const ureaInput = document.querySelector('[name="urea_realisasi_kg"]');
    const kclInput = document.querySelector('[name="kcl_realisasi_kg"]');
    const overPlanSection = document.getElementById('over-plan-section');
    const overrideSection = document.getElementById('override-annual-section');

    function checkLimits() {
        const ureaVal = parseFloat(ureaInput.value) || 0;
        const kclVal = parseFloat(kclInput.value) || 0;

        // Check over plan
        const overPlan = (ureaVal > ureaRencana && ureaRencana > 0) || (kclVal > kclRencana && kclRencana > 0);
        overPlanSection.classList.toggle('hidden', !overPlan);

        // Check over annual
        const totalUreaAfter = existingUrea + ureaVal;
        const totalKclAfter = existingKcl + kclVal;
        const overAnnual = (totalUreaAfter > totalTahunanUrea && totalTahunanUrea > 0) || (totalKclAfter > totalTahunanKcl && totalTahunanKcl > 0);
        overrideSection.classList.toggle('hidden', !overAnnual);
    }

    ureaInput.addEventListener('input', checkLimits);
    kclInput.addEventListener('input', checkLimits);
    checkLimits();
});
</script>
@endpush
