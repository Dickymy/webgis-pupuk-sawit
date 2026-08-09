# CODEBASE INDEX — SawitGIS (Aplikasi Rekomendasi Pemupukan Kelapa Sawit)

> Dokumen ini adalah index lengkap seluruh kode aplikasi untuk referensi AI.
> Versi mesin rekomendasi: **Pahan v2.8** | Framework: **Laravel 11** | PHP: **^8.2**

---

## 1. GAMBARAN UMUM APLIKASI

**Nama:** SawitGIS (`dickymy/sawitgis`)
**Deskripsi:** Aplikasi WebGIS berbasis Rule-Based System (RBS) untuk rekomendasi dan pencatatan pelaksanaan pemupukan kelapa sawit.

**Fitur Utama:**
- Dashboard WebGIS dengan peta interaktif (Leaflet) — status per blok lahan
- Manajemen anggota kelompok tani dan blok lahan
- Input observasi kondisi lahan lapangan
- Analisis RBS otomatis (forward chaining) setelah observasi disimpan
- Manajemen rule base (tambah/edit/aktifkan)
- Rekomendasi pemupukan 2 tahap (Tahap 1 = 50%, Tahap 2 = sisa) berbasis Iyung Pahan (2013)
- Pencatatan realisasi pemupukan dengan validasi ketat server-side
- Program pemupukan tahunan per blok (1 program aktif per blok per tahun)
- Laporan dan export PDF per rekomendasi
- Notifikasi in-app (database channel)
- Pengaturan tema (light/dark/system) dan ganti password

**Dependencies Utama:**
- `laravel/framework ^11.0`
- `barryvdh/laravel-dompdf ^3.1` — export laporan PDF
- `gasparesganga/php-shapefile ^3.4` — parsing file SHP untuk upload peta
- `laravel/tinker ^2.9`

---

## 2. ARSITEKTUR DATABASE

### Tabel Aktif (state final Juli 2026)

| Tabel | Deskripsi |
|---|---|
| `admins` | User admin sistem |
| `anggotas` | Anggota kelompok tani pemilik lahan |
| `blok_lahans` | Blok lahan dengan koordinat GeoJSON + kriteria agronomis |
| `kondisi_lahans` | Data observasi lapangan per blok |
| `rule_bases_lanjutan` | Rule IF-THEN untuk mesin RBS |
| `program_pemupukans` | Program pemupukan tahunan per blok |
| `rekomendasi_rbs` | Hasil analisis RBS (histori + terbaru) |
| `realisasi_pemupukans` | Pencatatan pelaksanaan pemupukan |
| `rekomendasi_operasional_histories` | Audit trail setiap perubahan realisasi |
| `notifications` | Notifikasi in-app (Laravel) |
| `sessions`, `cache`, `cache_locks` | Infrastruktur Laravel |

### Tabel Dihapus (cleanup)
`users`, `password_reset_tokens`, `kriteria_lahans`, `rule_bases`, `rekomendasi_spks`, `jobs`, `job_batches`, `failed_jobs`


---

## 3. STRUKTUR DIREKTORI

```
app/
├── Console/Commands/
│   ├── AuditPahanV2.php          — audit konsistensi data Pahan v2
│   ├── BackupDatabase.php         — backup database
│   ├── BackupList.php             — daftar file backup
│   ├── HealthCheck.php            — cek kesehatan sistem
│   ├── MaintenanceClearCache.php  — clear cache maintenance
│   └── ResetDemoData.php          — reset data demo
├── Enums/
│   ├── ApplicationFeasibilityStatus.php  — status kelayakan waktu pemupukan
│   ├── PlantConditionStatus.php          — status kondisi visual tanaman
│   └── PlantPhase.php                    — fase tanaman (TBM/TM)
├── Http/
│   ├── Controllers/               — lihat Bagian 4
│   ├── Middleware/
│   │   └── AdminAuthenticated.php — guard admin
│   └── Requests/                  — lihat Bagian 5
├── Models/                        — lihat Bagian 6
├── Notifications/
│   └── RealisasiNotification.php  — notifikasi realisasi (database channel)
├── Providers/
│   └── AppServiceProvider.php
└── Services/                      — lihat Bagian 7

resources/views/
├── anggota/         create, edit, index
├── auth/            login
├── blok_lahan/      create, edit, index, show
├── components/      komponen reusable Blade
├── dashboard/       index (WebGIS)
├── errors/          403, 404, 419, 422, 500, 503
├── kondisi_lahan/   _form, create, edit, index
├── laporan/         index, pdf, show
├── layouts/         app.blade.php (layout utama)
├── rbs/             detail, index + partials/
├── realisasi_pemupukan/ create, edit, index, show
├── rule_base/       _form, create, edit, index, info
└── settings/        index

database/
├── migrations/      50+ file migration (state final: Juli 2026)
└── seeders/
    └── AdminSeeder.php
```

