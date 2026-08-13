# CODEBASE INDEX — SawitGIS

> Dokumen ini dihasilkan secara otomatis untuk membantu AI lain memahami
> struktur, tanggung jawab, dan alur kerja aplikasi SawitGIS secara menyeluruh.
> Diperbarui terakhir: 2026-08-09

---

## 1. GAMBARAN UMUM APLIKASI

**Nama:** SawitGIS
**Deskripsi:** WebGIS berbasis Laravel untuk pencatatan kondisi blok lahan, analisis rekomendasi pemupukan kelapa sawit menggunakan Rule-Based System (RBS) dengan metode *forward chaining*, serta pencatatan pelaksanaan pemupukan untuk kelompok tani.
**Konteks:** Aplikasi skripsi — sistem pendukung keputusan, bukan pengganti ahli agronomi.

### Stack Teknologi
- **Backend:** PHP 8.2, Laravel 11
- **Frontend:** Blade + Tailwind CSS 4, Alpine.js (inline), Leaflet.js (peta), Turf.js (polygon)
- **PDF:** Dompdf (`barryvdh/laravel-dompdf`)
- **Shapefile:** `gasparesganga/php-shapefile`
- **Database:** MySQL atau SQLite
- **Testing:** PHPUnit 10
- **Build:** Vite + Laravel Vite Plugin

### Versi Mesin Rekomendasi
Versi terkini: `pahan-v2.9` (di `config/fertilization.php`)


---

## 2. ALUR KERJA UTAMA (WORKFLOW)

```
1. Admin daftar Anggota Kelompok Tani
2. Admin daftar Blok Lahan milik anggota (dengan peta GeoJSON)
3. Admin isi Observasi Lapangan (KondisiLahan):
      - Kondisi daun, drainase, curah hujan, kelembaban, gulma, hama
      - Foto observasi (opsional)
      - Data curah hujan: otomatis dari Open-Meteo API atau isi manual
4. Setelah observasi disimpan → sistem otomatis menjalankan Analisis RBS
5. Analisis RBS (Forward Chaining):
      - Evaluasi rule DIAGNOSIS_VISUAL → status kondisi tanaman
      - Evaluasi window (FertilizationWindowService) → kelayakan waktu
      - Hitung dosis dari tabel Pahan 2013
      - Simpan hasil sebagai RekomendasiRbs (is_latest)
6. Admin lihat hasil analisis per blok (rbs.detail)
7. Jika status TAHAP_1_SIAP atau TAHAP_2_SIAP → catat RealisasiPemupukan
8. Setiap realisasi memperbarui status tahap (CurrentApplicationCalculator)
9. Program Pemupukan otomatis SELESAI jika sisa = 0
10. Admin cetak laporan PDF per rekomendasi
```

---

## 3. STRUKTUR DIREKTORI

```
app/
  Console/Commands/     # Artisan commands operasional
  Enums/                # Enum PHP 8.1 (status, fase)
  Http/
    Controllers/        # Controller web dan API internal
    Middleware/         # AdminAuthenticated
    Requests/           # Form Request validasi
  Models/               # Eloquent models
  Notifications/        # Notifikasi database (RealisasiNotification)
  Providers/            # AppServiceProvider
  Services/             # Business logic (semuanya di sini)

config/
  fertilization.php     # Tabel dosis Pahan 2013, window, versi mesin
  observation.php       # Opsi kondisi daun observasi

database/
  migrations/           # 50+ migration file
  seeders/              # AdminSeeder, RuleBaseSeeder, DemoSawitGisSeeder

resources/views/        # Blade views (per modul)
routes/web.php          # Semua route aplikasi
tests/
  Feature/              # 50+ feature test
  Unit/                 # 14 unit test
  Support/              # Legacy schema builder untuk test upgrade
docs/                   # Dokumentasi sistem (termasuk file ini)
deploy/                 # Panduan deploy (InfinityFree, RumahWeb)
```


---

## 4. MODELS

### `Admin`
**Tabel:** `admins`
**Auth guard:** `admin`
**Fillable:** `username`, `password` (hashed), `nama_lengkap`, `tema` (light/dark/system)
**Trait:** `Authenticatable`, `Notifiable`, `HasFactory`
**Relasi:** Punya banyak `RealisasiPemupukan`, `RekomendasiRbs`; dapat menerima notifikasi Laravel.

---

### `Anggota`
**Tabel:** `anggotas`
**Fillable:** `nama`, `no_hp`, `alamat`
**Relasi:** `hasMany(BlokLahan)`

---

### `BlokLahan`
**Tabel:** `blok_lahans`
**Fillable:** `anggota_id`, `nama_blok`, `luas_ha`, `sph`, `koordinat_geojson`, `tahun_tanam`, `jenis_tanah`, `topografi`, `fase_tanaman` (TBM/TM), `jumlah_pohon`
**Casts:** `luas_ha` double, `sph` integer, `tahun_tanam` integer, `jumlah_pohon` integer
**Relasi:**
- `belongsTo(Anggota)`
- `hasMany(KondisiLahan)`
- `hasOne kondisiTerbaru` (latest by tanggal_observasi)
- `hasMany(RekomendasiRbs)`
- `hasOne rekomendasiRbsTerbaru` (where is_latest=true)
- `hasMany(RealisasiPemupukan)`
- `hasMany(ProgramPemupukan)`

