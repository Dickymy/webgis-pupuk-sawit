# TRACEABILITY PAHAN v2.4

## Mapping Acceptance Criteria → Implementasi

| # | Acceptance Criteria | File | Method/Section |
|---|-------|------|------|
| 1 | Fase historis menjadi dasar dosis | `RbsService.php` | `hitungDosisStandar($blok, $kondisi, $plantContext)` |
| 2 | Snapshot fase dan dosis konsisten | `PlantContextService.php` | `resolve()` |
| 3 | Umur 3 tanpa verifikasi tidak menghasilkan dosis | `RbsService.php` | `hasilPerluVerifikasiFase()` |
| 4 | Seluruh total tahunan tersimpan | `AnnualFertilizerSnapshotBuilder.php` | `build()` |
| 5 | Aplikasi saat ini tersimpan | `AnnualFertilizerSnapshotBuilder.php` | `build()` |
| 6 | Kebutuhan tahunan tetap ada saat ditunda | `AnnualFertilizerSnapshotBuilder.php` | `build($blok, $doseRef, false)` |
| 7 | Jadwal kosong saat tidak layak | `FertilizationScheduleService.php` | `generate()` → `return []` |
| 8 | Jadwal kosong saat hujan null | `FertilizationScheduleService.php` | `generate()` → `return []` |
| 9 | Jadwal kosong saat data tidak cukup | `RbsService.php` | `hasilDataTidakCukup()` / `hasilDosisDasarTanpaDiagnosis()` |
| 10 | Status legacy tidak mengendalikan keputusan | `RbsService.php` | `tentukanCatatanDosis()` |
| 11 | PDF memakai umur snapshot | `pdf.blade.php` | Section 3 info lahan |
| 12 | PDF memakai total tahunan | `pdf.blade.php` | Section 4 kebutuhan pupuk |
| 13 | PDF menampilkan kondisi & kelayakan terpisah | `pdf.blade.php` | Section 2 dual status banner |
| 14 | Dashboard memakai status baru | `DashboardController.php` | `index()` |
| 15 | Sanitizer mewajibkan metadata lengkap | `SupportingFertilizerSanitizer.php` | `isValidated()` |
| 16 | Tidak ada singkatan fase pada teks pengguna | `PlantPhaseResolver.php` | `resolve()` messages |
| 17 | Dead code lama dihapus | `RbsService.php` | Comment block at line ~168 |
| 18 | Fresh migration lulus | `.github/workflows/tests.yml` | `migrate:fresh` step |
| 19 | Upgrade migration lulus | Tidak ada migration baru (hanya kode) | — |
| 20 | Rollback lulus | `.github/workflows/tests.yml` | `migrate:rollback` step |
| 21 | Dry-run tidak mengubah data | `FinalizePahanV2_4.php` | `--dry-run` flag |
| 22 | Seluruh test lulus | `php artisan test` | 120 passed |
| 23 | Build lulus | `.github/workflows/tests.yml` | `npm run build` step |
| 24 | Pint lulus | `vendor/bin/pint --test` | 121 files |
| 25 | Histori dan rule pengguna aman | Tidak ada `migrate:fresh` pada produksi | — |

## Referensi

Pahan, Iyung. 2013. *Panduan Lengkap Kelapa Sawit*. Cetakan XI. Jakarta: Penebar Swadaya.
- Tabel 9.13: Dosis Urea per pokok per tahun
- Tabel 9.14: Dosis MOP/KCl per pokok per tahun
- Bab 9, hal. 157-159: Parameter kelayakan waktu aplikasi