---

## 4. CONTROLLERS

### AuthController
`app/Http/Controllers/AuthController.php`
- `showLoginForm()` — tampilkan form login, redirect jika sudah login
- `login(Request)` — autentikasi admin, set cookie tema, throttle 5/menit
- `logout(Request)` — invalidate session, redirect ke login

### DashboardController
`app/Http/Controllers/DashboardController.php`
- `index()` — ambil semua blok + evaluasi eligibility operasional, kirim ke view WebGIS
- Helper: `mapActionStatus()`, `recommendationNeedsObservation()`, `operationalEligibility()`

### AnggotaController
`app/Http/Controllers/AnggotaController.php`
- `index()` — daftar anggota dengan count blok, paginate 10
- `create()`, `store(Request)` — tambah anggota baru
- `edit(Anggota)`, `update(Request, Anggota)` — edit anggota
- `destroy(Anggota)` — hapus, tolak jika masih punya blok

### BlokLahanController
`app/Http/Controllers/BlokLahanController.php`
- `index(Request)` — daftar blok grouped by anggota, filter status/anggota
- `create()`, `store(StoreBlokLahanRequest)` — tambah blok + auto-set fase
- `show(BlokLahan)`, `edit(BlokLahan)`, `update(UpdateBlokLahanRequest, BlokLahan)` — CRUD
- `destroy(BlokLahan)` — tolak jika ada histori rekomendasi/realisasi
- Helper: `autoSetFase()` — TBM jika umur < 3, TM jika umur > 3

### KondisiLahanController
`app/Http/Controllers/KondisiLahanController.php`
- `index(Request)` — daftar blok grouped by anggota, tab status (semua/belum/perlu-rekomendasi/sudah)
- `create(Request)`, `store(StoreKondisiLahanRequest)` — simpan observasi + trigger analisis RBS otomatis
- `edit(KondisiLahan)`, `update(UpdateKondisiLahanRequest, KondisiLahan)` — edit + recalculate RBS
- `destroy(KondisiLahan)` — tolak jika sudah dipakai rekomendasi, hapus foto
- `photo(KondisiLahan)` — serve foto observasi dari storage
- Helper: `hitungCentroid()`, `normalizeObservationData()`, `validasiKonsistensi()`, `activeLeafConditions()`


### RuleBaseController
`app/Http/Controllers/RuleBaseController.php`
- `index()` — daftar rule aktif + pending, urut prioritas
- `info()` — halaman informasi rule base
- `create()`, `store(SaveRuleBaseRequest)` — tambah rule baru (default: nonaktif, perlu validasi)
- `edit(RuleBaseLanjutan)`, `update(SaveRuleBaseRequest, RuleBaseLanjutan)` — edit rule (reset ke nonaktif)
- `toggleStatus(Request, RuleBaseLanjutan)` — aktifkan/nonaktifkan rule, cek konflik sebelum aktifkan
- Helper: `normalizedRuleData()`, `generateUniqueCode()`, `findActiveConflict()`, `hasCompleteSource()`

