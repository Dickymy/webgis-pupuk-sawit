# PANDUAN REALISASI AMAN — Pahan v2.7

## Prinsip Keamanan

1. **Server menentukan segalanya** — Tahap, rencana, tahun program dihitung server
2. **Form bukan sumber kebenaran** — Input rencana/tahap dari browser diabaikan
3. **Status harus divalidasi** — SELESAI hanya diterima jika jumlah memenuhi rencana
4. **Isolasi program** — Realisasi tidak tercampur antarprogram/tahun

## Alur Pencatatan Realisasi

```
1. User klik tombol "Catat Realisasi" (hanya muncul jika layak)
2. RealisasiEligibilityService.evaluate() → cek kelayakan
3. Jika tidak layak → redirect dengan alasan
4. Jika layak → tampilkan form dengan:
   - Tahap aktif (display only, ditentukan server)
   - Rencana resmi (display only, dihitung server)
   - Input: tanggal, jumlah realisasi, status, catatan
5. User submit form
6. Server re-evaluasi kelayakan (double check)
7. Server hitung tahap/rencana/tahun resmi
8. Validasi status SELESAI vs jumlah kumulatif
9. Ensure program pemupukan
10. Simpan realisasi
11. Refresh operasional rekomendasi
12. Catat histori operasional
```

## Field yang Dikirim Form

Form hanya mengirim:
- `rekomendasi_rbs_id` (hidden)
- `tanggal_realisasi`
- `urea_realisasi_kg`
- `kcl_realisasi_kg`
- `status_realisasi`
- `catatan_pelaksana`
- `confirmed_over_plan` (jika over)
- `override_annual_limit` (jika over tahunan)
- `override_reason`

## Field yang Dihitung Server

Server menentukan:
- `tahap` → dari CurrentApplicationCalculator
- `tahun_program` → tahun berjalan
- `urea_rencana_kg` → dari eligibility
- `kcl_rencana_kg` → dari eligibility
- `blok_lahan_id` → dari rekomendasi
- `program_pemupukan_id` → dari ensureProgram()

## Validasi Status SELESAI

Status SELESAI ditolak jika:
- Total kumulatif Urea < rencana tahap - 0.01 kg
- ATAU total kumulatif KCl < rencana tahap - 0.01 kg

Contoh:
- Rencana Tahap 1: Urea 272 kg, KCl 340 kg
- Realisasi sebelumnya: 200 + 50 = 250 kg Urea
- Realisasi baru: 10 kg Urea → Total 260 kg < 272 kg → DITOLAK

## Risiko yang Masih Membutuhkan Validasi Ahli

1. Threshold toleransi 0.01 kg mungkin perlu disesuaikan untuk lapangan
2. Override tahunan sebaiknya memerlukan approval 2 level (belum diimplementasi)
3. Konversi antar-pupuk (Urea ↔ ZA) belum didukung