**Accessor penting:**
- `nama_pemilik` — dari relasi anggota
- `jumlah_pokok_aktual` — `jumlah_pohon` jika ada, else `luas_ha × sph`
- `umur_tanaman` — `now()->year - tahun_tanam`
- `kategori_umur` — Belum Menghasilkan / Remaja / Menghasilkan Muda / Menghasilkan Tua / Tua Renta
- `fase_label` — label panjang dari `PlantPhase::labelFromValue()`


---

### `KondisiLahan`
**Tabel:** `kondisi_lahans`
**Fillable:** `blok_lahan_id`, `tanggal_observasi`, `tanggal_pemupukan_terakhir`, `kelembaban_tanah`, `curah_hujan_kategori`, `curah_hujan_mm_bulanan`, `periode_curah_hujan`, `sumber_curah_hujan`, `musim_saat_ini`, `warna_daun`, `kondisi_drainase`, `ada_gulma_dominan` (bool), `ada_serangan_hama` (bool), `catatan_observasi`, `foto_observasi_path`, `status_verifikasi_gejala`
**Relasi:** `belongsTo(BlokLahan)`, `hasMany(RekomendasiRbs)`

**Nilai valid `warna_daun`** (dari `config/observation.php`):
- `Hijau Normal` (kondisi normal)
- `Daun Bawah Menguning`
- `Bercak Kuning/Transparan pada Daun Tua`
- `Tepi Daun Tua Menguning pada Bagian Terbuka`
- `Daun Muda Berbentuk Kait atau Memendek`

---

### `RuleBaseLanjutan`
**Tabel:** `rule_bases_lanjutan`
**Jenis rule (`jenis_rule`):** `DIAGNOSIS_VISUAL` | `PEMBATAS_APLIKASI`
**Kondisi IF:** `kondisi_warna_daun`, `kondisi_topografi`, `kondisi_curah_hujan_min_mm`, `kondisi_curah_hujan_max_mm`, `kondisi_kategori_umur`
**Output THEN:** `indikasi_masalah`, `jenis_pupuk_utama`, `saran_tindakan`, `status_kebutuhan`, `tingkat_keparahan`, `prioritas`
**Provenance:** `sumber_judul`, `sumber_penulis`, `sumber_tahun`, `sumber_halaman`, `tingkat_bukti`, `is_system_rule`, `status_validasi`
**Scope:** `scopeAktif()` — hanya rule dengan `aktif = true`

---

### `RekomendasiRbs`
**Tabel:** `rekomendasi_rbs`
Menyimpan hasil analisis RBS per blok. Hanya satu record `is_latest = true` per blok.

**Field utama:**
- `blok_lahan_id`, `kondisi_lahan_id`, `program_pemupukan_id`, `admin_id`
- `tanggal_analisis`, `is_latest`, `nomor_analisis`
- `rules_terpicu` (JSON array), `masalah_teridentifikasi` (JSON), `rekomendasi_pupuk` (JSON)
- `status_kondisi_tanaman` — dari enum `PlantConditionStatus`
- `status_kelayakan_aplikasi` — dari enum `ApplicationFeasibilityStatus`
- `fase_tanaman_snapshot`, `umur_tanaman_snapshot`, `jumlah_pokok_snapshot`
- `urea_*` dan `kcl_*` — min/max/estimasi per pokok, total tahunan, aplikasi saat ini, sisa
- `active_stage` (1 atau 2), `status_stage` (dari `CurrentApplicationCalculator`)
- `analysis_fingerprint` — SHA-256 untuk deteksi perubahan bermakna
- `versi_mesin_rekomendasi` — misal `pahan-v2.9`

**Accessor penting:** `warna_badge`, `label_status`, `label_fase`, `label_kondisi_tanaman`, `label_kelayakan`, `label_status_stage`, `is_tahap_siap`, `is_program_selesai`, `karung_urea`, `karung_kcl`


---

### `ProgramPemupukan`
**Tabel:** `program_pemupukans`
Identitas program pemupukan tahunan per blok. Satu blok hanya boleh punya satu program AKTIF per tahun (dijamin via kolom `active_key` UNIQUE).

**Fillable:** `uuid`, `blok_lahan_id`, `tahun_program`, `rekomendasi_awal_id`, `status_program`, `active_key`
**Status:** `AKTIF` | `SELESAI` | `DIBATALKAN` | `ARSIP`
**Relasi:** `belongsTo(BlokLahan)`, `belongsTo(RekomendasiRbs) as rekomendasiAwal`, `hasMany(RekomendasiRbs)`, `hasMany(RealisasiPemupukan)`
**Scopes:** `scopeAktif()`, `scopeForBlokTahun($blokId, $tahun)`

---

### `RealisasiPemupukan`
**Tabel:** `realisasi_pemupukans`
Catatan pelaksanaan pemupukan aktual per tahap.

**Fillable:** `rekomendasi_rbs_id`, `blok_lahan_id`, `program_pemupukan_id`, `admin_id`, `tahun_program`, `tahap` (1 atau 2), `tanggal_realisasi`, `urea_rencana_kg`, `kcl_rencana_kg`, `urea_realisasi_kg`, `kcl_realisasi_kg`, `status_realisasi`, `catatan_pelaksana`, `confirmed_over_plan`, `override_annual_limit`, `override_reason`, `submission_token`
**Status:** `SELESAI` | `SEBAGIAN` | `BATAL`
**Scopes:** `scopeAktif()` (non-BATAL), `scopeTahap($tahap)`, `scopeTahunProgram($tahun)`