### RbsController
`app/Http/Controllers/RbsController.php`
- `index(Request)` — daftar blok + status RBS grouped by anggota, filter 8 status
- `analisis(BlokLahan)` — trigger analisis satu blok
- `analisisSemua()` — analisis semua blok yang punya kondisi
- `detail(BlokLahan)` — detail hasil analisis + histori rekomendasi
- `apiPopup(BlokLahan)` — JSON untuk popup peta WebGIS
- `daftarBlokBelumAnalisis()` — JSON untuk progress bar batch analisis

### RealisasiPemupukanController
`app/Http/Controllers/RealisasiPemupukanController.php`
- `index(Request)` — daftar realisasi + tab siap/menunggu/riwayat
- `create(RekomendasiRbs)` — form baru, tolak jika tidak layak (evaluasi server-side)
- `store(StoreRealisasiPemupukanRequest)` — simpan dengan 3-layer anti-duplikasi:
  1. Submission token (idempotency)
  2. Semantic duplicate check (payload identik dalam 5 menit)
  3. `lockForUpdate()` dalam transaksi
- `show(RealisasiPemupukan)` — detail + histori operasional
- `edit(RealisasiPemupukan)`, `update(UpdateRealisasiPemupukanRequest, RealisasiPemupukan)` — edit dengan optimistic locking
- `cancel(RealisasiPemupukan)` — soft cancel (status BATAL, record tetap)

### LaporanController
`app/Http/Controllers/LaporanController.php`
- `index(Request)` — rekap rekomendasi grouped by anggota, filter anggota/blok/status/tahun/tahap
- `show(RekomendasiRbs)` — detail laporan satu rekomendasi
- `exportPdf(RekomendasiRbs)` — download PDF via DomPDF

### CuacaController
`app/Http/Controllers/CuacaController.php`
- `fetch(Request)` — ambil data cuaca dari Open-Meteo API (lat/lng), cache 12 jam
  - Hitung kategori curah hujan + deteksi musim via Neraca Air (P/ET0 ratio)
  - Graceful fallback jika API gagal (HTTP 200 + `success: false`)

### GeoUploadController
`app/Http/Controllers/GeoUploadController.php`
- `upload(Request)` — terima file GeoJSON atau ZIP berisi Shapefile
  - Validasi: path traversal, zip bomb, ukuran ekstraksi, format geometri
  - Output: GeoJSON Polygon siap render Leaflet

### SettingController
`app/Http/Controllers/SettingController.php`
- `index()` — halaman pengaturan
- `updatePassword(Request)` — ganti password dengan validasi current_password
- `updateTheme(Request)` — simpan preferensi tema ke DB (JSON response)

### NotificationController
`app/Http/Controllers/NotificationController.php`
- `recent()` — 10 notifikasi terbaru + unread count (JSON)
- `markAsRead(string $id)` — tandai satu notifikasi dibaca
- `markAllAsRead()` — tandai semua dibaca

---

## 5. FORM REQUESTS (VALIDASI)

| File | Mengvalidasi |
|---|---|
| `StoreBlokLahanRequest` | Blok baru: anggota_id, nama, luas, sph, geojson, tahun_tanam, jenis_tanah, topografi, fase_tanaman + validasi GeoJSON + konsistensi fase vs umur |
| `UpdateBlokLahanRequest` | Sama seperti Store dengan pengecualian unique |
| `StoreKondisiLahanRequest` | Observasi baru: blok_lahan_id, tanggal, warna_daun (required), curah hujan (2 mode: numerik/kategori), kelembaban, drainase, hama, gulma, foto + validasi antar-field |
| `UpdateKondisiLahanRequest` | Sama seperti Store untuk update |
| `SaveRuleBaseRequest` | Rule baru/edit: jenis_rule (DIAGNOSIS_VISUAL/PEMBATAS_APLIKASI), kondisi daun atau range curah hujan, indikasi_masalah, saran_tindakan, status_kebutuhan, tingkat_keparahan, prioritas, sumber acuan |
| `StoreRealisasiPemupukanRequest` | Realisasi: rekomendasi_rbs_id, tanggal, urea_kg, kcl_kg, status (SELESAI/SEBAGIAN), catatan, token, confirmed_over_plan, override_annual_limit + validasi server-side vs rencana |
| `UpdateRealisasiPemupukanRequest` | Update realisasi dengan validasi yang sama |


