# Panduan Migrasi ke Pahan-V2

## Prasyarat

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Laravel 11
- Backup database sudah dilakukan

## Langkah-Langkah

### 1. Backup Database

```bash
mysqldump -u root -p sawitgis > backup_sawitgis_$(date +%Y%m%d).sql
```

Atau backup via panel hosting.

### 2. Checkout Branch

```bash
git fetch origin
git checkout refactor/pahan-alignment
```

### 3. Install Dependencies (jika ada perubahan)

```bash
composer install --no-dev --optimize-autoloader
```

### 4. Jalankan Migration

```bash
php artisan migrate
```

Migration yang akan dijalankan:
- `2026_07_20_000001_add_pahan_v2_fields_to_blok_lahans_table` — kolom fase_tanaman
- `2026_07_20_000002_add_pahan_v2_fields_to_kondisi_lahans_table` — curah hujan numerik
- `2026_07_20_000003_add_pahan_v2_provenance_to_rule_bases_lanjutan_table` — metadata rule
- `2026_07_20_000004_add_pahan_v2_fields_to_rekomendasi_rbs_table` — snapshot dosis

Semua migration hanya menambah kolom (nullable). Data lama TIDAK terpengaruh.

### 5. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 6. Jalankan Test

```bash
php artisan test --filter=PahanDoseReferenceTest
```

Expected: 22 tests, 61 assertions, all PASS.

### 7. Build Assets (jika diperlukan)

```bash
npm run build
```

### 8. Verifikasi Aplikasi

- Buka dashboard → peta tetap menampilkan polygon
- Buka halaman analisis RBS → jalankan analisis pada satu blok
- Periksa hasil: harus menampilkan rentang dosis, bukan angka tunggal
- Periksa histori lama: masih dapat dilihat dengan label `legacy-v1`

## Rollback

Jika terjadi masalah:

```bash
php artisan migrate:rollback --step=4
```

Ini akan menghapus 4 kolom baru tanpa mempengaruhi data lama.

## Catatan Penting

- Data rekomendasi lama tetap tersimpan dan tidak berubah
- Rekomendasi lama otomatis dilabeli `versi_mesin_rekomendasi = legacy-v1`
- Analisis ulang akan menggunakan mesin `pahan-v2`
- Multiplier lama tersimpan di `config/fertilization.php` → `legacy_multipliers` (nonaktif)
- Blok yang belum diisi fase_tanaman akan diminta verifikasi saat analisis
