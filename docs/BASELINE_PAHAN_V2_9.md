# Baseline Pahan v2.9

> Dicatat sebelum perubahan stabilisasi dimulai.

## Status Awal (dari branch v2.8)

| Metrik | Nilai |
|--------|-------|
| Branch asal | fix/finalisasi-integrasi-dan-ux-pahan-v2-8 |
| Commit asal | f4d924e |
| Engine version | pahan-v2.8 |
| Total tests | 235 |
| Total assertions | 627 |
| Durasi test | ~10.5 detik |
| Pint | 185 files PASS |
| Migration status | 43 migrations, semua sudah dijalankan |
| Audit v2.8 | PASS (--dry-run) |

## Perubahan Target v2.9

- Engine version: pahan-v2.9
- Hapus bobot validasi_ahli dari skor keandalan
- Redistribusi bobot: total tetap 100
- Tambah demo seeder terpisah
- Tambah reset demo command
- Tambah backup commands
- Tambah health check command
- Tambah finalize v2.9 audit
- Tambah error pages (403, 404, 419, 422, 500, 503)
- Tambah 12 test files baru
- Mode demo via APP_DEMO_MODE

## Hasil Setelah v2.9

| Metrik | Nilai |
|--------|-------|
| Total tests | 276 |
| Total assertions | 706 |
| Durasi test | ~7.5 detik |
| Pint | 203 files PASS |
| Audit v2.9 | PASS |
| Health check | PASS (pada database bersih) |