---

## 6. MODELS

### Admin
`app/Models/Admin.php` — extends `Authenticatable`
- Guard: `admin`
- Fields: `username`, `password` (hashed), `nama_lengkap`, `tema`
- Notifiable (in-app notifications)

### Anggota
`app/Models/Anggota.php`
- Fields: `nama` (unique), `no_hp`, `alamat`
- Relations: `blokLahans()` hasMany

### BlokLahan
`app/Models/BlokLahan.php`
- Fields: `anggota_id`, `nama_blok`, `luas_ha`, `sph`, `koordinat_geojson`, `tahun_tanam`, `jenis_tanah`, `topografi`, `fase_tanaman`
- Relations: `anggota()`, `kondisiLahans()`, `kondisiTerbaru()`, `rekomendasiRbs()`, `rekomendasiRbsTerbaru()`, `realisasiPemupukans()`, `programPemupukans()`
- Accessors: `nama_pemilik`, `umur_tanaman`, `kategori_umur`, `fase_label`

### KondisiLahan
`app/Models/KondisiLahan.php`
- Fields: `blok_lahan_id`, `tanggal_observasi`, `tanggal_pemupukan_terakhir`, `ph_tanah`, `metode_pengukuran_ph`, `kelembaban_tanah`, `curah_hujan_kategori`, `curah_hujan_mm_bulanan`, `periode_curah_hujan`, `sumber_curah_hujan`, `musim_saat_ini`, `warna_daun`, `gejala_defisiensi` (json), `kondisi_drainase`, `ada_gulma_dominan`, `ada_serangan_hama`, `catatan_observasi`, `foto_observasi_path`, `status_verifikasi_gejala`
- **Kolom dihapus:** `kondisi_pelepah`, `kondisi_tandan`
- Relations: `blokLahan()`, `rekomendasiRbs()`
- Accessor: `label_ph`

### RuleBaseLanjutan
`app/Models/RuleBaseLanjutan.php`
- Fields aktif: `kode_rule`, `jenis_rule` (DIAGNOSIS_VISUAL/PEMBATAS_APLIKASI), `kondisi_warna_daun`, `kondisi_curah_hujan_min_mm`, `kondisi_curah_hujan_max_mm`, `kondisi_kategori_umur`, `indikasi_masalah`, `jenis_pupuk_utama`, `saran_tindakan`, `status_kebutuhan`, `tingkat_keparahan`, `prioritas`, `aktif`, `sumber_judul`, `sumber_penulis`, `sumber_tahun`, `sumber_halaman`, `sumber_tabel`, `tingkat_bukti`, `is_system_rule`, `status_validasi`, `catatan_validasi`
- **Kolom dihapus:** `kondisi_ph_min/max`, `kondisi_kelembaban`, `kondisi_curah_hujan_kategori`, `kondisi_musim`, `kondisi_drainase`, `kondisi_defisiensi`, `kondisi_pelepah`, `kondisi_tandan`, `ada_serangan_hama`, `ada_gulma_dominan`, `kondisi_intermediate`, `prasyarat_intermediate`, `jenis_pupuk_pendukung`, `dosis_anjuran`, `metode_aplikasi`, `waktu_aplikasi`, `keterangan_rule`, `versi_rule`, `divalidasi_oleh`, `tanggal_validasi`, `kategori_kesimpulan`
- Scope: `aktif()`

### ProgramPemupukan
`app/Models/ProgramPemupukan.php`
- Fields: `uuid`, `blok_lahan_id`, `tahun_program`, `rekomendasi_awal_id`, `status_program`, `active_key`
- Constants: `STATUS_AKTIF`, `STATUS_SELESAI`, `STATUS_DIBATALKAN`, `STATUS_ARSIP`
- Relations: `blokLahan()`, `rekomendasiAwal()`, `rekomendasiRbs()`, `realisasiPemupukans()`
- Scopes: `aktif()`, `forBlokTahun()`
- Accessors: `label_status`, `warna_status`

