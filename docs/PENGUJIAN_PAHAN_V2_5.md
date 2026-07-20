# Pengujian Pahan v2.5

## Unit Tests

| Test Class | Methods | Assertions |
|------------|---------|-----------|
| CurrentApplicationCalculatorTest | 5 | 20 |
| FingerprintV25Test | 5 | 5 |
| AnnualFertilizerSnapshotBuilderTest | (updated) | - |
| FertilizationScheduleServiceTest | (updated) | - |

## Feature/Integration Tests

| Test Class | Methods | Coverage |
|------------|---------|----------|
| DashboardNewStatusFeatureTest | 4 | Dashboard filter, legend, statistik, JS |
| MigrationUpgradePathTest | 5 | Schema v2.5, nullable, no duplicates |
| MigrationDataPreservationTest | 4 | Legacy data, rule pengguna, histori |
| RbsRealizationFlowIntegrationTest | 4 | Alur lengkap realisasi Tahap 1 & 2 |
| RbsHistoricalDoseIntegrationTest | 1 | Dosis historis TBM tahun ke-2 |
| PdfOperationalConsistencyTest | 4 | Konsistensi kebutuhan vs aplikasi |

## Skenario Realisasi yang Diuji

1. Sebelum realisasi → aplikasi saat ini = 50%
2. Realisasi Tahap 1, interval < 60 hari → Tahap 2 belum siap
3. Realisasi Tahap 1, interval > 60 hari → Tahap 2 siap (sisa aktual)
4. Realisasi penuh → SELESAI_TAHUNAN (0 kg)

## Cara Menjalankan

```bash
# Semua test
php artisan test

# Hanya unit test
php artisan test --testsuite=Unit

# Hanya feature test
php artisan test --testsuite=Feature

# Hanya test realisasi
php artisan test --filter=RbsRealizationFlow

# Hanya test migration
php artisan test --filter=MigrationUpgradePath

# Audit command
php artisan sawit:finalize-pahan-v2-5 --dry-run
```

## CI/CD

GitHub Actions workflow menjalankan:
1. composer install
2. npm ci + npm run build
3. vendor/bin/pint --test
4. migrate:fresh
5. php artisan test (pass 1)
6. php artisan test --filter=MigrationUpgradePathTest
7. migrate:rollback --step=1
8. migrate
9. php artisan test (pass 2)
10. php artisan sawit:finalize-pahan-v2-5 --dry-run
