# Migrasi Database — Pahan v2.6

## Migration Baru

### `2026_07_21_000001_upgrade_realisasi_pemupukans_for_v26.php`

Menambahkan field validasi override dan tahun program:

| Kolom | Tipe | Default | Keterangan |
|-------|------|---------|------------|
| tahun_program | SMALLINT | NULL | Tahun program pemupukan |
| confirmed_over_plan | BOOLEAN | false | Konfirmasi over-plan |
| override_annual_limit | BOOLEAN | false | Override batas tahunan |
| override_reason | TEXT | NULL | Alasan override |

### Backfill Otomatis
Migration secara otomatis mengisi `tahun_program` dari `YEAR(tanggal_realisasi)` untuk data lama.

## Fix Migration v2.5

### `upgrade_realisasi_pemupukans_for_v25.php`
- **Fix rollback**: `dropForeign(['blok_lahan_id'])` sebelum `dropColumn()`
- **Fix data copy**: Handling `jumlah_urea_realisasi` dan `jumlah_kcl_realisasi` secara terpisah

## Cara Menjalankan

```bash
# Pada database yang sudah memiliki v2.5:
php artisan migrate

# Pada database baru (CI/testing):
php artisan migrate:fresh --env=testing --force
```

## Rollback

```bash
# Rollback v2.6 saja:
php artisan migrate:rollback --path=database/migrations/2026_07_21_000001_upgrade_realisasi_pemupukans_for_v26.php

# Rollback v2.5 + v2.6 (urutan penting):
php artisan migrate:rollback --path=database/migrations/2026_07_21_000001_upgrade_realisasi_pemupukans_for_v26.php
php artisan migrate:rollback --path=database/migrations/2026_07_20_000002_add_v25_fields_to_rekomendasi_rbs_table.php
php artisan migrate:rollback --path=database/migrations/2026_07_20_000001_upgrade_realisasi_pemupukans_for_v25.php
```

## Verifikasi

```bash
php artisan sawit:finalize-pahan-v2-6 --dry-run
```
