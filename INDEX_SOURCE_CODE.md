# INDEX SOURCE CODE — SawitGIS

> Sistem Pendukung Keputusan Pemupukan Kelapa Sawit  
> Kelompok Tani Suluh Tani  
> Engine Version: **pahan-v2.8**  
> Framework: Laravel 11 · PHP 8.2 · MySQL 8 / SQLite  
> Terakhir diperbarui: 21 Juli 2026

---

## Struktur Direktori Utama

```
├── app/
│   ├── Console/Commands/     # Artisan commands (audit, migration)
│   ├── Enums/                # Enum classes (status, fase, severity)
│   ├── Http/
│   │   ├── Controllers/      # 14 controller
│   │   ├── Middleware/       # AdminAuthenticated
│   │   └── Requests/         # 6 form request validation
│   ├── Models/               # 9 Eloquent models
│   ├── Notifications/        # Laravel database notifications
│   ├── Providers/            # AppServiceProvider (view composer)
│   └── Services/             # 18 service classes (business logic)
├── config/
│   └── fertilization.php     # Konfigurasi dosis, window, reliability
├── database/
│   ├── factories/            # 5 model factories (testing)
│   ├── migrations/           # 43 migration files
│   └── seeders/              # 5 seeders
├── resources/views/          # Blade templates
├── routes/web.php            # Route definitions
├── tests/                    # 235 tests (15 Unit + 34 Feature)
└── docs/                     # Dokumentasi teknis
```

---

## App — Models (9)

| File | Deskripsi |
|------|-----------|
| `Admin.php` | User admin (Authenticatable + Notifiable) |
| `Anggota.php` | Anggota kelompok tani |
| `BlokLahan.php` | Blok lahan kelapa sawit (GeoJSON, SPH, luas) |
| `KondisiLahan.php` | Data observasi kondisi lahan per blok |
| `RuleBaseLanjutan.php` | Rule base RBS (Forward Chaining) |
| `RekomendasiRbs.php` | Hasil analisis & rekomendasi pemupukan |
| `ProgramPemupukan.php` | Program pemupukan tahunan per blok |
| `RealisasiPemupukan.php` | Realisasi pelaksanaan pemupukan |
| `RekomendasiOperasionalHistory.php` | Histori perubahan tahap operasional |

---

## App — Services (18)

| File | Deskripsi |
|------|-----------|
| `RbsService.php` | Mesin analisis utama (Forward Chaining + Rule Chaining) |
| `PahanDoseReferenceService.php` | Referensi dosis dari Pahan 2013 (Tabel 9.13 & 9.14) |
| `FertilizationCalculationService.php` | Hitung total kebutuhan (dosis × jumlah pokok) |
| `FertilizationWindowService.php` | Evaluasi kelayakan waktu (curah hujan, interval, drainase) |
| `FertilizationScheduleService.php` | Generate jadwal pemupukan 2 tahap/tahun |
| `FertilizationRealizationService.php` | Ringkasan realisasi (per blok atau per program) |
| `CurrentApplicationCalculator.php` | Hitung jumlah tahap aktif (50% Tahap 1, sisa Tahap 2) |
| `AnnualFertilizerSnapshotBuilder.php` | Build snapshot kebutuhan tahunan |
| `PlantAgeService.php` | Hitung umur tanaman berdasarkan tahun tanam |
| `PlantContextService.php` | Resolve konteks fase/umur pada tanggal observasi |
| `PlantPhaseResolver.php` | Tentukan fase TBM/TM berdasarkan umur |
| `ObservationCompletenessService.php` | Evaluasi kelengkapan data observasi |
| `RecommendationReliabilityService.php` | Hitung skor keandalan data |
| `SupportingFertilizerSanitizer.php` | Sanitasi pupuk pendukung tanpa metadata |
| `RealisasiEligibilityService.php` | Validasi kelayakan pencatatan realisasi |
| `RecommendationOperationalRefreshService.php` | Refresh tahap aktif setelah realisasi |
| `ProgramPemupukanService.php` | Resolve/buat program pemupukan (v2.8) |
| `ProgramStatusService.php` | Siklus hidup program: AKTIF → SELESAI (v2.8) |

---

## App — Controllers (14)

