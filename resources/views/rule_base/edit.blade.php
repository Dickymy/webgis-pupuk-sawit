@extends('layouts.app')

@section('title', 'Edit Rule')
@section('page-title', 'Edit Rule')
@section('page-subtitle', $rule->kode_rule.' · Versi '.$rule->versi_rule)

@section('content')
<div class="w-full space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('rule-base.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <div class="text-right text-xs text-slate-500 dark:text-slate-400">
            <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $rule->kode_rule }}</p>
            <p>Perubahan akan menaikkan versi dan menghentikan rule sementara.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('rule-base.update', $rule) }}" class="space-y-4">
        @csrf
        @method('PUT')
        @include('rule_base._form')
    </form>
</div>
@endsection