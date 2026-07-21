# Traceability Matrix — Pahan v2.8

## Requirement → Implementation → Test

| Req # | Requirement | Implementation | Test |
|-------|-------------|----------------|------|
| 4.1 | Integrasikan Program ke RbsService | `ProgramPemupukanService`, `RbsService::simpanDenganHistori()` | `RbsProgramIntegrationTest` |
| 4.2 | FertilizationRealizationService berbasis program | `getRealizationSummaryForProgram()` | `RealisasiProgramConsistencyTest` |
| 4.3 | Rekomendasi dan realisasi program sama | `RealisasiPemupukanController::ensureProgram()` | `RealisasiProgramConsistencyTest` |
| 4.4 | Cegah program aktif ganda | `active_key` UNIQUE + `lockForUpdate()` | `ProgramActiveUniquenessTest` |
| 4.5 | Siklus hidup program | `ProgramStatusService::synchronizeStatus()` | `ProgramLifecycleTest` |
| 4.6 | Tolak rekomendasi historis | `RealisasiEligibilityService::evaluate()` is_latest check | `HistoricalRecommendationRejectionTest` |
| 4.7 | Histori operasional lengkap | `recordOperationalHistory()` di controller | `OperationalStageTransitionHistoryTest` |
| 4.8 | Fingerprint berbasis program | `generateFingerprint()` includes `program_pemupukan_id` | `ProgramFingerprintTest` |
| 4.9 | Laporan non-legacy | `LaporanController` uses `status_stage` + `urea_aplikasi_saat_ini` | `LaporanNonLegacyDecisionTest` |
| 4.10 | True legacy upgrade test | `TrueLegacySchemaUpgradeV28Test`, `LegacySchemaBuilder` | `TrueLegacySchemaUpgradeV28Test` |

## Audit Checks → Source Code

| Audit # | Check | Source File | Method/Line |
|---------|-------|-------------|-------------|
| 1 | Config version | `config/fertilization.php` | `engine_version` |
| 2 | Rekomendasi tanpa program | `app/Services/RbsService.php` | `simpanDenganHistori()` |
| 3 | Mismatch program | `app/Http/Controllers/RealisasiPemupukanController.php` | `ensureProgram()` |
| 4 | Program aktif ganda | `database/migrations/2026_07_23_*` | `active_key` UNIQUE |
| 5 | Program selesai | `app/Services/ProgramStatusService.php` | `synchronizeStatus()` |
| 12 | Fingerprint program | `app/Services/RbsService.php` | `generateFingerprint()` |
| 13 | Laporan legacy | `app/Http/Controllers/LaporanController.php` | `index()` |
| 14 | Subtotal legacy | `app/Http/Controllers/LaporanController.php` | `sum('urea_aplikasi_saat_ini')` |

## Referensi Agronomis

| Prinsip | Implementasi | Verifikasi |
|---------|-------------|------------|
| Dosis Urea/KCl dari Pahan 2013 | `PahanDoseReferenceService` | Unit test `PahanDoseReferenceTest` |
| Tahap 1 = 50% kebutuhan tahunan | `CurrentApplicationCalculator::SPLIT_RATIO` | Unit test `CurrentApplicationCalculatorTest` |
| Interval minimal 60 hari | `FertilizationRealizationService::MIN_INTERVAL_DAYS` | Feature test + audit |
| Curah hujan 100-250 mm/bulan | `FertilizationWindowService` | Unit test `FertilizationWindowServiceTest` |
| Multiplier nonaktif | `config/fertilization.php` → `legacy_multipliers.enabled = false` | Config check |
