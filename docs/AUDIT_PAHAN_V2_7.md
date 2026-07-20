# AUDIT PAHAN v2.7

## Command

```bash
php artisan sawit:finalize-pahan-v2-7 --dry-run
```

## Pemeriksaan yang Dilakukan

| No | Audit | Deskripsi |
|----|-------|-----------|
| 1 | Config version | Versi mesin harus pahan-v2.7 |
| 2 | Legacy engine version | Rekomendasi terbaru tidak boleh versi lama |
| 3 | Asumsi layak palsu | Tidak ada `'layak' => true` hardcoded di controller |
| 4 | Status SELESAI palsu | Tidak ada SELESAI dengan jumlah < rencana |
| 5 | Tahap 2 tanpa Tahap 1 | Tidak ada Tahap 2 sebelum Tahap 1 memenuhi rencana |
| 6 | Tahap 2 sebelum 60 hari | Divalidasi oleh controller |
| 7 | Program aktif ganda | Tidak ada 2 program AKTIF untuk blok+tahun sama |
| 8 | Realisasi tanpa program | Data legacy tanpa program (acceptable) |
| 9 | Rekomendasi tanpa program | Data legacy tanpa program (acceptable) |
| 10 | Fingerprint tanpa realisasi | Fingerprint harus memasukkan data realisasi aktif |
| 11 | Override tanpa alasan | Tidak ada override tanpa alasan |
| 12 | Realisasi batal terhitung | Service harus mengecualikan status BATAL |
| 13 | Histori tidak tercatat | Controller harus memanggil recordOperationalHistory |
| 14 | Status legacy di keputusan utama | Static scan views, controllers, services |
| 15 | Migration belum dijalankan | Semua migration v2.7 harus ada di tabel migrations |

## Exit Codes

- `0` (SUCCESS): Tidak ada masalah
- `1` (FAILURE): Ada masalah yang harus diperbaiki

## Penggunaan di CI

```yaml
- name: Run finalize audit v2.7 (dry-run)
  run: php artisan sawit:finalize-pahan-v2-7 --dry-run
```

Jangan gunakan `|| true` — audit harus gagalkan build jika ada masalah.
