# PANDUAN PENGUJIAN PAHAN v2.3

## Perintah Pengujian

```bash
composer install
php artisan optimize:clear
php artisan test
npm ci
npm run build
vendor/bin/pint --test
```

## Migration Testing

```bash
php artisan migrate:fresh --env=testing
php artisan test
php artisan migrate:rollback --step=1 --env=testing
php artisan migrate --env=testing
```

## Test Cases (Acceptance Criteria)

### Feature Test Blok
- [ ] Umur 2 → fase otomatis TBM tersimpan
- [ ] Umur 10 → fase otomatis TM tersimpan
- [ ] Umur 3 tanpa fase → validasi ditolak
- [ ] Umur 3 dengan fase → diterima
- [ ] GeoJSON invalid → ditolak
- [ ] Tanpa autentikasi → 302 redirect login

### Feature Test Kondisi
- [ ] Tanggal pemupukan setelah observasi → ditolak
- [ ] Observasi sebelum tahun tanam → ditolak
- [ ] Data hujan numerik → diterima
- [ ] Sumber Open-Meteo tanpa periode → divalidasi

### Integration Test RBS
- [ ] Umur observasi menentukan kelompok dosis
- [ ] Fase historis mengikuti umur observasi (bukan fase blok saat ini)
- [ ] Data tidak cukup → diagnosis tidak dijalankan
- [ ] Data tidak cukup → jadwal kosong
- [ ] Kondisi berat + hujan layak → TIDAK otomatis ditunda
- [ ] Kondisi berat + hujan tinggi → dua status terpisah
- [ ] Kebutuhan tahunan tetap ada saat ditunda
- [ ] Aplikasi saat ini = 0 saat ditunda
- [ ] Fingerprint konsisten (hash deterministik)
- [ ] Histori aman (is_latest, nomor_analisis)

### Test Sanitizer
- [ ] Dolomit belum tervalidasi → angka disembunyikan
- [ ] Boraks belum tervalidasi → angka disembunyikan
- [ ] Rule tervalidasi sumber → angka boleh tampil
- [ ] Rule tervalidasi ahli → angka boleh tampil

### Test Jadwal
- [ ] Pembagian 50/50
- [ ] Tidak menggunakan Darurat/Segera untuk pembagian
- [ ] Tidak menetapkan Maret/September
- [ ] Tahap 2 menunggu realisasi tahap 1
- [ ] Interval minimal 60 hari
- [ ] Data tidak cukup → jadwal kosong
- [ ] Label fase lengkap (bukan singkatan)

### Test PDF dan UI
- [ ] PDF memakai umur snapshot
- [ ] PDF menampilkan fase lengkap
- [ ] PDF menampilkan kebutuhan tahunan saat ditunda
- [ ] Popup WebGIS memakai label lengkap
- [ ] Tidak ada teks TBM/TM pada UI pengguna
