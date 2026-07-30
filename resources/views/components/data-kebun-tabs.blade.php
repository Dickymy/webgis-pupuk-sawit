<nav class="inline-grid w-full shrink-0 grid-cols-2 rounded-xl bg-slate-100 p-1 dark:bg-slate-700/70 sm:w-auto" aria-label="Data kebun">
    <a href="{{ route('blok-lahan.index') }}"
       class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-center text-xs font-semibold transition-colors {{ request()->routeIs('blok-lahan.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-700' }}">
        Blok Lahan
    </a>
    <a href="{{ route('anggota.index') }}"
       class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-center text-xs font-semibold transition-colors {{ request()->routeIs('anggota.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-700' }}">
        Anggota
    </a>
</nav>