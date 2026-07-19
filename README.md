# SawitGIS — WebGIS Sistem Pemupukan Kelapa Sawit

> **Skripsi:** Rancang Bangun WebGIS Sistem Pemupukan Kelapa Sawit Menggunakan Metode Rule-Based System

## Ringkasan

SawitGIS adalah aplikasi berbasis web yang menggabungkan Geographic Information System (GIS) dengan Rule-Based System (RBS) untuk memberikan rekomendasi pemupukan kelapa sawit secara spesifik per blok lahan. Sistem menggunakan pendekatan Forward Chaining untuk mengevaluasi kondisi tanaman dan lingkungan, kemudian menentukan kebutuhan pupuk berdasarkan tabel referensi Iyung Pahan (2013).

## Fitur Utama

- **WebGIS Interaktif** — Peta blok lahan dengan polygon GeoJSON, visualisasi status per blok
- **Analisis Rule-Based System** — Forward Chaining dengan 25 rule berbasis gejala visual, pH, iklim, dan umur
- **Referensi Dosis Pahan (2013)** — Rentang dosis Urea & KCl dari Tabel 9.13 & 9.14
- **Kelayakan Waktu Aplikasi** — Evaluasi curah hujan, interval, dan drainase
- **Dual Status** — Kondisi Tanaman (gejala) dan Kelayakan Pemupukan (waktu) dipisahkan
- **Skor Kelengkapan & Keandalan Data** — Transparansi kualitas data input
- **Jadwal Pemupukan** — Split aplikasi 2x/tahun dengan estimasi bulan
- **Histori Analisis** — Fingerprint SHA-256, tidak duplikat jika hasil sama
- **Laporan PDF** — Export rekomendasi dengan snapshot data saat analisis
- **Data Cuaca Otomatis** — Integrasi Open-Meteo API (estimasi berbasis koordinat)
- **Multi-format Upload** — GeoJSON dan Shapefile (ZIP)

## Arsitektur Sistem

```
Laravel 11 + Blade + Tailwind CSS 4 + Leaflet.js
├── Services/
│   ├── RbsService.php              — Orchestrator analisis utama
│   ├── PahanDoseReferenceService   — Rentang dosis dari tabel Pahan
│   ├── FertilizationWindowService  — Evaluasi kelayakan waktu
│   ├── FertilizationCalculationService — Hitung total per blok
│   ├── RecommendationReliabilityService — Skor keandalan
│   └── PlantPhaseResolver          — Resolusi fase TBM/TM
├── Models/
│   ├── BlokLahan, KondisiLahan, RekomendasiRbs
│   └── RuleBaseLanjutan (dengan provenance metadata)
└── Controllers/
    ├── DashboardController  — WebGIS + statistik
    ├── RbsController        — Analisis & detail
    ├── LaporanController    — PDF export
    └── CuacaController      — Open-Meteo integration
```

## Alur Forward Chaining

1. Ambil kondisi lahan terbaru (observasi visual + lingkungan)
2. Cek kecukupan data minimum
3. Evaluasi setiap rule aktif (AND logic, semua kondisi di rule harus cocok)
4. Rule Chaining: rule dapat mengaktifkan intermediate flag untuk rule berikutnya
5. Tentukan status kondisi tanaman dari rule terpicu
6. Evaluasi kelayakan waktu (curah hujan, interval, drainase)
7. Hitung dosis dari tabel Pahan (tanpa multiplier)
8. Hitung skor keandalan data
9. Simpan dengan fingerprint histori

## Penjelasan Pahan-v2

Versi mesin rekomendasi `pahan-v2` menggunakan **rentang dosis** dari buku Iyung Pahan (2013), Tabel 9.13 & 9.14:

| Fase | Umur | Urea (kg/pokok/tahun) | KCl (kg/pokok/tahun) |
|------|------|-----------------------|----------------------|
| TBM | 1 tahun | 0.50–0.70 | 0.75–1.25 |
| TBM | 2 tahun | 0.70–0.85 | 1.00–1.75 |
| TBM | 3 tahun | 0.90–1.25 | 1.20–2.25 |
| TM | 3–5 tahun | 0.90–1.75 | 1.20–2.50 |
| TM | 6–15 tahun | 1.00–3.00 | 1.50–3.50 |
| TM | >15 tahun | 1.50–2.50 | 1.50–2.25 |

**Penting:**
- Multiplier jenis tanah, topografi, dan waktu **TIDAK aktif** di pahan-v2
- Dosis kuantitatif Urea/KCl **hanya** berasal dari `PahanDoseReferenceService`
- Rule menentukan diagnosis dan tindakan, bukan angka dosis Urea/KCl
- Strategi estimasi default: `midpoint` (titik tengah rentang)

## Instalasi Lokal

```bash
# Clone repository
git clone https://github.com/Dickymy/webgis-pupuk-sawit.git
cd webgis-pupuk-sawit

# Install dependencies
composer install
npm install

# Konfigurasi
cp .env.example .env
php artisan key:generate

# Edit .env: set database, admin credentials
# DB_DATABASE=sawit_spk
# INITIAL_ADMIN_USERNAME=admin
# INITIAL_ADMIN_PASSWORD=minimal8karakter
# INITIAL_ADMIN_NAME=Administrator

# Database
php artisan migrate
php artisan db:seed

# Migrasi data lama ke Pahan-v2 (opsional, jika ada data lama)
php artisan sawit:migrate-pahan-v2 --dry-run
php artisan sawit:migrate-pahan-v2

# Build frontend
npm run build

# Jalankan
php artisan serve
```

## Konfigurasi .env

