# Revisi Pahan v2.5

## Perubahan dari v2.4

| No | Komponen | v2.4 | v2.5 |
|----|----------|------|------|
| 1 | Aplikasi saat ini | = total estimasi tahunan | = 50% (Tahap 1) atau sisa aktual (Tahap 2) |
| 2 | Dashboard filter | status_rbs (legacy) | status_kondisi_tanaman |
| 3 | Realisasi | Tidak terintegrasi | FertilizationRealizationService |
| 4 | Fingerprint | 12 komponen | 23 komponen (+ luas/SPH/realisasi) |
| 5 | Snapshot | Hanya jumlah_pokok | + luas_ha_snapshot + sph_snapshot |
| 6 | Jadwal | Selalu 2 tahap (50/50) | 1 tahap aktif dari CurrentApplicationCalculator |
| 7 | PDF disclaimer | 2 disclaimer | 1 disclaimer |
| 8 | Status tahap | Tidak ada | 7 status tahap |
| 9 | Engine version | pahan-v2.4 | pahan-v2.5 |

## File yang Berubah

### Services
- `AnnualFertilizerSnapshotBuilder.php` — output 50% jika layak + luas/sph snapshot
- `CurrentApplicationCalculator.php` — BARU: hitung tahap aktif
- `FertilizationRealizationService.php` — BARU: query realisasi
- `FertilizationScheduleService.php` — satu entry tahap aktif, persiapan jadi prasyarat
- `RbsService.php` — integrasi realisasi + currentApp di semua method

### Models
- `RealisasiPemupukan.php` — BARU
- `RekomendasiRbs.php` — tambah field v2.5

### Controllers
- `DashboardController.php` — hapus status_rbs, pakai status_kondisi

### Views
- `dashboard/index.blade.php` — full refactor status baru
- `laporan/pdf.blade.php` — tahap aktif, hapus disclaimer ganda
- `laporan/show.blade.php` — tahap aktif info
- `rbs/detail.blade.php` — tahap aktif info
- `rbs/partials/_hasil_rbs.blade.php` — tahap aktif info

### Migrations
- `2026_07_20_000001` — upgrade realisasi_pemupukans
- `2026_07_20_000002` — add v2.5 fields to rekomendasi_rbs

### Tests
- 7 test class baru (28 test methods)

### Config
- `fertilization.php` — engine_version → pahan-v2.5
