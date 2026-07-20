# PENGUJIAN PAHAN v2.7

## Test Files Baru

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `tests/Feature/RealisasiEligibilityTest.php` | Kelayakan pencatatan realisasi |
| 2 | `tests/Feature/RealisasiTamperedRequestTest.php` | Manipulasi request (tahap, rencana, tahun palsu) |
| 3 | `tests/Feature/RealisasiStatusSelesaiValidationTest.php` | Validasi status SELESAI vs jumlah kumulatif |
| 4 | `tests/Feature/ProgramPemupukanIsolationTest.php` | Isolasi program pemupukan |
| 5 | `tests/Feature/OperationalHistoryTest.php` | Histori operasional dicatat |

## Skenario Test

### Kelayakan (RealisasiEligibilityTest)
- ✅ Status ditunda (curah hujan rendah) → form dan store ditolak
- ✅ Status layak → form diizinkan
- ✅ Akses URL create langsung saat tidak layak → ditolak
- ✅ Program selesai → form ditolak

### Manipulasi Request (RealisasiTamperedRequestTest)
- ✅ Rencana palsu 500 kg → server tetap menyimpan 272 kg
- ✅ Tahap palsu = 2 → server tetap menyimpan tahap = 1
- ✅ Tahun program palsu 2030 → server tetap menyimpan tahun berjalan

### Status Selesai (RealisasiStatusSelesaiValidationTest)
- ✅ Jumlah kurang + SELESAI → gagal
- ✅ Jumlah kumulatif memenuhi rencana → selesai diterima
- ✅ Beberapa SEBAGIAN yang totalnya memenuhi → selesai

### Program (ProgramPemupukanIsolationTest)
- ✅ Program otomatis dibuat saat realisasi pertama
- ✅ Dua realisasi satu blok satu tahun → satu program aktif

### Histori (OperationalHistoryTest)
- ✅ Create realisasi → histori REALISASI_DIBUAT
- ✅ Update realisasi → histori REALISASI_DIPERBARUI
- ✅ Cancel realisasi → histori REALISASI_DIBATALKAN

## Menjalankan Test

```bash
# Semua test
php artisan test

# Test spesifik v2.7
php artisan test --filter=RealisasiEligibilityTest
php artisan test --filter=RealisasiTamperedRequestTest
php artisan test --filter=RealisasiStatusSelesaiValidationTest
php artisan test --filter=ProgramPemupukanIsolationTest
php artisan test --filter=OperationalHistoryTest
```

## Hasil Test

```
Tests:    183 passed (478 assertions)
Duration: ~5s
Pint:     PASS (156 files)
```

## Verifikasi Manual

Checklist verifikasi browser:
- [ ] Buka rekomendasi yang ditunda → tombol realisasi tidak muncul
- [ ] Akses URL create langsung → redirect dengan error
- [ ] Buka rekomendasi Tahap 1 siap → catat realisasi sebagian
- [ ] Coba status selesai dengan jumlah kurang → ditolak
- [ ] Selesaikan Tahap 1 → menunggu 60 hari
- [ ] Setelah 60 hari → Tahap 2 siap
- [ ] Selesaikan program → status SELESAI_TAHUNAN
- [ ] Periksa histori operasional pada halaman detail
- [ ] Download PDF → histori realisasi tampil
