# Checklist Demo Skripsi — SawitGIS v2.9

## Sebelum Demo

- [ ] Backup database: `php artisan sawit:backup-database`
- [ ] Cek migration status: `php artisan migrate:status`
- [ ] Data demo tersedia: `php artisan db:seed --class=DemoSawitGisSeeder`
- [ ] Akun admin dapat login
- [ ] Peta tampil (cek koneksi internet untuk tile)
- [ ] Koneksi internet aktif
- [ ] Fallback jika internet gagal (screenshot peta)
- [ ] Browser diuji (Chrome / Firefox)
- [ ] PDF dapat dibuat: cek laporan → export PDF
- [ ] Cache dibersihkan: `php artisan cache:clear`
- [ ] Health check: `php artisan sawit:health-check --dry-run`

## Urutan Demo

1. **Dashboard dan Peta** — statistik ringkasan, polygon blok, warna status
2. **Anggota** — daftar anggota kelompok tani
3. **Blok Lahan** — data blok, luas, SPH, tahun tanam
4. **Kondisi Lahan** — isi observasi: warna daun, pH, curah hujan, drainase
5. **Analisis RBS** — jalankan analisis, tunjukkan rule terpicu
6. **Status Kondisi & Kelayakan** — status tanaman vs kelayakan aplikasi
7. **Kebutuhan Tahunan** — total Urea dan KCl berdasarkan Pahan 2013
8. **Aplikasi Tahap 1** — 50% kebutuhan tahunan
9. **Realisasi** — catat pelaksanaan
10. **Interval 60 Hari** — menunggu sebelum Tahap 2
11. **Tahap 2** — sisa aktual
12. **PDF Laporan** — export & cetak
13. **Histori** — riwayat operasional

## Rencana Cadangan

- [ ] Screenshot halaman penting disimpan di folder terpisah
- [ ] PDF contoh sudah dicetak
- [ ] Database demo lokal tersedia (backup .sql)
- [ ] Browser kedua siap
- [ ] Data siap pakai (tidak perlu input ulang)
- [ ] Jangan bergantung penuh pada API cuaca — data curah hujan bisa manual
- [ ] Jika peta gagal load tile, tunjukkan screenshot

## Setelah Demo

- [ ] Reset data demo jika perlu: `php artisan sawit:reset-demo-data`
- [ ] Catat pertanyaan penguji
- [ ] Catat masalah yang muncul
