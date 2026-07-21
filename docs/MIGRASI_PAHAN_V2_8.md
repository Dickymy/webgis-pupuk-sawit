# Migrasi Pahan v2.8

## Migration Baru

### `2026_07_23_000001_add_active_key_to_program_pemupukans_table.php`

**Tujuan**: Mencegah program aktif ganda melalui database constraint.

**Perubahan**:
- Tambah kolom `active_key` (VARCHAR 50, nullable, UNIQUE) ke `program_pemupukans`
- Backfill: Program AKTIF mendapat `active_key = "{blok_lahan_id}-{tahun_program}"`
- Duplikat diarsipkan otomatis

**Rollback**:
- Drop UNIQUE index
- Drop kolom `active_key`

## Langkah Migrasi

### Dari v2.7 ke v2.8

```bash
# 1. Backup database
mysqldump -u root -p sawitgis > backup_v27.sql

# 2. Jalankan migration
php artisan migrate

# 3. Jalankan audit
php artisan sawit:finalize-pahan-v2-8 --dry-run

# 4. Jika ada masalah, rollback
php artisan migrate:rollback --step=1
```

### Rollback Aman

```bash
# Rollback v2.8 saja
php artisan migrate:rollback --path=database/migrations/2026_07_23_000001_add_active_key_to_program_pemupukans_table.php

# Data tetap aman karena:
# - Kolom active_key nullable, jadi tidak menghapus data
# - Program yang diarsipkan tetap tersimpan
```

## Catatan Penting

- Migration TIDAK menghapus data apapun
- Migration REVERSIBLE (bisa rollback)
- Data legacy tanpa `program_pemupukan_id` tetap aman (nullable FK)
- Backfill hanya mempengaruhi program yang sudah AKTIF
