# MIGRASI SCHEMA — Pahan v2.7

## Migration Files

| No | File | Deskripsi |
|----|------|-----------|
| 1 | `2026_07_22_000001_create_program_pemupukans_table.php` | Buat tabel program_pemupukans + tambah FK program_pemupukan_id ke rekomendasi_rbs dan realisasi_pemupukans |
| 2 | `2026_07_22_000002_create_rekomendasi_operasional_histories_table.php` | Buat tabel histori operasional |

## Perubahan Schema

### Tabel Baru: program_pemupukans
- id, uuid, blok_lahan_id (FK), tahun_program, rekomendasi_awal_id (FK nullable), status_program, timestamps
- Index: blok_lahan_id + tahun_program + status_program

### Tabel Baru: rekomendasi_operasional_histories
- id, rekomendasi_rbs_id (FK), program_pemupukan_id (FK nullable), event_type, active_stage, status_stage, urea/kcl fields, tanggal, alasan, fingerprint, source_realisasi_id (FK nullable), created_at
- Index: rekomendasi_rbs_id + created_at, program_pemupukan_id + event_type

### Perubahan: rekomendasi_rbs
- Tambah kolom: program_pemupukan_id (FK nullable)

### Perubahan: realisasi_pemupukans
- Tambah kolom: program_pemupukan_id (FK nullable)

## Rollback

Rollback aman:
1. Drop FK program_pemupukan_id dari realisasi_pemupukans
2. Drop FK program_pemupukan_id dari rekomendasi_rbs
3. Drop tabel rekomendasi_operasional_histories
4. Drop tabel program_pemupukans

Data legacy tetap aman karena kolom baru nullable.

## Backfill

Data lama tanpa program_pemupukan_id tetap berfungsi:
- FertilizationRealizationService menggunakan fallback berdasarkan tahun kalender
- Program hanya wajib untuk data baru (v2.7+)

## Urutan Rollback di CI

```bash
php artisan migrate:rollback --path=database/migrations/2026_07_22_000002_create_rekomendasi_operasional_histories_table.php
php artisan migrate:rollback --path=database/migrations/2026_07_22_000001_create_program_pemupukans_table.php
php artisan migrate:rollback --path=database/migrations/2026_07_21_000001_upgrade_realisasi_pemupukans_for_v26.php
php artisan migrate:rollback --path=database/migrations/2026_07_20_000002_add_v25_fields_to_rekomendasi_rbs_table.php
php artisan migrate:rollback --path=database/migrations/2026_07_20_000001_upgrade_realisasi_pemupukans_for_v25.php
```