| File | Deskripsi |
|------|-----------|
| `DashboardController.php` | Dashboard WebGIS + statistik ringkasan |
| `AuthController.php` | Login/logout admin |
| `AnggotaController.php` | CRUD anggota kelompok tani |
| `BlokLahanController.php` | CRUD blok lahan + upload GeoJSON/SHP |
| `KondisiLahanController.php` | CRUD data observasi kondisi lahan |
| `RuleBaseController.php` | CRUD rule base RBS |
| `RbsController.php` | Analisis RBS + detail + notifikasi |
| `RealisasiPemupukanController.php` | CRUD realisasi pemupukan + program |
| `LaporanController.php` | Laporan rekap + export PDF |
| `NotificationController.php` | API notifikasi (recent, mark read) |
| `CuacaController.php` | Fetch curah hujan dari Open-Meteo |
| `GeoUploadController.php` | Upload SHP/GeoJSON → polygon |
| `SettingController.php` | Ganti password + tema tampilan |
| `Controller.php` | Base controller |

---

## App — Enums (5)

| File | Deskripsi |
|------|-----------|
| `PlantConditionStatus.php` | Status kondisi tanaman (NORMAL_VISUAL, GEJALA_BERAT, dll) |
| `ApplicationFeasibilityStatus.php` | Status kelayakan aplikasi (LAYAK, TUNDA, dll) |
| `PlantPhase.php` | Fase tanaman (TBM, TM) |
| `RuleType.php` | Jenis rule (DIAGNOSIS_VISUAL, PEMBATAS_APLIKASI, dll) |
| `SeverityLevel.php` | Tingkat keparahan (RINGAN, SEDANG, BERAT) |

---

## App — Notifications (1)

| File | Deskripsi |
|------|-----------|
| `RealisasiNotification.php` | Notifikasi database: tahap siap, realisasi dicatat, program selesai |

---

## App — Console Commands (10)

| File | Deskripsi |
|------|-----------|
| `FinalizePahanV2_8.php` | Audit command v2.8 (18 pemeriksaan) |
| `FinalizePahanV2_7.php` | Audit command v2.7 |
| `FinalizePahanV2_6.php` | Audit command v2.6 |
| `FinalizePahanV2_5.php` | Audit command v2.5 |
| `FinalizePahanV2_4.php` | Audit command v2.4 |
| `FinalizePahanV2_3.php` | Audit command v2.3 |
| `FinalizePahanV2_2.php` | Audit command v2.2 |
| `AuditPahanV2.php` | Audit command v2.0 |
| `MigratePahanV2.php` | Helper migration |
| `MaintenanceClearCache.php` | Maintenance: clear cache |

---

## App — Http/Requests (6)

| File | Deskripsi |
|------|-----------|
| `StoreBlokLahanRequest.php` | Validasi create blok lahan |
| `UpdateBlokLahanRequest.php` | Validasi update blok lahan |
| `StoreKondisiLahanRequest.php` | Validasi create kondisi lahan |
| `UpdateKondisiLahanRequest.php` | Validasi update kondisi lahan |
| `StoreRealisasiPemupukanRequest.php` | Validasi create realisasi |
| `UpdateRealisasiPemupukanRequest.php` | Validasi update realisasi |

---

## Database — Migrations (43)

### Tabel Utama
| Migration | Tabel/Perubahan |
|-----------|-----------------|
| `create_admins_table` | admins |
| `create_blok_lahans_table` | blok_lahans |
| `create_anggotas_table` | anggotas |
| `create_kondisi_lahans_table` | kondisi_lahans |
| `create_rule_bases_lanjutan_table` | rule_bases_lanjutan |
| `create_rekomendasi_rbs_table` | rekomendasi_rbs |
| `create_realisasi_pemupukans_table` | realisasi_pemupukans |
| `create_program_pemupukans_table` | program_pemupukans (v2.7) |
| `create_rekomendasi_operasional_histories_table` | histori operasional (v2.7) |
| `create_notifications_table` | notifications (v2.8) |
| `add_active_key_to_program_pemupukans_table` | UNIQUE active_key (v2.8) |

### Evolusi Schema
| Versi | Perubahan Utama |
|-------|-----------------|
| v2.0–2.1 | Tabel dasar (admin, blok, kondisi, rule, rekomendasi) |
| v2.2 | Fase/umur/dose fields pada rekomendasi |
| v2.3 | Annual estimate fields (total min/max/est tahunan, karung) |
| v2.4 | Fingerprint, provenance, reliability |
| v2.5 | Snapshot luas/SPH, active_stage, status_stage, realisasi upgrade |
| v2.6 | tahun_program, confirmed_over_plan, override pada realisasi |
| v2.7 | Tabel program_pemupukans, histori operasional, FK program |
| v2.8 | active_key UNIQUE constraint, notifications table |

---

## Database — Factories (5)

| File | Deskripsi |
|------|-----------|
| `AdminFactory.php` | Factory untuk Admin |
| `AnggotaFactory.php` | Factory untuk Anggota |
| `BlokLahanFactory.php` | Factory untuk BlokLahan (termasuk GeoJSON) |
| `KondisiLahanFactory.php` | Factory untuk KondisiLahan |
| `RekomendasiRbsFactory.php` | Factory untuk RekomendasiRbs |

