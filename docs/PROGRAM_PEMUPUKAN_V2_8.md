# Program Pemupukan — Pahan v2.8

## Konsep

Program Pemupukan adalah identitas tahunan yang mengelompokkan seluruh aktivitas pemupukan (rekomendasi + realisasi) untuk satu blok lahan dalam satu tahun.

## Aturan Utama

1. **Satu program aktif per blok per tahun** — dijamin oleh constraint `active_key` UNIQUE.
2. **Realisasi hanya dihitung dalam program yang sama** — tidak mencampur antarprogram.
3. **Program otomatis selesai** jika kebutuhan Urea dan KCl tahunan terpenuhi.
4. **Program yang sudah selesai tidak dibuka otomatis** — perlu tindakan eksplisit admin.

## Siklus Hidup

```
AKTIF → (realisasi mencukupi) → SELESAI
AKTIF → (admin membatalkan) → DIBATALKAN
SELESAI → (admin membuka ulang) → AKTIF (perlu alasan)
```

## Integrasi dengan Analisis

Setiap kali `RbsService::analisis()` dijalankan:
1. Resolve program aktif untuk blok dan tahun berjalan
2. Ambil ringkasan realisasi berbasis program (`getRealizationSummaryForProgram`)
3. Hitung tahap aktif berdasarkan realisasi dalam program tersebut
4. Simpan `program_pemupukan_id` pada rekomendasi baru

## Integrasi dengan Realisasi

1. Evaluasi kelayakan menggunakan program dari rekomendasi
2. Realisasi disimpan dengan `program_pemupukan_id` yang sama
3. Setelah realisasi, `ProgramStatusService` sinkronisasi status
4. Histori operasional dicatat pada setiap perubahan

## Pencegahan Program Aktif Ganda

- Field `active_key` = `{blok_lahan_id}-{tahun_program}` saat AKTIF, `null` saat tidak aktif
- UNIQUE constraint pada `active_key` di level database
- `lockForUpdate()` pada query saat resolve program baru
- Migration backfill arsipkan duplikat yang sudah ada

## Service Baru

### ProgramPemupukanService

- `resolveActiveProgram(BlokLahan, int tahun, ?RekomendasiRbs)` — cari atau buat program
- `getActiveProgram(BlokLahan, int tahun)` — ambil tanpa membuat baru

### ProgramStatusService

- `synchronizeStatus(ProgramPemupukan, array currentApp)` — sinkronisasi status berdasarkan sisa kebutuhan