### RekomendasiRbs
`app/Models/RekomendasiRbs.php`
- Fields utama: `blok_lahan_id`, `program_pemupukan_id`, `kondisi_lahan_id`, `admin_id`, `tanggal_analisis`, `is_latest`, `nomor_analisis`
- Output RBS: `rules_terpicu` (json), `masalah_teridentifikasi` (json), `rekomendasi_pupuk` (json), `saran_tindakan_utama`, `status_kebutuhan_dominan`, `jumlah_rule_terpicu`
- Dosis: `dosis_urea`, `dosis_kcl`, `total_urea`, `total_kcl`, `jadwal_pemupukan` (json)
- Validitas: `validitas_rekomendasi`, `confidence_score`, `confidence_label`, `data_cukup`, `data_kurang` (json)
- Snapshot Pahan-v2: `fase_tanaman_snapshot`, `umur_tanaman_snapshot`, `urea/kcl_total_estimasi_tahunan`, `luas_ha_snapshot`, `sph_snapshot`, `jumlah_pokok_snapshot`
- Status 2-dimensi: `status_kondisi_tanaman`, `status_kelayakan_aplikasi`, `alasan_kelayakan`
- Tahap: `active_stage`, `status_stage`, `urea/kcl_aplikasi_saat_ini`, `urea/kcl_sisa_tahunan`, `tanggal_minimum_tahap_berikutnya`
- Relations: `blokLahan()`, `kondisiLahan()`, `admin()`, `realisasiPemupukans()`, `programPemupukan()`
- Accessors: `warna_badge`, `label_status`, `label_fase`, `label_kondisi_tanaman`, `label_kelayakan`, `label_status_stage`, `warna_status_stage`, `is_tahap_siap`, `is_program_selesai`

### RealisasiPemupukan
`app/Models/RealisasiPemupukan.php`
- Fields: `rekomendasi_rbs_id`, `blok_lahan_id`, `program_pemupukan_id`, `admin_id`, `tahun_program`, `tahap`, `tanggal_realisasi`, `urea/kcl_rencana_kg`, `urea/kcl_realisasi_kg`, `status_realisasi`, `catatan_pelaksana`, `confirmed_over_plan`, `override_annual_limit`, `override_reason`, `submission_token`
- Constants: `STATUS_SELESAI`, `STATUS_SEBAGIAN`, `STATUS_BATAL`
- Scopes: `aktif()`, `tahap()`, `tahunProgram()`

### RekomendasiOperasionalHistory
`app/Models/RekomendasiOperasionalHistory.php`
- Tabel audit — tidak pernah dihapus
- Fields: `rekomendasi_rbs_id`, `program_pemupukan_id`, `event_type`, `active_stage`, `status_stage`, `urea/kcl_aplikasi_saat_ini`, `urea/kcl_sisa_tahunan`, `tanggal_minimum_tahap_berikutnya`, `analysis_fingerprint`, `source_realisasi_id`
- Event types: `ANALISIS_AWAL`, `REALISASI_DIBUAT`, `REALISASI_DIPERBARUI`, `REALISASI_DIBATALKAN`, `PROGRAM_SELESAI`


---

## 7. SERVICES

### RbsService
`app/Services/RbsService.php` — **Mesin analisis utama**
- `analisis(BlokLahan)` — jalankan forward chaining untuk satu blok:
  1. Ambil kondisi terbaru
  2. Resolve konteks tanaman (PlantContextService)
  3. Evaluasi kelengkapan observasi (ObservationCompletenessService)
  4. Forward chaining dengan fixpoint iteration (max = jumlah rule)
  5. Tentukan status kondisi tanaman (DIAGNOSIS_VISUAL only)
  6. Evaluasi kelayakan waktu (FertilizationWindowService)
  7. Hitung dosis (PahanDoseReferenceService + FertilizationCalculationService)
  8. Hitung snapshot tahunan (AnnualFertilizerSnapshotBuilder)
  9. Hitung aplikasi tahap aktif (CurrentApplicationCalculator)
  10. Simpan dengan histori + fingerprint SHA-256
