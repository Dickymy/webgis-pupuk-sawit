<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login Admin - SPK Pemupukan Kelapa Sawit">
    <title>Login Admin — SawitGIS</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-[Inter] flex items-center justify-center min-h-screen relative overflow-hidden">

    {{-- Animated background particles --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-emerald-100/40 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-green-100/40 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-50 rounded-full blur-3xl"></div>
    </div>

    {{-- Grid pattern --}}
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(0,0,0,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,.1) 1px, transparent 1px); background-size: 40px 40px;"></div>

    <div class="relative z-10 w-full max-w-md px-6 py-8">
        {{-- Title --}}
        <div class="text-center mb-8">
            <img src="{{ asset('img/logo-96.png') }}" alt="Logo Suluh Tani" class="w-24 h-24 object-contain mx-auto mb-4" width="96" height="96">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-none">SawitGIS</h1>
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mt-2.5">Sistem Pendukung Keputusan Pemupukan</p>
            <p class="text-slate-400 text-[10px] mt-1">Portal Internal Admin Kelompok Tani Suluh Tani</p>
        </div>

        {{-- Login Card --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-xl">
            <h2 class="text-base font-bold text-slate-800 mb-5">Masuk ke Sistem</h2>

            {{-- Error alert --}}
            @if($errors->any())
                <div class="mb-5 flex items-start gap-3 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs leading-relaxed">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf

                {{-- Username --}}
                <div>
                    <label for="username" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="{{ old('username') }}"
                            autocomplete="username"
                            required
                            placeholder="Masukkan username"
                            class="w-full pl-10 pr-4 py-2.5 bg-white border {{ $errors->has('username') ? 'border-red-500' : 'border-slate-200' }} rounded-xl text-base text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                        >
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            placeholder="Masukkan password"
                            class="w-full pl-10 pr-4 py-2.5 bg-white border {{ $errors->has('password') ? 'border-red-500' : 'border-slate-200' }} rounded-xl text-base text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                        >
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-2">
                    <button
                        type="submit"
                        id="btn-login"
                        class="w-full py-2.5 px-4 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-500 hover:to-green-500 text-white text-sm font-bold rounded-xl shadow-md shadow-emerald-600/10 transition-all duration-200 hover:shadow-emerald-600/20 hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-white"
                    >
                        Masuk ke Sistem
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
