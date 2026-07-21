# Backup dan Pemulihan — SawitGIS v2.9

## Backup Database

### Membuat Backup

```bash
php artisan sawit:backup-database
```

Hasil tersimpan di `storage/app/backups/` dengan format nama:
```
backup_sawit_spk_2026-07-21_143000.sql
```

### Melihat Daftar Backup

```bash
php artisan sawit:backup-list
```

### Lokasi Penyimpanan

- Path: `storage/app/backups/`
- TIDAK berada di `public/` — tidak dapat diakses via browser
- Format: SQL dump (mysqldump)

## Pemulihan Database

Pemulihan dilakukan secara manual melalui terminal:

```bash
mysql -u root -p sawit_spk < storage/app/backups/backup_sawit_spk_2026-07-21_143000.sql
```

### Langkah Pemulihan

1. Pastikan backup tersedia: `php artisan sawit:backup-list`
2. Stop akses ke aplikasi (maintenance mode): `php artisan down`
3. Restore: `mysql -u root -p nama_database < path/ke/file.sql`
4. Jalankan migration jika ada perubahan schema: `php artisan migrate`
5. Buka kembali: `php artisan up`

## Catatan Keamanan

- File backup berisi seluruh data termasuk password (hashed)
- Jangan upload backup ke repository atau folder public
- Backup secara rutin sebelum pengujian lapangan
- Simpan backup di lokasi terpisah dari server