- `analisisSemua()` — iterasi semua blok yang punya kondisi
- `evaluasiRule(rule, kondisi, kategoriUmur)` — AND logic, NULL = tidak relevan
- `simpanDenganHistori()` — cek fingerprint, skip save jika hasil identik

### FertilizationWindowService
`app/Services/FertilizationWindowService.php` — **Kelayakan waktu pemupukan**
- `evaluate(KondisiLahan, tanggalRencana?)` — evaluasi 4 kondisi:
  1. Curah hujan numerik (100-250 mm optimal; <60 atau >300 mm tunda)
  2. Kelembaban tanah aktual
  3. Interval dari pemupukan terakhir (min 120 hari, konfigurabel)
  4. Drainase lahan
- Output: `{status, layak, alasan[], curah_hujan_mm, interval_hari}`

### CurrentApplicationCalculator
`app/Services/CurrentApplicationCalculator.php` — **Jumlah pupuk tahap aktif**
- `calculate(input)` — 6 kasus:
  - `TAHAP_1_SIAP`: belum ada realisasi, layak → 50% tahunan
  - `TAHAP_1_SEBAGIAN`: ada realisasi sebagian → sisa rencana Tahap 1
  - `MENUNGGU_INTERVAL`: Tahap 1 selesai, interval belum terpenuhi
  - `MENUNGGU_KELAYAKAN`: kondisi lapangan tidak mendukung
  - `TAHAP_2_SIAP`: Tahap 1 selesai, interval terpenuhi, layak → sisa tahunan
  - `SELESAI_TAHUNAN`: sisa urea dan KCl = 0

### RealisasiEligibilityService
`app/Services/RealisasiEligibilityService.php` — **Validasi kelayakan form realisasi**
- `evaluate(RekomendasiRbs)` — tolak jika:
  - `is_latest = false` (rekomendasi historis)
  - Data belum cukup (status PERLU_VERIFIKASI)
  - Program terkait bukan AKTIF
  - Status stage bukan TAHAP_1_SIAP / TAHAP_1_SEBAGIAN / TAHAP_2_SIAP
- Output: `{boleh_mencatat, active_stage, status_stage, urea/kcl_rencana_kg, tahun_program, ...}`

### ProgramPemupukanService
`app/Services/ProgramPemupukanService.php` — **Resolve program tahunan**
- `resolveActiveProgram(blok, tahun, rekomendasi?)` — ambil atau buat program aktif (lockForUpdate)
- `getActiveProgram(blok, tahun)` — ambil tanpa membuat baru

### ProgramStatusService
`app/Services/ProgramStatusService.php` — **Siklus hidup program**
- `synchronizeStatus(program, currentApp)` — otomatis set SELESAI jika sisa urea+KCl = 0
  - Catat histori PROGRAM_SELESAI di `rekomendasi_operasional_histories`

### FertilizationCalculationService
`app/Services/FertilizationCalculationService.php`
- `calculate(blok, doseReference)` — hitung total kebutuhan:
  - `jumlah_pokok = luas_ha × sph`
  - `total = dosis × jumlah_pokok`
  - `karung = total / 50`

### PlantContextService
`app/Services/PlantContextService.php`
- `resolve(blok, tanggalReferensi)` — tentukan umur + fase pada tanggal historis:
  - umur < 3 → TBM otomatis
  - umur = 3, fase null → `needs_phase_verification = true`
  - umur > 3 → TM otomatis
  - Deteksi konflik fase manual vs umur historis

### Service Lainnya

