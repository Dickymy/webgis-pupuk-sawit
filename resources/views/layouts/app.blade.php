<!DOCTYPE html>
<html lang="id" class="min-h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="SawitGIS - Sistem Pendukung Keputusan Pemupukan Kelapa Sawit - Kelompok Tani Suluh Tani">

    {{-- ANTI-FOUC: set class 'dark' ke <html> SEBELUM CSS/konten dirender --}}
    <script>
        (function () {
            // Prioritas: nilai dari database (server) > cookie lokal
            var serverTema = '{{ auth("admin")->check() ? auth("admin")->user()->tema ?? "system" : "" }}';
            var cookieTema = document.cookie.split('; ').find(function(row) { return row.startsWith('tema_tampilan='); });
            var tema = serverTema || (cookieTema ? cookieTema.split('=')[1] : 'system');

            // Sinkronkan cookie dengan nilai server agar lintas-device konsisten
            if (serverTema && (!cookieTema || cookieTema.split('=')[1] !== serverTema)) {
                document.cookie = 'tema_tampilan=' + serverTema + '; path=/; max-age=31536000; SameSite=Lax';
            }

            var isDark = tema === 'dark' || (tema === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.style.colorScheme = 'dark';
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.style.colorScheme = 'light';
            }
        })();
    </script>

    <title>@yield('title', 'Dashboard') — SawitGIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preload" href="{{ asset('img/logo-96.png') }}" as="image" type="image/png">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        /* Fix: Leaflet z-index harus di bawah sidebar mobile */
        .leaflet-pane, .leaflet-control, .leaflet-top, .leaflet-bottom { z-index: 40 !important; }
        .leaflet-control { z-index: 41 !important; }
        /* Dark mode: Leaflet layer control (Peta/Satelit switcher) */
        .dark .leaflet-control-layers {
            background: #1e293b !important;
            border: 1px solid #475569 !important;
            border-radius: 10px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
            color: #e2e8f0 !important;
        }
        .dark .leaflet-control-layers-toggle {
            background-color: #1e293b !important;
            border-radius: 10px !important;
        }
        .dark .leaflet-control-layers label {
            color: #e2e8f0 !important;
        }
        .dark .leaflet-control-layers label span {
            color: #e2e8f0 !important;
        }
        .dark .leaflet-control-layers-separator {
            border-top-color: #475569 !important;
        }
        .dark .leaflet-control-layers input[type="radio"] {
            accent-color: #10b981;
        }
        /* Layer control: lebih jelas & mudah ditemukan */
        .leaflet-control-layers {
            border-radius: 10px !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.12) !important;
            border: 1px solid #e2e8f0 !important;
        }
        .leaflet-control-layers-toggle {
            width: 36px !important;
            height: 36px !important;
            border-radius: 10px !important;
        }
        .leaflet-control-layers-expanded {
            padding: 8px 12px !important;
        }
        .leaflet-control-layers label {
            font-size: 13px !important;
            font-weight: 500 !important;
            padding: 3px 0 !important;
        }
        /* Responsive: popup tidak overflow di mobile */
        .leaflet-popup-content-wrapper { max-width: 90vw !important; }
        .leaflet-popup-content { max-width: 100% !important; overflow-x: hidden; }
        /* Dark mode: Leaflet popup — tema gelap */
        .dark .leaflet-popup-content-wrapper {
            background: #1e293b !important;
            color: #e2e8f0 !important;
            border: 1px solid #334155 !important;
        }
        .dark .leaflet-popup-content {
            color: #e2e8f0 !important;
            background: #1e293b !important;
        }
        .dark .leaflet-popup-tip {
            background: #1e293b !important;
        }
        .dark .leaflet-popup-close-button {
            color: #94a3b8 !important;
        }
        /* KRITIS: Prevent horizontal scroll */
        html { overflow-x: hidden; scroll-behavior: smooth; }
        body { overflow-x: hidden; min-width: 0; word-wrap: break-word; overflow-wrap: break-word; }
        /* Responsive table */
        @media (max-width: 640px) {
            .hide-mobile { display: none !important; }
            table th, table td { padding: 6px 8px !important; font-size: 11px; }
            /* Fix: prevent iOS auto-zoom on input focus */
            input[type="text"],
            input[type="number"],
            input[type="email"],
            input[type="password"],
            input[type="tel"],
            input[type="url"],
            input[type="search"],
            input[type="date"],
            textarea,
            select { font-size: 16px !important; }
            /* Fix: form container tidak overflow di mobile */
            .max-w-4xl, form, .space-y-4, .space-y-6 {
                min-width: 0;
                overflow-x: hidden;
            }
            /* Fix: grid tidak meluber */
            .grid {
                min-width: 0;
            }
            .grid > * {
                min-width: 0;
                overflow: visible;
            }
            /* Fix select teks panjang di mobile */
            select {
                padding-right: 2rem !important;
                text-overflow: ellipsis;
            }
        }
        /* Gambar tidak meluber */
        img { max-width: 100%; height: auto; }
        /* Touch target minimum untuk checkbox/toggle di HP */
        input[type="checkbox"] { min-width: 18px; min-height: 18px; cursor: pointer; }
        /* Fix: semua child element tidak boleh exceed parent */
        *, *::before, *::after { max-width: 100%; }
        /* Exclude elements that need to overflow (tables, maps, pagination, etc) */
        table, table *, .leaflet-container, .leaflet-container *, svg, canvas, video, iframe, nav, nav *, [id$="-dropdown"], [id$="-dropdown"] *, .notif-dropdown-panel, .notif-dropdown-panel *, button, button * { max-width: none; }
        /* Fix khusus select di Android: pastikan tidak overflow dan teks tidak keluar */
        select {
            max-width: 100% !important;
            box-sizing: border-box !important;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
            /* Fix double arrow: hapus arrow bawaan semua browser */
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }
        /* Fix: form grid tidak overflow di mobile */
        form .grid, form > div {
            min-width: 0;
        }
        /* Fix: semua input dan select tidak keluar dari parent */
        input, select, textarea {
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }
        /* Spinner animation for inline-styled elements */
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        /* Print styles */
        @media print {
            .no-print, nav, aside, .sidebar, button, .filter-bar, header { display: none !important; }
            .lg\:ml-64, [data-main-content] { margin-left: 0 !important; }
            main, .main-content, .container { max-width: 100% !important; padding: 0 !important; }
            table { width: 100% !important; font-size: 11px; }
            .shadow-sm, .shadow-lg { box-shadow: none !important; }
        }
        /* Notification dropdown: override max-width constraint */
        .notif-dropdown-panel,
        .notif-dropdown-panel * {
            max-width: none !important;
        }
        /* Dropdown panels (absolute positioned) harus bebas dari max-width */
        [id$="-dropdown"],
        [id$="-drop"] {
            max-width: none !important;
        }
        /* Clean Map: Hide leaflet attribution on mobile */
        @media (max-width: 640px) {
            .leaflet-control-attribution {
                display: none !important;
            }
        }
    </style>
