# Traceability Pahan v2.5

## Mapping Kebutuhan → Implementasi → Test

| Kebutuhan (Acceptance Criteria) | Implementasi | Test |
|------|------|------|
| Dashboard sepenuhnya pakai status baru | DashboardController + index.blade.php | DashboardNewStatusFeatureTest |
| Filter dan statistik tidak pakai legacy | JavaScript activeStatuses menggunakan kode baru | DashboardNewStatusFeatureTest::test_dashboard_view_has_no_legacy_filter_buttons |
| Aplikasi saat ini = tahap aktif | CurrentApplicationCalculator | CurrentApplicationCalculatorTest |
| Sebelum realisasi Tahap 1 = 50% | CurrentApplicationCalculator::TAHAP_1_SIAP | RbsRealizationFlowIntegrationTest::test_aplikasi_saat_ini_is_50_percent |
| Tahap 2 mengikuti sisa aktual | CurrentApplicationCalculator::TAHAP_2_SIAP | RbsRealizationFlowIntegrationTest::test_tahap_2_ready_after_60_days |
| Tahap 2 membaca realisasi Tahap 1 | FertilizationRealizationService | RbsRealizationFlowIntegrationTest |
| Tahap 2 tidak aktif sebelum 60 hari | CurrentApplicationCalculator::MENUNGGU_INTERVAL | RbsRealizationFlowIntegrationTest::test_tahap_2_not_ready_before_60_days |
| Kebutuhan selesai = 0 | CurrentApplicationCalculator::SELESAI_TAHUNAN | RbsRealizationFlowIntegrationTest::test_selesai_tahunan |
| Fingerprint berubah saat luas/SPH/realisasi berubah | RbsService::generateFingerprint() | FingerprintV25Test |
| Luas dan SPH disimpan snapshot | AnnualFertilizerSnapshotBuilder + migration | MigrationUpgradePathTest + RbsHistoricalDoseIntegrationTest |
| PDF konsisten | laporan/pdf.blade.php | PdfOperationalConsistencyTest |
| Disclaimer PDF tidak ganda | Hapus DISCLAIMER TAMBAHAN | Visual check + grep |
| Upgrade database lama diuji | LegacyDatabaseFixture | MigrationDataPreservationTest |
| Audit command memeriksa semua masalah | FinalizePahanV2_5 | CI dry-run |
| Histori dan rule pengguna aman | Nullable migrations | MigrationDataPreservationTest |

## Referensi Pahan (2013)

| Aturan | Halaman | Implementasi |
|--------|---------|-------------|
| Dosis Urea/KCl per umur | 163-164 (Tabel 9.13, 9.14) | config/fertilization.php dose_reference |
| Curah hujan layak 100-250 mm | 157-159 | FertilizationWindowService |
| Interval minimum 60 hari | 157-159 | CurrentApplicationCalculator, FertilizationRealizationService |
| Pembagian 2 tahap/tahun | 157-159 | CurrentApplicationCalculator (50%/sisa) |
| Keterlambatan tidak menaikkan dosis | 157-159 | FertilizationWindowService::TERLAMBAT (tidak mengubah dosis) |
