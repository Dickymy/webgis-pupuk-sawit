# PENGUJIAN PAHAN v2.4

## Hasil Pengujian

```
Jumlah test    : 120
Jumlah assertion: 271
Hasil test     : PASS (120 passed, 0 failed)
Hasil Pint     : PASS (121 files)
Hasil fresh migration: PASS
Hasil rollback : PASS
Engine version : pahan-v2.4
```

## Test yang Ditambahkan/Diubah

### AnnualFertilizerSnapshotBuilderTest (BARU)
- `test_build_with_valid_dose_and_applicable` — total tahunan & aplikasi benar
- `test_build_with_valid_dose_but_not_applicable` — aplikasi saat ini = 0, tahunan tetap ada
- `test_build_with_null_dose_returns_null_annual` — null dose → null annual

### FertilizationScheduleServiceTest (DIUBAH)
- `test_not_feasible_returns_empty_array` — tidak layak → `[]` (bukan item "Ditunda")
- `test_no_numeric_rainfall_returns_empty_array` — hujan null → `[]` (bukan item "Menunggu Data")

## Test Coverage per Acceptance Criteria

| Criteria | Test | Status |
|----------|------|--------|
| Fase historis menjadi dasar dosis | PlantContextServiceTest | ✅ |
| Umur 3 tanpa verifikasi tidak menghasilkan dosis | PlantContextServiceTest::umur_3_needs_verification | ✅ |
| Jadwal kosong saat tidak layak | FertilizationScheduleServiceTest::not_feasible_returns_empty_array | ✅ |
| Jadwal kosong saat hujan null | FertilizationScheduleServiceTest::no_numeric_rainfall_returns_empty_array | ✅ |
| Total tahunan tersimpan | AnnualFertilizerSnapshotBuilderTest | ✅ |
| Aplikasi saat ini 0 saat ditunda | AnnualFertilizerSnapshotBuilderTest::not_applicable | ✅ |
| Kebutuhan tahunan tetap ada saat ditunda | AnnualFertilizerSnapshotBuilderTest::not_applicable | ✅ |
| Sanitizer metadata lengkap | SupportingFertilizerSanitizerTest | ✅ |
| Nama campuran tidak lolos | SupportingFertilizerSanitizerTest (implicit) | ✅ |
| Tidak ada singkatan fase | NoPlantPhaseAbbreviationInViewsTest + FertilizationScheduleServiceTest | ✅ |
| Fresh migration lulus | CI workflow step | ✅ |
| Rollback lulus | CI workflow step | ✅ |
| Build lulus | CI workflow step | ✅ |
| Pint lulus | CI workflow step | ✅ |

## Cara Menjalankan

```bash
# Full test suite
php artisan test

# Unit test saja
php artisan test --testsuite=Unit

# Test spesifik
php artisan test --filter=AnnualFertilizerSnapshotBuilderTest
php artisan test --filter=FertilizationScheduleServiceTest

# Code style
vendor/bin/pint --test

# Audit command
php artisan sawit:finalize-pahan-v2-4 --dry-run

# Migration testing
php artisan migrate:fresh --env=testing
php artisan test
php artisan migrate:rollback --step=1 --env=testing
php artisan migrate --env=testing
php artisan test
```
