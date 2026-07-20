# INDEX SOURCE CODE — SawitGIS

> **Proyek:** Rancang Bangun WebGIS Sistem Pemupukan Kelapa Sawit Menggunakan Metode Rule-Based System  
> **Framework:** Laravel 11 + Blade + Tailwind CSS 4 + Leaflet.js  
> **Mesin Rekomendasi:** Pahan-v2.7 (Forward Chaining Rule-Based System)  
> **Tanggal Generate:** 22 Juli 2026

---

## Daftar Isi

1. [File Konfigurasi Root](#1-file-konfigurasi-root)
2. [Bootstrap](#2-bootstrap)
3. [Config](#3-config)
4. [Routes](#4-routes)
5. [Controllers](#5-controllers)
6. [Models](#6-models)
7. [Services](#7-services)
8. [Enums](#8-enums)
9. [Middleware](#9-middleware)
10. [Form Requests](#10-form-requests)
11. [Providers](#11-providers)
12. [Console Commands](#12-console-commands)
13. [Database — Migrations](#13-database--migrations)
14. [Database — Seeders](#14-database--seeders)
15. [Views (Blade Templates)](#15-views-blade-templates)
16. [Resources — JavaScript](#16-resources--javascript)
17. [Resources — CSS](#17-resources--css)
18. [Public Assets](#18-public-assets)
19. [Tests](#19-tests)
20. [Deploy](#20-deploy)
21. [Dokumentasi](#21-dokumentasi)
22. [Aset Gambar](#22-aset-gambar)
23. [Ringkasan Statistik](#23-ringkasan-statistik)

---

## 1. File Konfigurasi Root

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `composer.json` | Dependensi PHP — Laravel 11, laravel-dompdf (export PDF), php-shapefile (parsing SHP), tinker |
| 2 | `package.json` | Dependensi JS — Tailwind CSS 4, Vite, @turf/turf (deteksi overlap polygon), axios |
| 3 | `vite.config.js` | Build config Vite — plugin Tailwind CSS + Laravel Vite Plugin (entry: css/app.css, js/app.js) |
| 4 | `phpunit.xml` | Konfigurasi PHPUnit untuk test suite |
| 5 | `.env.example` | Template environment variable |
| 6 | `.env.production` | Konfigurasi environment untuk production |
| 7 | `.htaccess` | Rewrite rules Apache untuk shared hosting (redirect ke public/) |
| 8 | `.editorconfig` | Standar formatting editor (indent, charset, EOL) |
| 9 | `.gitignore` | Pola file yang diabaikan Git |
| 10 | `.gitattributes` | Atribut Git (line ending, diff) |
| 11 | `artisan` | Entry point CLI Laravel |
| 12 | `README.md` | Dokumentasi lengkap proyek — arsitektur, instalasi, tabel dosis, forward chaining |

---

## 2. Bootstrap

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `bootstrap/app.php` | Bootstrap aplikasi — routing, middleware alias (auth.admin), trust proxy, CSRF exception, cookie encryption exception |
| 2 | `bootstrap/providers.php` | Registrasi AppServiceProvider |
| 3 | `bootstrap/cache/packages.php` | Cache auto-discovered packages |
| 4 | `bootstrap/cache/services.php` | Cache service providers |

---

## 3. Config

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `config/app.php` | Nama aplikasi, environment, timezone (Asia/Makassar), locale, enkripsi |
| 2 | `config/auth.php` | Guard default 'admin', provider Admin model |
| 3 | `config/database.php` | Koneksi database — MySQL (default), MariaDB, PostgreSQL, SQL Server |
| 4 | `config/fertilization.php` | **Custom** — Tabel dosis Pahan 2013 (Tabel 9.13 & 9.14), strategi estimasi, parameter window (curah hujan 100-250mm, interval 60 hari, terlambat 120 hari), bobot keandalan, kategori, versi mesin (pahan-v2.2), legacy multipliers (nonaktif) |
| 5 | `config/cache.php` | Konfigurasi cache |
| 6 | `config/filesystems.php` | Disk filesystem |
| 7 | `config/logging.php` | Channel logging |
| 8 | `config/session.php` | Konfigurasi session |

---

## 4. Routes

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `routes/web.php` | Seluruh route web — auth (guest), route terproteksi admin: dashboard, CRUD (anggota, blok-lahan, kondisi-lahan, rule-base), analisis RBS, laporan, settings, API endpoints (cuaca, geo-upload, rbs-popup) |
| 2 | `routes/console.php` | Route console — command 'inspire' bawaan Laravel |

---

## 5. Controllers

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `app/Http/Controllers/Controller.php` | Base controller abstrak |
| 2 | `app/Http/Controllers/AuthController.php` | Login/logout dengan guard admin, tema cookie, manajemen session |
| 3 | `app/Http/Controllers/DashboardController.php` | Dashboard WebGIS — data peta, statistik blok, delta bulan lalu, blok perlu perhatian |
| 4 | `app/Http/Controllers/AnggotaController.php` | CRUD anggota kelompok tani (pemilik lahan) dengan proteksi cascade |
| 5 | `app/Http/Controllers/BlokLahanController.php` | CRUD blok lahan — dikelompokkan per anggota, validasi SPH, konsistensi fase |
| 6 | `app/Http/Controllers/KondisiLahanController.php` | CRUD kondisi lahan (observasi) — kalkulasi centroid, warning konsistensi cross-field, filter cascading anggota |
| 7 | `app/Http/Controllers/RbsController.php` | Analisis RBS — single/batch analysis, detail hasil, API popup untuk WebGIS, daftar blok belum analisis |
| 8 | `app/Http/Controllers/RuleBaseController.php` | CRUD rule base lanjutan — validasi kondisi IF-THEN |
| 9 | `app/Http/Controllers/LaporanController.php` | Laporan rekomendasi — grouped by anggota, grand total, filter status/anggota/blok, export PDF via DomPDF |
| 10 | `app/Http/Controllers/CuacaController.php` | Auto-fetch cuaca dari Open-Meteo API — presipitasi, ET0, water balance, deteksi musim dinamis |
| 11 | `app/Http/Controllers/GeoUploadController.php` | Upload SHP (ZIP) atau GeoJSON — parse, validasi polygon, ekstrak koordinat |
| 12 | `app/Http/Controllers/SettingController.php` | Pengaturan admin — ganti password (verifikasi current_password), preferensi tema (light/dark/system) |
| 13 | `app/Http/Controllers/RealisasiPemupukanController.php` | (v2.7) CRUD realisasi pemupukan — eligibility check, server-determined tahap/rencana/tahun, validasi status SELESAI vs jumlah kumulatif, program pemupukan, histori operasional |

---

## 6. Models

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `app/Models/Admin.php` | Model admin (Authenticatable) — username, password, nama_lengkap, tema |
| 2 | `app/Models/Anggota.php` | Model anggota kelompok tani — nama, no_hp, alamat; relasi hasMany blokLahans |
| 3 | `app/Models/BlokLahan.php` | Model blok lahan — nama, luas, SPH, GeoJSON coords, tahun_tanam, jenis_tanah, topografi, fase; accessor: umur_tanaman, kategori_umur, fase_label |
| 4 | `app/Models/KondisiLahan.php` | Model observasi kondisi lahan — pH, kelembaban, curah hujan, warna daun, pelepah, gejala defisiensi, drainase, gulma, hama |
| 5 | `app/Models/RuleBaseLanjutan.php` | Model rule base RBS — kode_rule, conditions (warna daun, pH, kelembaban, curah hujan, musim, drainase, dll), outputs (indikasi, pupuk, dosis, saran), provenance metadata (sumber, versi, validasi) |
| 6 | `app/Models/RekomendasiRbs.php` | Model hasil rekomendasi RBS — rules terpicu, masalah, pupuk, jadwal, dosis, confidence, status kondisi/kelayakan, histori fingerprint; many accessor untuk label tampilan |
| 7 | `app/Models/RealisasiPemupukan.php` | (v2.6) Model realisasi pemupukan — rencana vs realisasi, status (SELESAI/SEBAGIAN/BATAL), tahap, tahun program, override, relasi program |
| 8 | `app/Models/ProgramPemupukan.php` | (v2.7) Model program pemupukan tahunan — uuid, blok, tahun, status (AKTIF/SELESAI/DIBATALKAN/ARSIP), satu blok satu program aktif per tahun |
| 9 | `app/Models/RekomendasiOperasionalHistory.php` | (v2.7) Model histori operasional — event_type, snapshot state tahap, sisa tahunan, fingerprint, tidak pernah dihapus |

---

## 7. Services

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `app/Services/RbsService.php` | **Orchestrator utama** — Forward Chaining engine: evaluasi rule, rule chaining (intermediate flags), susun hasil, cek kecukupan data, confidence score, jadwal pemupukan per tahap, konsistensi data |
| 2 | `app/Services/PahanDoseReferenceService.php` | Lookup dosis dari config (Pahan 2013, Tabel 9.13 & 9.14) — mapping fase+umur ke rentang Urea/KCl, strategi: min/midpoint/max |
| 3 | `app/Services/FertilizationCalculationService.php` | Kalkulasi total pupuk — jumlah_pokok = luas × SPH, total per tahun, karung (50kg), per-tahap |
| 4 | `app/Services/FertilizationWindowService.php` | Evaluasi kelayakan waktu pemupukan — curah hujan (100-250mm layak), interval min 60 hari, cek drainase, deteksi keterlambatan (>120 hari) |
| 5 | `app/Services/ObservationCompletenessService.php` | Cek kecukupan data — minimal 5/7 parameter terisi, warna daun wajib, pH atau drainase wajib |
| 6 | `app/Services/PlantPhaseResolver.php` | Resolusi fase TBM/TM — validasi override manual, deteksi konflik (umur vs fase), auto-suggest dari umur |
| 7 | `app/Services/PlantAgeService.php` | Kalkulasi umur tanaman — calculateAgeAt(blok, referenceDate) untuk analisis historikal, currentAge untuk dashboard |
| 8 | `app/Services/RecommendationReliabilityService.php` | Skor keandalan data (0-100) — 9 kriteria berbobot: identitas, fase, pH, curah hujan, tgl pemupukan, visual, drainase, rule bersumber, validasi ahli |
| 9 | `app/Services/PlantContextService.php` | Resolve konteks tanaman (umur, fase) pada tanggal observasi — historis, bukan saat ini |
| 10 | `app/Services/FertilizationScheduleService.php` | Jadwal pemupukan — nama tahap sesuai active_stage, jadwal kosong jika menunggu/selesai, dosis per pokok dari snapshot |
| 11 | `app/Services/AnnualFertilizerSnapshotBuilder.php` | Build snapshot kebutuhan tahunan — luas, SPH, jumlah pokok, total min/max/estimasi, karung |
| 12 | `app/Services/FertilizationRealizationService.php` | Ringkasan realisasi — status Tahap 1 (selesai/sebagian/batal), interval 60 hari, filter tahun_program/rekomendasi_rbs_id |
| 13 | `app/Services/CurrentApplicationCalculator.php` | Tahap aktif saat ini — TAHAP_1_SIAP/SEBAGIAN, MENUNGGU_INTERVAL/KELAYAKAN, TAHAP_2_SIAP, SELESAI_TAHUNAN |
| 14 | `app/Services/SupportingFertilizerSanitizer.php` | Sanitasi pupuk pendukung — sembunyikan angka tanpa metadata lengkap |
| 15 | `app/Services/RecommendationOperationalRefreshService.php` | (v2.6) Refresh operasional rekomendasi setelah realisasi berubah — update tahap, sisa, jadwal, fingerprint tanpa re-diagnosis |
| 16 | `app/Services/RealisasiEligibilityService.php` | (v2.7) Validasi kelayakan pencatatan realisasi — server menentukan tahap, rencana, tahun program; form ditolak jika tidak layak |

---

## 8. Enums

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `app/Enums/PlantPhase.php` | Enum fase tanaman TBM/TM dengan label: "Tanaman Belum Menghasilkan" / "Tanaman Menghasilkan" |
| 2 | `app/Enums/PlantConditionStatus.php` | Status kondisi tanaman — NORMAL_VISUAL, DEFISIENSI_RINGAN, DEFISIENSI, GEJALA_BERAT, PERLU_VERIFIKASI |
| 3 | `app/Enums/ApplicationFeasibilityStatus.php` | Status kelayakan waktu aplikasi — LAYAK, TUNDA_HUJAN_RENDAH/TINGGI, TUNDA_INTERVAL, TUNDA_DRAINASE, TERLAMBAT |

---

## 9. Middleware

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `app/Http/Middleware/AdminAuthenticated.php` | Guard sesi admin — redirect user yang belum login ke halaman login |

---

## 10. Form Requests

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `app/Http/Requests/StoreBlokLahanRequest.php` | Validasi pembuatan blok lahan — validasi GeoJSON, cek konsistensi fase-umur |
| 2 | `app/Http/Requests/UpdateBlokLahanRequest.php` | Validasi update blok lahan — aturan sama dengan Store |
| 3 | `app/Http/Requests/StoreKondisiLahanRequest.php` | Validasi pembuatan kondisi lahan — logika tanggal (pemupukan <= observasi, observasi >= tahun_tanam) |
| 4 | `app/Http/Requests/UpdateKondisiLahanRequest.php` | Validasi update kondisi lahan — aturan sama dengan Store |
| 5 | `app/Http/Requests/StoreRealisasiPemupukanRequest.php` | (v2.6) Validasi pencatatan realisasi — cek tahap, interval 60 hari, over-plan konfirmasi, override kebutuhan tahunan |
| 6 | `app/Http/Requests/UpdateRealisasiPemupukanRequest.php` | (v2.6) Validasi update realisasi — cek over-plan, override batas tahunan dengan konteks existing |

---

## 11. Providers

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `app/Providers/AppServiceProvider.php` | View composer untuk layouts.app — share notifBlokDarurat (blok berstatus "Darurat"), data admin |

---

## 12. Console Commands

| No | File | Command | Deskripsi |
|----|------|---------|-----------|
| 1 | `app/Console/Commands/AuditPahanV2.php` | `sawit:audit-pahan-v2` | Audit konsistensi data: blok tanpa fase, rule tanpa sumber, rekomendasi tanpa versi |
| 2 | `app/Console/Commands/MigratePahanV2.php` | `sawit:migrate-pahan-v2` | Migrasi data lama: auto-set fase TBM/TM dari umur, label rekomendasi lama sebagai legacy-v1 |
| 3 | `app/Console/Commands/FinalizePahanV2_2.php` | `sawit:finalize-pahan-v2-2` | Audit v2.2: konflik fase, umur snapshot hilang, dosis belum tervalidasi |
| 4 | `app/Console/Commands/MaintenanceClearCache.php` | `sawit:clear-cache` | Bersihkan semua cache (config, app, route, view) via terminal |
| 5 | `app/Console/Commands/FinalizePahanV2_3.php` | `sawit:finalize-pahan-v2-3` | Audit v2.3: annual snapshot, jadwal, sanitizer |
| 6 | `app/Console/Commands/FinalizePahanV2_4.php` | `sawit:finalize-pahan-v2-4` | Audit v2.4: fase historis, jadwal kosong |
| 7 | `app/Console/Commands/FinalizePahanV2_5.php` | `sawit:finalize-pahan-v2-5` | Audit v2.5: snapshot luas/SPH, tahap aktif, fingerprint |
| 8 | `app/Console/Commands/FinalizePahanV2_6.php` | `sawit:finalize-pahan-v2-6` | (v2.6) Audit menyeluruh: schema v2.6, versi mesin, snapshot, tahap, realisasi, jadwal, pupuk pendukung, status legacy, fingerprint |
| 9 | `app/Console/Commands/FinalizePahanV2_7.php` | `sawit:finalize-pahan-v2-7` | (v2.7) Audit penutupan celah: eligibility, manipulasi request, status SELESAI, program pemupukan, histori operasional, fingerprint realisasi, status legacy, migration |

---

## 13. Database — Migrations

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `0001_01_01_000000_create_users_table.php` | Tabel users bawaan Laravel |
| 2 | `0001_01_01_000001_create_cache_table.php` | Tabel cache |
| 3 | `0001_01_01_000002_create_jobs_table.php` | Tabel jobs |
| 4 | `2026_05_20_205515_create_admins_table.php` | Tabel admins |
| 5 | `2026_05_20_205515_create_blok_lahans_table.php` | Tabel blok_lahans |
| 6 | `2026_05_20_205516_create_kriteria_lahans_table.php` | Tabel kriteria_lahans (legacy) |
| 7 | `2026_05_20_205516_create_rule_bases_table.php` | Tabel rule_bases (legacy) |
| 8 | `2026_05_20_205517_create_rekomendasi_spks_table.php` | Tabel rekomendasi_spks (legacy) |
| 9 | `2026_05_20_231656_add_nama_pemilik_to_blok_lahans_table.php` | Tambah kolom nama_pemilik |
| 10 | `2026_06_04_000000_add_panen_fields_to_blok_lahans_table.php` | Tambah field panen |
| 11 | `2026_06_04_000001_modify_jenis_tanah_column_in_kriteria_lahans_table.php` | Modifikasi kolom jenis_tanah |
| 12 | `2026_06_04_100000_create_kondisi_lahans_table.php` | Tabel kondisi_lahans (observasi) |
| 13 | `2026_06_04_100001_create_rule_bases_lanjutan_table.php` | Tabel rule_bases_lanjutan (RBS lanjutan) |
| 14 | `2026_06_04_100002_create_rekomendasi_rbs_table.php` | Tabel rekomendasi_rbs |
| 15 | `2026_06_04_100003_add_missing_columns_to_rule_bases_lanjutan_table.php` | Kolom tambahan untuk rules |
| 16 | `2026_06_04_200000_add_dosis_columns_to_rekomendasi_rbs_table.php` | Kolom dosis di rekomendasi |
| 17 | `2026_06_07_000000_drop_panen_fields_from_blok_lahans_table.php` | Hapus field panen yang tidak dipakai |
| 18 | `2026_06_07_100000_create_anggotas_table.php` | Tabel anggotas (anggota kelompok tani) |
| 19 | `2026_06_07_100001_add_anggota_id_to_blok_lahans_table.php` | Relasi blok ke anggota |
| 20 | `2026_06_07_200000_merge_kriteria_into_blok_lahans_table.php` | Gabung kolom kriteria ke blok_lahans |
| 21 | `2026_06_07_200001_add_catatan_dosis_to_rekomendasi_rbs_table.php` | Tambah catatan_dosis |
| 22 | `2026_06_12_213121_add_intermediate_fields_to_rule_bases_lanjutan_table.php` | Field intermediate untuk rule chaining |
| 23 | `2026_06_12_213130_add_tanggal_pemupukan_terakhir_to_kondisi_lahans_table.php` | Tambah tanggal_pemupukan_terakhir |
| 24 | `2026_06_12_213138_create_realisasi_pemupukans_table.php` | Tabel realisasi_pemupukans (record pemupukan) |
| 25 | `2026_06_14_000001_add_histori_fields_to_rekomendasi_rbs_table.php` | Field histori/versioning |
| 26 | `2026_06_14_000002_add_curah_hujan_gulma_to_rule_bases_lanjutan_table.php` | Tambah curah hujan & gulma ke rules |
| 27 | `2026_07_13_000000_cleanup_unused_tables.php` | Hapus tabel legacy yang tidak dipakai |
| 28 | `2026_07_13_100000_add_tema_to_admins_table.php` | Tambah preferensi tema ke admins |
| 29 | `2026_07_20_000000_add_pahan_v2_2_columns_to_rekomendasi_rbs_table.php` | Kolom Pahan v2.2 |
| 30 | `2026_07_20_000001_add_pahan_v2_fields_to_blok_lahans_table.php` | Field Pahan v2 untuk blok |
| 31 | `2026_07_20_000002_add_pahan_v2_fields_to_kondisi_lahans_table.php` | Field Pahan v2 untuk kondisi |
| 32 | `2026_07_20_000003_add_pahan_v2_provenance_to_rule_bases_lanjutan_table.php` | Provenance metadata untuk rules |
| 33 | `2026_07_20_000004_add_pahan_v2_fields_to_rekomendasi_rbs_table.php` | Field Pahan v2 untuk rekomendasi |
| 34 | `2026_07_20_000010_add_analysis_fingerprint_to_rekomendasi_rbs_table.php` | SHA-256 fingerprint untuk deduplikasi |
| 35 | `2026_07_20_100000_finalize_pahan_v2_add_fields.php` | Finalisasi field tambahan v2 |

---

## 14. Database — Seeders

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `database/seeders/DatabaseSeeder.php` | Master seeder — memanggil AdminSeeder, RuleBaseLanjutanSeeder, PahanRuleBaseV2Seeder |
| 2 | `database/seeders/AdminSeeder.php` | Seed admin awal dari env (INITIAL_ADMIN_USERNAME/PASSWORD), opsional akun tester (dev only) |
| 3 | `database/seeders/RuleBaseLanjutanSeeder.php` | Seed rule-rule dasar RBS (gejala visual, kondisi lingkungan) |
| 4 | `database/seeders/PahanRuleBaseV2Seeder.php` | Seed provenance metadata untuk Pahan-v2 (sumber, penulis, tahun, tabel) |
| 5 | `database/seeders/RuleCurahHujanGulmaSeeder.php` | Seed 3 rule: curah hujan sangat tinggi/rendah (tunda), gulma dominan (segera) |

---

## 15. Views (Blade Templates)

### 15.1 Layout

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/layouts/app.blade.php` | Layout utama — sidebar navigasi, top bar, notifikasi (darurat), dark mode, Leaflet/Alpine includes |

### 15.2 Auth

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/auth/login.blade.php` | Halaman login |

### 15.3 Dashboard

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/dashboard/index.blade.php` | Peta WebGIS + kartu statistik + daftar blok perlu perhatian |

### 15.4 Anggota

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/anggota/index.blade.php` | Daftar anggota dengan jumlah blok |
| 2 | `resources/views/anggota/create.blade.php` | Form tambah anggota |
| 3 | `resources/views/anggota/edit.blade.php` | Form edit anggota |

### 15.5 Blok Lahan

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/blok_lahan/index.blade.php` | Daftar blok grouped by anggota, filter anggota/status |
| 2 | `resources/views/blok_lahan/create.blade.php` | Form + peta Leaflet untuk gambar polygon, upload SHP/GeoJSON, deteksi overlap |
| 3 | `resources/views/blok_lahan/edit.blade.php` | Edit blok + polygon existing di peta |
| 4 | `resources/views/blok_lahan/show.blade.php` | Detail blok — info, kondisi terbaru, rekomendasi |

### 15.6 Kondisi Lahan

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/kondisi_lahan/index.blade.php` | Daftar kondisi grouped by anggota |
| 2 | `resources/views/kondisi_lahan/create.blade.php` | Form observasi — filter cascading, auto-cuaca dari Open-Meteo |
| 3 | `resources/views/kondisi_lahan/edit.blade.php` | Edit data observasi |

### 15.7 RBS (Analisis)

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/rbs/index.blade.php` | Daftar blok + status analisis, tombol analisis batch, statistik |
| 2 | `resources/views/rbs/detail.blade.php` | Detail hasil RBS — status, rules terpicu, jadwal, dosis, confidence, histori |
| 3 | `resources/views/rbs/partials/_hasil_rbs.blade.php` | Partial: tampilan hasil RBS yang reusable |

### 15.8 Rule Base

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/rule_base/index.blade.php` | Daftar semua rule dengan status/prioritas |
| 2 | `resources/views/rule_base/create.blade.php` | Form tambah rule (kondisi IF + output THEN) |
| 3 | `resources/views/rule_base/edit.blade.php` | Form edit rule |
| 4 | `resources/views/rule_base/info.blade.php` | Halaman informasi penjelasan rule-based system |

### 15.9 Laporan

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/laporan/index.blade.php` | Rekap laporan — grouped by anggota, grand total Urea/KCl, filter |
| 2 | `resources/views/laporan/show.blade.php` | Detail laporan satu rekomendasi |
| 3 | `resources/views/laporan/pdf.blade.php` | Template PDF (DomPDF) untuk export laporan |

### 15.10 Settings

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/settings/index.blade.php` | Pengaturan admin — ganti password, pilih tema |

### 15.11 Components

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/components/custom-select.blade.php` | Komponen dropdown select reusable |
| 2 | `resources/views/components/searchable-select.blade.php` | Komponen dropdown dengan pencarian |
| 3 | `resources/views/components/filter-searchable.blade.php` | Filter dengan fungsi pencarian |
| 4 | `resources/views/components/status-badge.blade.php` | Komponen badge status berwarna |

### 15.12 Vendor (Pagination)

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/views/vendor/pagination/tailwind.blade.php` | View pagination Tailwind |
| 2 | `resources/views/vendor/pagination/bootstrap-4.blade.php` | View pagination Bootstrap 4 |
| 3 | `resources/views/vendor/pagination/bootstrap-5.blade.php` | View pagination Bootstrap 5 |
| 4 | `resources/views/vendor/pagination/default.blade.php` | View pagination default |
| 5 | `resources/views/vendor/pagination/semantic-ui.blade.php` | View pagination Semantic UI |
| 6 | `resources/views/vendor/pagination/simple-bootstrap-4.blade.php` | View pagination simple Bootstrap 4 |
| 7 | `resources/views/vendor/pagination/simple-bootstrap-5.blade.php` | View pagination simple Bootstrap 5 |
| 8 | `resources/views/vendor/pagination/simple-default.blade.php` | View pagination simple default |
| 9 | `resources/views/vendor/pagination/simple-tailwind.blade.php` | View pagination simple Tailwind |

---

## 16. Resources — JavaScript

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/js/app.js` | Entry point — import bootstrap, theme, overlap-detector |
| 2 | `resources/js/bootstrap.js` | Setup Axios dengan header X-Requested-With |
| 3 | `resources/js/theme.js` | Manager tema dark/light/system — localStorage + cookie + OS preference listener |
| 4 | `resources/js/overlap-detector.js` | Deteksi overlap polygon menggunakan Turf.js — checkOverlap, validatePolygon, calculateArea |

---

## 17. Resources — CSS

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `resources/css/app.css` | Import Tailwind CSS + custom dark mode overrides komprehensif — cards, borders, text, tables, forms, map, popups, komponen custom, variabel tema |

---

## 18. Public Assets

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `public/index.php` | Entry point Laravel |
| 2 | `public/.htaccess` | URL rewriting Apache |
| 3 | `public/robots.txt` | Direktif search engine |
| 4 | `public/favicon.ico` | Favicon ICO |
| 5 | `public/favicon.png` | Favicon PNG |
| 6 | `public/apple-touch-icon.png` | Icon Apple Touch |
| 7 | `public/img/logo-96.png` | Logo aplikasi 96px |
| 8 | `public/img/logo-150.png` | Logo aplikasi 150px |
| 9 | `public/build/manifest.json` | Manifest build Vite |
| 10 | `public/build/assets/app-*.js` | Bundle JavaScript terkompilasi |
| 11 | `public/build/assets/app-*.css` | Bundle CSS terkompilasi |

---

## 19. Tests

### 19.1 Base

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `tests/TestCase.php` | Base test case class |

### 19.2 Feature Tests

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `tests/Feature/SecurityTest.php` | Test keamanan — route publik dihapus, auth enforced |

### 19.3 Unit Tests

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `tests/Unit/PlantPhaseResolverTest.php` | Test resolusi TBM/TM dan deteksi konflik |
| 2 | `tests/Unit/PlantAgeServiceTest.php` | Test kalkulasi umur pada tanggal referensi |
| 3 | `tests/Unit/PahanDoseReferenceTest.php` | Test lookup dosis berdasarkan fase/umur |
| 4 | `tests/Unit/FertilizationWindowServiceTest.php` | Test evaluasi waktu (curah hujan, interval, drainase) |
| 5 | `tests/Unit/ObservationCompletenessTest.php` | Test cek kecukupan data |
| 6 | `tests/Unit/RecommendationReliabilityTest.php` | Test skor keandalan |
| 7 | `tests/Unit/PlantPhaseValidationTest.php` | Test validasi fase tambahan |
| 8 | `tests/Unit/RainfallFallbackTest.php` | Test logika fallback kategori curah hujan |
| 9 | `tests/Unit/RuleEvaluationTest.php` | Test evaluasi rule (logika AND, intermediate flags) |

### 19.4 Sample Files

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `tests/sample_files/sample_blok_lahan.geojson` | Contoh GeoJSON polygon untuk testing |
| 2 | `tests/sample_files/sample_blok_lahan.zip` | Contoh ZIP SHP untuk testing upload |
| 3 | `tests/sample_files/generate_sample_shp.php` | Script generator shapefile sample |

---

## 20. Deploy

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `deploy/PANDUAN_DEPLOY_INFINITYFREE.md` | Panduan deploy ke hosting InfinityFree |
| 2 | `deploy/PANDUAN_DEPLOY_RUMAHWEB.md` | Panduan deploy ke hosting Rumahweb |
| 3 | `deploy/setup-route.php` | Script route sementara untuk setup database di shared hosting (jalankan migration+seed via URL) |
| 4 | `deploy/htaccess-root.txt` | Template .htaccess root untuk shared hosting |

---

## 21. Dokumentasi

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `docs/AUDIT_FINAL_PAHAN_V2.md` | Laporan audit final implementasi Pahan-v2 |
| 2 | `docs/AUDIT_PENYEMPURNAAN_FINAL.md` | Dokumen audit penyempurnaan |
| 3 | `docs/AUDIT_REVISI_PAHAN.md` | Audit revisi Pahan |
| 4 | `docs/MIGRASI_PAHAN_V2.md` | Dokumentasi migrasi v1 ke v2 |
| 5 | `docs/PENGUJIAN_PAHAN_V2.md` | Dokumentasi pengujian Pahan-v2 |
| 6 | `docs/REVISI_APLIKASI_PAHAN.md` | Catatan revisi aplikasi |
| 7 | `docs/LAPORAN_FINALISASI_PAHAN_V2.md` | Laporan finalisasi |
| 8 | `docs/TRACEABILITY_RULE_FINAL.md` | Matriks traceability rule final |
| 9 | `docs/TRACEABILITY_RULE_PAHAN.md` | Traceability rule Pahan |
| 10 | `docs/DFD_Level_0.drawio` | Data Flow Diagram Level 0 |
| 11 | `docs/DFD_Level_1.drawio` | Data Flow Diagram Level 1 |
| 12 | `docs/diagram/dfd-spk-sawit.drawio` | Diagram DFD sistem SPK |
| 13 | `docs/referensi/REFERENSI_TAMBAHAN.md` | Referensi tambahan |
| 14 | `docs/referensi/TEKS_SKRIPSI_TABEL_DOSIS.md` | Teks skripsi: tabel dosis |
| 15 | `docs/referensi/My Collection.bib` | File bibliografi |
| 16 | `docs/Proposal/Proposal Skripsi - Dicky Muhammad Yahya.docx` | Proposal skripsi (DOCX) |
| 17 | `docs/Proposal/Proposal Skripsi - Dicky Muhammad Yahya.pdf` | Proposal skripsi (PDF) |

---

## 22. Aset Gambar

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `gambar/Logo suluh tani.png` | Logo branding aplikasi |

---

## 23. Ringkasan Statistik

| Kategori | Jumlah |
|----------|--------|
| **PHP — Controllers** | 12 file |
| **PHP — Models** | 6 file |
| **PHP — Services** | 8 file |
| **PHP — Enums** | 3 file |
| **PHP — Middleware** | 1 file |
| **PHP — Form Requests** | 4 file |
| **PHP — Providers** | 1 file |
| **PHP — Console Commands** | 4 file |
| **PHP — Config** | 8 file |
| **PHP — Routes** | 2 file |
| **PHP — Migrations** | 35 file |
| **PHP — Seeders** | 5 file |
| **PHP — Tests** | 10 file |
| **Blade Templates** | 28 file |
| **JavaScript** | 4 file |
| **CSS** | 1 file |
| **Dokumentasi** | 17 file |
| **Deploy** | 4 file |
| **Konfigurasi Root** | 12 file |
| | |
| **TOTAL FILE** | **~165 file** |

---

## Arsitektur Sistem (Ringkas)

```
Laravel 11 + Blade + Tailwind CSS 4 + Leaflet.js + Vite
│
├── app/
│   ├── Console/Commands/     → Artisan commands (migrasi, audit, clear-cache)
│   ├── Enums/                → Status enum (fase, kondisi, kelayakan)
│   ├── Http/
│   │   ├── Controllers/      → 12 controller (CRUD, analisis, API, export)
│   │   ├── Middleware/       → Auth guard admin
│   │   └── Requests/        → Form validation (blok, kondisi)
│   ├── Models/               → 6 Eloquent model
│   ├── Providers/            → View composer (notifikasi darurat)
│   └── Services/             → 8 service (RBS engine, dosis, window, reliability)
│
├── config/fertilization.php  → Tabel dosis Pahan 2013 + parameter sistem
├── database/migrations/      → 35 migration (evolusi schema)
├── database/seeders/         → 5 seeder (admin, rules, provenance)
├── resources/
│   ├── views/                → 28 Blade template
│   ├── js/                   → 4 file JS (theme, overlap, axios)
│   └── css/                  → 1 file CSS (Tailwind + dark mode)
├── routes/web.php            → Semua route aplikasi
├── tests/                    → 10 test file (1 feature, 9 unit)
├── deploy/                   → Panduan + script deployment
└── docs/                     → 17 file dokumentasi
```

---

## Alur Forward Chaining (Ringkas)

```
Input: BlokLahan + KondisiLahan (observasi terbaru)
  │
  ├─ 1. Hitung umur tanaman pada tanggal observasi
  ├─ 2. Cek kecukupan data minimum
  ├─ 3. Evaluasi kelengkapan observasi
  ├─ 4. Evaluasi setiap rule aktif (AND logic)
  ├─ 5. Rule Chaining: intermediate flag → rule berikutnya
  ├─ 6. Tentukan status kondisi tanaman
  ├─ 7. Evaluasi kelayakan waktu (curah hujan, interval, drainase)
  ├─ 8. Hitung dosis dari tabel Pahan (rentang min-max, strategi midpoint)
  ├─ 9. Hitung total per blok (luas × SPH × dosis/pokok)
  ├─ 10. Hitung skor keandalan data (0-100)
  └─ 11. Simpan dengan fingerprint SHA-256 (deduplikasi histori)
  │
Output: RekomendasiRbs (status, dosis, jadwal, rules terpicu, skor)
```

---

> **Catatan:** File ini di-generate secara otomatis sebagai referensi index seluruh source code aplikasi SawitGIS.
