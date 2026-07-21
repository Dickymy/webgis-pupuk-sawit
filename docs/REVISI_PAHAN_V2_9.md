# Revisi Pahan v2.9 — Stabilisasi Demo & Pengujian Lapangan

## Ringkasan

Versi v2.9 berfokus pada stabilisasi, bukan fitur baru. Tujuan utama:
- Kesiapan demo skripsi
- Keamanan pengujian lapangan
- Konsistensi data dan tampilan
- Penghapusan sisa konsep verifikasi manual

## Perubahan Teknis

### 1. Engine Version
- Config `fertilization.engine_version` diubah ke `pahan-v2.9`
- Semua rekomendasi baru akan memakai versi ini

### 2. Penghapusan Bobot Validasi Ahli
- `validasi_ahli` (bobot 5) dihapus dari `reliability_weights`
- Redistribusi:
  - `curah_hujan`: 15 → 20
  - `data_visual`: 15 → 20
  - `rule_bersumber`: 10 → 5
- Total tetap 100
- Saran "Verifikasi fase tanaman (TBM/TM)" diganti "Lengkapi fase tanaman pada data blok."

### 3. Demo Seeder (DemoSawitGisSeeder)
- 5 anggota, 10 blok, 8 kondisi lahan
- Semua memakai prefix "DEMO -"
- Tidak dipanggil dari DatabaseSeeder
- Idempoten (firstOrCreate)

### 4. Reset Demo Command
- `php artisan sawit:reset-demo-data`
- Hanya hapus data prefix "DEMO -"
- Wajib konfirmasi
- Diblokir di production tanpa --force
- Mendukung --dry-run

### 5. Backup Commands
- `php artisan sawit:backup-database` — MySQL dump ke storage/app/backups/
- `php artisan sawit:backup-list` — Daftar backup yang tersedia

### 6. Health Check Command
- `php artisan sawit:health-check --dry-run`
- 20+ pemeriksaan integritas data
- Kategorisasi: Database, Program, Rekomendasi, Realisasi, Histori, Konfigurasi

### 7. Finalize Audit v2.9
- `php artisan sawit:finalize-pahan-v2-9 --dry-run`
- 17 pemeriksaan kode dan konfigurasi
- Memanggil health-check sebagai bagian dari audit

### 8. Error Pages
- 403, 404, 419, 422, 500, 503
- Bahasa Indonesia sederhana
- Tanpa stack trace
- Tombol kembali ke dashboard

### 9. Mode Demo
- `APP_DEMO_MODE=false` di .env.example
- Tidak mengubah perhitungan

## Test Baru (12 file)

| File | Tujuan |
|------|--------|
| DemoSeederSafetyTest | Seeder demo aman & idempoten |
| DemoResetSafetyTest | Reset hanya hapus demo |
| NoManualVerificationFeatureTest | Tidak ada fitur verifikasi manual |
| ReliabilityWeightTotalTest | Bobot = 100, tanpa validasi_ahli |
| DoubleSubmitAnalysisTest | Analisis double-click aman |
| DoubleSubmitRealizationTest | Realisasi double-click aman |
| FriendlyErrorPageTest | Error pages ada & ramah |
| DatabaseHealthCheckTest | Health check mendeteksi masalah |
| PdfConsistencyTest | PDF dapat dibuat |
| MapInvalidGeoJsonTest | Peta tahan GeoJSON rusak |
| DemoModeTest | Mode demo tidak ubah perhitungan |
| ProductionSafetyTest | Keamanan production |
