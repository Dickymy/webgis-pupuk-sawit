/**
 * Terapkan tema ke <html> secara instan (tanpa reload halaman).
 * @param {'light'|'dark'|'system'} tema
 */
function applyTheme(tema) {
    const isDark = tema === 'dark'
        || (tema === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    if (isDark) {
        document.documentElement.classList.add('dark');
        document.documentElement.style.colorScheme = 'dark';
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.style.colorScheme = 'light';
    }

    // Simpan di cookie supaya script anti-FOUC di <head> baca nilai terbaru
    document.cookie = `tema_tampilan=${tema}; path=/; max-age=${60 * 60 * 24 * 365}; SameSite=Lax`;
}

// Jalankan sekali saat JS dimuat — pastikan state <html> konsisten dengan cookie
(function () {
    const cookieTema = document.cookie.split('; ').find(row => row.startsWith('tema_tampilan='));
    const tema = cookieTema ? cookieTema.split('=')[1] : 'system';
    applyTheme(tema);
})();

// Kalau admin pilih 'system', ikuti perubahan preferensi OS secara real-time
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const cookieTema = document.cookie.split('; ').find(row => row.startsWith('tema_tampilan='))?.split('=')[1];
    if (cookieTema === 'system' || !cookieTema) {
        applyTheme('system');
    }
});

// Expose globally so settings page can call it
window.applyTheme = applyTheme;