---

## Database — Seeders (5)

| File | Deskripsi |
|------|-----------|
| `DatabaseSeeder.php` | Seeder utama |
| `AdminSeeder.php` | Seeder admin default |
| `RuleBaseLanjutanSeeder.php` | Seeder rule base awal |
| `PahanRuleBaseV2Seeder.php` | Seeder rule v2 dengan provenance |
| `RuleCurahHujanGulmaSeeder.php` | Seeder rule curah hujan & gulma |

---

## Resources — Views (Blade)

### Layout
| File | Deskripsi |
|------|-----------|
| `layouts/app.blade.php` | Layout utama (sidebar, header, notifikasi bell, dark mode) |

### Halaman Utama
| Folder | File | Deskripsi |
|--------|------|-----------|
| `auth/` | `login.blade.php` | Form login |
| `dashboard/` | `index.blade.php` | Dashboard WebGIS + peta + statistik |
| `anggota/` | `index`, `create`, `edit` | CRUD anggota |
| `blok_lahan/` | `index`, `create`, `edit`, `show` | CRUD blok lahan |
| `kondisi_lahan/` | `index`, `create`, `edit` | CRUD kondisi lahan |
| `rule_base/` | `index`, `create`, `edit`, `info` | CRUD rule base + info referensi |
| `rbs/` | `index`, `detail` | Daftar analisis + detail hasil |
| `rbs/partials/` | `_hasil_rbs.blade.php` | Komponen reusable hasil analisis |
| `realisasi_pemupukan/` | `index`, `create`, `edit`, `show` | CRUD realisasi |
| `laporan/` | `index`, `show`, `pdf` | Rekap laporan + PDF |
| `settings/` | `index` | Pengaturan (password, tema) |

### Komponen
| File | Deskripsi |
|------|-----------|
| `components/filter-searchable.blade.php` | Dropdown filter dengan search |
| `components/searchable-select.blade.php` | Select dengan pencarian |
| `components/custom-select.blade.php` | Custom select styling |
| `components/status-badge.blade.php` | Badge status |

---

## Routes (web.php)

| Prefix | Controller | Fitur |
|--------|-----------|-------|
| `/` | redirect → dashboard | |
| `/login`, `/logout` | AuthController | Autentikasi |
| `/dashboard` | DashboardController | WebGIS + statistik |
| `/anggota` | AnggotaController | CRUD anggota |
| `/blok-lahan` | BlokLahanController | CRUD blok lahan |
| `/kondisi-lahan` | KondisiLahanController | CRUD kondisi |
| `/rule-base` | RuleBaseController | CRUD rule |
| `/rbs` | RbsController | Analisis RBS |
| `/realisasi-pemupukan` | RealisasiPemupukanController | CRUD realisasi |
| `/laporan` | LaporanController | Rekap + PDF |
| `/settings` | SettingController | Pengaturan |
| `/api/notifications` | NotificationController | API notifikasi |
| `/api/rbs-popup/{blok}` | RbsController@apiPopup | Popup peta |
| `/api/cuaca/fetch` | CuacaController | Fetch curah hujan |
| `/api/geo-upload` | GeoUploadController | Upload GeoJSON/SHP |

---

## Tests (235 total)

### Unit Tests (15)

| File | Deskripsi |
|------|-----------|
| `AnnualFertilizerSnapshotBuilderTest` | Snapshot kebutuhan tahunan |
| `CurrentApplicationCalculatorTest` | Kalkulasi tahap aktif |
| `FertilizationScheduleServiceTest` | Jadwal pemupukan |
| `FertilizationWindowServiceTest` | Kelayakan curah hujan/interval |
| `FingerprintV25Test` | Fingerprint SHA-256 |
| `ObservationCompletenessTest` | Kelengkapan data observasi |
| `PahanDoseReferenceTest` | Referensi dosis Pahan 2013 |
| `PlantAgeServiceTest` | Perhitungan umur tanaman |
| `PlantContextServiceTest` | Konteks fase/umur historis |
| `PlantPhaseResolverTest` | Resolusi fase TBM/TM |
| `PlantPhaseValidationTest` | Validasi fase vs umur |
| `RainfallFallbackTest` | Fallback kategori curah hujan |
| `RecommendationReliabilityTest` | Skor keandalan data |
| `RuleEvaluationTest` | Evaluasi rule (AND logic) |
| `SupportingFertilizerSanitizerTest` | Sanitasi pupuk pendukung |

### Feature Tests (34)

