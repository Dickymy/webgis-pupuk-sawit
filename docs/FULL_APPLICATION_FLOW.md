# FULL APPLICATION FLOW — SawitGIS
> Dokumentasi lengkap alur aplikasi, data, logika bisnis, dan arsitektur sistem.
> Versi Mesin Rekomendasi: **pahan-v2.9** | Laravel 11 | PHP 8.2
> Diperbarui: 2026-08-11

---

## DAFTAR ISI

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Stack Teknologi & Dependensi](#2-stack-teknologi--dependensi)
3. [Struktur Database — Semua Tabel & Kolom](#3-struktur-database--semua-tabel--kolom)
4. [Relasi Antar Entitas (ERD Tekstual)](#4-relasi-antar-entitas-erd-tekstual)
5. [Alur Utama Aplikasi (Full Workflow)](#5-alur-utama-aplikasi-full-workflow)
6. [Alur Detail: Observasi & Auto-Analisis RBS](#6-alur-detail-observasi--auto-analisis-rbs)
7. [Mesin RBS — Forward Chaining (Detail Lengkap)](#7-mesin-rbs--forward-chaining-detail-lengkap)
8. [Logika Dosis — Tabel Pahan 2013](#8-logika-dosis--tabel-pahan-2013)
9. [Kelayakan Waktu Pemupukan (FertilizationWindowService)](#9-kelayakan-waktu-pemupukan-fertilizationwindowservice)
10. [Sistem Tahap Aktif (CurrentApplicationCalculator)](#10-sistem-tahap-aktif-currentapplicationcalculator)
11. [Program Pemupukan — Siklus Hidup](#11-program-pemupukan--siklus-hidup)
12. [Pencatatan Realisasi Pemupukan (Triple Anti-Duplikat)](#12-pencatatan-realisasi-pemupukan-triple-anti-duplikat)
13. [Fingerprint & Histori Rekomendasi](#13-fingerprint--histori-rekomendasi)
14. [Sistem Notifikasi](#14-sistem-notifikasi)
15. [Rule Base — Struktur & Jenis Rule](#15-rule-base--struktur--jenis-rule)
16. [Semua Route & Endpoint API](#16-semua-route--endpoint-api)
17. [Services — Tanggung Jawab & Interaksi](#17-services--tanggung-jawab--interaksi)
18. [Dashboard WebGIS — Logika Peta](#18-dashboard-webgis--logika-peta)
19. [Laporan & Ekspor PDF](#19-laporan--ekspor-pdf)
20. [Keamanan & Validasi](#20-keamanan--validasi)
21. [Seeders & Data Awal](#21-seeders--data-awal)
22. [Artisan Commands Operasional](#22-artisan-commands-operasional)
23. [Keputusan Desain Akademik (Penting!)](#23-keputusan-desain-akademik-penting)

---

## 1. GAMBARAN UMUM SISTEM

**SawitGIS** adalah WebGIS berbasis Laravel untuk kelompok tani kelapa sawit.
Fungsi utamanya adalah:

1. **Pencatatan** blok lahan milik anggota kelompok tani (dengan peta polygon GeoJSON)
2. **Observasi lapangan** kondisi tanaman (warna daun, curah hujan, drainase, hama, gulma)
3. **Analisis otomatis** menggunakan Rule-Based System (RBS) metode *Forward Chaining*
4. **Rekomendasi pemupukan** berbasis dosis Pahan 2013, dikombinasikan diagnosis visual
5. **Pencatatan realisasi** pelaksanaan pemupukan aktual per tahap (2 tahap/tahun)
6. **Laporan PDF** per rekomendasi
7. **WebGIS** peta interaktif status semua blok

**Batasan penting:** Sistem ini adalah *pendukung keputusan*, bukan pengganti analisis laboratorium atau ahli agronomi.


---

## 2. STACK TEKNOLOGI & DEPENDENSI

| Komponen | Teknologi | Keterangan |
|----------|-----------|------------|
| Backend | PHP 8.2, Laravel 11 | Framework utama |
| Frontend | Blade + Tailwind CSS 4, Alpine.js | UI responsif |
| Peta | Leaflet.js + Turf.js | WebGIS polygon interaktif |
| PDF | `barryvdh/laravel-dompdf` | Ekspor laporan |
| Shapefile | `gasparesganga/php-shapefile` | Upload SHP → GeoJSON |
| Database | MySQL (produksi) / SQLite (testing) | Data utama |
| Testing | PHPUnit 10 | 50+ Feature + 14 Unit test |
| Build | Vite + Laravel Vite Plugin | Asset bundling |
| Cuaca | Open-Meteo API | Data curah hujan otomatis |
| Auth | Laravel Guard `admin` | Single guard admin |
| Notifikasi | Laravel Database Notifications | Tabel `notifications` |

### Konfigurasi Kritis

- `config/fertilization.php` — Tabel dosis Pahan 2013, parameter window, interval, versi mesin
- `config/observation.php` — Opsi warna daun, kondisi normal, nilai yang di-NULL-kan
- `.env` — `DOSE_STRATEGY` (midpoint/minimum/maximum), `INITIAL_ADMIN_*`, `DB_*`


---

## 3. STRUKTUR DATABASE — SEMUA TABEL & KOLOM

### Tabel: `admins`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | Auto increment |
| `username` | string unique | Username login |
| `password` | string | Bcrypt hash |
| `nama_lengkap` | string | Nama tampil |
| `tema` | string | `light` / `dark` / `system` |
| `created_at`, `updated_at` | timestamp | — |

---

### Tabel: `anggotas`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | — |
| `nama` | string | Nama lengkap anggota |
| `no_hp` | string nullable | Nomor HP |
| `alamat` | text nullable | Alamat |
| `created_at`, `updated_at` | timestamp | — |

---

### Tabel: `blok_lahans`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | — |
| `anggota_id` | FK → anggotas | Pemilik blok |
| `nama_blok` | string | Nama/kode blok |
| `luas_ha` | double | Luas hektar |
| `sph` | integer | Standar Pokok per Hektar |
| `koordinat_geojson` | text nullable | Polygon GeoJSON dari peta/upload |
| `tahun_tanam` | integer | Tahun tanam (untuk hitung umur) |
| `topografi` | string nullable | `Datar - Landai (< 12°)` / `Bergelombang - Miring (12° - 23°)` / `Curam - Berbukit (> 23°)` |
| `fase_tanaman` | string nullable | `TBM` / `TM` (diisi manual jika umur = 3 tahun) |
| `jumlah_pohon` | integer nullable | Override manual jumlah pokok (jika null → luas × sph) |
| `created_at`, `updated_at` | timestamp | — |

**Accessor (tidak tersimpan di DB):**
- `jumlah_pokok_aktual` → `jumlah_pohon` jika ada, else `luas_ha × sph`
- `umur_tanaman` → `now()->year - tahun_tanam`
- `kategori_umur` → Belum Menghasilkan / Remaja / Menghasilkan Muda / Menghasilkan Tua / Tua Renta
- `fase_label` → label panjang dari PlantPhase enum
- `nama_pemilik` → dari relasi anggota


---

### Tabel: `kondisi_lahans`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | — |
| `blok_lahan_id` | FK → blok_lahans | Blok yang diobservasi |
| `tanggal_observasi` | date | Tanggal pemeriksaan lapangan |
| `tanggal_pemupukan_terakhir` | date nullable | Otomatis dari realisasi aktif terakhir jika ada |
| `kelembaban_tanah` | string nullable | `Sangat Kering` / `Kering` / `Sedang` / `Lembab` / `Sangat Lembab` |
| `curah_hujan_kategori` | string nullable | `Sangat Rendah` / `Rendah` / `Normal` / `Tinggi` / `Sangat Tinggi` |
| `curah_hujan_mm_bulanan` | decimal(8,1) nullable | Nilai numerik curah hujan mm/bulan (lebih akurat) |
| `periode_curah_hujan` | string nullable | Keterangan periode data curah hujan |
| `sumber_curah_hujan` | string nullable | `Open-Meteo API` / `Manual` / keterangan lain |
| `musim_saat_ini` | string nullable | `Musim Hujan` / `Musim Kemarau` / `Peralihan` |
| `warna_daun` | string nullable | Lihat nilai valid di bawah |
| `kondisi_drainase` | string nullable | `Baik` / `Cukup Baik` / `Buruk — Tergenang` |
| `ada_gulma_dominan` | boolean | Default: false |
| `ada_serangan_hama` | boolean | Default: false |
| `catatan_observasi` | text nullable | Catatan bebas petugas lapangan |
| `foto_observasi_path` | string nullable | Path file foto di storage |
| `status_verifikasi_gejala` | string nullable | `terverifikasi` / `perlu_konfirmasi` |
| `created_at`, `updated_at` | timestamp | — |

**Nilai valid `warna_daun`:**
- `Hijau Normal` → kondisi normal, tidak ada gejala
- `Daun Bawah Menguning` → indikasi defisiensi Nitrogen
- `Bercak Kuning/Transparan pada Daun Tua` → indikasi defisiensi Kalium
- `Tepi Daun Tua Menguning pada Bagian Terbuka` → indikasi defisiensi Magnesium
- `Daun Muda Berbentuk Kait atau Memendek` → indikasi defisiensi Boron

---

### Tabel: `rule_bases_lanjutan`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | — |
| `kode_rule` | string nullable | Kode unik, contoh: `VIS-N-01`, `WAKTU-HUJAN-RENDAH` |
| `jenis_rule` | string | `DIAGNOSIS_VISUAL` / `PEMBATAS_APLIKASI` / `PENENTU_DOSIS` / `PENENTU_METODE` |
| `tahap_eksekusi` | integer | 1 (diagnosis), 2 (penentuan dosis), 3 (penyesuaian/tunda) |
| `kondisi_warna_daun` | string nullable | Kondisi IF: warna daun |
| `kondisi_topografi` | string nullable | Kondisi IF: topografi blok |
| `kondisi_curah_hujan_min_mm` | decimal nullable | Batas bawah curah hujan mm/bulan |
| `kondisi_curah_hujan_max_mm` | decimal nullable | Batas atas curah hujan mm/bulan |
| `kondisi_kategori_umur` | string nullable | Kategori umur IF |
| `kondisi_kelembaban` | string nullable | Kelembaban tanah IF |
| `kondisi_drainase` | string nullable | Kondisi drainase IF |
| `ada_gulma_dominan` | boolean nullable | NULL = abaikan |
| `ada_serangan_hama` | boolean nullable | NULL = abaikan |
| `kondisi_umur_tahun` | integer nullable | Umur persis dalam tahun IF |
| `rekomendasi_dosis_urea` | decimal nullable | Override dosis Urea (kg/pokok) |
| `rekomendasi_dosis_kcl` | decimal nullable | Override dosis KCl (kg/pokok) |
| `fakta_yang_dihasilkan` | JSON nullable | Fakta baru ke Working Memory (Forward Chaining) |
| `prasyarat_fakta` | JSON nullable | Fakta yang harus ada di Working Memory dulu |
| `indikasi_masalah` | string nullable | Output THEN: deskripsi indikasi |
| `jenis_pupuk_utama` | string nullable | Output THEN: pupuk yang diindikasikan |
| `saran_tindakan` | text nullable | Output THEN: saran untuk petugas |
| `status_kebutuhan` | string nullable | `Normal` / `Segera` / `Darurat` / `Tunda` |
| `tingkat_keparahan` | string nullable | `NORMAL` / `RINGAN` / `SEDANG` / `BERAT` |
| `prioritas` | integer | Urutan eksekusi (1 = tertinggi) |
| `aktif` | boolean | Rule hanya dieksekusi jika aktif |
| `sumber_judul` | string nullable | Judul referensi literatur |
| `sumber_penulis` | string nullable | Penulis referensi |
| `sumber_tahun` | integer nullable | Tahun publikasi |
| `sumber_halaman` | string nullable | Halaman/tabel referensi |
| `sumber_tabel` | string nullable | Nomor tabel referensi |
| `tingkat_bukti` | string nullable | `JURNAL` / `BUKU` / dll |
| `is_system_rule` | boolean | True = rule bawaan seeder, tidak dihapus |
| `status_validasi` | string nullable | `TERVERIFIKASI_SUMBER` / `DRAFT` / dll |
| `catatan_validasi` | text nullable | Catatan validasi akademik |
| `created_at`, `updated_at` | timestamp | — |


---

### Tabel: `rekomendasi_rbs`
> Menyimpan hasil analisis RBS. Hanya 1 record `is_latest = true` per blok.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | — |
| `blok_lahan_id` | FK → blok_lahans | Blok yang dianalisis |
| `program_pemupukan_id` | FK nullable → program_pemupukans | Program terkait |
| `kondisi_lahan_id` | FK → kondisi_lahans | Kondisi yang digunakan |
| `admin_id` | FK → admins | Admin yang menjalankan analisis |
| `tanggal_analisis` | date | Tanggal analisis dijalankan |
| `is_latest` | boolean | True = rekomendasi terbaru aktif |
| `nomor_analisis` | integer | Nomor urut analisis per blok |
| `analysis_fingerprint` | string nullable | SHA-256 hash data penting |
| `versi_mesin_rekomendasi` | string | Versi engine, contoh: `pahan-v2.9` |
| `rules_terpicu` | JSON | Array rule yang terpicu beserta detail |
| `masalah_teridentifikasi` | JSON | Array deskripsi masalah |
| `rekomendasi_pupuk` | JSON | Array rekomendasi pupuk |
| `saran_tindakan_utama` | text | Saran ringkas untuk petugas |
| `status_kebutuhan_dominan` | string | LEGACY: `Normal`/`Segera`/`Darurat`/`Tunda` |
| `jumlah_rule_terpicu` | integer | Jumlah rule yang cocok |
| `dosis_urea` | double | Dosis per pokok (lama, untuk kompatibilitas) |
| `dosis_kcl` | double | Dosis per pokok (lama, untuk kompatibilitas) |
| `total_urea` | double | Total Urea aplikasi saat ini |
| `total_kcl` | double | Total KCl aplikasi saat ini |
| `catatan_dosis` | text | Penjelasan operasional dosis |
| `jadwal_pemupukan` | JSON | Jadwal operasional (dari FertilizationScheduleService) |
| `validitas_rekomendasi` | string | `Terverifikasi` / `Cukup Kuat` / `Estimasi Visual` |
| `catatan_validitas` | text | Alasan validitas |
| `confidence_score` | integer 0-100 | Skor kelengkapan data pendukung |
| `confidence_label` | string | `Tinggi` / `Sedang` / `Rendah` |
| `catatan_confidence` | text | Detail skor keandalan |
| `data_cukup` | boolean | Apakah data cukup untuk diagnosis |
| `data_kurang` | JSON | Field observasi yang kurang |
| `notifikasi_data` | text | Pesan notifikasi kelengkapan data |
| `fase_tanaman_snapshot` | string | Fase tanaman saat observasi (`TBM`/`TM`) |
| `umur_tanaman_snapshot` | integer | Umur tanaman saat observasi (tahun) |
| `urea_min_kg_per_pokok_tahun` | double | Dosis minimum Urea dari tabel Pahan |
| `urea_max_kg_per_pokok_tahun` | double | Dosis maksimum Urea dari tabel Pahan |
| `urea_estimasi_kg_per_pokok_tahun` | double | Dosis estimasi Urea (midpoint) |
| `kcl_min_kg_per_pokok_tahun` | double | Dosis minimum KCl dari tabel Pahan |
| `kcl_max_kg_per_pokok_tahun` | double | Dosis maksimum KCl dari tabel Pahan |
| `kcl_estimasi_kg_per_pokok_tahun` | double | Dosis estimasi KCl (midpoint) |
| `strategi_estimasi_dosis` | string | `midpoint` / `minimum` / `maximum` |
| `jumlah_pokok_snapshot` | integer | Jumlah pokok saat analisis |
| `dasar_perhitungan_json` | JSON | Transparansi dasar kalkulasi |
| `peringatan_json` | JSON | Array peringatan dari engine |
| `kelengkapan_data_score` | integer | Skor keandalan 0-100 |
| `kategori_keandalan` | string | `Perlu Dilengkapi` / `Cukup Lengkap` / `Lengkap` |
| `rincian_skor_json` | JSON | Detail bobot skor |
| `status_kondisi_tanaman` | string | Dari enum `PlantConditionStatus` |
| `status_kelayakan_aplikasi` | string | Dari enum `ApplicationFeasibilityStatus` |
| `alasan_kelayakan` | text | Penjelasan status kelayakan |
| `metode_perhitungan_umur` | string | Metode kalkulasi umur historis |
| `tanggal_referensi_umur` | date | Tanggal acuan perhitungan umur |
| `urea_total_min_tahunan` | double | Kebutuhan Urea minimum × jumlah pokok |
| `urea_total_max_tahunan` | double | Kebutuhan Urea maksimum × jumlah pokok |
| `urea_total_estimasi_tahunan` | double | Kebutuhan Urea estimasi × jumlah pokok |
| `kcl_total_min_tahunan` | double | Kebutuhan KCl minimum × jumlah pokok |
| `kcl_total_max_tahunan` | double | Kebutuhan KCl maksimum × jumlah pokok |
| `kcl_total_estimasi_tahunan` | double | Kebutuhan KCl estimasi × jumlah pokok |
| `urea_karung_estimasi_tahunan` | integer | Estimasi karung Urea (ceil ÷ 50 kg) |
| `kcl_karung_estimasi_tahunan` | integer | Estimasi karung KCl (ceil ÷ 50 kg) |
| `urea_aplikasi_saat_ini` | double | Urea untuk tahap aktif saat ini |
| `kcl_aplikasi_saat_ini` | double | KCl untuk tahap aktif saat ini |
| `luas_ha_snapshot` | double | Luas blok saat analisis |
| `sph_snapshot` | integer | SPH blok saat analisis |
| `active_stage` | integer | Tahap aktif: 0/1/2 |
| `status_stage` | string | Status dari `CurrentApplicationCalculator` |
| `urea_sisa_tahunan` | double | Sisa Urea tahunan setelah realisasi |
| `kcl_sisa_tahunan` | double | Sisa KCl tahunan setelah realisasi |
| `tanggal_minimum_tahap_berikutnya` | date nullable | Kapan tahap berikutnya bisa dilakukan |
| `alasan_tahap` | text | Penjelasan status tahap |
| `created_at`, `updated_at` | timestamp | — |


---

### Tabel: `program_pemupukans`
> Identitas program pemupukan tahunan per blok. UNIQUE constraint: satu blok hanya boleh punya satu program AKTIF per tahun.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | — |
| `uuid` | string unique | UUID v4 untuk identifikasi eksternal |
| `blok_lahan_id` | FK → blok_lahans | Blok terkait |
| `tahun_program` | integer | Tahun program (contoh: 2026) |
| `rekomendasi_awal_id` | FK nullable → rekomendasi_rbs | Rekomendasi pertama yang membuat program |
| `status_program` | string | `AKTIF` / `SELESAI` / `DIBATALKAN` / `ARSIP` |
| `active_key` | string unique nullable | Format: `{blok_id}-{tahun}`, NULL jika bukan AKTIF |
| `created_at`, `updated_at` | timestamp | — |

**Catatan:** Kolom `active_key` dengan UNIQUE constraint adalah mekanisme yang menjamin hanya satu program AKTIF per blok per tahun. Saat program berubah status dari AKTIF, `active_key` di-NULL-kan (partial unique index).

---

### Tabel: `realisasi_pemupukans`
> Catatan pelaksanaan pemupukan aktual per tahap. Record BATAL tetap tersimpan untuk audit.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | — |
| `rekomendasi_rbs_id` | FK → rekomendasi_rbs | Rekomendasi yang dirujuk |
| `blok_lahan_id` | FK → blok_lahans | Blok terkait |
| `program_pemupukan_id` | FK → program_pemupukans | Program terkait |
| `admin_id` | FK → admins | Admin yang mencatat |
| `tahun_program` | integer | Tahun program (dari server, bukan browser) |
| `tahap` | integer | 1 atau 2 (dari server, bukan browser) |
| `tanggal_realisasi` | date | Tanggal aktual pemupukan |
| `urea_rencana_kg` | decimal(10,2) | Rencana Urea dari server |
| `kcl_rencana_kg` | decimal(10,2) | Rencana KCl dari server |
| `urea_realisasi_kg` | decimal(10,2) | Aktual Urea yang diaplikasikan |
| `kcl_realisasi_kg` | decimal(10,2) | Aktual KCl yang diaplikasikan |
| `status_realisasi` | string | `SELESAI` / `SEBAGIAN` / `BATAL` |
| `catatan_pelaksana` | text nullable | Catatan dari petugas lapangan |
| `confirmed_over_plan` | boolean | Konfirmasi sadar melebihi rencana |
| `override_annual_limit` | boolean | Override batas tahunan |
| `override_reason` | text nullable | Alasan override |
| `submission_token` | string unique nullable | UUID anti double-submit |
| `created_at`, `updated_at` | timestamp | — |

---

### Tabel: `rekomendasi_operasional_histories`
> Log audit setiap perubahan operasional. Tidak pernah dihapus.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | — |
| `rekomendasi_rbs_id` | FK → rekomendasi_rbs | Rekomendasi terkait |
| `program_pemupukan_id` | FK nullable | Program terkait |
| `source_realisasi_id` | FK nullable → realisasi_pemupukans | Realisasi pemicu |
| `event_type` | string | Jenis event (lihat di bawah) |
| `active_stage` | integer | Snapshot tahap aktif saat event |
| `status_stage` | string | Snapshot status stage saat event |
| `urea_aplikasi_saat_ini` | double | Snapshot Urea saat event |
| `kcl_aplikasi_saat_ini` | double | Snapshot KCl saat event |
| `urea_sisa_tahunan` | double | Snapshot sisa Urea saat event |
| `kcl_sisa_tahunan` | double | Snapshot sisa KCl saat event |
| `tanggal_minimum_tahap_berikutnya` | date nullable | Snapshot tanggal minimum tahap berikutnya |
| `alasan_tahap` | text | Snapshot alasan tahap saat event |
| `analysis_fingerprint` | string | Snapshot fingerprint saat event |
| `created_at` | timestamp | Waktu event |

**Event types:** `ANALISIS_AWAL` | `REALISASI_DIBUAT` | `REALISASI_DIPERBARUI` | `REALISASI_DIBATALKAN` | `TAHAP_1_SEBAGIAN` | `TAHAP_1_SELESAI` | `TAHAP_2_SIAP` | `PROGRAM_SELESAI`

---

### Tabel: `notifications` (Laravel default)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | uuid PK | — |
| `type` | string | Class notifikasi |
| `notifiable_type` | string | Biasanya `App\Models\Admin` |
| `notifiable_id` | bigint | ID admin |
| `data` | JSON | Payload notifikasi |
| `read_at` | timestamp nullable | Waktu dibaca |
| `created_at`, `updated_at` | timestamp | — |

---

### Tabel-tabel sistem Laravel (tidak dipakai langsung)
- `users` — Laravel default, tidak dipakai (aplikasi pakai `admins`)
- `cache` — Cache framework
- `jobs` — Queue jobs


---

## 4. RELASI ANTAR ENTITAS (ERD TEKSTUAL)

```
Admins
  └── has many → RealisasiPemupukan (admin_id)
  └── has many → RekomendasiRbs (admin_id)
  └── has many → Notifications (notifiable)

Anggota
  └── has many → BlokLahan (anggota_id)

BlokLahan
  ├── belongs to → Anggota
  ├── has many → KondisiLahan
  ├── has one   → kondisiTerbaru (latest by tanggal_observasi)
  ├── has many → RekomendasiRbs
  ├── has one   → rekomendasiRbsTerbaru (where is_latest=true)
  ├── has many → RealisasiPemupukan
  └── has many → ProgramPemupukan

KondisiLahan
  ├── belongs to → BlokLahan
  └── has many → RekomendasiRbs (kondisi_lahan_id)

RuleBaseLanjutan
  └── (tidak berelasi FK ke tabel lain — dipakai via Service)

RekomendasiRbs
  ├── belongs to → BlokLahan
  ├── belongs to → KondisiLahan
  ├── belongs to → ProgramPemupukan
  ├── belongs to → Admin
  ├── has many → RealisasiPemupukan
  └── has many → RekomendasiOperasionalHistory

ProgramPemupukan
  ├── belongs to → BlokLahan
  ├── belongs to → RekomendasiRbs (rekomendasi_awal_id)
  ├── has many → RekomendasiRbs (program_pemupukan_id)
  └── has many → RealisasiPemupukan

RealisasiPemupukan
  ├── belongs to → RekomendasiRbs
  ├── belongs to → BlokLahan
  ├── belongs to → ProgramPemupukan
  └── belongs to → Admin

RekomendasiOperasionalHistory
  ├── belongs to → RekomendasiRbs
  ├── belongs to → ProgramPemupukan (nullable)
  └── belongs to → RealisasiPemupukan (source_realisasi_id, nullable)
```

**Hirarki kepemilikan data:**
```
Anggota
  └── BlokLahan (N blok per anggota)
        └── KondisiLahan (N observasi per blok)
        └── ProgramPemupukan (1 AKTIF per tahun per blok)
              └── RekomendasiRbs (N analisis, 1 is_latest)
              └── RealisasiPemupukan (Tahap 1 + Tahap 2)
                    └── RekomendasiOperasionalHistory (audit log)
```


---

## 5. ALUR UTAMA APLIKASI (FULL WORKFLOW)

Berikut adalah alur kerja lengkap dari awal hingga akhir satu siklus pemupukan tahunan:

```
┌─────────────────────────────────────────────────────────────────┐
│  FASE 1: SETUP DATA MASTER                                      │
│                                                                 │
│  Admin Login                                                    │
│    │                                                            │
│    ▼                                                            │
│  Daftarkan Anggota Kelompok Tani                                │
│    │  (nama, no_hp, alamat)                                     │
│    ▼                                                            │
│  Daftarkan Blok Lahan per Anggota                               │
│    │  (nama_blok, luas_ha, sph, tahun_tanam, topografi,         │
│    │   fase_tanaman, koordinat polygon dari peta/upload SHP)    │
│    ▼                                                            │
│  Kelola Rule Base RBS (opsional — sudah ada rule sistem)        │
│    │  (tambah/edit/aktifkan/nonaktifkan rule)                   │
└─────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│  FASE 2: OBSERVASI LAPANGAN                                     │
│                                                                 │
│  Admin Catat Observasi (KondisiLahan) per Blok                  │
│    │  Field wajib: blok_lahan_id, tanggal_observasi             │
│    │  Field observasi: warna_daun, kondisi_drainase,            │
│    │    kelembaban_tanah, curah_hujan_mm_bulanan,               │
│    │    ada_gulma_dominan, ada_serangan_hama                    │
│    │  Curah hujan: bisa fetch otomatis dari Open-Meteo API      │
│    │  Foto opsional di-upload                                   │
│    │                                                            │
│    ▼  [OTOMATIS setelah simpan]                                 │
│  Sistem Jalankan Analisis RBS (RbsService::analisis())          │
└─────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│  FASE 3: ANALISIS RBS (OTOMATIS)                                │
│                                                                 │
│  PlantContextService → umur & fase tanaman saat observasi       │
│    │                                                            │
│    ▼                                                            │
│  ObservationCompletenessService → cek kelengkapan data          │
│    │                                                            │
│    ▼  [jika data cukup]                                         │
│  Forward Chaining 3 Tahap:                                      │
│    Tahap 1: Evaluasi rule DIAGNOSIS_VISUAL                      │
│    Tahap 2: Evaluasi rule PENENTU_DOSIS                         │
│    Tahap 3: Evaluasi rule PEMBATAS_APLIKASI / PENENTU_METODE    │
│    │                                                            │
│    ▼                                                            │
│  PahanDoseReferenceService → dosis dari tabel Pahan 2013        │
│    │                                                            │
│    ▼                                                            │
│  FertilizationWindowService → kelayakan waktu aplikasi          │
│    │                                                            │
│    ▼                                                            │
│  AnnualFertilizerSnapshotBuilder → kebutuhan tahunan total      │
│    │                                                            │
│    ▼                                                            │
│  CurrentApplicationCalculator → jumlah & tahap aktif           │
│    │                                                            │
│    ▼                                                            │
│  Simpan RekomendasiRbs (is_latest=true, fingerprint SHA-256)    │
│    │                                                            │
│    ▼                                                            │
│  Redirect ke halaman detail hasil analisis                      │
└─────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│  FASE 4: PENCATATAN REALISASI                                   │
│                                                                 │
│  Cek status: apakah TAHAP_1_SIAP atau TAHAP_2_SIAP?            │
│    │                                                            │
│    ├─ TIDAK → tampilkan alasan tunggu/tunda                     │
│    │                                                            │
│    └─ YA → Buka Form Realisasi                                  │
│              │                                                  │
│              ▼                                                  │
│           RealisasiEligibilityService::evaluate()               │
│           (server-side gate — tolak jika tidak layak)           │
│              │                                                  │
│              ▼                                                  │
│           Admin isi form: tanggal, urea_realisasi_kg,           │
│             kcl_realisasi_kg, status (SELESAI/SEBAGIAN)         │
│              │                                                  │
│              ▼  [3 lapis anti-duplikat]                         │
│           (1) Cek submission_token                              │
│           (2) Cek duplikasi semantik 5 menit                    │
│           (3) lockForUpdate dalam transaksi DB                  │
│              │                                                  │
│              ▼                                                  │
│           Simpan RealisasiPemupukan                             │
│           → RecommendationOperationalRefreshService (update RBS)│
│           → ProgramStatusService (cek apakah SELESAI)           │
│           → Kirim Notifikasi                                    │
└─────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│  FASE 5: PROGRAM SELESAI / LAPORAN                              │
│                                                                 │
│  Jika sisa tahunan = 0:                                         │
│    ProgramPemupukan.status_program → SELESAI                    │
│    active_key di-NULL-kan                                       │
│                                                                 │
│  Admin bisa cetak Laporan PDF per rekomendasi                   │
│    (LaporanController → Dompdf)                                 │
└─────────────────────────────────────────────────────────────────┘
```


---

## 6. ALUR DETAIL: OBSERVASI & AUTO-ANALISIS RBS

### 6.1 Alur Penyimpanan Observasi (`KondisiLahanController::store()`)

```
POST /kondisi-lahan
  │
  ├─ StoreKondisiLahanRequest::validate()
  │    Aturan validasi:
  │    - blok_lahan_id: required, exists
  │    - tanggal_observasi: required, date, tidak di masa depan
  │    - warna_daun: nullable, in:[nilai valid dari config]
  │    - curah_hujan_mm_bulanan: nullable, numeric, min:0, max:9999
  │    - ada_gulma_dominan, ada_serangan_hama: boolean
  │
  ├─ normalizeObservationData()
  │    - ada_gulma_dominan / ada_serangan_hama → cast boolean eksplisit
  │    - warna_daun: jika nilai ada di unmatched_leaf_values → di-NULL-kan,
  │      status_verifikasi_gejala = 'perlu_konfirmasi'
  │    - mode curah hujan 'perkiraan' → hapus nilai numerik
  │    - mode curah hujan 'tidak_tersedia' → hapus semua data curah hujan
  │    - tanggal_pemupukan_terakhir: OVERRIDE dari RealisasiPemupukan aktif
  │      terakhir (sumber kebenaran = realisasi resmi, bukan input manual)
  │
  ├─ validasiKonsistensi() → array warnings (tidak gagalkan simpan)
  │    Contoh: musim kemarau tapi kelembaban sangat lembab → warning
  │
  ├─ Simpan foto ke storage/public/observasi/ (jika ada)
  │
  ├─ KondisiLahan::create($validated)
  │
  └─ RbsService::analisis($blok) ← OTOMATIS
       │
       ├─ Sukses → redirect ke rbs.detail dengan flash 'success'
       └─ Gagal  → redirect ke rbs.detail dengan flash 'warning' (observasi tetap tersimpan)
```

### 6.2 Validasi Konsistensi Lintas-Field (Peringatan, Tidak Menggagalkan)

| Kombinasi | Peringatan |
|-----------|------------|
| Musim Kemarau + Kelembaban Lembab/Sangat Lembab | Mohon verifikasi data |
| Musim Hujan + Kelembaban Kering/Sangat Kering | Mohon verifikasi data |
| Drainase Tergenang + Curah Hujan Sangat Rendah | Situasi jarang, verifikasi |
| Drainase Tergenang + Musim Kemarau | Situasi tidak lazim, catat penjelasan |
| Curah Hujan Sangat Tinggi + Kelembaban Kering | Data kontradiktif |
| Curah Hujan Sangat Rendah + Kelembaban Sangat Lembab | Verifikasi sumber air lain |
| Musim Hujan + Curah Hujan Sangat Rendah | Pastikan data musim/curah hujan benar |
| Musim Kemarau + Curah Hujan Sangat Tinggi | Data tidak lazim |

### 6.3 Fetch Cuaca Otomatis (`CuacaController::fetch()`)

```
POST /api/cuaca/fetch
  Body: { blok_lahan_id, tanggal_observasi }
  │
  ├─ Hitung centroid dari koordinat_geojson blok
  ├─ Panggil Open-Meteo API (lat, lng, tanggal)
  ├─ Ekstrak curah_hujan_mm_bulanan dari respons
  └─ Return JSON: { curah_hujan_mm_bulanan, sumber: 'Open-Meteo API', periode }
```

Form observasi akan mengisi field curah hujan otomatis via AJAX jika admin klik tombol "Ambil Data Cuaca".


---

## 7. MESIN RBS — FORWARD CHAINING (DETAIL LENGKAP)

### 7.1 Titik Masuk: `RbsService::analisis(BlokLahan $blok)`

```
analisis(BlokLahan $blok)
  │
  ├─ [1] Ambil kondisiTerbaru (by tanggal_observasi DESC)
  │       → Exception jika belum ada kondisi
  │
  ├─ [2] PlantContextService::resolve($blok, $tanggalObservasi)
  │       → umur tanaman SAAT observasi (historis akurat)
  │       → fase tanaman SAAT observasi
  │       → Aturan fase:
  │           umur < 3  → fase = TBM (otomatis)
  │           umur > 3  → fase = TM (otomatis)
  │           umur = 3  → cek blok.fase_tanaman:
  │                         jika diisi → pakai itu
  │                         jika null  → needs_phase_verification = true
  │
  ├─ [3] ObservationCompletenessService::evaluate($kondisi)
  │       → can_run_diagnosis: warna_daun tidak null → true
  │       → missing_fields: daftar field yang kosong
  │
  ├─ [4] Cek verifikasi fase (umur = 3 & fase = null)
  │       → Return hasilPerluVerifikasiFase() — tidak ada dosis, tidak ada jadwal
  │
  ├─ [5] Cek kondisiCukupMinimal()
  │       → minimal 1 field terisi (warna_daun, kelembaban, drainase, dll)
  │       → Jika tidak → Return hasilDataTidakCukup()
  │
  ├─ [6] Jika !can_run_diagnosis
  │       → Return hasilDosisDasarTanpaDiagnosis()
  │         (kebutuhan tahunan tetap dihitung, jadwal kosong)
  │
  ├─ [7] Tentukan kategori umur dari nilai integer:
  │       < 3  → 'Belum Menghasilkan'
  │       3–8  → 'Remaja'
  │       9–14 → 'Menghasilkan Muda'
  │       15–25→ 'Menghasilkan Tua'
  │       > 25 → 'Tua Renta'
  │
  ├─ [8] Ambil semua rule aktif, urutkan by prioritas ASC
  │
  ├─ [9] FORWARD CHAINING — 3 TAHAP EKSEKUSI:
  │
  │   ┌─ TAHAP 1 (tahap_eksekusi = 1): Diagnosis Kondisi
  │   │   Untuk setiap rule di tahap 1:
  │   │   ├─ cekPrasyaratFakta(rule, workingMemory)
  │   │   │    → semua key:value di prasyarat_fakta harus ada di workingMemory
  │   │   ├─ evaluasiRule(rule, kondisi, kategoriUmur, blok, umur)
  │   │   │    → semua kondisi yang DIISI di rule harus cocok (AND logic)
  │   │   │    → kondisi NULL di rule = diabaikan
  │   │   │    → jika cocok: tambah rule ke rulesTerpicu[]
  │   │   │    → masukkan fakta_yang_dihasilkan ke workingMemory
  │   │   └─ Hasil: list rule DIAGNOSIS_VISUAL yang terpicu
  │   │
  │   ├─ TAHAP 2 (tahap_eksekusi = 2): Penentuan Dosis
  │   │   Rule PENENTU_DOSIS dapat override dosis dari tabel Pahan
  │   │   prasyarat_fakta memastikan rule dosis hanya terpicu jika
  │   │   fakta dari Tahap 1 sudah ada di workingMemory
  │   │
  │   └─ TAHAP 3 (tahap_eksekusi = 3): Penyesuaian / Tunda
  │       Rule PEMBATAS_APLIKASI (curah hujan, topografi, hama/gulma)
  │       Jika status_dominan = 'Tunda' → override status kelayakan
  │
  ├─ [10] Cek hasVisualRule:
  │        Jika tidak ada rule DIAGNOSIS_VISUAL terpicu
  │        DAN warna_daun ≠ 'Hijau Normal'
  │        → Return hasilDosisDasarTanpaDiagnosis()
  │          (gejala belum cocok dengan rule aktif)
  │
  ├─ [11] Jika rulesTerpicu kosong → hasilNormal()
  │         (tidak ditemukan gejala, dosis tetap dari Pahan)
  │
  └─ [12] susunHasil() → simpanDenganHistori()
```

### 7.2 Logika Evaluasi Rule (`evaluasiRule`)

Semua kondisi yang **diisi** (tidak NULL) di sebuah rule harus cocok (AND logic).
Kondisi NULL = kondisi tersebut tidak relevan / diabaikan untuk rule itu.

| Kondisi Rule | Field Kondisi Lahan | Catatan |
|--------------|--------------------|---------| 
| `kondisi_warna_daun` | `kondisi.warna_daun` | Exact match |
| `kondisi_topografi` | `blok.topografi` | Exact match |
| `kondisi_kelembaban` | `kondisi.kelembaban_tanah` | Exact match |
| `kondisi_curah_hujan_min_mm` | `kondisi.curah_hujan_mm_bulanan` | `>= min` |
| `kondisi_curah_hujan_max_mm` | `kondisi.curah_hujan_mm_bulanan` | `<= max` |
| `kondisi_drainase` | `kondisi.kondisi_drainase` | Exact match |
| `ada_gulma_dominan` | `kondisi.ada_gulma_dominan` | Boolean match |
| `ada_serangan_hama` | `kondisi.ada_serangan_hama` | Boolean match |
| `kondisi_kategori_umur` | Dari `kategoriUmur` string | Exact match |
| `kondisi_umur_tahun` | `umurSaatObservasi` integer | Exact match |

**Aturan khusus:** Rule tanpa kondisi apapun (jumlahKondisiDiRule = 0) hanya boleh terpicu jika memiliki `prasyarat_fakta` yang terpenuhi.

### 7.3 Working Memory & Forward Chaining Antar Tahap

```
workingMemory = {}   // Kosong di awal

Tahap 1 rule terpicu → misalnya: fakta_yang_dihasilkan = {"defisiensi": "nitrogen"}
workingMemory = {"defisiensi": "nitrogen"}

Tahap 2 rule: prasyarat_fakta = {"defisiensi": "nitrogen"}
→ cek: workingMemory["defisiensi"] === "nitrogen" → TRUE → rule terpicu
→ Rule ini bisa override dosis_urea / dosis_kcl
```

Ini memungkinkan rule dosis (Tahap 2) hanya aktif jika diagnosis tertentu sudah dikonfirmasi di Tahap 1.

### 7.4 Penentuan Status Kondisi Tanaman

Status kondisi tanaman (`status_kondisi_tanaman`) **hanya** berasal dari rule `DIAGNOSIS_VISUAL`.
Rule `PEMBATAS_APLIKASI` atau `PENENTU_METODE` tidak mengubah status kondisi tanaman.

| Tingkat Keparahan Rule | Status Kondisi Tanaman |
|------------------------|----------------------|
| `BERAT` | `GEJALA_BERAT` |
| `SEDANG` | `TERINDIKASI_DEFISIENSI` |
| `RINGAN` | `TERINDIKASI_DEFISIENSI_RINGAN` |
| `PERLU_VERIFIKASI` | `PERLU_VERIFIKASI` |
| `NORMAL` | `NORMAL_VISUAL` |
| Tidak ada rule DIAGNOSIS_VISUAL terpicu + daun Hijau Normal | `NORMAL_VISUAL` |
| Tidak ada rule DIAGNOSIS_VISUAL terpicu + daun ≠ Hijau Normal | `PERLU_VERIFIKASI` |

Jika ada beberapa rule DIAGNOSIS_VISUAL terpicu, diambil `tingkat_keparahan` **tertinggi**.

### 7.5 Penentuan Status Kelayakan Aplikasi

Status kelayakan (`status_kelayakan_aplikasi`) **hanya** berasal dari `FertilizationWindowService`.
Dikecualikan: jika `status_dominan = 'Tunda'` (dari rule PEMBATAS_APLIKASI),
sistem override status kelayakan menjadi `TUNDA_KONDISI_LAHAN`.

Kedua status ini **independen** — kondisi tanaman buruk tidak otomatis menghalangi pemupukan dari sisi waktu, dan sebaliknya.


---

## 8. LOGIKA DOSIS — TABEL PAHAN 2013

### 8.1 Sumber & Prinsip

Semua dosis Urea dan KCl berasal **hanya** dari Tabel 9.13 & 9.14 buku:
> Pahan, Iyung. (2013). *Panduan Lengkap Kelapa Sawit: Manajemen Agribisnis dari Hulu hingga Hilir*. Penebar Swadaya.

**Prinsip yang tidak boleh dilanggar:**
- Gejala visual **tidak mengubah angka dosis** — hanya menghasilkan indikasi dan saran pemeriksaan
- Jenis tanah dan topografi **tidak menjadi pengali dosis**
- Curah hujan menentukan **waktu** aplikasi, bukan angka dosis
- Strategi estimasi (midpoint/minimum/maximum) dikontrol via `.env DOSE_STRATEGY`

### 8.2 Tabel Dosis Referensi Pahan 2013

| Fase | Kelompok Umur | Urea min (kg/pokok/th) | Urea max (kg/pokok/th) | Urea estimasi* | KCl min (kg/pokok/th) | KCl max (kg/pokok/th) | KCl estimasi* |
|------|--------------|------------------------|------------------------|----------------|-----------------------|-----------------------|---------------|
| TBM | Tahun ke-1 | 0.50 | 0.70 | 0.60 | 0.75 | 1.25 | 1.00 |
| TBM | Tahun ke-2 | 0.70 | 0.85 | 0.775 | 1.00 | 1.75 | 1.375 |
| TBM | Tahun ke-3 | 0.90 | 1.25 | 1.075 | 1.20 | 2.25 | 1.725 |
| TM | 3–5 tahun | 0.90 | 1.75 | 1.325 | 1.20 | 2.50 | 1.850 |
| TM | 6–15 tahun | 1.00 | 3.00 | 2.000 | 1.50 | 3.50 | 2.500 |
| TM | > 15 tahun | 1.50 | 2.50 | 2.000 | 1.50 | 2.25 | 1.875 |

*Estimasi = (min + max) / 2 jika strategi `midpoint` (default)*

### 8.3 Alur `PahanDoseReferenceService::getDoseReferenceForContext()`

```
Input: BlokLahan, int $umur, string $fase ('TBM' atau 'TM')
  │
  ├─ Umur = 3 & fase = null & needs_phase_verification = true
  │   → Return null dosis + warning (tidak bisa tentukan kelompok)
  │
  ├─ Tentukan kelompok dosis dari (fase, umur):
  │   TBM + umur=1 → kelompok TBM-1
  │   TBM + umur=2 → kelompok TBM-2
  │   TBM + umur=3 → kelompok TBM-3
  │   TM  + umur 3-5  → kelompok TM-muda
  │   TM  + umur 6-15 → kelompok TM-tengah
  │   TM  + umur >15  → kelompok TM-tua
  │
  ├─ Ambil min, max dari config/fertilization.php
  ├─ Hitung estimasi berdasarkan DOSE_STRATEGY:
  │   midpoint  → (min + max) / 2
  │   minimum   → min
  │   maximum   → max
  │
  └─ Return: { urea: {min, max, estimate}, kcl: {min, max, estimate}, warnings[] }
```

### 8.4 Kalkulasi Total: `FertilizationCalculationService::calculate()`

```
jumlah_pokok = blok.jumlah_pokok_aktual
             = blok.jumlah_pohon  (jika diisi manual)
             = blok.luas_ha × blok.sph  (jika jumlah_pohon null)

total_urea_min = dosis_urea_min × jumlah_pokok
total_urea_max = dosis_urea_max × jumlah_pokok
total_urea_est = dosis_urea_est × jumlah_pokok

total_kcl_min = dosis_kcl_min × jumlah_pokok
total_kcl_max = dosis_kcl_max × jumlah_pokok
total_kcl_est = dosis_kcl_est × jumlah_pokok
```

### 8.5 `AnnualFertilizerSnapshotBuilder::build()`

Menghitung kebutuhan tahunan + snapshot luas/SPH pada saat analisis:

```
Output snapshot:
  urea_total_estimasi_tahunan = dosis_urea_est × jumlah_pokok  (dibulatkan 2 desimal)
  kcl_total_estimasi_tahunan  = dosis_kcl_est  × jumlah_pokok
  urea_karung_estimasi_tahunan = ceil(urea_total_estimasi_tahunan / 50)
  kcl_karung_estimasi_tahunan  = ceil(kcl_total_estimasi_tahunan / 50)
  jumlah_pokok     = jumlah_pokok_aktual saat analisis
  luas_ha_snapshot = blok.luas_ha saat analisis
  sph_snapshot     = blok.sph saat analisis
  urea_aplikasi_saat_ini = 50% jika layak (akan di-override CurrentApplicationCalculator)
  kcl_aplikasi_saat_ini  = 50% jika layak
```

Karung dibulatkan ke atas (`ceil`) agar stok di lapangan selalu cukup. Sisa karung bisa dipakai aplikasi berikutnya.

### 8.6 Override Dosis dari Rule PENENTU_DOSIS

Jika ada rule dengan `jenis_rule = 'PENENTU_DOSIS'` yang terpicu (biasanya di Tahap 2 forward chaining), dosis estimasi dari tabel Pahan di-**override** dengan nilai dari rule tersebut:

```php
$ruleDosis = collect($rules)->firstWhere('jenis_rule', 'PENENTU_DOSIS');
if ($ruleDosis) {
    $dosisRef['dosis_urea'] = $ruleDosis->rekomendasi_dosis_urea;
    $dosisRef['dosis_kcl']  = $ruleDosis->rekomendasi_dosis_kcl;
    // Sinkronisasi ke dose_reference agar snapshot builder konsisten
    $dosisRef['dose_reference']['urea']['estimate'] = $ruleDosis->rekomendasi_dosis_urea;
    $dosisRef['dose_reference']['kcl']['estimate']  = $ruleDosis->rekomendasi_dosis_kcl;
}
```

Ini memungkinkan dosen/admin menyesuaikan dosis secara dinamis melalui rule base tanpa mengubah kode.

---

## 9. KELAYAKAN WAKTU PEMUPUKAN (FertilizationWindowService)

### 9.1 `FertilizationWindowService::evaluate(KondisiLahan)`

Evaluasi dilakukan berdasarkan data kondisi lahan. Menghasilkan status kelayakan dan alasan.

```
Urutan evaluasi (dari prioritas tertinggi):

[1] Cek curah hujan numerik (curah_hujan_mm_bulanan):
    < 60 mm/bulan   → TUNDA_HUJAN_RENDAH
    > 300 mm/bulan  → TUNDA_HUJAN_TINGGI
    60–100 mm/bulan → PERLU_VERIFIKASI_DATA (di luar optimal tapi belum tunda)
    250–300 mm/bulan→ PERLU_VERIFIKASI_DATA
    100–250 mm/bulan→ OK (tidak ada status tunda dari curah hujan)

[2] Jika tidak ada nilai numerik, fallback ke kategori:
    'Sangat Rendah' → TUNDA_HUJAN_RENDAH
    'Sangat Tinggi' → TUNDA_HUJAN_TINGGI
    'Rendah'/'Tinggi'/'Normal' → PERLU_VERIFIKASI_DATA
    (tidak ada data hujan sama sekali → PERLU_VERIFIKASI_DATA)

[3] Cek kelembaban tanah:
    'Sangat Kering' → TUNDA_TANAH_KERING
    'Kering'        → PERLU_VERIFIKASI_DATA

[4] Cek interval pemupukan terakhir:
    tanggal_pemupukan_terakhir ada DAN
    interval < 120 hari → TUNDA_INTERVAL

[5] Cek drainase:
    'Buruk — Tergenang' → PERLU_PERBAIKAN_DRAINASE
```

### 9.2 Prioritas Status Final

Jika lebih dari satu kondisi tunda terpenuhi, diambil yang prioritas tertinggi:

```
PERLU_PERBAIKAN_DRAINASE  → prioritas 5 (tertinggi)
TUNDA_INTERVAL            → prioritas 4
TUNDA_HUJAN_TINGGI        → prioritas 3
TUNDA_HUJAN_RENDAH        → prioritas 3
TUNDA_TANAH_KERING        → prioritas 3
PERLU_VERIFIKASI_DATA     → prioritas 1 (terendah)
```

### 9.3 Nilai `layak`

```
layak = true  hanya jika status = LAYAK_DIJADWALKAN atau TERLAMBAT_PERLU_DIJADWALKAN
layak = false untuk semua status lainnya
```

### 9.4 Tabel Referensi Parameter Window

| Parameter | Nilai | Sumber |
|-----------|-------|--------|
| Curah hujan optimal min | 100 mm/bulan | PPKS/Barus dkk. 2025 |
| Curah hujan optimal max | 250 mm/bulan | PPKS/Barus dkk. 2025 |
| Batas tunda rendah | 60 mm/bulan | Pradiko dkk. 2021 |
| Batas tunda tinggi | 300 mm/bulan | Pradiko dkk. 2021 |
| Interval minimum antar aplikasi | 120 hari | Adaptasi PPKS 2021 (2-3x/tahun) |

Semua nilai ini dikonfigurasi di `config/fertilization.php` dan bisa diubah tanpa menyentuh kode.


---

## 10. SISTEM TAHAP AKTIF (CurrentApplicationCalculator)

### 10.1 Konsep

Setiap tahun, satu blok lahan menjalani **2 tahap pemupukan**:
- **Tahap 1** = 50% dari kebutuhan tahunan estimasi
- **Tahap 2** = sisa aktual setelah Tahap 1 (total tahunan − yang sudah direalisasi)

Pembagian 50%/50% adalah adaptasi operasional dari rekomendasi frekuensi 2–3 kali/tahun (PPKS 2021), **bukan** dari Pahan 2013.
Interval minimum 120 hari antar tahap adalah turunan dari frekuensi tersebut.

### 10.2 `CurrentApplicationCalculator::calculate(array $input)`

```
Input:
  annual_snapshot.urea_total_estimasi_tahunan
  annual_snapshot.kcl_total_estimasi_tahunan
  window_result.layak
  realization_summary  (dari FertilizationRealizationService)
  analysis_date
```

**Logika pengambilan keputusan (urutan pemeriksaan):**

```
[Pra-cek] Kebutuhan tahunan = 0?
  → PERLU_VERIFIKASI_REALISASI

[Kasus 6] sisa_urea ≤ 0 DAN sisa_kcl ≤ 0?
  → SELESAI_TAHUNAN (aplikasi = 0)

[Kasus 2] !layak?
  → MENUNGGU_KELAYAKAN (aplikasi = 0, sisa tetap dicatat)

[Kasus 3] tahap_1_ada=true DAN tahap_1_selesai=false?  [tahap 1 SEBAGIAN]
  → TAHAP_1_SEBAGIAN
  → aplikasi = sisa rencana Tahap 1 (rencana − sudah direalisasi)

[Kasus 1] !tahap_1_ada DAN !tahap_1_selesai?  [belum ada realisasi]
  → TAHAP_1_SIAP
  → aplikasi = 50% kebutuhan tahunan

[Kasus 4] tahap_1_selesai=true DAN !interval_terpenuhi?
  → MENUNGGU_INTERVAL (aplikasi = 0)

[Kasus 5] tahap_1_selesai=true DAN interval_terpenuhi DAN layak?
  → TAHAP_2_SIAP
  → aplikasi = sisa aktual (total tahunan − total sudah direalisasi)
```

### 10.3 Tabel Status Lengkap

| Konstanta | Label Tampilan | Warna Badge | Boleh Catat Realisasi |
|-----------|---------------|-------------|----------------------|
| `TAHAP_1_SIAP` | Tahap 1 Siap Dipupuk | emerald | ✅ YA |
| `TAHAP_1_SEBAGIAN` | Tahap 1 Sudah Dicatat Sebagian | amber | ✅ YA |
| `MENUNGGU_INTERVAL` | Menunggu Jarak Waktu 120 Hari | blue | ❌ TIDAK |
| `MENUNGGU_KELAYAKAN` | Menunggu Kondisi Lapangan Mendukung | amber | ❌ TIDAK |
| `TAHAP_2_SIAP` | Tahap 2 Siap Dipupuk | emerald | ✅ YA |
| `SELESAI_TAHUNAN` | Program Pemupukan Tahun Ini Selesai | green | ❌ TIDAK |
| `PERLU_VERIFIKASI_REALISASI` | Periksa Catatan Pelaksanaan | red | ❌ TIDAK |

### 10.4 Evaluasi Urea & KCl Secara Independen

Tahap 1 dianggap **selesai** hanya jika **kedua** Urea DAN KCl memenuhi rencana (toleransi 0.01 kg).
Sistem tidak berpindah ke Tahap 2 jika salah satu pupuk belum terpenuhi.

```
tahap_1_selesai = (urea_realisasi >= urea_rencana - 0.01)
               AND (kcl_realisasi  >= kcl_rencana  - 0.01)
```

### 10.5 Sumber Data Realisasi: `FertilizationRealizationService`

Dua metode penghitungan ringkasan realisasi:

**Method utama (pahan-v2.8):** `getRealizationSummaryForProgram(ProgramPemupukan)`
- Filter berdasarkan `program_pemupukan_id`
- Memastikan realisasi dari program berbeda tidak tercampur

**Method fallback (legacy):** `getRealizationSummary(BlokLahan, ?rekomendasiRbsId)`
- Filter berdasarkan `rekomendasi_rbs_id` atau `tahun_program`/tahun kalender
- Dipakai jika rekomendasi belum punya program

Kedua method menghasilkan struktur data yang sama, termasuk:
- `tahap_1_ada`, `tahap_1_sebagian`, `tahap_1_selesai`, `tahap_1_batal`
- `urea_rencana_tahap_1`, `kcl_rencana_tahap_1`
- `urea_realisasi_tahap_1`, `kcl_realisasi_tahap_1`
- `total_urea_realisasi`, `total_kcl_realisasi`
- `tanggal_minimum_tahap_2`, `interval_hari_sejak_tahap_1`, `interval_terpenuhi`


---

## 11. PROGRAM PEMUPUKAN — SIKLUS HIDUP

### 11.1 Konsep

`ProgramPemupukan` adalah wadah yang mengikat satu siklus pemupukan tahunan per blok.
Ia menjamin bahwa realisasi dari tahun yang berbeda tidak tercampur, dan memungkinkan
status program dilacak secara independen dari rekomendasi.

**Aturan inti:** Satu blok hanya boleh punya **satu** program berstatus `AKTIF` per tahun.
Dijamin oleh kolom `active_key` (format: `{blok_id}-{tahun}`) dengan UNIQUE constraint.

### 11.2 Kapan Program Dibuat

Program dibuat otomatis oleh `ProgramPemupukanService::resolveActiveProgram()` pada:
1. **Saat analisis RBS** — jika blok punya dosis tahunan > 0 dan belum ada program aktif tahun ini
2. **Saat store realisasi** — dipanggil `RealisasiPemupukanController::ensureProgram()` sebelum simpan

Pembuatan menggunakan `lockForUpdate()` di dalam transaksi DB untuk mencegah race condition (dua request simultan membuat dua program untuk blok/tahun yang sama).

### 11.3 Siklus Status Program

```
         [Buat Otomatis]
              │
              ▼
           AKTIF ──────────────── Realisasi dicatat
              │                   sisa tahunan masih ada
              │
              │  ProgramStatusService::synchronizeStatus()
              │  dipanggil setelah setiap realisasi
              │
              ▼
    sisa_urea ≤ 0 AND sisa_kcl ≤ 0?
              │
         ┌────┴────┐
        YA         TIDAK
         │          │
         ▼          ▼
      SELESAI    tetap AKTIF
    active_key
      → NULL
```

### 11.4 `ProgramStatusService::synchronizeStatus()`

```php
// Dipanggil setelah setiap simpan/update/batal realisasi
$postCurrentApp = $eligibilityService->evaluate($rekomendasi);
$programStatusService->synchronizeStatus($program, $postCurrentApp['current_app']);

// Di dalam synchronizeStatus:
if ($currentApp['status_stage'] === SELESAI_TAHUNAN) {
    $program->update([
        'status_program' => ProgramPemupukan::STATUS_SELESAI,
        'active_key'     => null,   // Lepas UNIQUE constraint
    ]);
}
```

### 11.5 Hubungan Program–Rekomendasi–Realisasi

```
ProgramPemupukan (AKTIF, tahun 2026, blok A)
  ├── RekomendasiRbs #1 (program_pemupukan_id = program.id)
  │     analisis pertama → set rekomendasi_awal_id
  ├── RekomendasiRbs #2 (is_latest = true, program_pemupukan_id = program.id)
  │     analisis setelah ada perubahan kondisi
  ├── RealisasiPemupukan #1 (tahap=1, SELESAI)
  └── RealisasiPemupukan #2 (tahap=2, SEBAGIAN)
```

Jika `rekomendasi.program_pemupukan_id` belum terisi saat realisasi pertama dicatat,
`RealisasiPemupukanController::ensureProgram()` mengisi field tersebut secara otomatis.


---

## 12. PENCATATAN REALISASI PEMUPUKAN (TRIPLE ANTI-DUPLIKAT)

### 12.1 Gate Kelayakan Server-Side (`RealisasiEligibilityService::evaluate()`)

Sebelum form dibuka dan sebelum data disimpan, server mengevaluasi:

```
evaluate(RekomendasiRbs $rekomendasi):
  │
  ├─ [1] is_latest = false?
  │       → TOLAK: "Realisasi tidak dapat dicatat dari rekomendasi historis"
  │
  ├─ [2] status_kondisi_tanaman = PERLU_VERIFIKASI/BELUM_DIOBSERVASI
  │       ATAU status_kelayakan_aplikasi = PERLU_VERIFIKASI_DATA?
  │       → TOLAK: "Data observasi belum cukup"
  │         (status_stage = MENUNGGU_KELAYAKAN, active_stage tetap)
  │
  ├─ [3] Ada program terkait rekomendasi?
  │       → Cek program.isAktif()
  │       → Jika tidak aktif → TOLAK: "Program sudah berstatus X"
  │
  ├─ [4] Tidak ada program? → Cari program aktif tahun ini untuk blok
  │
  ├─ [5] Evaluasi window terbaru dari kondisi terbaru blok
  │       (bukan dari snapshot rekomendasi lama)
  │       Khusus: jika status_kelayakan_aplikasi = TUNDA_KONDISI_LAHAN
  │       → override window.layak = false (hama/gulma memblokir)
  │
  ├─ [6] Hitung CurrentApplicationCalculator
  │
  └─ [7] boleh_mencatat = statusStage ∈ {TAHAP_1_SIAP, TAHAP_1_SEBAGIAN, TAHAP_2_SIAP}
                        AND (ureaRencana > 0 OR kclRencana > 0)
```

### 12.2 Nilai yang Ditentukan Server (Tidak dari Browser)

Saat penyimpanan, nilai-nilai berikut **selalu** diambil dari server:

| Field | Sumber Server | Mengapa |
|-------|--------------|---------|
| `tahap` | `eligibility['active_stage']` | Browser bisa dimanipulasi |
| `tahun_program` | `eligibility['tahun_program']` | Konsistensi program |
| `urea_rencana_kg` | `eligibility['urea_rencana_kg']` | Dihitung dari CurrentAppCalc |
| `kcl_rencana_kg` | `eligibility['kcl_rencana_kg']` | Dihitung dari CurrentAppCalc |

### 12.3 Tiga Lapis Perlindungan Double-Submit

**Lapisan 1 — Submission Token (Idempotensi):**
```
- UUID dibuat di server saat form di-render
- Dikirim kembali saat form di-submit
- Cek: apakah token sudah ada di DB? → redirect ke realisasi lama
- Unik per form-load, sehingga tab duplikat pun tertangani
```

**Lapisan 2 — Duplikasi Semantik (5 Menit):**
```
Cek realisasi aktif yang:
- blok_lahan_id sama
- rekomendasi_rbs_id sama
- tanggal_realisasi sama
- urea_realisasi_kg sama (toleransi ±0.02 kg)
- kcl_realisasi_kg sama (toleransi ±0.02 kg)
- status bukan BATAL
- dibuat dalam 5 menit terakhir
→ Jika ditemukan: redirect ke realisasi yang sudah ada
```

**Lapisan 3 — DB Transaction dengan lockForUpdate:**
```php
DB::transaction(function() {
    $lockedRekomendasi = RekomendasiRbs::lockForUpdate()->find($id);
    $lockedProgram     = ProgramPemupukan::lockForUpdate()->find($id);

    // Re-check token di dalam transaksi (race condition protection)
    $existingInTx = RealisasiPemupukan::where('submission_token', $token)->first();
    if ($existingInTx) return $existingInTx;

    // Re-evaluasi kelayakan setelah lock (tahap mungkin berubah)
    $freshEligibility = $eligibilityService->evaluate($lockedRekomendasi);
    if (!$freshEligibility['boleh_mencatat']) {
        throw new RuntimeException('STAGE_CHANGED:...');
    }

    return RealisasiPemupukan::create([...]);
});
```

### 12.4 Validasi Status SELESAI

Status `SELESAI` hanya diterima jika total kumulatif ≥ rencana tahap (toleransi 0.01 kg):

```
Tahap 1:
  rencana = 50% × urea_total_estimasi_tahunan
  total kumulatif = urea_realisasi_baru + sum(realisasi tahap 1 sebelumnya)
  valid = total_kumulatif >= rencana - 0.01

Tahap 2:
  rencana = urea_total_estimasi_tahunan − sum(realisasi tahap 1 aktif)
  total kumulatif = urea_realisasi_baru + sum(realisasi tahap 2 sebelumnya)
  valid = total_kumulatif >= rencana - 0.01
```

Jika tidak valid, form dikembalikan dengan pesan error spesifik:
`"Urea: 45.00 / 50.00 kg; KCl: 30.00 / 35.00 kg"`

### 12.5 Pasca-Simpan Realisasi

```
Setelah RealisasiPemupukan berhasil dibuat:
  │
  ├─ RecommendationOperationalRefreshService::refreshAfterRealization()
  │   → Update field operasional rekomendasi (active_stage, status_stage,
  │     urea_aplikasi_saat_ini, sisa_tahunan, dll)
  │   → TANPA menjalankan ulang diagnosis visual (forward chaining tidak diulang)
  │
  ├─ recordOperationalHistory() → catat event REALISASI_DIBUAT di audit log
  │
  ├─ ProgramStatusService::synchronizeStatus()
  │   → Jika sisa = 0 → program menjadi SELESAI
  │
  └─ sendRealisasiNotification()
      ├─ Selalu: realisasiDicatat(namaBlok, tahap, url)
      ├─ Jika SELESAI_TAHUNAN: programSelesai(namaBlok, url)
      └─ Jika status SEBAGIAN: realisasiSebagian(namaBlok, tahap, url)
```

### 12.6 Pembatalan Realisasi

Pembatalan bersifat **soft** — record tidak dihapus, hanya `status_realisasi` diubah ke `BATAL`:

```
PATCH /realisasi-pemupukan/{id}/batal
  │
  ├─ Cek sudah BATAL? → redirect dengan warning
  ├─ update status_realisasi = 'BATAL'
  ├─ refreshAfterRealization() → recalculate stage (mungkin kembali ke TAHAP_1_SIAP)
  └─ recordOperationalHistory(REALISASI_DIBATALKAN)
```

Record BATAL tidak dihitung dalam ringkasan realisasi, namun tetap ada di DB untuk keperluan audit.


---

## 13. FINGERPRINT & HISTORI REKOMENDASI

### 13.1 Sistem Fingerprint SHA-256

Setiap hasil analisis menghasilkan hash SHA-256 dari komponen data penting.
Tujuan: mendeteksi apakah hasil analisis **bermakna berubah** dibanding sebelumnya.

**Komponen fingerprint (diurutkan by key agar deterministik):**

```
kondisi_lahan_id          versi_mesin
program_pemupukan_id      fase, umur
strategi_estimasi         urea_estimasi, kcl_estimasi
status_kondisi_tanaman    status_kelayakan
rules_terpicu (kode)      jumlah_jadwal
kelengkapan_data_score    luas_ha_snapshot, sph_snapshot
jumlah_pokok_snapshot     urea_total_estimasi_tahunan, kcl_total_estimasi_tahunan
urea_aplikasi_saat_ini    kcl_aplikasi_saat_ini
urea_sisa_tahunan         kcl_sisa_tahunan
active_stage              status_stage
```

### 13.2 Logika `simpanDenganHistori()`

```
simpanDenganHistori(blokLahanId, data):
  │
  ├─ [1] Resolve / buat program pemupukan (jika dosis > 0)
  │
  ├─ [2] Generate fingerprint SHA-256 dari data
  │
  ├─ [3] Ambil rekomendasi is_latest=true yang ada
  │
  ├─ [4] Bandingkan fingerprint:
  │       SAMA → hanya update field yang boleh berubah seiring waktu:
  │               tanggal_analisis, fingerprint, alasan_kelayakan,
  │               catatan_dosis, jadwal_pemupukan, saran_tindakan_utama,
  │               catatan_validitas, notifikasi_data, catatan_confidence
  │               → touch() (update updated_at)
  │               → RETURN rekomendasi yang sudah ada (tidak buat baru)
  │
  └─ [5] BERBEDA → buat record baru:
          - Set semua is_latest=true yang lama → false (histori)
          - nomor_analisis = max(nomor_analisis) + 1
          - is_latest = true
          - Simpan record baru
```

### 13.3 Histori Rekomendasi

Setiap perubahan bermakna menghasilkan record baru di `rekomendasi_rbs` dengan `is_latest=false`.
Maksimal 20 histori terakhir ditampilkan di halaman `rbs.detail`.

```
GET /rbs/detail/{blokLahan}
  → Tampilkan rekomendasi is_latest=true
  → Tampilkan histori: is_latest=false, ordered by tanggal_analisis DESC, limit 20
```

### 13.4 Refresh Operasional Tanpa Re-diagnosis

`RecommendationOperationalRefreshService::refreshAfterRealization()` memperbarui field
operasional rekomendasi setelah ada realisasi — **tanpa** menjalankan ulang forward chaining:

```
refreshAfterRealization(RealisasiPemupukan $realisasi):
  │
  ├─ Ambil rekomendasi is_latest terkait
  ├─ Hitung ulang realizationSummary
  ├─ Hitung ulang windowResult dari kondisi terbaru
  ├─ Hitung ulang CurrentApplicationCalculator
  │
  └─ Update di rekomendasi:
       active_stage, status_stage,
       urea_aplikasi_saat_ini, kcl_aplikasi_saat_ini,
       urea_sisa_tahunan, kcl_sisa_tahunan,
       tanggal_minimum_tahap_berikutnya, alasan_tahap,
       analysis_fingerprint (diperbarui)
```

Ini menjaga field operasional selalu sinkron tanpa membuang histori diagnosis visual.

---

## 14. SISTEM NOTIFIKASI

### 14.1 Channel & Storage

- Channel: `database` (Laravel Database Notifications)
- Tabel: `notifications` (UUID primary key, JSON `data`)
- Penerima: `Admin` model (via `Notifiable` trait)

### 14.2 Jenis Notifikasi (`RealisasiNotification`)

| Factory Method | Kapan Dikirim | Trigger |
|----------------|--------------|---------|
| `tahapSiap(namaBlok, tahap, url)` | Setelah analisis RBS jika TAHAP_1_SIAP/TAHAP_2_SIAP | `RbsController` |
| `intervalTerpenuhi(namaBlok, url)` | Interval 120 hari terpenuhi, Tahap 2 siap | `RbsController` |
| `realisasiDicatat(namaBlok, tahap, url)` | Setelah realisasi berhasil disimpan | `RealisasiPemupukanController` |
| `programSelesai(namaBlok, url)` | Sisa tahunan = 0 setelah realisasi | `RealisasiPemupukanController` |
| `realisasiSebagian(namaBlok, tahap, url)` | Realisasi disimpan dengan status SEBAGIAN | `RealisasiPemupukanController` |

### 14.3 Anti-Spam Notifikasi

Sebelum mengirim notifikasi `tahapSiap`, sistem cek apakah sudah ada notifikasi
yang sama yang belum dibaca untuk blok dan tahap yang sama:

```php
$existing = $admin->unreadNotifications()
    ->where('data->tipe', 'tahap_siap')
    ->where('data->meta->blok', $blokLahan->nama_blok)
    ->where('data->meta->tahap', $rekomendasi->active_stage)
    ->first();

if ($existing) return; // Tidak kirim duplikat
```

### 14.4 API Notifikasi

```
GET  /api/notifications          → 10 notifikasi terbaru (JSON)
POST /api/notifications/{id}/read → Tandai satu notifikasi dibaca
POST /api/notifications/read-all  → Tandai semua dibaca
```

Notifikasi ditampilkan via "bell icon" di navbar (komponen Alpine.js polling AJAX).
Badge merah menunjukkan jumlah notifikasi belum dibaca.

---

## 15. RULE BASE — STRUKTUR & JENIS RULE

### 15.1 Jenis Rule (`jenis_rule`)

| Jenis | Tahap | Fungsi |
|-------|-------|--------|
| `DIAGNOSIS_VISUAL` | 1 | Mendiagnosis gejala dari warna daun → menghasilkan `status_kondisi_tanaman` |
| `PEMBATAS_APLIKASI` | 1 atau 3 | Menentukan waktu aplikasi (curah hujan) atau memblokir pemupukan |
| `PENENTU_DOSIS` | 2 | Override dosis dari tabel Pahan (jika diperlukan) |
| `PENENTU_METODE` | 1 atau 3 | Menentukan metode aplikasi berdasarkan topografi |

### 15.2 Rule Sistem Bawaan (7 Rule dari Seeder, + 3 Topografi)

| Kode | Jenis | Kondisi IF | Output THEN |
|------|-------|-----------|-------------|
| `VIS-N-01` | DIAGNOSIS_VISUAL | `warna_daun = 'Daun Bawah Menguning'` | Indikasi defisiensi N, saran Urea, keparahan SEDANG |
| `VIS-K-02` | DIAGNOSIS_VISUAL | `warna_daun = 'Bercak Kuning/Transparan pada Daun Tua'` | Indikasi defisiensi K, saran KCl, keparahan SEDANG |
| `VIS-MG-01` | DIAGNOSIS_VISUAL | `warna_daun = 'Tepi Daun Tua Menguning pada Bagian Terbuka'` | Indikasi defisiensi Mg, saran pemeriksaan, keparahan RINGAN |
| `VIS-B-01` | DIAGNOSIS_VISUAL | `warna_daun = 'Daun Muda Berbentuk Kait atau Memendek'` | Indikasi defisiensi B, saran pemeriksaan, keparahan RINGAN |
| `WAKTU-HUJAN-RENDAH` | PEMBATAS_APLIKASI | `curah_hujan_max_mm = 59.9` | Tunda pemupukan, status Tunda |
| `WAKTU-HUJAN-OPTIMAL` | PEMBATAS_APLIKASI | `curah_hujan_min=100, max=250` | Waktu mendukung, status Normal |
| `WAKTU-HUJAN-TINGGI` | PEMBATAS_APLIKASI | `curah_hujan_min_mm = 300.1` | Tunda pemupukan, status Tunda |
| `TOPO-01` | PENENTU_METODE | `topografi = 'Datar - Landai (< 12°)'` | Metode broadcasting merata |
| `TOPO-02` | PENENTU_METODE | `topografi = 'Bergelombang - Miring (12° - 23°)'` | Metode pocketing/terasan |
| `TOPO-03` | PENENTU_METODE | `topografi = 'Curam - Berbukit (> 23°)'` | Wajib sistem benam dalam rorak |

### 15.3 Sumber Literatur Rule Sistem

| Rule | Sumber |
|------|--------|
| VIS-N-01, VIS-K-02, VIS-MG-01, VIS-B-01 | Barus, Hutagalung, Syarovy, Fauzi — PPKS 2025, Hal. 23-38, Tabel 1 |
| WAKTU-HUJAN-RENDAH, WAKTU-HUJAN-TINGGI | Pradiko, Rahutomo, Siregar, Darlan — PPKS 2021, Hal. 67-80 |
| WAKTU-HUJAN-OPTIMAL | Barus dkk. — PPKS 2025, rentang optimal 100-250 mm |
| TOPO-01, TOPO-02, TOPO-03 | Pahan 2013, Hal. 82, Tabel 4.5 |

### 15.4 Manajemen Rule via Admin (`RuleBaseController`)

```
GET  /rule-base           → Daftar semua rule (aktif & nonaktif)
GET  /rule-base/info      → Panduan pengelolaan rule
GET  /rule-base/tambah    → Form tambah rule baru
POST /rule-base           → Simpan rule baru
GET  /rule-base/{id}/edit → Form edit rule
PUT  /rule-base/{id}      → Update rule
PATCH /rule-base/{id}/status → Toggle aktif/nonaktif
```

Rule sistem (`is_system_rule = true`) bisa diedit tapi tidak dihapus oleh seeder
saat dijalankan ulang (seeder menggunakan `firstOrCreate`).

### 15.5 Integrasi Warna Daun dengan Rule

Form observasi hanya menampilkan nilai `warna_daun` yang punya rule aktif:

```php
// KondisiLahanController::activeLeafConditions()
$active = RuleBaseLanjutan::aktif()
    ->whereNotNull('kondisi_warna_daun')
    ->orderBy('prioritas')
    ->pluck('kondisi_warna_daun')
    ->filter(fn($c) => in_array($c, config('observation.diagnostic_leaf_conditions')))
    ->unique()->values()->all();

// Selalu sertakan 'Hijau Normal' di awal
return array_unique(array_merge(['Hijau Normal'], $active));
```

Ini memastikan pilihan form selalu sinkron dengan rule yang aktif di database.


---

## 16. SEMUA ROUTE & ENDPOINT API

### 16.1 Route Publik (Guest)

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/` | Closure | Redirect ke `/dashboard` |
| GET | `/login` | `AuthController@showLoginForm` | Form login (guest only) |
| POST | `/login` | `AuthController@login` | Proses login, throttle 5/menit |
| POST | `/logout` | `AuthController@logout` | Logout |

### 16.2 Route Terproteksi (AdminAuthenticated)

**Dashboard**

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/dashboard` | `DashboardController@index` | WebGIS + statistik |

**Anggota**

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/anggota` | `index` | Daftar anggota + pagination |
| GET | `/anggota/create` | `create` | Form tambah |
| POST | `/anggota` | `store` | Simpan anggota |
| GET | `/anggota/{id}/edit` | `edit` | Form edit |
| PUT | `/anggota/{id}` | `update` | Update anggota |
| DELETE | `/anggota/{id}` | `destroy` | Hapus anggota |

**Blok Lahan**

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/blok-lahan` | `index` | Daftar blok grouped by anggota |
| GET | `/blok-lahan/create` | `create` | Form + peta Leaflet gambar polygon |
| POST | `/blok-lahan` | `store` | Simpan blok + GeoJSON |
| GET | `/blok-lahan/{id}` | `show` | Detail blok |
| GET | `/blok-lahan/{id}/edit` | `edit` | Form edit + peta |
| PUT | `/blok-lahan/{id}` | `update` | Update blok |
| DELETE | `/blok-lahan/{id}` | `destroy` | Hapus blok |

**Kondisi Lahan (Observasi)**

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/kondisi-lahan` | `index` | Daftar blok + status observasi, filter tab |
| GET | `/kondisi-lahan/create` | `create` | Form observasi baru |
| POST | `/kondisi-lahan` | `store` | Simpan + auto-analisis RBS |
| GET | `/kondisi-lahan/{id}/edit` | `edit` | Form edit observasi |
| PUT | `/kondisi-lahan/{id}` | `update` | Update + auto-analisis ulang |
| DELETE | `/kondisi-lahan/{id}` | `destroy` | Hapus (blokir jika sudah ada rekomendasi) |
| GET | `/kondisi-lahan/{id}/foto` | `photo` | Tampilkan foto observasi |

**Rule Base**

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/rule-base` | `index` | Daftar rule + status aktif |
| GET | `/rule-base/info` | `info` | Panduan pengelolaan rule |
| GET | `/rule-base/tambah` | `create` | Form tambah rule |
| POST | `/rule-base` | `store` | Simpan rule |
| GET | `/rule-base/{id}/edit` | `edit` | Form edit rule |
| PUT | `/rule-base/{id}` | `update` | Update rule |
| PATCH | `/rule-base/{id}/status` | `toggleStatus` | Toggle aktif/nonaktif |

**Analisis RBS**

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/rbs` | `index` | Daftar blok + status analisis, filter, stats |
| GET | `/rbs/daftar-blok-belum-analisis` | `daftarBlokBelumAnalisis` | JSON: blok belum analisis |
| POST | `/rbs/analisis/{blokLahan}` | `analisis` | Analisis satu blok |
| POST | `/rbs/analisis-semua` | `analisisSemua` | Analisis semua blok berkonidisi |
| GET | `/rbs/detail/{blokLahan}` | `detail` | Detail hasil + histori analisis |

**Laporan**

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/laporan` | `index` | Daftar laporan grouped by anggota |
| GET | `/laporan/{rekomendasiRbs}` | `show` | Detail laporan satu rekomendasi |
| GET | `/laporan/{rekomendasiRbs}/pdf` | `exportPdf` | Export PDF via Dompdf |

**Realisasi Pemupukan**

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/realisasi-pemupukan` | `index` | 3 tab: Siap, Menunggu, Riwayat |
| GET | `/realisasi-pemupukan/create/{rekomendasiRbs}` | `create` | Form (cek eligibility dulu) |
| POST | `/realisasi-pemupukan` | `store` | Simpan (triple anti-duplikat) |
| GET | `/realisasi-pemupukan/{id}` | `show` | Detail + histori operasional |
| GET | `/realisasi-pemupukan/{id}/edit` | `edit` | Form edit |
| PUT | `/realisasi-pemupukan/{id}` | `update` | Update + validasi SELESAI |
| PATCH | `/realisasi-pemupukan/{id}/batal` | `cancel` | Soft cancel → status BATAL |

**Pengaturan**

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/settings` | `index` | Halaman password & tema |
| PUT | `/settings/password` | `updatePassword` | Ganti password |
| PUT | `/settings/theme` | `updateTheme` | Ubah tema (JSON response) |

### 16.3 Endpoint API Internal (JSON)

| Method | URL | Handler | Keterangan |
|--------|-----|---------|------------|
| GET | `/api/rbs-popup/{blokLahan}` | `RbsController@apiPopup` | Data popup peta WebGIS |
| POST | `/api/cuaca/fetch` | `CuacaController@fetch` | Fetch cuaca Open-Meteo |
| POST | `/api/geo-upload` | `GeoUploadController@upload` | Upload SHP/GeoJSON → polygon |
| GET | `/api/notifications` | `NotificationController@recent` | 10 notifikasi terbaru |
| POST | `/api/notifications/{id}/read` | `markAsRead` | Tandai dibaca |
| POST | `/api/notifications/read-all` | `markAllAsRead` | Tandai semua dibaca |


---

## 17. SERVICES — TANGGUNG JAWAB & INTERAKSI

*(Sudah terdokumentasi lengkap di CODEBASE_INDEX.md Section 6. Bagian ini menjadi ringkasan interaksi antar service.)*

```
KondisiLahanController::store()
  └─► RbsService::analisis()
        ├─► PlantContextService::resolve()
        ├─► ObservationCompletenessService::evaluate()
        ├─► RuleBaseLanjutan::aktif()->orderBy('prioritas')->get()
        │     [Forward Chaining 3 Tahap]
        ├─► PahanDoseReferenceService::getDoseReferenceForContext()
        ├─► FertilizationCalculationService::calculate()
        ├─► FertilizationWindowService::evaluate()          [status kelayakan awal]
        │     ↓ jika status_dominan = 'Tunda'
        │     override → TUNDA_KONDISI_LAHAN               [rule KONDISI_LAHAN menang]
        ├─► AnnualFertilizerSnapshotBuilder::build()
        ├─► ProgramPemupukanService::getActiveProgram()
        ├─► FertilizationRealizationService::getRealizationSummaryForProgram()
        ├─► CurrentApplicationCalculator::calculate()
        ├─► FertilizationScheduleService::generate()
        ├─► RecommendationReliabilityService::calculate()
        └─► simpanDenganHistori() → RekomendasiRbs::create/update

RealisasiPemupukanController::store()
  ├─► RealisasiEligibilityService::evaluate()
  │     ├─► FertilizationRealizationService
  │     ├─► FertilizationWindowService::evaluate()
  │     └─► CurrentApplicationCalculator::calculate()
  ├─► ProgramPemupukanService::resolveActiveProgram()
  ├─► DB::transaction() [lockForUpdate]
  │     └─► RealisasiPemupukan::create()
  ├─► RecommendationOperationalRefreshService::refreshAfterRealization()
  │     ├─► FertilizationRealizationService
  │     ├─► CurrentApplicationCalculator
  │     └─► RekomendasiRbs::update() [field operasional saja]
  ├─► RekomendasiOperasionalHistory::create()
  ├─► ProgramStatusService::synchronizeStatus()
  └─► RealisasiNotification::notify()
```

---

## 18. DASHBOARD WEBGIS — LOGIKA PETA

### 18.1 Status Warna Peta (`mapActionStatus`)

Status peta berfokus pada **tindakan yang diperlukan**, bukan diagnosis unsur hara:

| Status Peta | Warna | Kondisi |
|-------------|-------|---------|
| `BELUM_DIPERIKSA` | Abu-abu | Belum ada kondisi, belum ada rekomendasi, atau data belum cukup |
| `ADA_GEJALA` | Oranye/Merah | `status_kondisi_tanaman` ∈ {GEJALA_BERAT, TERINDIKASI_DEFISIENSI, TERINDIKASI_DEFISIENSI_RINGAN} |
| `SIAP_DIPUPUK` | Hijau | `boleh_mencatat = true` (dari eligibility service) |
| `DITUNDA` | Kuning | Ada rekomendasi valid tapi belum siap dipupuk |

### 18.2 Statistik Dashboard

| Stat | Cara Hitung |
|------|------------|
| Total anggota | `Anggota::count()` |
| Total blok | `BlokLahan::count()` |
| Total luas | `sum(luas_ha)` |
| Belum kondisi | blok tanpa `kondisiTerbaru` |
| Siap dipupuk | blok dengan `boleh_mencatat = true` |
| Menunggu interval | status_stage = `MENUNGGU_INTERVAL` |
| Program selesai | `ProgramPemupukan` tahun ini dengan status SELESAI |
| Gejala berat | `status_kondisi_tanaman = GEJALA_BERAT` |
| Terindikasi defisiensi | `TERINDIKASI_DEFISIENSI` |

### 18.3 Popup Peta (API `/api/rbs-popup/{blokLahan}`)

Data yang dikembalikan ke Leaflet.js untuk popup blok:
- `status`, `kelayakan`, `warna_badge`, `tanggal`, `masalah`, `pupuk`, `saran`
- `fase`, `umur`, `urea_estimasi`, `kcl_estimasi`, `skor_keandalan`
- `anggota_nama`, `sibling_bloks` (blok lain milik anggota yang sama)

---

## 19. LAPORAN & EKSPOR PDF

### 19.1 Halaman Laporan Index

Filter yang tersedia:
- `status_kondisi_tanaman` — filter berdasarkan kondisi tanaman (bukan `status_kebutuhan_dominan` yang legacy)
- `status_kelayakan_aplikasi` — filter berdasarkan kelayakan aplikasi
- `status_program` — filter program AKTIF/SELESAI/dll
- `tahun_program` — filter tahun program
- `anggota_id`, `blok_lahan_id` — filter hierarki
- `status_stage` — filter tahap aktif
- `histori=semua` — tampilkan rekomendasi non-latest

**Grand Total** dihitung dari `urea_operasional` dan `kcl_operasional` (dari eligibility service, bukan snapshot statis). Ini memastikan angka di laporan konsisten dengan angka di halaman detail rekomendasi.

### 19.2 View PDF (`laporan/pdf.blade.php`)

Menggunakan `status_kondisi_tanaman` untuk banner warna (bukan `status_kebutuhan_dominan`).
Histori realisasi diambil dari program pemupukan yang sama (bukan hanya yang terhubung ke `rekomendasi_rbs_id`), sehingga semua realisasi dalam satu program tergabung dalam satu laporan.

---

## 20. KEAMANAN & VALIDASI

### 20.1 Autentikasi

- Guard: `admin` (Eloquent guard terpisah, bukan `web`)
- Middleware: `AdminAuthenticated` — semua route terproteksi
- Login throttle: 5 request/menit per IP (`throttle:5,1`)
- Session: Laravel session standar

### 20.2 Form Request Validation (Ringkasan)

| Request | Aturan Penting |
|---------|---------------|
| `StoreBlokLahanRequest` | GeoJSON valid jika diisi, fase harus TBM/TM |
| `StoreKondisiLahanRequest` | blok_lahan_id exists, tanggal tidak di masa depan, curah hujan numeric min:0 |
| `StoreRealisasiPemupukanRequest` | submission_token required, urea/kcl min:0, status ∈ {SELESAI,SEBAGIAN,BATAL} |
| `UpdateRealisasiPemupukanRequest` | Same as store, tanpa submission_token |
| `SaveRuleBaseRequest` | jenis_rule ∈ enum, sumber_judul required, tingkat_bukti required |

### 20.3 Perlindungan Data

- Observasi tidak bisa dihapus jika sudah dipakai di rekomendasi (audit trail)
- Realisasi tidak pernah dihapus — hanya status → BATAL (soft cancel)
- `program_pemupukan_id` di-lock saat pembuatan program (race condition protection)
- Submission token anti double-submit (UUID per form-load)

---

## 21. SEEDERS & DATA AWAL

### 21.1 `DatabaseSeeder`
Memanggil: `AdminSeeder` → `RuleBaseSeeder`

### 21.2 `AdminSeeder`
Membuat akun admin dari environment variable:
```
INITIAL_ADMIN_USERNAME=admin
INITIAL_ADMIN_PASSWORD=password
INITIAL_ADMIN_NAME=Administrator
```

### 21.3 `RuleBaseSeeder` (7 rule sistem bawaan)
Rule dengan `is_system_rule = true`, `status_validasi = TERVERIFIKASI_SUMBER`.
Menggunakan `firstOrCreate` — tidak menimpa perubahan yang sudah dilakukan admin.
Menghapus rule sistem lama yang kode-nya tidak ada di daftar saat ini.

### 21.4 `RuleBaseDosisSeeder` (25 rule PENENTU_DOSIS)
Rule untuk tahun ke-1 sampai ke-25 dengan dosis per umur.
`is_system_rule = true`. Dijalankan jika admin ingin mengaktifkan override dosis berbasis rule.
**Catatan:** Seeder ini menghapus semua rule `PENENTU_DOSIS` yang ada sebelum insert ulang.

### 21.5 `RuleTahapanSeeder`
Mengatur `tahap_eksekusi` pada rule yang sudah ada, dan membuat contoh rule `KOREKSI-N-01`
yang mendemonstrasikan working memory antar tahap (`prasyarat_fakta = {"status_nitrogen": "Defisiensi"}`).
`is_system_rule = false` — ini rule demonstrasi, bukan rule sistem bawaan.

### 21.6 `DemoSawitGisSeeder`
Data demo: anggota, blok, kondisi, realisasi. Digunakan untuk keperluan testing/demo.

---

## 22. ARTISAN COMMANDS OPERASIONAL

| Command | Fungsi Utama |
|---------|-------------|
| `php artisan sawit:health-check [--dry-run]` | Cek integritas: program ganda, rekomendasi orphan, realisasi tanpa program, sisa tidak sinkron |
| `php artisan sawit:audit-pahan-v2` | Audit konsistensi rule dan rekomendasi, cek rule legacy |
| `php artisan sawit:backup-database` | Backup MySQL ke `storage/app/backups/` |
| `php artisan sawit:backup-list` | Daftar file backup yang tersedia |
| `php artisan sawit:clear-cache` | Hapus config/view/route/app cache |
| `php artisan sawit:reset-demo-data [--dry-run]` | Bersihkan data demo (blok, kondisi, realisasi, rekomendasi) |

---

## 23. KEPUTUSAN DESAIN AKADEMIK (PENTING!)

### 23.1 Pemisahan Status yang Harus Dipahami

| Status | Sumber Tunggal | Tidak Boleh Dipengaruhi Oleh |
|--------|---------------|------------------------------|
| `status_kondisi_tanaman` | Rule `DIAGNOSIS_VISUAL` saja | Rule PEMBATAS_APLIKASI, PENENTU_DOSIS, PENENTU_METODE |
| `status_kelayakan_aplikasi` | `FertilizationWindowService` → bisa di-override ke `TUNDA_KONDISI_LAHAN` jika ada rule dengan `status_kebutuhan = 'Tunda'` | Tidak ada mekanisme lain |
| `status_stage` | `CurrentApplicationCalculator` (berbasis realisasi aktual) | Tidak dari browser/form |

### 23.2 Strategi Resolusi Konflik Rule

| Situasi | Strategi | Dokumentasi di Kode |
|---------|----------|---------------------|
| >1 rule `DIAGNOSIS_VISUAL` terpicu | Ambil `tingkat_keparahan` tertinggi (BERAT > SEDANG > RINGAN) | `tentukanStatusKondisiTanaman()`, docblock |
| >1 rule `PENENTU_DOSIS` terpicu | Ambil yang pertama by `prioritas` ASC (nilai integer kecil = prioritas tinggi). Rule lain dicatat ke `peringatan_json` | `susunHasil()` — updated v2.9 |
| Rule `KONDISI_LAHAN` vs `FertilizationWindowService` | Window service jalan dulu → rule override jika `status_kebutuhan = 'Tunda'`. Alasan digabung (tidak saling menghapus) | `susunHasil()` — updated v2.9 |
| >1 rule `PEMBATAS_APLIKASI` (curah hujan) | Cegah saat aktivasi: `RuleBaseController::findActiveConflict()` menolak aktivasi jika rentang curah hujan overlap | `RuleBaseController` |

### 23.3 Prinsip Dosis yang Tidak Boleh Dilanggar

1. Dosis Urea & KCl hanya dari **Tabel 9.13 & 9.14 Pahan 2013**
2. Gejala visual → menghasilkan **indikasi dan saran**, TIDAK mengubah angka dosis
3. Jenis tanah dan topografi → metadata blok, TIDAK jadi pengali dosis
4. Curah hujan → menentukan **waktu** aplikasi, TIDAK angka dosis
5. Pembagian 2 tahap 50%/50% → adaptasi operasional penelitian (PPKS 2021), bukan dari Pahan

### 23.4 Jenis Rule LAHAN-CUSTOM (Admin-Created)

Rule dengan prefix `LAHAN-CUSTOM-` dibuat admin melalui menu Rule Base, `jenis_rule = 'KONDISI_LAHAN'`, `is_system_rule = false`.
Contoh di Tabel 17 skripsi: LAHAN-CUSTOM-001..004 (gulma, tergenang, kering, hama).
Ini mendemonstrasikan fleksibilitas basis pengetahuan yang bisa diperluas admin non-programmer.

### 23.5 Working Memory & Fakta Antara

Secara arsitektural, sistem mendukung chaining fakta antar tahap via `fakta_yang_dihasilkan` (JSON) dan `prasyarat_fakta` (JSON) di setiap rule. Contoh implementasi: rule `KOREKSI-N-01` di `RuleTahapanSeeder`.

**7 rule sistem bawaan tidak menggunakan fakta antara** — mereka mengevaluasi kondisi lapangan langsung (data-driven satu langkah). Ini disebut secara eksplisit di naskah sebagai "menghasilkan kesimpulan langsung dari fakta kondisi daun atau curah hujan."

---

## RINGKASAN VERIFIKASI KONSISTENSI KODE vs NASKAH SKRIPSI

*(Dihasilkan dari audit kode aktual — 2026-08-11)*

| Klaim Naskah | Status di Kode | Catatan |
|-------------|---------------|---------|
| RBS menggunakan Forward Chaining | ✅ Terverifikasi | `RbsService::eksekusiTahap()` — 3 tahap eksplisit |
| Observasi otomatis memicu analisis | ✅ Terverifikasi | `KondisiLahanController::store()` memanggil `$this->rbsService->analisis($blok)` |
| 7 rule sistem bawaan | ✅ Terverifikasi | `RuleBaseSeeder` — 7 rule + 3 topografi = 10 total di seeder |
| LAHAN-CUSTOM-001..004 di Tabel 17 | ✅ Terkonfirmasi rule admin | `is_system_rule = false`, dibuat via UI bukan seeder |
| Dosis dari Pahan 2013, tidak terpengaruh topografi/jenis tanah | ✅ Terverifikasi | `PahanDoseReferenceService` — tidak ada multiplier tanah/topografi |
| LaporanController tidak pakai `status_kebutuhan_dominan` | ✅ Terverifikasi | Pakai `operational_boleh_mencatat` berbasis `status_stage` |
| Sistem mendukung fakta antara (working memory) | ✅ Arsitektural | Kolom `fakta_yang_dihasilkan` & `prasyarat_fakta` ada. 7 rule bawaan tidak menggunakannya (sesuai klaim naskah hal. 22) |
| Conflict resolution: keparahan tertinggi untuk DIAGNOSIS_VISUAL | ✅ Terverifikasi + Terdokumentasi | `tentukanStatusKondisiTanaman()` — sorted by severity descending |
| Conflict resolution: rule PENENTU_DOSIS | ✅ Diperbaiki + Terdokumentasi | Pakai prioritas ASC, konflik dicatat ke `peringatan_json` |
| Alur konflik window service vs rule KONDISI_LAHAN | ✅ Deterministik, konsisten | Window jalan dulu, rule override jika Tunda, alasan digabung |

