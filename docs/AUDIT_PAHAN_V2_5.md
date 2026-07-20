# Audit Pahan v2.5

## Ringkasan Perubahan

### 1. Aplikasi Saat Ini = Tahap Aktif (Bukan Total Tahunan)
- Sebelumnya: `urea_aplikasi_saat_ini = total_estimasi_tahunan` saat layak
- Sekarang: `urea_aplikasi_saat_ini = 50% kebutuhan_tahunan` (Tahap 1) atau `sisa_aktual` (Tahap 2)

### 2. Dashboard Frontend Menggunakan Status Baru
- Filter utama: `status_kondisi_tanaman` (GEJALA_BERAT, TERINDIKASI_DEFISIENSI, dll)
- Warna polygon: berdasarkan kondisi tanaman
- Kelayakan aplikasi: badge/filter sekunder
- Status legacy (Darurat/Segera/Normal/Tunda) dihapus dari filter

### 3. Integrasi Realisasi Pemupukan
- `FertilizationRealizationService`: mengelola data realisasi
- `CurrentApplicationCalculator`: menghitung tahap aktif berdasarkan realisasi
- Tahap 2 = sisa aktual (bukan selalu 50%)

### 4. Fingerprint Mencakup Komponen Baru
- luas_ha_snapshot, sph_snapshot, jumlah_pokok_snapshot
- urea_total_estimasi_tahunan, kcl_total_estimasi_tahunan
- urea_aplikasi_saat_ini, kcl_aplikasi_saat_ini
- urea_sisa_tahunan, kcl_sisa_tahunan
- active_stage, status_stage

### 5. Snapshot Luas dan SPH
- Disimpan saat analisis agar PDF histori tidak berubah

## Command Audit
```bash
php artisan sawit:finalize-pahan-v2-5 --dry-run
```

## Versi Mesin
```
pahan-v2.5
```