</head>
<body class="min-h-full bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-[Inter]">

<div class="flex min-h-screen overflow-x-hidden">
    {{-- SIDEBAR --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-[9000] w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex flex-col transition-all duration-300 lg:translate-x-0 -translate-x-full shadow-sm">

        {{-- Nama Aplikasi --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <img src="{{ asset('img/logo-96.png') }}" alt="Logo Suluh Tani" class="w-14 h-14 object-contain flex-shrink-0" width="56" height="56">
                <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-slate-100 leading-tight">SawitGIS</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Kelompok Tani Suluh Tani</p>
                </div>
            </div>
            {{-- Tombol collapse sidebar (desktop) --}}
            <button onclick="collapseSidebar()" class="hidden lg:block p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Tutup Sidebar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Utama</p>

            <a href="{{ route('dashboard') }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-emerald-700 dark:hover:text-emerald-400' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                Peta Lahan (WebGIS)
            </a>

            <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 mt-4">Data Master</p>

            <a href="{{ route('anggota.index') }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('anggota.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-emerald-700 dark:hover:text-emerald-400' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Anggota Kelompok Tani
            </a>

            <a href="{{ route('blok-lahan.index') }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('blok-lahan.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-emerald-700 dark:hover:text-emerald-400' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                </svg>
                Manajemen Blok Lahan
            </a>

            <a href="{{ route('kondisi-lahan.index') }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('kondisi-lahan.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-emerald-700 dark:hover:text-emerald-400' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Kondisi Lahan
            </a>

            <a href="{{ route('rule-base.index') }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('rule-base.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-emerald-700 dark:hover:text-emerald-400' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Rule Base
            </a>

            <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 mt-4">Analisis</p>

            <a href="{{ route('rbs.index') }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('rbs.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-emerald-700 dark:hover:text-emerald-400' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Analisis Pemupukan
            </a>

            <a href="{{ route('laporan.index') }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('laporan.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-emerald-700 dark:hover:text-emerald-400' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Laporan & Rekap
            </a>

            <p class="px-3 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2 mt-4">Lainnya</p>

            <a href="{{ route('settings.index') }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('settings.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-emerald-700 dark:hover:text-emerald-400' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                Pengaturan
            </a>

            {{-- Menu Panduan (disembunyikan sementara — uncomment untuk menampilkan kembali)
            <a href="{{ route('panduan') }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('panduan') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-emerald-700 dark:hover:text-emerald-400' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Panduan
            </a>
            --}}
        </nav>

        {{-- Admin Info --}}
        <div class="px-4 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-xs font-bold text-white">{{ strtoupper(substr(Auth::guard('admin')->user()->nama_lengkap ?? 'A', 0, 1)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ Auth::guard('admin')->user()->nama_lengkap ?? 'Admin' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ Auth::guard('admin')->user()->username ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                    @csrf
                    <button type="button" onclick="confirmLogout()" title="Logout" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- MOBILE OVERLAY --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-[8999] lg:hidden hidden" onclick="toggleSidebar()"></div>

    {{-- MAIN CONTENT --}}
    <div class="flex-1 flex flex-col lg:ml-64 min-h-screen min-w-0 transition-[margin] duration-300" data-main-content>
        {{-- Top Bar --}}
        <header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <button onclick="collapseSidebar()" class="hidden lg:block p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" title="Toggle Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-slate-100 truncate">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 truncate">@yield('page-subtitle', 'SPK Pemupukan Kelapa Sawit')</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- Notification Bell (E3) --}}
                <div class="relative" id="notif-wrapper">
                    <button onclick="toggleNotifDropdown()" class="relative p-1.5 sm:p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" type="button">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if(($jumlahNotifDarurat ?? 0) > 0)
                        <span class="absolute top-1.5 right-1.5 flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                        @endif
                    </button>
                    {{-- Dropdown --}}
                    <div id="notif-dropdown" class="notif-dropdown-panel absolute right-0 top-full mt-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl z-50 hidden overflow-hidden" style="width:290px;max-width:calc(100vw - 32px);">
                        <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200 whitespace-nowrap">Blok Defisiensi Berat</p>
                            @if(($jumlahNotifDarurat ?? 0) > 0)
                            <span class="inline-flex px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-red-100 text-red-700">{{ $jumlahNotifDarurat }}</span>
                            @endif
                        </div>
                        @if(($notifBlokDarurat ?? collect())->isEmpty())
                        <div class="px-4 py-8 text-center flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mb-2.5 border border-emerald-100">
                                🛡️
                            </div>
                            <p class="text-xs text-slate-800 font-bold">Semua Blok Lahan Aman</p>
                            <p class="text-[10px] text-slate-500 mt-1 leading-relaxed max-w-[200px] mx-auto">Tidak ada blok lahan yang mengalami defisiensi unsur hara tingkat berat saat ini.</p>
                        </div>
                        @else
                        <div class="max-h-60 overflow-y-auto divide-y divide-slate-100">
                            @foreach($notifBlokDarurat ?? [] as $nb)
                            @php $latestRbs = $nb->rekomendasiRbsTerbaru; @endphp
                            <a href="{{ route('rbs.detail', $nb) }}" class="flex items-start gap-2.5 px-4 py-3 hover:bg-slate-50 transition-colors">
                                <div class="w-6 h-6 rounded-full bg-red-50 text-red-600 flex items-center justify-center flex-shrink-0 text-xs font-bold mt-0.5">
                                    🚨
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-slate-800 truncate leading-tight">{{ $nb->nama_blok }}</p>
                                    <p class="text-[10px] text-slate-500 truncate mt-0.5">Pemilik: {{ $nb->anggota?->nama ?? '-' }}</p>
                                    @if($latestRbs)
                                    <p class="text-[9px] text-red-500 mt-0.5 font-semibold">Defisiensi Berat · {{ $latestRbs->tanggal_analisis->diffForHumans() }}</p>
                                    @if(!empty($latestRbs->masalah_teridentifikasi))
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach(array_slice($latestRbs->masalah_teridentifikasi, 0, 2) as $m)
                                        <span class="px-1.5 py-0.5 bg-red-50 text-red-700 border border-red-100 rounded text-[8px] font-medium leading-none whitespace-nowrap truncate max-w-[120px]">{{ $m }}</span>
                                        @endforeach
                                    </div>
                                    @endif
                                    @endif
                                </div>
                            </a>
                            @endforeach
                        </div>
                        <div class="px-4 py-2 border-t border-slate-100 bg-slate-50 text-center">
                            <a href="{{ route('rbs.index') }}" class="text-[10px] text-emerald-600 font-bold hover:text-emerald-700 hover:underline">Lihat Semua Blok →</a>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 text-right leading-tight hidden sm:block">
                    {{ now()->setTimezone('Asia/Makassar')->translatedFormat('l, d F Y') }}
                </div>
            </div>
        </header>

        {{-- Flash Messages (ditampilkan sebagai toast oleh JS di bawah) --}}
        @if(session('success'))
            <div id="flash-success" data-msg="{{ session('success') }}" style="display:none;"></div>
        @endif
        @if(session('error'))
            <div id="flash-error" data-msg="{{ session('error') }}" style="display:none;"></div>
        @endif
        @if(session('warning'))
            <div id="flash-warning" data-msg="{{ session('warning') }}" style="display:none;"></div>
        @endif

        {{-- Page Content --}}
        <main class="flex-1 p-3 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    // Desktop: collapse/expand sidebar
    var sidebarCollapsed = false;
    function collapseSidebar() {
        var sidebar = document.getElementById('sidebar');
        var mainContent = document.querySelector('[data-main-content]');
        sidebarCollapsed = !sidebarCollapsed;
        if (sidebarCollapsed) {
            sidebar.style.transform = 'translateX(-100%)';
            if (mainContent) mainContent.style.marginLeft = '0';
        } else {
            sidebar.style.transform = '';
            if (mainContent) mainContent.style.marginLeft = '';
        }
        // Dispatch event agar peta di halaman lain bisa invalidateSize
        setTimeout(function() {
            document.dispatchEvent(new Event('sidebarToggled'));
        }, 300);
    }

    // ─── Custom Confirm Modal ────────────────────────────────────
    function showConfirm(message, onConfirm) {
        var modal = document.getElementById('confirm-modal');
        var msgEl = document.getElementById('confirm-message');
        msgEl.textContent = message;
        modal.classList.remove('hidden');
        modal._onConfirm = onConfirm;
        // Re-enable confirm button
        var confirmBtn = document.getElementById('confirm-btn-yes');
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '';
        }
    }
    function closeConfirm() {
        document.getElementById('confirm-modal').classList.add('hidden');
    }
    function doConfirm() {
        var modal = document.getElementById('confirm-modal');
        // Disable confirm button to prevent double click
        var confirmBtn = document.getElementById('confirm-btn-yes');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.style.opacity = '0.6';
        }
        if (modal._onConfirm) modal._onConfirm();
        closeConfirm();
    }

    // Helper untuk form delete dengan custom confirm
    function confirmDelete(formEl, nama) {
        showConfirm('Yakin ingin menghapus "' + nama + '"? Data terkait akan ikut terhapus.', function() {
            formEl.submit();
        });
    }
    function confirmLogout() {
        showConfirm('Apakah Anda yakin ingin keluar dari sistem?', function() {
            document.getElementById('logout-form').submit();
        });
    }

    // Back to Top button visibility
    var btnBackTop = document.getElementById('btn-back-top');
    window.addEventListener('scroll', function() {
        if (!btnBackTop) btnBackTop = document.getElementById('btn-back-top');
        if (!btnBackTop) return;
        if (window.scrollY > 300) {
            btnBackTop.style.opacity = '1';
            btnBackTop.style.pointerEvents = 'auto';
        } else {
            btnBackTop.style.opacity = '0';
            btnBackTop.style.pointerEvents = 'none';
        }
    });

    // Notification dropdown toggle (E3)
    function toggleNotifDropdown() {
        var dd = document.getElementById('notif-dropdown');
        dd.classList.toggle('hidden');
    }
    document.addEventListener('click', function(e) {
        var wrapper = document.getElementById('notif-wrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            document.getElementById('notif-dropdown').classList.add('hidden');
        }
    });
</script>

{{-- Global Confirm Modal --}}
<div id="confirm-modal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm hidden">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 p-6 max-w-sm w-full mx-4 animate-[fadeIn_0.15s_ease-out]">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Konfirmasi</h3>
                <p id="confirm-message" class="text-sm text-slate-600 dark:text-slate-300 mt-0.5"></p>
            </div>
        </div>
        <div class="flex gap-2 justify-end">
            <button onclick="closeConfirm()" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition-colors">Batal</button>
            <button id="confirm-btn-yes" onclick="doConfirm()" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors shadow-sm">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

{{-- ═══ GLOBAL TOAST SYSTEM ═══════════════════════════════════════════════ --}}
{{-- Container toast — fixed di pojok kanan atas, responsif --}}
<div id="toast-container"
    style="position:fixed; top:16px; right:16px; z-index:99998; display:flex; flex-direction:column; gap:10px; pointer-events:none; width:360px; max-width:calc(100vw - 32px);"
    aria-live="polite" aria-atomic="false">
</div>

<style>
@keyframes toastSlideIn {
    from { opacity:0; transform:translateX(100%); }
    to   { opacity:1; transform:translateX(0); }
}
@keyframes toastSlideOut {
    from { opacity:1; transform:translateX(0); }
    to   { opacity:0; transform:translateX(110%); }
}
.toast-item {
    pointer-events: auto;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12), 0 2px 6px rgba(0,0,0,0.06);
    font-size: 13px;
    font-weight: 500;
    line-height: 1.45;
    animation: toastSlideIn 0.28s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    position: relative;
    overflow: hidden;
    border: 1px solid transparent;
    word-break: break-word;
}
.toast-item.toast-out {
    animation: toastSlideOut 0.22s ease-in both;
}
/* Progress bar bawah */
.toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    border-radius: 0 0 14px 14px;
    transition: width linear;
}
/* Warna per tipe */
.toast-success { background:#f0fdf4; border-color:#bbf7d0; color:#14532d; }
.toast-success .toast-icon { color:#16a34a; }
.toast-success .toast-progress { background:#16a34a; }

.toast-error { background:#fff1f2; border-color:#fecdd3; color:#881337; }
.toast-error .toast-icon { color:#e11d48; }
.toast-error .toast-progress { background:#e11d48; }

.toast-warning { background:#fffbeb; border-color:#fde68a; color:#78350f; }
.toast-warning .toast-icon { color:#d97706; }
.toast-warning .toast-progress { background:#d97706; }

.toast-info { background:#eff6ff; border-color:#bfdbfe; color:#1e3a5f; }
.toast-info .toast-icon { color:#2563eb; }
.toast-info .toast-progress { background:#2563eb; }

.toast-close-btn {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    line-height: 1;
    opacity: 0.5;
    transition: opacity 0.15s, background 0.15s;
    padding: 0;
}
.toast-close-btn:hover { opacity: 1; background: rgba(0,0,0,0.06); }

@media (max-width: 480px) {
    #toast-container { top:12px; right:12px; left:12px; width:auto; }
    .toast-item { font-size: 12px; padding: 10px 12px; }
}
</style>

<script>
// ═══ GLOBAL TOAST SYSTEM ══════════════════════════════════════════════
(function() {
    // Map untuk deduplication: key = type+message, value = toast element
    var _activeToasts = {};
    var ICONS = {
        success: '<svg class="toast-icon" style="width:18px;height:18px;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        error:   '<svg class="toast-icon" style="width:18px;height:18px;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        warning: '<svg class="toast-icon" style="width:18px;height:18px;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        info:    '<svg class="toast-icon" style="width:18px;height:18px;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    };

    function getContainer() {
        return document.getElementById('toast-container');
    }

    function dismissToast(el, key) {
        if (!el || el._dismissed) return;
        el._dismissed = true;
        el.classList.add('toast-out');
        var bar = el.querySelector('.toast-progress');
        if (bar) bar.style.width = '0%';
        setTimeout(function() {
            if (el.parentNode) el.parentNode.removeChild(el);
            if (key && _activeToasts[key] === el) delete _activeToasts[key];
        }, 240);
    }

    /**
     * showToast(type, message, duration)
     * type: 'success' | 'error' | 'warning' | 'info'
     * duration: ms, default 5000. 0 = persistent (no auto-dismiss)
     *
     * DEDUPLICATION: jika toast dengan message + type yang sama sudah tampil,
     * tidak akan ditambah — hanya progress bar-nya yang di-restart.
     */
    window.showToast = function(type, message, duration) {
        duration = (duration === undefined || duration === null) ? 5000 : duration;
        type = type || 'info';

        var key = type + ':' + message;

        // ─── Deduplication: jika sudah ada, restart timer-nya saja ───
        if (_activeToasts[key] && !_activeToasts[key]._dismissed) {
            var existing = _activeToasts[key];
            // Reset progress bar
            var bar = existing.querySelector('.toast-progress');
            if (bar && duration > 0) {
                bar.style.transition = 'none';
                bar.style.width = '100%';
                setTimeout(function() {
                    bar.style.transition = 'width ' + (duration / 1000) + 's linear';
                    bar.style.width = '0%';
                }, 20);
            }
            // Reset timer
            if (existing._timer) clearTimeout(existing._timer);
            if (duration > 0) {
                existing._timer = setTimeout(function() { dismissToast(existing, key); }, duration);
            }
            // Shake animation untuk feedback
            existing.style.animation = 'none';
            setTimeout(function() { existing.style.animation = ''; }, 10);
            return existing;
        }

        var container = getContainer();
        if (!container) return;

        // Batasi max 5 toast sekaligus
        var all = container.querySelectorAll('.toast-item');
        if (all.length >= 5) {
            dismissToast(all[0], null);
        }

        // Buat elemen toast
        var el = document.createElement('div');
        el.className = 'toast-item toast-' + type;
        el.setAttribute('role', 'alert');
        el.innerHTML =
            (ICONS[type] || ICONS.info) +
            '<span style="flex:1;">' + message + '</span>' +
            '<button class="toast-close-btn" aria-label="Tutup" title="Tutup" onclick="(function(b){' +
                'var t=b.closest(\'.toast-item\');' +
                'window._dismissToastEl(t);})(this)">✕</button>' +
            '<div class="toast-progress" style="width:100%;"></div>';

        container.appendChild(el);
        _activeToasts[key] = el;

        // Animasi progress bar
        var bar = el.querySelector('.toast-progress');
        if (duration > 0 && bar) {
            setTimeout(function() {
                bar.style.transition = 'width ' + (duration / 1000) + 's linear';
                bar.style.width = '0%';
            }, 30);
            el._timer = setTimeout(function() { dismissToast(el, key); }, duration);
        }

        return el;
    };

    // Expose dismiss untuk tombol close inline
    window._dismissToastEl = function(el) {
        if (!el) return;
        // Cari key
        var key = null;
        Object.keys(_activeToasts).forEach(function(k) {
            if (_activeToasts[k] === el) key = k;
        });
        dismissToast(el, key);
    };

    // ─── Auto-show flash messages dari server ──────────────────
    document.addEventListener('DOMContentLoaded', function() {
        var flashSuccess = document.getElementById('flash-success');
        var flashError   = document.getElementById('flash-error');
        var flashWarning = document.getElementById('flash-warning');

        if (flashSuccess && flashSuccess.dataset.msg) {
            showToast('success', flashSuccess.dataset.msg, 6000);
        }
        if (flashError && flashError.dataset.msg) {
            showToast('error', flashError.dataset.msg, 8000);
        }
        if (flashWarning && flashWarning.dataset.msg) {
            showToast('warning', flashWarning.dataset.msg, 8000);
        }
    });
})();
</script>

{{-- Flash data hidden divs (ditampilkan oleh JS toast di atas) --}}

{{-- Back to Top Button --}}
<button id="btn-back-top" onclick="window.scrollTo({top:0,behavior:'smooth'})"
    class="fixed bottom-5 right-5 z-50 w-10 h-10 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all opacity-0 pointer-events-none"
    aria-label="Kembali ke atas">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
</button>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

{{-- Global: Prevent Double Submit pada SEMUA form --}}
<script>
(function() {
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        // Skip form yang ditandai no-prevent (filter forms, search, etc)
        if (form.dataset.noPreventDouble === 'true') return;

        // Skip jika form sudah ditandai submitting
        if (form.dataset.submitting === 'true') {
            e.preventDefault();
            return;
        }

        // Tandai form sedang di-submit
        form.dataset.submitting = 'true';

        // Disable semua submit button di form ini
        var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        buttons.forEach(function(btn) {
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';

            // Simpan teks asli dan ganti dengan loading
            if (btn.tagName === 'BUTTON') {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = '<svg class="animate-spin h-4 w-4 inline-block mr-1.5 align-middle" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="align-middle">Menyimpan...</span>';
            }
        });

        // Safety: re-enable setelah 10 detik (timeout/error jaringan)
        setTimeout(function() {
            form.dataset.submitting = 'false';
            buttons.forEach(function(btn) {
                btn.disabled = false;
                btn.style.opacity = '';
                btn.style.cursor = '';
                if (btn.tagName === 'BUTTON' && btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }, 10000);
    });
})();
</script>

@stack('scripts')
</body>
</html>