---

### `RekomendasiOperasionalHistory`
**Tabel:** `rekomendasi_operasional_histories`
Log audit setiap perubahan operasional pada rekomendasi (tidak pernah dihapus).
**Event types:** `ANALISIS_AWAL` | `REALISASI_DIBUAT` | `REALISASI_DIPERBARUI` | `REALISASI_DIBATALKAN` | `TAHAP_1_SEBAGIAN` | `TAHAP_1_SELESAI` | `TAHAP_2_SIAP` | `PROGRAM_SELESAI`

---

## 5. ENUMS

### `PlantPhase` (app/Enums/PlantPhase.php)
- `BELUM_MENGHASILKAN` → nilai DB: `TBM`
- `MENGHASILKAN` → nilai DB: `TM`
- Method: `label()`, `description()`, `labelFromValue(?string)`, `options()`

### `PlantConditionStatus` (app/Enums/PlantConditionStatus.php)
Status diagnosis visual tanaman. Hanya berasal dari rule `DIAGNOSIS_VISUAL`.
- `NORMAL_VISUAL`, `TERINDIKASI_DEFISIENSI_RINGAN`, `TERINDIKASI_DEFISIENSI`, `GEJALA_BERAT`, `PERLU_VERIFIKASI`, `BELUM_DIOBSERVASI`
- Method: `label()`, `labelFromValue(?string)`, `fromSeverity(?string)`

### `ApplicationFeasibilityStatus` (app/Enums/ApplicationFeasibilityStatus.php)
Status kelayakan waktu aplikasi pupuk. Hanya berasal dari `FertilizationWindowService`.
- `LAYAK_DIJADWALKAN`, `TUNDA_HUJAN_RENDAH`, `TUNDA_HUJAN_TINGGI`, `TUNDA_TANAH_KERING`, `TUNDA_INTERVAL`, `PERLU_PERBAIKAN_DRAINASE`, `PERLU_VERIFIKASI_DATA`, `TERLAMBAT_PERLU_DIJADWALKAN`
- Method: `label()`, `labelFromValue(?string)`, `isApplicable()`


---

## 6. SERVICES

Semua business logic ada di `app/Services/`. Controller hanya memanggil service.

### `RbsService` ⭐ (Service Utama)
**File:** `app/Services/RbsService.php`
**Tanggung jawab:** Menjalankan Forward Chaining, menyimpan hasil analisis.
**Method publik:**
- `analisis(BlokLahan $blok): array` — Analisis satu blok. Melempar `\Exception` jika data tidak cukup.
- `analisisSemua(): array` — Analisis semua blok yang punya kondisi. Return `['results', 'errors']`.

**Alur `analisis()`:**
1. Ambil `kondisiTerbaru` blok
2. Resolve konteks tanaman via `PlantContextService` (umur & fase SAAT observasi)
3. Evaluasi kelengkapan observasi via `ObservationCompletenessService`
4. Cek verifikasi fase untuk umur = 3
5. Cek minimal data (minimal 1 field kondisi terisi)
6. Jika data kurang → kembalikan dosis dasar tanpa diagnosis
7. Ambil semua rule aktif, urutkan prioritas
8. **Forward Chaining Fixpoint:** evaluasi rule sampai tidak ada fakta baru
9. Evaluasi `PlantConditionStatus` dari rule DIAGNOSIS_VISUAL
10. Evaluasi `ApplicationFeasibilityStatus` dari `FertilizationWindowService`
11. Hitung dosis Pahan via `PahanDoseReferenceService`
12. Bangun snapshot tahunan via `AnnualFertilizerSnapshotBuilder`
13. Hitung tahap aktif via `CurrentApplicationCalculator`
14. Susun jadwal via `FertilizationScheduleService`
15. Hitung skor keandalan via `RecommendationReliabilityService`
16. Simpan dengan histori via `simpanDenganHistori()` (gunakan fingerprint SHA-256)

---

### `PahanDoseReferenceService`
**Tanggung jawab:** Menyediakan rentang dosis Urea & KCl per pokok per tahun dari tabel Pahan 2013.
**Method:**
- `getDoseReferenceForContext(BlokLahan, int $umur, string $fase): array` — ⭐ Gunakan ini untuk analisis historis
- `getDoseReference(BlokLahan): array` — Kompatibilitas, menggunakan umur saat ini

**Tabel dosis** (`config/fertilization.php`):
| Fase | Kelompok Umur | Urea (kg/pokok/th) | KCl (kg/pokok/th) |
|------|--------------|---------------------|-------------------|
| TBM  | Tahun ke-1   | 0.50 – 0.70         | 0.75 – 1.25       |
| TBM  | Tahun ke-2   | 0.70 – 0.85         | 1.00 – 1.75       |
| TBM  | Tahun ke-3   | 0.90 – 1.25         | 1.20 – 2.25       |
| TM   | 3–5 tahun    | 0.90 – 1.75         | 1.20 – 2.50       |
| TM   | 6–15 tahun   | 1.00 – 3.00         | 1.50 – 3.50       |
| TM   | > 15 tahun   | 1.50 – 2.50         | 1.50 – 2.25       |