| Service | Fungsi |
|---|---|
| `PahanDoseReferenceService` | Referensi dosis Urea/KCl dari tabel Iyung Pahan (2013) berdasarkan fase dan umur |
| `PlantAgeService` | Hitung umur tanaman pada tanggal tertentu dari tahun_tanam |
| `PlantPhaseResolver` | Resolve fase dari umur |
| `ObservationCompletenessService` | Hitung skor kelengkapan data observasi, tentukan `can_run_diagnosis` |
| `RecommendationReliabilityService` | Hitung skor keandalan rekomendasi (kelengkapan_data_score, kategori_keandalan) |
| `FertilizationRealizationService` | Rekapitulasi realisasi per program/blok, cek interval, `getRealizationSummary()` |
| `FertilizationScheduleService` | Generate jadwal pemupukan (json) dari dosis + konteks |
| `AnnualFertilizerSnapshotBuilder` | Build snapshot tahunan (total urea/KCl estimasi, karung) |
| `RecommendationOperationalRefreshService` | Refresh state operasional rekomendasi setelah realisasi dicatat/dibatalkan |
| `SupportingFertilizerSanitizer` | Sanitasi list rekomendasi pupuk dari rules terpicu |


---

## 8. ENUMS

### PlantPhase
`app/Enums/PlantPhase.php`
- `BELUM_MENGHASILKAN` = `TBM` → "Tanaman Belum Menghasilkan"
- `MENGHASILKAN` = `TM` → "Tanaman Menghasilkan"
- `labelFromValue(?string)`, `options()`

### PlantConditionStatus
`app/Enums/PlantConditionStatus.php` — berasal dari rule `DIAGNOSIS_VISUAL`
- `NORMAL_VISUAL`, `TERINDIKASI_DEFISIENSI_RINGAN`, `TERINDIKASI_DEFISIENSI`, `GEJALA_BERAT`, `PERLU_VERIFIKASI`, `BELUM_DIOBSERVASI`
- `fromSeverity(?string tingkatKeparahan)` — konversi RINGAN/SEDANG/BERAT/NORMAL ke status

### ApplicationFeasibilityStatus
`app/Enums/ApplicationFeasibilityStatus.php` — berasal dari `FertilizationWindowService`
- `LAYAK_DIJADWALKAN`, `TUNDA_HUJAN_RENDAH`, `TUNDA_HUJAN_TINGGI`, `TUNDA_TANAH_KERING`, `TUNDA_INTERVAL`, `PERLU_PERBAIKAN_DRAINASE`, `PERLU_VERIFIKASI_DATA`, `TERLAMBAT_PERLU_DIJADWALKAN`
- `isApplicable()` — true untuk LAYAK_DIJADWALKAN dan TERLAMBAT

---

## 9. ROUTES

File: `routes/web.php`

| Metode | URI | Nama Route | Controller@Method |
|---|---|---|---|
| GET | `/` | — | redirect ke dashboard |
| GET | `/login` | `login` | `AuthController@showLoginForm` |
| POST | `/login` | `login.submit` | `AuthController@login` (throttle 5/1) |
| POST | `/logout` | `logout` | `AuthController@logout` |
| GET | `/dashboard` | `dashboard` | `DashboardController@index` |
| CRUD | `/anggota` | `anggota.*` | `AnggotaController` (no show) |
| CRUD | `/blok-lahan` | `blok-lahan.*` | `BlokLahanController` |
| GET | `/kondisi-lahan/{id}/foto` | `kondisi-lahan.photo` | `KondisiLahanController@photo` |
| CRUD | `/kondisi-lahan` | `kondisi-lahan.*` | `KondisiLahanController` (no show) |
| GET | `/rule-base` | `rule-base.index` | `RuleBaseController@index` |
| GET | `/rule-base/info` | `rule-base.info` | `RuleBaseController@info` |
| GET | `/rule-base/tambah` | `rule-base.create` | `RuleBaseController@create` |
| POST | `/rule-base` | `rule-base.store` | `RuleBaseController@store` |
| GET | `/rule-base/{rule}/edit` | `rule-base.edit` | `RuleBaseController@edit` |
| PUT | `/rule-base/{rule}` | `rule-base.update` | `RuleBaseController@update` |
| PATCH | `/rule-base/{rule}/status` | `rule-base.status` | `RuleBaseController@toggleStatus` |
| GET | `/rbs` | `rbs.index` | `RbsController@index` |
| POST | `/rbs/analisis/{blok}` | `rbs.analisis` | `RbsController@analisis` |
| POST | `/rbs/analisis-semua` | `rbs.analisisSemua` | `RbsController@analisisSemua` |
| GET | `/rbs/detail/{blok}` | `rbs.detail` | `RbsController@detail` |
| GET | `/laporan` | `laporan.index` | `LaporanController@index` |
| GET | `/laporan/{rbs}/pdf` | `laporan.pdf` | `LaporanController@exportPdf` |
| GET | `/laporan/{rbs}` | `laporan.show` | `LaporanController@show` |
| GET | `/api/rbs-popup/{blok}` | `api.rbs.popup` | `RbsController@apiPopup` |
| POST | `/api/cuaca/fetch` | `api.cuaca.fetch` | `CuacaController@fetch` |
| POST | `/api/geo-upload` | `api.geo.upload` | `GeoUploadController@upload` |
| CRUD | `/realisasi-pemupukan` | `realisasi-pemupukan.*` | `RealisasiPemupukanController` |
| PATCH | `/realisasi-pemupukan/{id}/batal` | `realisasi-pemupukan.cancel` | `RealisasiPemupukanController@cancel` |
| GET | `/settings` | `settings.index` | `SettingController@index` |
| PUT | `/settings/password` | `settings.password.update` | `SettingController@updatePassword` |
| PUT | `/settings/theme` | `settings.theme.update` | `SettingController@updateTheme` |
| GET | `/api/notifications` | `notifications.recent` | `NotificationController@recent` |
| POST | `/api/notifications/{id}/read` | `notifications.read` | `NotificationController@markAsRead` |
| POST | `/api/notifications/read-all` | `notifications.readAll` | `NotificationController@markAllAsRead` |

