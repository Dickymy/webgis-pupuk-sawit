@extends('layouts.app')

@section('title', 'Rule Based')
@section('page-title', 'Rule Based')
@section('page-subtitle', 'Kelola aturan keputusan dan sumber pengetahuannya')

@section('content')
@php
    $activeRules = $rules->where('aktif', true);
    $pendingRules = $rules->where('aktif', false);
    $visualRuleCount = $activeRules->where('jenis_rule', 'DIAGNOSIS_VISUAL')->count();
    $timingRuleCount = $activeRules->where('jenis_rule', 'PEMBATAS_APLIKASI')->count();
@endphp

<div class="w-full space-y-4">
    <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3.5 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/30">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-100">
                    <span class="text-emerald-700 dark:text-emerald-300">Alur sistem</span>
                    <span class="rounded-lg border border-emerald-200 bg-white px-2.5 py-1 dark:border-emerald-800 dark:bg-slate-900">Observasi</span>
                    <span aria-hidden="true" class="text-emerald-500">→</span>
                    <span class="rounded-lg border border-emerald-200 bg-white px-2.5 py-1 dark:border-emerald-800 dark:bg-slate-900">Cocokkan rule</span>
                    <span aria-hidden="true" class="text-emerald-500">→</span>
                    <span class="rounded-lg border border-emerald-200 bg-white px-2.5 py-1 dark:border-emerald-800 dark:bg-slate-900">Hasil rekomendasi</span>
                </div>
                <p class="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300">Rule baru disimpan sebagai belum digunakan. Pastikan kondisi, hasil, dan sumbernya sesuai sebelum memilih Gunakan.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('rule-base.info') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-emerald-300 bg-white px-3.5 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-slate-900 dark:text-emerald-300">Penjelasan Rule Based</a>
                <a href="{{ route('rule-base.create') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Rule
                </a>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-3 gap-2 sm:gap-3" aria-label="Ringkasan rule">
        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
            <p class="text-[10px] uppercase text-slate-400">Rule digunakan</p>
            <p class="mt-1 text-xl font-bold text-slate-800 dark:text-slate-100">{{ $activeRules->count() }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 dark:border-amber-900 dark:bg-amber-950/30">
            <p class="text-[10px] uppercase text-amber-700 dark:text-amber-300">Diagnosis Visual</p>
            <p class="mt-1 text-xl font-bold text-amber-800 dark:text-amber-200">{{ $visualRuleCount }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-3 dark:border-blue-900 dark:bg-blue-950/30">
            <p class="text-[10px] uppercase text-blue-700 dark:text-blue-300">Waktu pemupukan</p>
            <p class="mt-1 text-xl font-bold text-blue-800 dark:text-blue-200">{{ $timingRuleCount }}</p>
        </div>
    </section>

    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Aturan yang dikelola</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Rule arsip lama tidak ditampilkan dan tidak memengaruhi analisis.</p>
        </div>
        @if($pendingRules->isNotEmpty())
            <p class="text-xs font-semibold text-amber-700 dark:text-amber-300">{{ $pendingRules->count() }} rule belum digunakan</p>
        @endif
    </div>

    <section class="space-y-3">
        @forelse($rules as $rule)
            @php
                $isVisual = $rule->jenis_rule === 'DIAGNOSIS_VISUAL';
                if ($rule->kondisi_topografi !== null) {
                    $condition = 'Topografi lahan: '.$rule->kondisi_topografi;
                } elseif ($isVisual) {
                    $condition = 'Kondisi daun: '.$rule->kondisi_warna_daun;
                } elseif ($rule->kondisi_curah_hujan_min_mm !== null && $rule->kondisi_curah_hujan_max_mm !== null) {
                    $condition = 'Curah hujan '.(float) $rule->kondisi_curah_hujan_min_mm.'–'.(float) $rule->kondisi_curah_hujan_max_mm.' mm/bulan';
                } elseif ($rule->kondisi_curah_hujan_min_mm !== null) {
                    $condition = 'Curah hujan minimal '.(float) $rule->kondisi_curah_hujan_min_mm.' mm/bulan';
                } else {
                    $condition = 'Curah hujan maksimal '.(float) $rule->kondisi_curah_hujan_max_mm.' mm/bulan';
                }
                $source = filled($rule->sumber_penulis)
                    ? $rule->sumber_penulis.($rule->sumber_tahun ? ' ('.$rule->sumber_tahun.')' : '')
                    : $rule->sumber_judul;
            @endphp

            <article class="overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-slate-800 {{ $rule->aktif ? 'border-slate-200 dark:border-slate-700' : 'border-amber-200 dark:border-amber-900' }}">
                <header class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-slate-900 px-2 py-1 font-mono text-[10px] font-bold text-white dark:bg-slate-100 dark:text-slate-900">{{ $rule->kode_rule }}</span>
                        <span class="rounded-full px-2 py-1 text-[9px] font-semibold {{ $rule->aktif ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">{{ $rule->aktif ? 'Digunakan' : 'Belum digunakan' }}</span>
                        <span class="text-[10px] text-slate-400">Versi {{ $rule->versi_rule }} · Prioritas {{ $rule->prioritas }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('rule-base.edit', $rule) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Edit</a>
                        @if($rule->aktif)
                            <form method="POST" action="{{ route('rule-base.status', $rule) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="deactivate">
                                <button type="button" onclick="showConfirm('Rule tidak akan digunakan pada analisis baru. Lanjutkan?', () => this.form.submit())" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-red-200 px-3 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-900 dark:text-red-300">Hentikan</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('rule-base.status', $rule) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="activate">
                                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-emerald-600 px-3 text-xs font-bold text-white transition hover:bg-emerald-700">Gunakan</button>
                            </form>
                        @endif
                    </div>
                </header>

                <div class="grid lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                    <div class="p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-blue-600">Jika</p>
                        <p class="mt-1 text-sm font-semibold leading-6 text-slate-900 dark:text-white">{{ $condition }}</p>
                    </div>
                    <div class="border-t border-slate-100 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-900/30 lg:border-l lg:border-t-0">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-600">Maka</p>
                        <p class="mt-1 text-sm font-semibold leading-6 text-slate-900 dark:text-white">{{ $rule->indikasi_masalah }}</p>
                        <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $rule->saran_tindakan }}</p>
                    </div>
                </div>

                <footer class="flex flex-col gap-1 border-t border-slate-100 px-4 py-3 text-[10px] text-slate-500 dark:border-slate-700 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <span><strong>Sumber:</strong> {{ $source ?: 'Belum lengkap' }}</span>
                    <span>{{ $isVisual ? ($rule->kondisi_topografi !== null ? 'Kondisi topografi' : 'Kondisi daun') : 'Waktu pemupukan' }}</span>
                </footer>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-800">
                <p class="text-sm text-slate-500">Belum ada rule yang dapat dikelola.</p>
                <a href="{{ route('rule-base.create') }}" class="mt-3 inline-flex min-h-10 items-center rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white">Tambah Rule</a>
            </div>
        @endforelse
    </section>
</div>
@endsection