**Strategi estimasi:** default `midpoint` (rata-rata min+max), bisa diubah ke `minimum`/`maximum` via `.env DOSE_STRATEGY`.


---

### `FertilizationWindowService`
**Tanggung jawab:** Menentukan kelayakan waktu aplikasi pupuk berdasarkan curah hujan, kelembaban, drainase, dan interval.
**Method:** `evaluate(KondisiLahan, ?Carbon): array`

**Aturan evaluasi:**
- Curah hujan < 60 mm/bln → `TUNDA_HUJAN_RENDAH`
- Curah hujan > 300 mm/bln → `TUNDA_HUJAN_TINGGI`
- Curah hujan 60–100 atau 250–300 mm/bln → `PERLU_VERIFIKASI_DATA`
- Kelembaban `Sangat Kering` → `TUNDA_TANAH_KERING`
- Interval < 120 hari dari pemupukan terakhir → `TUNDA_INTERVAL`
- Drainase `Buruk — Tergenang` → `PERLU_PERBAIKAN_DRAINASE`
- Semua OK → `LAYAK_DIJADWALKAN`

---

### `CurrentApplicationCalculator`
**Tanggung jawab:** Menentukan jumlah pupuk yang perlu diaplikasikan pada tahap aktif saat ini.
**Method:** `calculate(array $input): array`

**Status tahap yang dihasilkan:**
| Konstan | Arti |
|---------|------|
| `TAHAP_1_SIAP` | Belum ada realisasi, siap 50% tahunan |
| `TAHAP_1_SEBAGIAN` | Realisasi tahap 1 ada tapi belum selesai |
| `MENUNGGU_INTERVAL` | Tahap 1 selesai, interval 120 hari belum terpenuhi |
| `MENUNGGU_KELAYAKAN` | Kondisi lapangan belum mendukung |
| `TAHAP_2_SIAP` | Tahap 1 selesai, interval terpenuhi, sisa tahunan |
| `SELESAI_TAHUNAN` | Kebutuhan tahunan sudah terpenuhi |
| `PERLU_VERIFIKASI_REALISASI` | Kebutuhan tahunan belum ditentukan |

---

### `FertilizationRealizationService`
**Tanggung jawab:** Menghitung ringkasan realisasi pemupukan (total, per tahap, interval).
**Method utama:**
- `getRealizationSummaryForProgram(ProgramPemupukan): array` — ⭐ Direkomendasikan (berbasis program)
- `getRealizationSummary(BlokLahan, ?int, ?Carbon): array` — Fallback legacy
- `calculateRemaining(float, float, array): array` — Hitung sisa tahunan
- `exceedsAnnualRequirement(...): bool` — Cek melebihi batas tahunan

---

### `RealisasiEligibilityService`
**Tanggung jawab:** Validasi kelayakan pencatatan realisasi (server-side gate).
**Method:** `evaluate(RekomendasiRbs): array`
**Return:** `boleh_mencatat` (bool), `active_stage`, `status_stage`, `urea_rencana_kg`, `kcl_rencana_kg`, `tahun_program`, dll.
**Penolakan otomatis:** rekomendasi historis (`is_latest=false`), data observasi belum cukup, program bukan AKTIF.

---

### `ProgramPemupukanService`
**Tanggung jawab:** Resolve dan buat program pemupukan tahunan. Dijamin via `lockForUpdate()` untuk mencegah race condition.
**Method:**
- `resolveActiveProgram(BlokLahan, int, ?RekomendasiRbs): ProgramPemupukan`
- `getActiveProgram(BlokLahan, int): ?ProgramPemupukan`

---

### `ProgramStatusService`
**Tanggung jawab:** Sinkronisasi status program (otomatis SELESAI jika sisa = 0).
**Method:** `synchronizeStatus(ProgramPemupukan, array $currentApplication): void`


---

### `AnnualFertilizerSnapshotBuilder`
**Tanggung jawab:** Menghitung total kebutuhan pupuk tahunan dan snapshot (luas, SPH, jumlah pokok).
**Method:** `build(BlokLahan, array $doseReference, bool $isApplicable): array`
Menghasilkan `urea_total_estimasi_tahunan`, `kcl_total_estimasi_tahunan`, `urea_karung_estimasi_tahunan`, dll.

---

### `FertilizationCalculationService`
**Tanggung jawab:** Kalkulasi total pupuk dari dosis referensi dan jumlah pokok aktual.
**Rumus:** `total = dosis_per_pokok × jumlah_pokok_aktual`
**Method:** `calculate(BlokLahan, array $doseReference): array`

---

### `FertilizationScheduleService`
**Tanggung jawab:** Menyusun jadwal pemupukan (hanya jika layak dan curah hujan numerik tersedia).
**Method:** `generate(array, KondisiLahan, BlokLahan, array $windowResult, array $plantContext): array`
Menghasilkan array kosong `[]` jika tidak layak atau status menunggu.

---

### `ObservationCompletenessService`
**Tanggung jawab:** Menentukan apakah data observasi cukup untuk diagnosis RBS.
**Method:** `evaluate(KondisiLahan): array`
**Field yang diperiksa:** `warna_daun` (blocking), `kondisi_drainase`, `curah_hujan`, `kelembaban_tanah`, `musim_saat_ini`, `tanggal_pemupukan_terakhir`
**Key output:** `can_run_diagnosis` (bool) — hanya butuh `warna_daun` terisi.

