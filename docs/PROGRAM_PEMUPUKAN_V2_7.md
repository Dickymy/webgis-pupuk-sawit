# PROGRAM PEMUPUKAN TAHUNAN — Pahan v2.7

## Konsep

Program pemupukan adalah entitas yang mengidentifikasi satu siklus pemupukan tahunan untuk satu blok lahan. Setiap blok hanya boleh memiliki **satu program aktif per tahun**.

## Tujuan

1. Mencegah pencampuran realisasi antarprogram/tahun
2. Memastikan isolasi data — realisasi tahun lalu tidak memengaruhi tahun ini
3. Memberikan audit trail yang jelas per siklus pemupukan

## Schema

```sql
program_pemupukans:
  id              BIGINT PRIMARY KEY
  uuid            UUID UNIQUE
  blok_lahan_id   FK → blok_lahans
  tahun_program   SMALLINT
  rekomendasi_awal_id  FK → rekomendasi_rbs (nullable)
  status_program  ENUM('AKTIF', 'SELESAI', 'DIBATALKAN', 'ARSIP')
  created_at      TIMESTAMP
  updated_at      TIMESTAMP
```

## Status Program

| Status | Deskripsi |
|--------|-----------|
| AKTIF | Program sedang berjalan, realisasi dapat dicatat |
| SELESAI | Kebutuhan tahunan terpenuhi |
| DIBATALKAN | Program dibatalkan (misalnya replanting) |
| ARSIP | Program lama yang diarsipkan |

## Aturan Bisnis

1. **Satu blok satu program aktif per tahun** — Tidak boleh ada 2 program AKTIF untuk blok yang sama di tahun yang sama.
2. **Auto-create** — Program otomatis dibuat saat realisasi pertama dicatat untuk blok/tahun tertentu.
3. **Reuse** — Analisis baru dalam program yang sama tetap memakai program yang sama.
4. **Isolasi** — Realisasi hanya dihitung dalam program yang sama.
5. **Legacy** — Data lama tanpa program_pemupukan_id tetap berfungsi (fallback berdasarkan tahun kalender).
6. **Selesai tidak bisa dibuka** — Program selesai tidak bisa dibuka kembali tanpa proses eksplisit.

## Relasi

```
BlokLahan hasMany ProgramPemupukan
ProgramPemupukan hasMany RekomendasiRbs
ProgramPemupukan hasMany RealisasiPemupukan
```

## Integrasi

- `RealisasiPemupukanController::store()` memanggil `ensureProgram()` sebelum menyimpan
- `FertilizationRealizationService` memfilter berdasarkan `program_pemupukan_id` (future)
- `RecommendationOperationalRefreshService` menyertakan `program_pemupukan_id` dalam fingerprint
