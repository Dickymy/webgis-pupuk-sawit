# AUDIT FINAL PAHAN-V2 — SawitGIS

**Tanggal Audit:** 20 Juli 2026  
**Versi Mesin:** pahan-v2 → pahan-v2.1 (finalisasi)  
**Auditor:** Kiro AI (Senior Laravel Engineer)

---

## 1. Masalah yang Ditemukan

### 1.1 Bug Kritis: Logika `kondisi_defisiensi` (DIPERBAIKI)

**Lokasi:** `app/Services/RbsService.php` → method `evaluasiRule()`

**Masalah:** Ketika `gejala_defisiensi` kosong/null pada input, kondisi `kondisi_defisiensi` pada rule DILEWATI (`skip`) tanpa return false. Akibatnya rule `warna_daun=Kuning Merata + kondisi_defisiensi=N` bisa terpicu hanya dari warna daun.

**Kode Bermasalah:**
```php
if ($rule->kondisi_defisiensi !== null) {
    $defisiensiInput = $kondisi->gejala_defisiensi ?? [];
    if (!empty($defisiensiInput)) { // ← BUG: skip jika kosong
        // ...
    }
}
```

**Solusi:** Jika rule mensyaratkan defisiensi, input WAJIB berisi data defisiensi yang cocok. Return false jika kosong.

### 1.2 Data Minimum Diagnosis Tidak Ditegakkan

**Masalah:** Analisis bisa berjalan hanya dengan 1 parameter terisi, menghasilkan diagnosis dari data yang sangat terbatas.

**Solusi:** Dibuat `ObservationCompletenessService` dengan syarat minimum:
- Minimal 5 dari 7 parameter penting terisi
- Warna daun WAJIB
- pH tanah ATAU kondisi drainase WAJIB (salah satu)

### 1.3 Fallback Curah Hujan Terlalu Permisif

**Masalah:** Kategori `Rendah` dan `Normal` tanpa data numerik langsung dianggap layak.

**Solusi:** Kategori tanpa nilai mm/bulan → `PERLU_VERIFIKASI_DATA`. Hanya `Sangat Rendah`/`Sangat Tinggi` yang boleh memberi indikasi tunda.

### 1.4 Validasi Fase-Umur Tidak Ada

**Masalah:** User bisa set `fase_tanaman=TBM` pada umur 10 tahun tanpa penolakan.

**Solusi:** `PlantPhaseResolver` sekarang mendeteksi konflik fase-umur dan menolak kombinasi tidak logis.

### 1.5 Umur Dihitung dari now() Bukan Tanggal Observasi

**Masalah:** `BlokLahan::getUmurTanamanAttribute()` selalu gunakan `now()->year`.

**Solusi:** Dibuat `PlantAgeService::calculateAgeAt()` yang menerima tanggal referensi (tanggal observasi).

### 1.6 Halaman Sumber Tabel 9.5 Salah

**Masalah:** `PahanRuleBaseV2Seeder` menggunakan halaman `145-148` untuk rule Tabel 9.5.

**Solusi:** Dikoreksi menjadi `152-153` sesuai referensi Pahan (2013), Cetakan XI.

### 1.7 Rule Zn Tidak Memiliki Sumber Ilmiah Cukup

**Masalah:** Rule VIS-ZN-01 aktif sebagai rule diagnosis utama tanpa sumber spesifik.

**Solusi:** Rule Zn dinonaktifkan (`aktif=false`) dengan status `PERLU_VALIDASI_AHLI`.

### 1.8 Rule Tanaman Tua Otomatis Menghentikan Pemupukan

**Masalah:** Rule UMUR-TUA-01 menggunakan `status_kebutuhan=Tunda` dan menyatakan efisiensi "sangat rendah" sebagai fakta.

**Solusi:** Status diubah ke `Normal`, teks menjadi "Perlu evaluasi produktivitas dan kelayakan peremajaan". Kebutuhan tahunan Pahan tetap dihitung.

---

## 2. File Terdampak