---

### `PlantContextService`
**Tanggung jawab:** Menentukan umur dan fase tanaman pada tanggal tertentu (historis akurat).
**Method:** `resolve(BlokLahan, Carbon $tanggalReferensi): array`
**Aturan fase:**
- umur < 3 → TBM otomatis
- umur > 3 → TM otomatis
- umur = 3 → perlu verifikasi (pakai `fase_tanaman` dari blok jika ada)

---

### `PlantAgeService`
**Tanggung jawab:** Kalkulasi umur tanaman pada tanggal referensi.
**Method:** `calculateAgeAt(BlokLahan, Carbon): array`, `calculateCurrentAge(BlokLahan): ?int`

---

### `PlantPhaseResolver`
**Tanggung jawab:** Resolve fase tanaman untuk analisis saat ini (kompatibilitas).
**Digunakan oleh:** `PahanDoseReferenceService::getDoseReference()`

---

### `RecommendationReliabilityService`
**Tanggung jawab:** Hitung skor kelengkapan data pendukung (0-100).
**Bobot:** identitas_blok 20%, fase_terverifikasi 10%, curah_hujan 30%, tgl_pemupukan 15%, data_visual 15%, kondisi_lapangan 10%.
**Kategori:** Perlu Dilengkapi (<70), Cukup Lengkap (70–89), Lengkap (≥90).

---

### `RecommendationOperationalRefreshService`
**Tanggung jawab:** Memperbarui field operasional rekomendasi setelah ada realisasi baru/diubah/dibatal, **tanpa** menjalankan ulang diagnosis visual.
**Method:** `refreshAfterRealization(RealisasiPemupukan): void`, `refreshForBlok(BlokLahan): void`

---

### `SupportingFertilizerSanitizer`
**Tanggung jawab:** Sanitasi daftar pupuk dari rule (menghapus duplikat dan entri tidak valid).


---

## 7. CONTROLLERS

| Controller | Route Prefix | Tanggung Jawab |
|-----------|-------------|----------------|
| `AuthController` | `/login`, `/logout` | Login/logout admin (guard `admin`) |
| `DashboardController` | `/dashboard` | WebGIS — peta semua blok + statistik |
| `AnggotaController` | `/anggota` | CRUD anggota kelompok tani |
| `BlokLahanController` | `/blok-lahan` | CRUD blok lahan + upload GeoJSON |
| `KondisiLahanController` | `/kondisi-lahan` | CRUD observasi lapangan + auto-analisis setelah simpan |
| `RuleBaseController` | `/rule-base` | Kelola rule RBS (tambah, edit, aktifkan/nonaktifkan) |
| `RbsController` | `/rbs` | Analisis RBS, detail hasil, API popup peta |
| `LaporanController` | `/laporan` | Daftar laporan + export PDF |
| `RealisasiPemupukanController` | `/realisasi-pemupukan` | CRUD realisasi + validasi ketat server-side |
| `SettingController` | `/settings` | Ganti password, ubah tema |
| `CuacaController` | `POST /api/cuaca/fetch` | Fetch otomatis dari Open-Meteo API |
| `GeoUploadController` | `POST /api/geo-upload` | Upload SHP/GeoJSON → polygon GeoJSON |
| `NotificationController` | `/api/notifications` | API notifikasi (recent, markAsRead, markAllAsRead) |

### Method penting `RealisasiPemupukanController`:
- **Perlindungan double-submit 3 lapis:** (1) submission_token idempotent, (2) duplikasi semantik (5 menit), (3) `lockForUpdate` dalam transaksi
- Server menentukan: `tahap`, `tahun_program`, `urea_rencana_kg`, `kcl_rencana_kg` — tidak dari browser
- Validasi status SELESAI: total kumulatif harus ≥ rencana tahap (toleransi 0.01 kg)

---

## 8. MIDDLEWARE

### `AdminAuthenticated`
**File:** `app/Http/Middleware/AdminAuthenticated.php`
Semua route yang dilindungi menggunakan middleware ini. Redirect ke `/login` jika tidak terautentikasi.

---

## 9. ROUTES (Ringkasan)

