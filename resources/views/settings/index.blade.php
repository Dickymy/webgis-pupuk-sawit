@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')
@section('page-subtitle', 'Kelola password & tampilan aplikasi')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 space-y-4">

    @if (session('success'))
        <div class="flex items-center gap-3 rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ================= ACCORDION: GANTI PASSWORD ================= --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
        <button type="button" onclick="toggleAccordion('password-section')"
            class="w-full flex items-center justify-between px-5 sm:px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="text-left">
                    <h2 class="text-sm sm:text-base font-semibold text-slate-800 dark:text-slate-100">Ganti Password</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Ubah password akun Anda</p>
                </div>
            </div>
            <svg id="password-section-chevron" class="w-5 h-5 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div id="password-section" class="hidden border-t border-slate-100 dark:border-slate-700">
            <div class="px-5 sm:px-6 py-5">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                    Login sebagai <span class="font-medium text-slate-700 dark:text-slate-300">{{ $admin->username }}</span>. Masukkan password lama lalu password baru.
                </p>

                <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="password_lama" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Password Lama
                        </label>
                        <div class="relative">
                            <input type="password" id="password_lama" name="password_lama"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100
                                       focus:border-emerald-500 focus:ring-emerald-500 text-sm pr-10"
                                autocomplete="current-password" required>
                            <button type="button" onclick="togglePassword('password_lama')" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" tabindex="-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        @error('password_lama')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_baru" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Password Baru
                        </label>
                        <div class="relative">
                            <input type="password" id="password_baru" name="password_baru"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100
                                       focus:border-emerald-500 focus:ring-emerald-500 text-sm pr-10"
                                autocomplete="new-password" required>
                            <button type="button" onclick="togglePassword('password_baru')" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" tabindex="-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Minimal 8 karakter, kombinasi huruf & angka.</p>
                        @error('password_baru')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_baru_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Konfirmasi Password Baru
                        </label>
                        <div class="relative">
                            <input type="password" id="password_baru_confirmation" name="password_baru_confirmation"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100
                                       focus:border-emerald-500 focus:ring-emerald-500 text-sm pr-10"
                                autocomplete="new-password" required>
                            <button type="button" onclick="togglePassword('password_baru_confirmation')" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" tabindex="-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700
                                   dark:bg-emerald-700 dark:hover:bg-emerald-600 text-white text-sm font-medium
                                   transition shadow-sm hover:shadow-md">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Simpan Password Baru
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================= ACCORDION: MODE TAMPILAN ================= --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
        <button type="button" onclick="toggleAccordion('theme-section')"
            class="w-full flex items-center justify-between px-5 sm:px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                </div>
                <div class="text-left">
                    <h2 class="text-sm sm:text-base font-semibold text-slate-800 dark:text-slate-100">Mode Tampilan</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pilih tema terang, gelap, atau ikuti sistem</p>
                </div>
            </div>
            <svg id="theme-section-chevron" class="w-5 h-5 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div id="theme-section" class="hidden border-t border-slate-100 dark:border-slate-700">
            <div class="px-5 sm:px-6 py-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" id="theme-options">
                    <button type="button" data-tema="light"
                        class="theme-btn group flex flex-col items-center gap-2.5 border-2 rounded-xl p-4 sm:p-5 text-sm font-medium
                               text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-600 hover:border-emerald-400 dark:hover:border-emerald-400 transition-all duration-200 hover:shadow-sm">
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-xl">☀️</span>
                        </div>
                        <span>Terang</span>
                    </button>
                    <button type="button" data-tema="dark"
                        class="theme-btn group flex flex-col items-center gap-2.5 border-2 rounded-xl p-4 sm:p-5 text-sm font-medium
                               text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-600 hover:border-emerald-400 dark:hover:border-emerald-400 transition-all duration-200 hover:shadow-sm">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-xl">🌙</span>
                        </div>
                        <span>Gelap</span>
                    </button>
                    <button type="button" data-tema="system"
                        class="theme-btn group flex flex-col items-center gap-2.5 border-2 rounded-xl p-4 sm:p-5 text-sm font-medium
                               text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-600 hover:border-emerald-400 dark:hover:border-emerald-400 transition-all duration-200 hover:shadow-sm">
                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="text-xl">🖥️</span>
                        </div>
                        <span>Ikuti Sistem</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    // Accordion toggle
    function toggleAccordion(sectionId) {
        var section = document.getElementById(sectionId);
        var chevron = document.getElementById(sectionId + '-chevron');

        if (section.classList.contains('hidden')) {
            section.classList.remove('hidden');
            chevron.style.transform = 'rotate(180deg)';
        } else {
            section.classList.add('hidden');
            chevron.style.transform = 'rotate(0deg)';
        }
    }

    // Toggle show/hide password
    function togglePassword(inputId) {
        var input = document.getElementById(inputId);
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    document.addEventListener('DOMContentLoaded', function() {
        var temaAktif = '{{ $admin->tema ?? "system" }}';
        highlightThemeButton(temaAktif);

        document.querySelectorAll('.theme-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tema = btn.dataset.tema;
                applyTheme(tema);
                highlightThemeButton(tema);
                saveThemeToServer(tema);
            });
        });

        // Auto-open password section if there are validation errors
        @if($errors->any())
            toggleAccordion('password-section');
        @endif
    });

    function highlightThemeButton(tema) {
        document.querySelectorAll('.theme-btn').forEach(function(btn) {
            var active = btn.dataset.tema === tema;
            btn.classList.toggle('border-emerald-500', active);
            btn.classList.toggle('dark:border-emerald-500', active);
            btn.classList.toggle('bg-emerald-50', active);
            btn.classList.toggle('dark:bg-emerald-900/20', active);
            btn.classList.toggle('shadow-sm', active);
            if (active) {
                btn.classList.remove('border-slate-200', 'dark:border-slate-600');
            } else {
                btn.classList.add('border-slate-200', 'dark:border-slate-600');
                btn.classList.remove('border-emerald-500', 'dark:border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-900/20', 'shadow-sm');
            }
        });
    }

    function saveThemeToServer(tema) {
        fetch('{{ route("settings.theme.update") }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ tema: tema }),
        }).catch(function() {
            console.warn('Gagal menyimpan preferensi tema ke server.');
        });
    }
</script>
@endpush
@endsection