| File | Perubahan |
|------|-----------|
| `app/Services/RbsService.php` | Fix bug defisiensi, integrasi ObservationCompletenessService, PlantAgeService |
| `app/Services/PlantPhaseResolver.php` | Tambah validasi fase-umur, deteksi konflik |
| `app/Services/FertilizationWindowService.php` | Perbaiki fallback curah hujan |
| `app/Services/ObservationCompletenessService.php` | **BARU** — evaluasi kelengkapan data |
| `app/Services/PlantAgeService.php` | **BARU** — umur berdasarkan tanggal observasi |
| `app/Models/RuleBaseLanjutan.php` | Tambah field jenis_rule, tingkat_keparahan, kategori_kesimpulan |
| `app/Models/RekomendasiRbs.php` | Tambah field metode_perhitungan_umur, tanggal_referensi_umur |
| `database/migrations/2026_07_20_100000_*.php` | **BARU** — migration field finalisasi |
| `database/seeders/PahanRuleBaseV2Seeder.php` | Koreksi halaman, nonaktifkan Zn, perbaiki Tanaman Tua, assign kategori |
| `tests/Unit/RuleEvaluationTest.php` | **BARU** — 11 test kasus rule evaluation |
| `tests/Unit/ObservationCompletenessTest.php` | **BARU** — 6 test kasus data minimum |
| `tests/Unit/PlantPhaseValidationTest.php` | **BARU** — 11 test kasus fase-umur |
| `tests/Unit/RainfallFallbackTest.php` | **BARU** — 9 test kasus curah hujan |
| `tests/Unit/PlantAgeServiceTest.php` | **BARU** — 5 test kasus umur observasi |
| `tests/Unit/PlantPhaseResolverTest.php` | Updated — sesuaikan test lama dengan validasi baru |

---

## 3. Solusi yang Diterapkan

| No | Prioritas | Masalah | Solusi |
|----|-----------|---------|--------|
| 1 | KRITIS | Bug defisiensi → rule terpicu salah | AND logic ketat + strict comparison |
| 2 | KRITIS | Data minimum tidak ditegakkan | ObservationCompletenessService |
| 3 | KRITIS | Status kondisi vs aplikasi tercampur | Dua output terpisah di RbsService |
| 4 | PENTING | Dosis pupuk pendukung tanpa sumber | Teks dosis diganti catatan verifikasi |
| 5 | PENTING | Halaman Tabel 9.5 salah | Dikoreksi 145-148 → 152-153 |
| 6 | PENTING | Validasi fase-umur | PlantPhaseResolver + detectPhaseConflict |
| 7 | PENTING | Umur pakai now() bukan tanggal observasi | PlantAgeService |
| 8 | PENTING | Fallback curah hujan terlalu permisif | Kategori tanpa angka → PERLU_VERIFIKASI |
| 9 | PENTING | Kebutuhan tahunan hilang saat tunda | Kolom pahan-v2 selalu terisi |
| 10 | MEDIUM | Rule Zn aktif tanpa sumber | Dinonaktifkan |
| 11 | MEDIUM | Rule Tanaman Tua otomatis stop | Diubah ke evaluasi, bukan penghentian |

---

## 4. Risiko Kompatibilitas

- **Kolom lama tetap diisi:** `dosis_urea`, `dosis_kcl`, `total_urea`, `total_kcl` tetap diisi dari estimasi untuk view lama.
- **Histori tidak berubah:** Tidak ada perubahan pada rekomendasi yang sudah tersimpan.
- **Migration nullable:** Semua kolom baru nullable → data lama aman.
- **Seeder idempotent:** `PahanRuleBaseV2Seeder` menggunakan updateOrCreate → aman dijalankan berulang.
- **Rule buatan user:** Tidak tersentuh (hanya system rule yang diupdate berdasarkan `kode_rule`/`indikasi_masalah`).

---

## 5. Hasil Pengujian

```
Tests:    94 passed (196 assertions)
Duration: 5.07s

npm run build: ✓ built in 4.59s (345 modules)
```

Semua test existing + test baru lulus tanpa kegagalan.

---

## 6. Langkah Deployment

```bash
git checkout -b fix/finalisasi-rbs-pahan-v2
git add .
git commit -m "fix: finalisasi pahan-v2 — bug defisiensi, data minimum, fase-umur, curah hujan"

# Deploy
composer install --no-dev --optimize-autoloader
php artisan migrate
php artisan db:seed --class=PahanRuleBaseV2Seeder
php artisan optimize:clear
php artisan test
npm install
npm run build
```
