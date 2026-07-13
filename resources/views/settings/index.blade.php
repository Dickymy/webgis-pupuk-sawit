@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')
@section('page-subtitle', 'Kelola password & tampilan aplikasi')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 space-y-6">

    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-100">Pengaturan Akun</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Kelola password dan tampilan aplikasi Anda.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- ================= KARTU 1: GANTI PASSWORD ================= --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 sm:p-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Ganti Password</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Login sebagai <span class="font-medium">{{ $admin->username }}</span>. Masukkan password lama lalu password baru.
        </p>

        <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="password_lama" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Password Lama
                </label>
                <input type="password" id="password_lama" name="password_lama"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100
                           focus:border-green-500 focus:ring-green-500 text-sm"
                    autocomplete="current-password" required>
                @error('password_lama')
                    <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_baru" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Password Baru
                </label>
                <input type="password" id="password_baru" name="password_baru"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100
                           focus:border-green-500 focus:ring-green-500 text-sm"
                    autocomplete="new-password" required>
                <p class="text-xs text-gray-400 mt-1">Minimal 8 karakter, kombinasi huruf & angka.</p>
                @error('password_baru')
                    <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_baru_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Konfirmasi Password Baru
                </label>
                <input type="password" id="password_baru_confirmation" name="password_baru_confirmation"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100
                           focus:border-green-500 focus:ring-green-500 text-sm"
                    autocomplete="new-password" required>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-green-600 hover:bg-green-700
                           dark:bg-green-700 dark:hover:bg-green-600 text-white text-sm font-medium transition">
                    Simpan Password Baru
                </button>
            </div>
        </form>
    </div>

    {{-- ================= KARTU 2: MODE TAMPILAN ================= --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5 sm:p-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Mode Tampilan</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Pilih tampilan terang, gelap, atau ikuti sistem perangkat.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" id="theme-options">
            <button type="button" data-tema="light"
                class="theme-btn flex flex-col items-center gap-2 border-2 rounded-lg p-4 text-sm font-medium
                       text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600 hover:border-green-400 dark:hover:border-green-400 transition">
                <span class="text-2xl">☀️</span> Terang
            </button>
            <button type="button" data-tema="dark"
                class="theme-btn flex flex-col items-center gap-2 border-2 rounded-lg p-4 text-sm font-medium
                       text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600 hover:border-green-400 dark:hover:border-green-400 transition">
                <span class="text-2xl">🌙</span> Gelap
            </button>
            <button type="button" data-tema="system"
                class="theme-btn flex flex-col items-center gap-2 border-2 rounded-lg p-4 text-sm font-medium
                       text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600 hover:border-green-400 dark:hover:border-green-400 transition">
                <span class="text-2xl">🖥️</span> Ikuti Sistem
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const temaAktif = '{{ $admin->tema }}';
        highlightThemeButton(temaAktif);

        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tema = btn.dataset.tema;
                applyTheme(tema);
                highlightThemeButton(tema);
                saveThemeToServer(tema);
            });
        });
    });

    function highlightThemeButton(tema) {
        document.querySelectorAll('.theme-btn').forEach(btn => {
            const active = btn.dataset.tema === tema;
            btn.classList.toggle('border-green-500', active);
            btn.classList.toggle('dark:border-green-500', active);
            btn.classList.toggle('bg-green-50', active);
            btn.classList.toggle('dark:bg-green-900/20', active);
            // Remove default border when active
            if (active) {
                btn.classList.remove('border-gray-200', 'dark:border-gray-600');
            } else {
                btn.classList.add('border-gray-200', 'dark:border-gray-600');
                btn.classList.remove('border-green-500', 'dark:border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
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
            body: JSON.stringify({ tema }),
        }).catch(() => {
            console.warn('Gagal menyimpan preferensi tema ke server.');
        });
    }
</script>
@endpush
@endsection