```env
# Akun Admin Awal (wajib untuk db:seed)
INITIAL_ADMIN_USERNAME=admin
INITIAL_ADMIN_PASSWORD=password_min_8_karakter
INITIAL_ADMIN_NAME=Administrator

# Akun Tester (hanya development, diabaikan di production)
CREATE_TESTER_ACCOUNT=false
TESTER_USERNAME=
TESTER_PASSWORD=

# Strategi estimasi dosis: minimum, midpoint, maximum
DOSE_STRATEGY=midpoint
```

## Command Artisan

| Command | Fungsi |
|---------|--------|
| `php artisan db:seed` | Seed admin + rule base + provenance |
| `php artisan sawit:migrate-pahan-v2` | Migrasi data lama ke format v2 |
| `php artisan sawit:migrate-pahan-v2 --dry-run` | Preview tanpa eksekusi |
| `php artisan sawit:audit-pahan-v2` | Audit konsistensi data |
| `php artisan sawit:clear-cache` | Bersihkan semua cache |
| `php artisan test` | Jalankan test suite |

## Menjalankan Test

```bash
php artisan test
```

Test mencakup:
- **Unit**: PlantPhaseResolver, PahanDoseReference, FertilizationWindow, RecommendationReliability
- **Feature**: Security (route publik dihapus, auth), login

## Build Frontend

```bash
npm run build    # Production
npm run dev      # Development (hot reload)
```

## Keamanan Production

- Tidak ada route maintenance publik (`/setup-database`, `/seed-tester`, `/fix-cache` telah dihapus)
- Kredensial admin hanya dari environment variable
- Password minimal 8 karakter
- Akun tester tidak dibuat di production
- Session dan CSRF protection aktif

## Disclaimer Akademik

Rekomendasi yang dihasilkan sistem ini merupakan **estimasi awal** berbasis data blok, observasi visual, kondisi lingkungan, dan basis aturan. Hasil ini **tidak menggantikan** analisis laboratorium tanah/daun maupun keputusan ahli agronomi profesional.

## Lisensi

Proyek ini dibuat untuk keperluan akademik (skripsi). Hak cipta © 2026.


## Penjelasan Pahan-v2

Versi mesin rekomendasi `pahan-v2` menggunakan **rentang dosis** dari buku Iyung Pahan (2013), Tabel 9.13 & 9.14:

| Fase | Umur | Urea (kg/pokok/tahun) | KCl (kg/pokok/tahun) |
|------|------|-----------------------|----------------------|
| TBM | 1 tahun | 0.50–0.70 | 0.75–1.25 |
| TBM | 2 tahun | 0.70–0.85 | 1.00–1.75 |
| TBM | 3 tahun | 0.90–1.25 | 1.20–2.25 |
| TM | 3–5 tahun | 0.90–1.75 | 1.20–2.50 |
| TM | 6–15 tahun | 1.00–3.00 | 1.50–3.50 |
| TM | >15 tahun | 1.50–2.50 | 1.50–2.25 |

**Penting:**
- Multiplier jenis tanah, topografi, dan waktu **TIDAK aktif** di pahan-v2
- Dosis kuantitatif Urea/KCl **hanya** berasal dari `PahanDoseReferenceService`
- Rule menentukan diagnosis dan tindakan, bukan angka dosis Urea/KCl
- Strategi estimasi default: `midpoint` (titik tengah rentang)

## Instalasi Lokal

```bash
# Clone repository
git clone https://github.com/Dickymy/webgis-pupuk-sawit.git
cd webgis-pupuk-sawit

# Install dependencies
composer install
npm install

# Konfigurasi
cp .env.example .env
php artisan key:generate

# Edit .env: set database + admin credentials

# Database
php artisan migrate
php artisan db:seed

# Migrasi data lama ke Pahan-v2 (opsional)
php artisan sawit:migrate-pahan-v2 --dry-run
php artisan sawit:migrate-pahan-v2

# Build frontend
npm run build

# Jalankan
php artisan serve
```

## Konfigurasi .env

```env
# Akun Admin Awal (wajib untuk db:seed)
INITIAL_ADMIN_USERNAME=admin
INITIAL_ADMIN_PASSWORD=password_min_8_karakter
INITIAL_ADMIN_NAME=Administrator

# Akun Tester (development only, diabaikan di production)
CREATE_TESTER_ACCOUNT=false
TESTER_USERNAME=
TESTER_PASSWORD=

# Strategi estimasi dosis: minimum, midpoint, maximum
DOSE_STRATEGY=midpoint
```

## Command Artisan

| Command | Fungsi |
|---------|--------|
| `php artisan db:seed` | Seed admin + rule base + provenance |
| `php artisan sawit:migrate-pahan-v2` | Migrasi data lama ke format v2 |
| `php artisan sawit:migrate-pahan-v2 --dry-run` | Preview tanpa eksekusi |
| `php artisan sawit:audit-pahan-v2` | Audit konsistensi data |
| `php artisan sawit:clear-cache` | Bersihkan semua cache |
| `php artisan test` | Jalankan test suite |

## Menjalankan Test

```bash
php artisan test
```

## Build Frontend

```bash
npm run build
```

## Keamanan Production

- Route maintenance publik telah dihapus
- Kredensial admin hanya dari environment variable
- Password minimal 8 karakter
- Akun tester tidak dibuat di production

## Disclaimer Akademik

Rekomendasi yang dihasilkan sistem ini merupakan **estimasi awal** berbasis data blok, observasi visual, kondisi lingkungan, dan basis aturan. Hasil ini **tidak menggantikan** analisis laboratorium tanah/daun maupun keputusan ahli agronomi profesional.

## Lisensi

Proyek ini dibuat untuk keperluan akademik (skripsi). Hak cipta © 2026.
