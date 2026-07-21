# Audit Pahan v2.8

## Command

```bash
php artisan sawit:finalize-pahan-v2-8 --dry-run
```

## Pemeriksaan yang Dilakukan

| # | Audit | Deskripsi |
|---|-------|-----------|
| 1 | Config version | `engine_version` harus `pahan-v2.8` |
| 2 | Rekomendasi tanpa program | Rekomendasi terbaru (dengan dosis) wajib punya `program_pemupukan_id` |
| 3 | Mismatch program | `realisasi.program_pemupukan_id` harus sama dengan `rekomendasi.program_pemupukan_id` |
| 4 | Program aktif ganda | Tidak boleh ada >1 program AKTIF per blok/tahun |
| 5 | Program selesai masih aktif | Program dengan sisa = 0 harus status SELESAI |
| 6 | Realisasi campur program | Blok/tahun tidak boleh punya realisasi di >1 program |
| 7 | Rekomendasi historis bisa realisasi | EligibilityService WAJIB menolak `is_latest = false` |
| 8 | Tahap 2 tanpa Tahap 1 | Tahap 2 hanya boleh ada jika Tahap 1 sudah memenuhi rencana |
| 9 | Tahap 2 sebelum 60 hari | Interval minimal antar tahap = 60 hari |
| 10 | Urea/KCl independen | Kedua pupuk dievaluasi terpisah |
| 11 | Histori transisi tahap | Controller harus mencatat semua event |
| 12 | Fingerprint berbasis program | Fingerprint memasukkan `program_pemupukan_id` |
| 13 | Laporan legacy | Tidak boleh filter/subtotal berdasarkan `status_kebutuhan_dominan` |
| 14 | Subtotal legacy | Tidak boleh `sum('total_urea')` — harus `urea_aplikasi_saat_ini` |
| 15 | Tombol realisasi salah | View harus cek kelayakan sebelum tampilkan tombol |
| 16 | Kode teknis di UI | Kode internal tidak boleh tampil langsung |
| 17 | Migration rollback | Migration v2.8 harus sudah dijalankan |
| 18 | True legacy test | File test upgrade v2.8 harus ada dan nyata |

## Hasil

- `SUCCESS` (exit 0): Semua pemeriksaan lulus
- `FAILURE` (exit 1): Ada masalah yang perlu diperbaiki
