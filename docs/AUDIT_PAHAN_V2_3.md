# AUDIT PAHAN v2.3

## Ringkasan Temuan

Audit dilakukan pada seluruh source code mesin rekomendasi SawitGIS Pahan v2.2 untuk mengidentifikasi masalah yang diperbaiki di v2.3.

## 1. Fase Historis (KRITIS)

**Masalah**: `PlantPhaseResolver::resolve()` mengambil `$blok->fase_tanaman` (fase SAAT INI) untuk analisis historis. Contoh: blok ditanam 2020, observasi 2022, umur observasi = 2 tahun → harusnya TBM, tapi bisa salah jika blok sudah di-update ke TM.

**Solusi**: Buat `PlantContextService` yang menentukan fase berdasarkan umur PADA TANGGAL OBSERVASI, bukan fase blok saat ini.

## 2. Fase NULL untuk Umur Tidak Ambigu

**Masalah**: `StoreBlokLahanRequest` memvalidasi konsistensi fase, tapi tidak auto-set. Umur 2 tahun bisa tersimpan tanpa fase (NULL).

**Solusi**: Auto-set fase di `BlokLahanController::autoSetFase()`:
- umur < 3 → TBM otomatis
- umur > 3 → TM otomatis  
- umur = 3 → validasi reject jika NULL

## 3. Singkatan TBM/TM di UI

**Masalah**: `DashboardController` mengirim `$blok->fase_tanaman` (kode internal TBM/TM) ke view.

**Solusi**: Ganti dengan `$blok->fase_label` yang mengembalikan label lengkap.

## 4. Status Kondisi vs Kelayakan Tidak Terpisah

**Masalah**: Di `susunHasil()`, status `Darurat`/`Tunda` dari rule langsung dipakai untuk menunda aplikasi. Kondisi berat otomatis menunda Urea/KCl.

**Solusi**: 
- `status_kondisi_tanaman` hanya dari rule `DIAGNOSIS_VISUAL`
- `status_kelayakan_aplikasi` hanya dari `FertilizationWindowService`
- Kondisi berat + waktu layak = tetap boleh dijadwalkan

## 5. Jadwal Pemupukan

**Masalah**: 
- Pembagian 70/30 (Darurat) dan 60/40 (Segera)
- Penentuan Maret/September otomatis
- Tahap kedua selalu 6 bulan setelah observasi
- Jeda Urea-KCl 2-3 minggu tanpa sumber

**Solusi**: `FertilizationScheduleService`:
- Default 50/50
- Tidak ada bulan otomatis
- Tahap 2 menunggu realisasi tahap 1 (min 60 hari)
- Jeda Urea-KCl dinonaktifkan

## 6. Pupuk Pendukung

**Masalah**: Dosis Dolomit, Kieserit, Boraks, dll langsung disalin dari `$rule->dosis_anjuran` tanpa validasi.

**Solusi**: `SupportingFertilizerSanitizer`:
- Angka hanya tampil jika `status_validasi = TERVERIFIKASI_SUMBER` atau `TERVERIFIKASI_AHLI`
- Jika belum valid → pesan umum tanpa angka

## 7. Kebutuhan Tahunan vs Aplikasi Saat Ini

**Masalah**: Field lama `total_urea`/`total_kcl` menjadi 0 saat ditunda, menghilangkan kebutuhan tahunan.

**Solusi**: Field baru `urea_total_estimasi_tahunan` dsb selalu terisi. `urea_aplikasi_saat_ini` = 0 saat ditunda.

## 8. Satu Sumber Kelengkapan Data

**Masalah**: `cekKecukupanData()` dan `kondisiCukup()` duplikasi logika `ObservationCompletenessService`.

**Solusi**: `ObservationCompletenessService` menjadi sumber utama. Method lama dipertahankan sebagai wrapper minimal.

## 9. Duplicate Keys

**Masalah**: Di `hasilDataTidakCukup()`:
```php
'dosis_urea' => $dosisRef['dosis_urea'],
'dosis_kcl'  => $dosisRef['dosis_kcl'],
'dosis_urea' => 0.0,
'dosis_kcl'  => 0.0,
```

**Solusi**: Dihapus, hanya satu assignment.

## 10. Typo Method Name

**Masalah**: `hasilDosisBasarTanpaDiagnosis` (Basar → Dasar)

**Solusi**: Renamed ke `hasilDosisDasarTanpaDiagnosis`

## 11. Migration

**Masalah**: Tiga migration menambah kolom ke `rekomendasi_rbs` — potensi duplikat kolom.

**Solusi**: Semua migration menggunakan `Schema::hasColumn()` guard. Migration baru v2.3 aman untuk upgrade dan fresh install.

---

Tanggal audit: 2026-07-20
Versi target: pahan-v2.3
