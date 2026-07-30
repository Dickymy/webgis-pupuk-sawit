# SawitGIS

SawitGIS adalah WebGIS untuk membantu pencatatan kondisi blok, analisis berbasis rule, perhitungan kebutuhan Urea dan KCl, pelaksanaan pemupukan, serta laporan kelompok tani kelapa sawit. Proyek ini dibuat sebagai aplikasi skripsi menggunakan Laravel 11 dan metode forward chaining.

## Ruang lingkup

Alur utama aplikasi:

1. Admin mencatat anggota dan blok lahan.
2. Admin mengisi observasi lapangan dan data hujan.
3. Sistem mengevaluasi rule aktif dari fakta observasi.
4. Sistem menghitung kebutuhan Urea dan KCl menurut umur, fase, luas, dan SPH.
5. Sistem memeriksa kesiapan berdasarkan hujan, kondisi lapangan, dan interval.
6. Admin mencatat realisasi pemupukan.
7. Sistem menyediakan riwayat dan laporan PDF.

Keputusan akademik dipisahkan dengan jelas:

- Rule visual hanya menghasilkan indikasi awal dan saran pemeriksaan.
- Rule hujan menentukan kesiapan waktu, bukan angka dosis.
- Dosis Urea dan KCl berasal dari acuan Iyung Pahan (2013), bukan dari gejala visual.
- Jenis tanah dan topografi disimpan sebagai identitas blok, tetapi tidak menjadi pengali dosis.
- Hasil sistem adalah pendukung keputusan dan bukan pengganti analisis laboratorium atau ahli agronomi.

## Teknologi

- PHP 8.2 dan Laravel 11
- Blade dan Tailwind CSS 4
- Leaflet untuk peta
- Turf.js untuk pemeriksaan polygon
- Dompdf untuk laporan PDF
- MySQL atau SQLite
- PHPUnit untuk pengujian

## Bagian kode utama

| Bagian | Tanggung jawab |
|---|---|
| `app/Services/RbsService.php` | Menjalankan forward chaining dan menyimpan hasil analisis |
| `app/Services/PahanDoseReferenceService.php` | Menyediakan rentang dosis berdasarkan umur dan fase |
| `app/Services/FertilizationWindowService.php` | Memeriksa kesiapan waktu pemupukan |
| `app/Services/CurrentApplicationCalculator.php` | Menghitung kebutuhan pada tahap aktif |
| `app/Services/FertilizationRealizationService.php` | Mengolah realisasi dan sisa kebutuhan |
| `database/seeders/RuleBaseSeeder.php` | Menyediakan tujuh rule sistem bersumber |
| `config/observation.php` | Menyamakan pilihan kondisi daun pada observasi dan rule |
| `config/fertilization.php` | Menyimpan dosis, interval, dan konfigurasi perhitungan |

Penjelasan lebih lengkap tersedia di [docs/ARSITEKTUR_SISTEM.md](docs/ARSITEKTUR_SISTEM.md).

## Instalasi

```bash
git clone <alamat-repository>
cd <folder-repository>
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Atur koneksi database dan akun admin pada `.env`, lalu jalankan:

```bash
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

Variabel akun awal:

```env
INITIAL_ADMIN_USERNAME=admin
INITIAL_ADMIN_PASSWORD=password_minimal_8_karakter
INITIAL_ADMIN_NAME=Administrator
```

## Pemeriksaan kualitas

```bash
vendor/bin/pint --test
php artisan test
npm run build
php artisan sawit:health-check --dry-run
```

Command operasional yang dipertahankan:

| Command | Fungsi |
|---|---|
| `php artisan sawit:audit-pahan-v2` | Memeriksa konsistensi rule dan rekomendasi |
| `php artisan sawit:health-check --dry-run` | Memeriksa kesehatan database tanpa mengubah data |
| `php artisan sawit:backup-database` | Membuat backup database MySQL |
| `php artisan sawit:backup-list` | Menampilkan daftar backup |
| `php artisan sawit:clear-cache` | Membersihkan cache aplikasi |
| `php artisan sawit:reset-demo-data --dry-run` | Melihat data demo yang dapat dibersihkan |

## Dokumentasi aktif

- [Arsitektur sistem](docs/ARSITEKTUR_SISTEM.md)
- [Matriks sumber rule](docs/MATRIKS_SUMBER_RULE_RBS.md)
- [Panduan admin](docs/PANDUAN_ADMIN.md)
- [Panduan pengujian](docs/PANDUAN_PENGUJIAN.md)
- [Keterbatasan sistem](docs/KETERBATASAN_SISTEM.md)
- Diagram DFD, ERD, dan relasi tabel berada di folder `docs`.

## Keamanan repository

File `.env`, kredensial, backup, hasil build, dependency lokal, dan unggahan pengguna tidak disimpan di Git. Gunakan `.env.example` sebagai contoh konfigurasi.

## Lisensi

Proyek ini dibuat untuk keperluan akademik (skripsi).
