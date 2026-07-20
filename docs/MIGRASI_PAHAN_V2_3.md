# PANDUAN MIGRASI PAHAN v2.3

## Langkah Upgrade dari v2.2

### 1. Backup Database
```bash
mysqldump -u root -p sawitgis > backup_sebelum_v23.sql
```

### 2. Pull Kode Terbaru
```bash
git pull origin fix/penyempurnaan-pahan-v2-3
```

### 3. Install Dependencies
```bash
composer install
npm ci
npm run build
```

### 4. Jalankan Audit (Dry Run)
```bash
php artisan sawit:finalize-pahan-v2-3 --dry-run
```

### 5. Jalankan Migration
```bash
php artisan migrate
```

### 6. Jalankan Finalisasi (Live)
```bash
php artisan sawit:finalize-pahan-v2-3
```

### 7. Clear Cache
```bash
php artisan optimize:clear
```

### 8. Re-analisis Blok (Opsional)
Blok yang sudah dianalisis tidak perlu di-re-analisis. Analisis baru akan otomatis menggunakan mesin v2.3.

## Rollback

```bash
php artisan migrate:rollback --step=1
```

Migration v2.3 hanya menambah kolom nullable — rollback aman.

## Kompatibilitas

- Field lama (`dosis_urea`, `total_urea`, dll) tetap diisi untuk backward compatibility
- Histori rekomendasi lama tidak diubah
- Rule buatan pengguna tidak dihapus
- WebGIS, GeoJSON, PDF, dark mode tetap berjalan

## Testing

```bash
php artisan migrate:fresh --env=testing
php artisan test
php artisan migrate:rollback --step=1 --env=testing
php artisan migrate --env=testing
```

Jangan jalankan `migrate:fresh` pada database production.