```
GET  /                             → redirect ke /dashboard
GET  /login                        → form login (guest only)
POST /login                        → proses login (throttle 5/menit)
POST /logout                       → logout

[Semua route berikut memerlukan AdminAuthenticated]

GET  /dashboard                    → WebGIS utama
CRUD /anggota                      → kelola anggota (index, create, store, edit, update, destroy)
CRUD /blok-lahan                   → kelola blok (termasuk show)
CRUD /kondisi-lahan                → observasi (index, create, store, edit, update, destroy)
GET  /kondisi-lahan/{id}/foto      → tampilkan foto observasi

GET  /rule-base                    → daftar rule
GET  /rule-base/info               → panduan pengelolaan rule
GET  /rule-base/tambah             → form tambah rule
POST /rule-base                    → simpan rule
GET  /rule-base/{id}/edit          → form edit rule
PUT  /rule-base/{id}               → update rule
PATCH /rule-base/{id}/status       → toggle aktif/nonaktif

GET  /rbs                          → daftar blok + status analisis
GET  /rbs/daftar-blok-belum-analisis → API JSON
POST /rbs/analisis/{blokLahan}     → analisis satu blok
POST /rbs/analisis-semua           → analisis semua blok
GET  /rbs/detail/{blokLahan}       → detail hasil analisis

GET  /laporan                      → daftar laporan
GET  /laporan/{id}                 → detail laporan
GET  /laporan/{id}/pdf             → export PDF

GET  /realisasi-pemupukan                    → daftar (tab: siap, menunggu, riwayat)
GET  /realisasi-pemupukan/create/{rekRbs}    → form realisasi (cek eligibility)
POST /realisasi-pemupukan                    → simpan realisasi
GET  /realisasi-pemupukan/{id}               → detail realisasi
GET  /realisasi-pemupukan/{id}/edit          → form edit
PUT  /realisasi-pemupukan/{id}               → update realisasi
PATCH /realisasi-pemupukan/{id}/batal        → batalkan realisasi

GET  /settings                     → halaman pengaturan
PUT  /settings/password            → ubah password
PUT  /settings/theme               → ubah tema (JSON response)

GET  /api/rbs-popup/{blokLahan}    → data popup peta WebGIS (JSON)
POST /api/cuaca/fetch              → fetch data cuaca Open-Meteo (JSON)
POST /api/geo-upload               → upload shapefile/GeoJSON (JSON)

GET  /api/notifications            → 10 notifikasi terbaru (JSON)
POST /api/notifications/{id}/read  → tandai dibaca
POST /api/notifications/read-all   → tandai semua dibaca
```


---

## 10. VIEWS (Blade)

```
layouts/app.blade.php              # Layout utama (sidebar, navbar, notifikasi bell)
auth/login.blade.php               # Halaman login

dashboard/index.blade.php          # WebGIS Leaflet + statistik + popup blok

anggota/
  index.blade.php                  # Daftar anggota + pagination
  create.blade.php                 # Form tambah
  edit.blade.php                   # Form edit

blok_lahan/
  index.blade.php                  # Daftar blok (grouped by anggota)
  create.blade.php                 # Form + peta Leaflet untuk gambar polygon
  edit.blade.php                   # Form edit + peta
  show.blade.php                   # Detail blok

kondisi_lahan/
  _form.blade.php                  # Partial form observasi (dipakai create & edit)
  create.blade.php                 # Form observasi baru
  edit.blade.php                   # Form edit observasi
  index.blade.php                  # Daftar blok + status observasi

rule_base/
  _form.blade.php                  # Partial form rule
  index.blade.php                  # Daftar rule aktif
  create.blade.php                 # Form tambah rule
  edit.blade.php                   # Form edit rule
  info.blade.php                   # Panduan pengelolaan rule

rbs/
  index.blade.php                  # Daftar blok + status analisis (filter, stats)
  detail.blade.php                 # Detail hasil analisis satu blok
  partials/
    _detail_readable.blade.php     # Detail hasil yang mudah dibaca
    _hasil_rbs.blade.php           # Tampilan hasil rule

laporan/
  index.blade.php                  # Daftar laporan per anggota + grand total
  show.blade.php                   # Detail laporan satu rekomendasi
  pdf.blade.php                    # Template PDF (Dompdf)

realisasi_pemupukan/
  index.blade.php                  # 3 tab: Siap, Menunggu, Riwayat
  create.blade.php                 # Form realisasi (data dari server)
  edit.blade.php                   # Form edit realisasi
  show.blade.php                   # Detail realisasi + histori operasional

settings/index.blade.php           # Pengaturan password & tema

components/
  custom-select.blade.php
  data-kebun-tabs.blade.php
  filter-searchable.blade.php
  next-block-action.blade.php
  observation-step-navigation.blade.php
  observation-stepper.blade.php
  recommendation-status.blade.php
  searchable-select.blade.php

errors/
  403.blade.php, 404.blade.php, 419.blade.php,
  422.blade.php, 500.blade.php, 503.blade.php
```

---

## 11. DATABASE MIGRATIONS (Urutan Kronologis)

| File | Tabel/Perubahan |
|------|----------------|
| `0001_01_01_000000` | `users` (Laravel default, tidak dipakai) |
| `0001_01_01_000001` | `cache` |
| `0001_01_01_000002` | `jobs` |
| `2026_05_20_*` | `admins`, `blok_lahans` awal, `kriteria_lahans` (kemudian digabung), `rule_bases` (kemudian diganti `rule_bases_lanjutan`), `rekomendasi_spks` (kemudian diganti) |
| `2026_06_04_*` | `kondisi_lahans`, `rule_bases_lanjutan`, `rekomendasi_rbs` |
| `2026_06_07_*` | `anggotas`, tambah `anggota_id` ke blok, gabung kriteria ke blok |
| `2026_06_12_*` | Field intermediate rule, `tanggal_pemupukan_terakhir`, `realisasi_pemupukans` |
| `2026_06_14_*` | Field histori rekomendasi, curah hujan gulma ke rule |
| `2026_07_13_*` | Cleanup tabel lama, tambah `tema` ke admins |
| `2026_07_20_*` | Field Pahan v2.2–v2.5 ke rekomendasi, blok, kondisi, realisasi |
| `2026_07_21_*` | Upgrade realisasi v2.6, `submission_token`, `notifications` |
| `2026_07_22_*` | `program_pemupukans`, `rekomendasi_operasional_histories` |
| `2026_07_23_*` | `active_key` ke program |
| `2026_07_28_*` | Expand `warna_daun` enum, traceable rule conditions |
| `2026_07_29_*` | Foto observasi |
| `2026_07_30_*` | Hapus artefak rule lama, cleanup kolom |
| `2026_08_03_*` | Hapus `ph` dan `defisiensi` dari kondisi |
| `2026_08_07_*` | `jumlah_pohon` ke blok |
| `2026_08_08_*` | `topografi` ke rule_bases_lanjutan |


