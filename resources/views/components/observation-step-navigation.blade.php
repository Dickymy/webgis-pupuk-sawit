<div id="observation-step-navigation" class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <a id="observation-step-cancel" href="{{ route('kondisi-lahan.index') }}"
       class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
        Batal
    </a>
    <div class="ml-auto flex items-center gap-2">
        <button type="button" id="observation-step-previous"
            class="hidden rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
            Kembali
        </button>
        <button type="button" id="observation-step-next"
            class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700">
            Lanjutkan
        </button>
    </div>
</div>