Semua route kecuali login/logout dilindungi middleware `AdminAuthenticated`.

---

## 10. NOTIFIKASI

### RealisasiNotification
`app/Notifications/RealisasiNotification.php`
- Channel: `database`
- Factory methods: `tahapSiap()`, `intervalTerpenuhi()`, `realisasiDicatat()`, `programSelesai()`, `realisasiSebagian()`
- Data: `{tipe, judul, pesan, url, meta}`

---

## 11. MIDDLEWARE

### AdminAuthenticated
`app/Http/Middleware/AdminAuthenticated.php`
- Cek guard `admin`, redirect ke `login` jika belum terautentikasi

---

## 12. KONFIGURASI PENTING

- `config/fertilization.php` — parameter window: `rainfall_optimal_min_mm` (100), `rainfall_optimal_max_mm` (250), `rainfall_defer_below_mm` (60), `rainfall_defer_above_mm` (300), `min_interval_days` (120)
- `config/observation.php` — `diagnostic_leaf_conditions` (array nilai warna daun valid), `normal_leaf_condition` ('Hijau Normal'), `unmatched_leaf_values`
- Autentikasi: guard `admin` → model `App\Models\Admin`, driver `session`

---

## 13. ATURAN BISNIS PENTING

1. **Rule aktif harus punya sumber acuan** (`sumber_judul` + `tingkat_bukti` terisi) sebelum bisa diaktifkan
2. **Tidak boleh ada 2 rule aktif dengan kondisi daun yang sama** (conflict check)
3. **Satu program aktif per blok per tahun** — dijamin via `active_key` UNIQUE constraint
4. **Realisasi hanya boleh dicatat dari rekomendasi terbaru** (`is_latest = true`) pada program AKTIF
5. **Status SELESAI pada realisasi** hanya valid jika kumulatif realisasi tahap ≥ rencana tahap (toleransi 0.01 kg)
6. **Analisis otomatis** dijalankan setiap kali observasi disimpan/diperbarui
7. **Histori rekomendasi** tidak dihapus — `is_latest = false` untuk yang lama
8. **Kolom kondisi pelepah dan tandan** sudah dihapus dari DB dan tidak lagi dipakai
9. **Dosis referensi** selalu dari tabel Iyung Pahan (2013), bukan dari input manual rule
10. **Program otomatis SELESAI** ketika sisa urea + KCl tahunan = 0 setelah realisasi dicatat