---

## 12. SEEDERS

| Seeder | Fungsi |
|--------|--------|
| `DatabaseSeeder` | Memanggil `AdminSeeder` + `RuleBaseSeeder` |
| `AdminSeeder` | Buat akun admin awal dari env `INITIAL_ADMIN_*` |
| `RuleBaseSeeder` | Seed 7 rule sistem (bersumber dari Pahan 2013 & PPKS) |
| `DemoSawitGisSeeder` | Seed data demo: anggota, blok, observasi, realisasi |

---

## 13. ARTISAN COMMANDS

| Command | Class | Fungsi |
|---------|-------|--------|
| `php artisan sawit:health-check [--dry-run]` | `HealthCheck` | Periksa integritas database: program ganda, rekomendasi konsisten, realisasi valid, dll. |
| `php artisan sawit:audit-pahan-v2` | `AuditPahanV2` | Audit konsistensi rule dan rekomendasi |
| `php artisan sawit:backup-database` | `BackupDatabase` | Backup database MySQL ke storage |
| `php artisan sawit:backup-list` | `BackupList` | Daftar file backup |
| `php artisan sawit:clear-cache` | `MaintenanceClearCache` | Bersihkan semua cache aplikasi |
| `php artisan sawit:reset-demo-data [--dry-run]` | `ResetDemoData` | Bersihkan data demo |

---

## 14. NOTIFIKASI

**Class:** `RealisasiNotification` (`app/Notifications/`)
**Channel:** `database` (tersimpan di tabel `notifications`)
**Tipe event dan factory methods:**
- `tahapSiap(namaBlok, tahap, url)` — blok siap pemupukan tahap tertentu
- `intervalTerpenuhi(namaBlok, url)` — interval 120 hari terpenuhi, Tahap 2 siap
- `realisasiDicatat(namaBlok, tahap, url)` — setelah realisasi berhasil disimpan
- `programSelesai(namaBlok, url)` — kebutuhan tahunan terpenuhi
- `realisasiSebagian(namaBlok, tahap, url)` — realisasi sebagian, perlu dilengkapi

Notifikasi dikirim oleh `RbsController` (analisis) dan `RealisasiPemupukanController` (realisasi).
Anti-spam: cek notifikasi yang sama belum dibaca sebelum mengirim ulang.

---

## 15. KONFIGURASI PENTING

### `config/fertilization.php`
- `reference_dose_strategy` — `midpoint` (default), `minimum`, `maximum`
- `dose_reference` — Tabel dosis Pahan 2013 (TBM/TM × kelompok umur)
- `reference_source` — Bibliografi Pahan 2013 lengkap
- `window.rainfall_optimal_min_mm` — 100
- `window.rainfall_optimal_max_mm` — 250
- `window.rainfall_defer_below_mm` — 60
- `window.rainfall_defer_above_mm` — 300
- `window.min_interval_days` — 120
- `reliability_weights` — bobot skor kelengkapan data
- `engine_version` — `pahan-v2.9`

### `config/observation.php`
- `normal_leaf_condition` — `Hijau Normal`
- `diagnostic_leaf_conditions` — 4 kondisi daun yang punya rule
- `leaf_conditions` — normal + 4 diagnostik
- `leaf_condition_labels` — label ramah pengguna
- `leaf_condition_descriptions` — deskripsi untuk petugas lapangan
- `unmatched_leaf_values` — nilai khusus form yang diubah ke NULL saat simpan

---

## 16. LOGIKA INTI YANG PERLU DIPAHAMI

### Forward Chaining (Fixpoint Algorithm)
Algoritma di `RbsService::analisis()`:
1. Semua rule aktif diurutkan berdasarkan `prioritas`
2. Loop sampai tidak ada fakta baru (`factsChanged = false`)
3. Setiap rule hanya bisa terpicu **sekali** (via `$triggeredRuleIds`)
4. Rule bisa menghasilkan `kondisi_intermediate` (fakta baru) untuk rule chaining
5. Evaluasi rule: semua kondisi yang diisi di rule harus cocok (AND logic); kondisi NULL di rule = diabaikan
6. Maksimum iterasi = jumlah rule aktif

### Pemisahan Status (Desain Penting)
- **`status_kondisi_tanaman`** (`PlantConditionStatus`) — HANYA dari rule `DIAGNOSIS_VISUAL`, TIDAK dari rule lain
- **`status_kelayakan_aplikasi`** (`ApplicationFeasibilityStatus`) — HANYA dari `FertilizationWindowService`
- Keduanya **independen** — kondisi tanaman buruk tidak otomatis menghalangi pemupukan

### Sistem Fingerprint
- Setiap analisis menghasilkan hash SHA-256 dari data penting
- Jika fingerprint sama dengan analisis sebelumnya → hanya update `tanggal_analisis`, tidak buat record baru
- Setelah realisasi → fingerprint diperbarui oleh `RecommendationOperationalRefreshService`

