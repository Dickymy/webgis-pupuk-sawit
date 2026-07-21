# Revisi Pahan v2.8

## Ringkasan Perubahan

### Backend

1. **ProgramPemupukanService** — Service baru untuk resolve dan kelola program pemupukan
2. **ProgramStatusService** — Kelola siklus hidup program (AKTIF → SELESAI)
3. **RbsService** — Diintegrasikan dengan program pemupukan; setiap analisis membuat/menggunakan program
4. **FertilizationRealizationService** — Method baru `getRealizationSummaryForProgram()` berbasis program
5. **RealisasiEligibilityService** — Tolak rekomendasi historis (`is_latest = false`) dan program non-aktif
6. **LaporanController** — Subtotal berdasarkan `urea_aplikasi_saat_ini`; filter berdasarkan status baru
7. **RealisasiPemupukanController** — Integrasi ProgramPemupukanService dan ProgramStatusService
8. **Fingerprint** — Memasukkan `program_pemupukan_id`

### Database

1. **Migration**: `2026_07_23_000001_add_active_key_to_program_pemupukans_table.php`
   - Tambah kolom `active_key` (UNIQUE) untuk mencegah program aktif ganda
   - Backfill data existing

### Config

- `fertilization.engine_version` diubah dari `pahan-v2.7` ke `pahan-v2.8`

### Test Baru

- `RbsProgramIntegrationTest` — Analisis membuat program dan menghubungkan rekomendasi
- `RealisasiProgramConsistencyTest` — Realisasi memakai program yang sama
- `ProgramActiveUniquenessTest` — Hanya satu program aktif per blok/tahun
- `ProgramLifecycleTest` — Siklus hidup program (AKTIF → SELESAI)
- `HistoricalRecommendationRejectionTest` — Rekomendasi historis ditolak
- `OperationalStageTransitionHistoryTest` — Histori transisi tahap dicatat
- `ProgramFingerprintTest` — Fingerprint memasukkan program
- `LaporanNonLegacyDecisionTest` — Laporan tidak memakai status legacy
- `TrueLegacySchemaUpgradeV28Test` — True legacy upgrade test

### Audit

- Command baru: `sawit:finalize-pahan-v2-8 --dry-run`
- 18 pemeriksaan otomatis
