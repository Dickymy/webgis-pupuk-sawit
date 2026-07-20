# MIGRASI PAHAN v2.4

## Perubahan Database

Pahan v2.4 **TIDAK** memerlukan migration baru karena semua field yang dibutuhkan
sudah ada dari v2.3 migration:

- `urea_total_min_tahunan` — sudah ada
- `urea_total_max_tahunan` — sudah ada
- `urea_total_estimasi_tahunan` — sudah ada
- `kcl_total_min_tahunan` — sudah ada
- `kcl_total_max_tahunan` — sudah ada
- `kcl_total_estimasi_tahunan` — sudah ada
- `urea_karung_estimasi_tahunan` — sudah ada
- `kcl_karung_estimasi_tahunan` — sudah ada
- `urea_aplikasi_saat_ini` — sudah ada
- `kcl_aplikasi_saat_ini` — sudah ada

## Upgrade Path

### Dari v2.3 ke v2.4

1. Deploy kode baru (tanpa migration baru)
2. Jalankan `php artisan sawit:finalize-pahan-v2-4 --dry-run` untuk audit
3. Jalankan analisis ulang pada blok yang terdeteksi bermasalah
4. Data lama tetap aman (field null diisi saat analisis ulang)

### Kompatibilitas

- Field lama (`total_urea`, `total_kcl`, `dosis_urea`, `dosis_kcl`) tetap diisi untuk kompatibilitas
- `status_kebutuhan_dominan` tetap diisi (legacy)
- `jadwal_pemupukan` berisi `[]` (array kosong) saat tidak layak — bukan item "Ditunda"
- Histori rekomendasi TIDAK dihapus
- Rule pengguna TIDAK dihapus

## Rollback

Jika perlu rollback dari v2.4 ke v2.3:
- Deploy kode v2.3 kembali
- Database TIDAK perlu diubah (semua field kompatibel)
- Rekomendasi yang dibuat v2.4 tetap bisa dibaca v2.3

## Keamanan

- TIDAK menjalankan `migrate:fresh` pada database produksi
- TIDAK menghapus histori rekomendasi
- TIDAK menghapus rule pengguna
- TIDAK menghitung ulang histori lama secara massal
- Semua perubahan bersifat kode (bukan skema)