### Program Pemupukan
- Satu blok = satu program AKTIF per tahun (dijamin oleh UNIQUE constraint `active_key`)
- `ProgramPemupukanService::resolveActiveProgram()` menggunakan `lockForUpdate()` untuk race condition
- Program otomatis SELESAI via `ProgramStatusService::synchronizeStatus()`


---

## 17. TESTING

### Unit Tests (`tests/Unit/`)
| File | Yang Diuji |
|------|-----------|
| `PahanDoseReferenceTest` | Tabel dosis Pahan semua kelompok umur/fase |
| `FertilizationWindowServiceTest` | Semua skenario curah hujan, kelembaban, drainase, interval |
| `CurrentApplicationCalculatorTest` | 6 kasus tahap aktif (TAHAP_1_SIAP sampai SELESAI_TAHUNAN) |
| `ObservationCompletenessTest` | Kriteria `can_run_diagnosis` |
| `PlantAgeServiceTest` | Kalkulasi umur historis |
| `PlantContextServiceTest` | Resolve fase dari umur historis |
| `PlantPhaseResolverTest` | Resolver fase saat ini |
| `PlantPhaseValidationTest` | Validasi edge case fase |
| `RuleEvaluationTest` | Evaluasi kondisi rule (AND logic) |
| `FertilizationScheduleServiceTest` | Generasi jadwal |
| `AnnualFertilizerSnapshotBuilderTest` | Snapshot kebutuhan tahunan |
| `RecommendationReliabilityTest` | Skor kelengkapan data |
| `FingerprintV25Test` | Fingerprint SHA-256 deterministik |
| `RainfallFallbackTest` | Fallback kategori curah hujan tanpa numerik |

### Feature Tests (`tests/Feature/`) — 50+ test
**Kategori utama:**
- Keamanan: `SecurityTest`, `ProductionSafetyTest`
- Alur realisasi: `RbsRealizationFlowIntegrationTest`, `RealisasiPemupukanCrudTest`, `DoubleSubmitRealizationTest`, `RealisasiStageLockTest`, `RealisasiTamperedRequestTest`
- Program pemupukan: `ProgramLifecycleTest`, `ProgramActiveUniquenessTest`, `ProgramPemupukanIsolationTest`
- Validasi akademik: `AcademicRuleEvidencePolicyTest`, `LegacyStatusStaticAuditTest`
- Laporan & PDF: `PdfConsistencyTest`, `PdfOperationalConsistencyTest`
- Forward chaining: `ForwardChainingFixpointTest`
- Upgrade schema: `MigrationUpgradePathTest`, `TrueLegacySchemaUpgradeTest`

### Test Support
- `tests/Support/LegacySchemaBuilder.php` — membangun skema DB versi lama untuk test upgrade
- `tests/sample_files/sample_blok_lahan.geojson` — GeoJSON contoh untuk test upload

---

## 18. KEPUTUSAN DESAIN AKADEMIK (PENTING)

Berikut keputusan yang harus dipahami agar tidak salah memodifikasi sistem:

| Aspek | Keputusan |
|-------|-----------|
| Sumber dosis | **Hanya dari Pahan 2013** Tabel 9.13 & 9.14. Bukan dari gejala visual, topografi, atau jenis tanah. |
| Gejala visual | Hanya menghasilkan `indikasi` dan `saran pemeriksaan`, TIDAK mengubah dosis. |
| Rule curah hujan | Menentukan **waktu** aplikasi, bukan angka dosis. |
| Jenis tanah & topografi | Disimpan sebagai identitas blok, TIDAK menjadi pengali dosis. |
| Pembagian tahap | 50%/50% adalah **adaptasi operasional** penelitian (dari rekomendasi frekuensi 2-3x/tahun PPKS 2021), bukan dari Pahan 2013. |
| Interval 120 hari | Parameter operasional turunan dari frekuensi PPKS, bukan nilai dari Pahan. |
| Hasil sistem | Pendukung keputusan — bukan pengganti analisis lab atau ahli agronomi. |

---

## 19. DOKUMENTASI LAIN DI FOLDER `docs/`

| File | Isi |
|------|-----|
| `ARSITEKTUR_SISTEM.md` | Penjelasan arsitektur lengkap tiap lapisan |
| `MATRIKS_SUMBER_RULE_RBS.md` | Matriks sumber literatur tiap rule sistem |
| `PANDUAN_ADMIN.md` | Panduan penggunaan untuk admin |
| `PANDUAN_PENGUJIAN.md` | Panduan menjalankan pengujian |
| `KETERBATASAN_SISTEM.md` | Batasan akademik dan teknis yang diakui |
| `ERD.drawio` | Entity Relationship Diagram |
| `DFD_Level_0.drawio` | DFD Level 0 |
| `DFD_Level_1.drawio` | DFD Level 1 |
| `Relasi_Tabel.drawio` | Diagram relasi tabel |
| `docs/referensi/` | Referensi tambahan dan teks skripsi tabel dosis |

---

## 20. INSTALASI SINGKAT

```bash
git clone <repo>
cd <folder>
composer install
npm install
cp .env.example .env
php artisan key:generate
# Atur DB_* dan INITIAL_ADMIN_* di .env
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

**Cek kesehatan:**
```bash
php artisan sawit:health-check --dry-run
php artisan test
vendor/bin/pint --test
```

---

*File ini adalah index otomatis. Untuk detail implementasi, baca langsung source code yang dirujuk.*
