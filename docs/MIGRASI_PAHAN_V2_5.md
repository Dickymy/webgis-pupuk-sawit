# Migrasi Pahan v2.5

## Prasyarat

- PHP 8.2+
- Laravel 11
- Database MySQL 8.0 atau SQLite (testing)

## Langkah Migrasi

```bash
# 1. Pull branch terbaru
git pull origin fix/finalisasi-operasional-pahan-v2-5

# 2. Install dependencies
composer install
npm ci && npm run build

# 3. Jalankan migrasi (aman, tidak destructive)
php artisan migrate

# 4. Verifikasi dengan audit
php artisan sawit:finalize-pahan-v2-5 --dry-run

# 5. (Opsional) Perbaiki data lama — hanya field aman
php artisan sawit:finalize-pahan-v2-5
```

## Migration Files

### 2026_07_20_000001_upgrade_realisasi_pemupukans_for_v25

Menambahkan field ke tabel `realisasi_pemupukans`:
- `blok_lahan_id` (FK nullable)
- `tahap` (1 atau 2)
- `urea_rencana_kg`
- `kcl_rencana_kg`
- `urea_realisasi_kg`
- `kcl_realisasi_kg`
- `status_realisasi` (SELESAI/SEBAGIAN/BATAL)

### 2026_07_20_000002_add_v25_fields_to_rekomendasi_rbs_table

Menambahkan field ke tabel `rekomendasi_rbs`:
- `luas_ha_snapshot` (nullable)
- `sph_snapshot` (nullable)
- `active_stage` (nullable, 1 atau 2)
- `status_stage` (nullable, enum string)
- `urea_sisa_tahunan` (nullable)
- `kcl_sisa_tahunan` (nullable)
- `tanggal_minimum_tahap_berikutnya` (nullable)
- `alasan_tahap` (nullable)

## Keamanan Data

- Semua field baru **nullable** atau memiliki default aman
- Data lama TIDAK dihapus atau dimodifikasi
- Rule pengguna tetap utuh
- Histori rekomendasi tetap utuh
- Tidak ada `migrate:fresh` pada database produksi
- Rollback tersedia untuk semua migration

## Rollback

```bash
php artisan migrate:rollback --step=2
```

Ini akan menghapus field v2.5 saja, data lama tetap aman.