| File | Deskripsi |
|------|-----------|
| `BlokLahanFaseTest` | Validasi fase tanaman pada blok |
| `DashboardNewStatusFeatureTest` | Dashboard memakai status baru |
| `DashboardNextActionTest` | Dashboard: tindakan berikutnya, no stepper |
| `FingerprintRealizationDetailTest` | Fingerprint berubah saat realisasi berubah |
| `HistoricalRecommendationRejectionTest` | Rekomendasi historis ditolak |
| `LaporanNonLegacyDecisionTest` | Laporan tanpa status legacy |
| `LegacyStatusStaticAuditTest` | Tidak ada keputusan berdasarkan legacy |
| `MigrationDataPreservationTest` | Data lama aman setelah migration |
| `MigrationUpgradePathTest` | Upgrade path schema benar |
| `NoPlantPhaseAbbreviationInViewsTest` | TBM/TM tidak muncul di UI |
| `OperationalHistoryTest` | Histori operasional dicatat |
| `OperationalStageTransitionHistoryTest` | Transisi tahap tercatat |
| `PdfOperationalConsistencyTest` | PDF konsisten dengan snapshot |
| `ProgramActiveUniquenessTest` | Satu program aktif per blok/tahun |
| `ProgramFingerprintTest` | Fingerprint memasukkan program |
| `ProgramLifecycleTest` | Siklus hidup AKTIF → SELESAI |
| `ProgramPemupukanIsolationTest` | Isolasi realisasi per program |
| `RbsHistoricalDoseIntegrationTest` | Dosis historis berdasarkan umur saat observasi |
| `RbsProgramIntegrationTest` | Analisis membuat program otomatis |
| `RbsRealizationFlowIntegrationTest` | Alur realisasi end-to-end |
| `RealisasiEligibilityTest` | Kelayakan form realisasi |
| `RealisasiOverLimitTest` | Validasi override batas |
| `RealisasiPartialFlowTest` | Alur realisasi sebagian |
| `RealisasiPemupukanCrudTest` | CRUD realisasi |
| `RealisasiProgramConsistencyTest` | Konsistensi program rekomendasi↔realisasi |
| `RealisasiStageLockTest` | Lock tahap saat interval/selesai |
| `RealisasiStatusSelesaiValidationTest` | Validasi status selesai vs jumlah |
| `RealisasiTamperedRequestTest` | Tolak request yang dimanipulasi |
| `ReportSnapshotFullConsistencyTest` | Laporan memakai snapshot |
| `SecurityTest` | Autentikasi + tidak ada route berbahaya |
| `TrueLegacySchemaUpgradeTest` | Upgrade schema legacy (v2.5–v2.7) |
| `TrueLegacySchemaUpgradeV28Test` | Upgrade schema v2.8 + rollback |
| `UxPrimaryActionTest` | Halaman utama bisa diakses |
| `UxTechnicalCodeHiddenTest` | Kode teknis tidak tampil di UI |

### Test Support
| File | Deskripsi |
|------|-----------|
| `Support/LegacyDatabaseFixture.php` | Fixture database legacy |
| `Support/LegacySchemaBuilder.php` | Builder schema legacy untuk upgrade test |

---

## Config Utama

| File | Deskripsi |
|------|-----------|
| `config/fertilization.php` | Dosis Pahan 2013, window, reliability weights, engine version |

---

## Dokumentasi (docs/)

| File | Deskripsi |
|------|-----------|
| `PANDUAN_ADMIN_SINGKAT_V2_8.md` | Panduan admin 3–5 halaman |
| `ALUR_PENGGUNA_SAWITGIS_V2_8.md` | Alur kerja pengguna |
| `UX_GUIDELINES_SAWITGIS_V2_8.md` | Panduan UX (warna, label, mobile) |
| `PROGRAM_PEMUPUKAN_V2_8.md` | Arsitektur program pemupukan |
| `MIGRASI_PAHAN_V2_8.md` | Panduan migrasi v2.8 |
| `REVISI_PAHAN_V2_8.md` | Ringkasan perubahan v2.8 |
| `AUDIT_PAHAN_V2_8.md` | Dokumentasi audit command |
| `PENGUJIAN_PAHAN_V2_8.md` | Checklist pengujian |
| `TRACEABILITY_PAHAN_V2_8.md` | Traceability requirement → test |

---

## Statistik

| Metrik | Nilai |
|--------|-------|
| Versi mesin | pahan-v2.8 |
| Total file PHP (app) | ~60 |
| Total migrations | 43 |
| Total tests | 235 |
| Total assertions | 628 |
| Total views | ~35 blade files |
| Code style | Laravel Pint (185 files PASS) |
| Test duration | ~8 detik |
