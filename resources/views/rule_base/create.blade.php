@extends('layouts.app')

@section('title', 'Tambah Rule')
@section('page-title', 'Tambah Rule')
@section('page-subtitle', 'Tambahkan aturan yang jelas dan dapat ditelusuri sumbernya')

@section('content')
<div class="w-full space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('rule-base.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <p class="text-xs text-slate-500 dark:text-slate-400">Kode rule dibuat otomatis setelah disimpan.</p>
    </div>

    <form method="POST" action="{{ route('rule-base.store') }}" class="space-y-4">
        @csrf
        @include('rule_base._form')
    </form>
</div>
@endsection