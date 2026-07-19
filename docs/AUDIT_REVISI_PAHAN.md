# Audit Revisi Pahan — SawitGIS

**Tanggal Audit:** 20 Juli 2026  
**Branch:** `refactor/pahan-alignment`  
**Versi Mesin Lama:** `legacy-v1`  
**Versi Mesin Baru:** `pahan-v2`

---

## 1. Alur Analisis Lama (legacy-v1)

```
BlokLahan + KondisiLahan
    → RbsService::analisis()
        → Ambil kondisi terbaru
        → Cek kecukupan data (7 field penting)
        → Evaluasi rule (Forward Chaining + Rule Chaining)
        → hitungDosisStandar()
            → baseDosis dari kategoriUmur (5 kategori)
            → × multiplierTanah (9 jenis)
            → × multiplierTopografi (3 level)
            → × multiplierWaktu (0.75 / 1.0 / 1.25)
            → round ke 0.25 terdekat
        → Confidence Score (max 100)
        → Simpan rekomendasi
```

## 2. Lokasi Kode Dosis Lama

- **File:** `app/Services/RbsService.php`
- **Method:** `hitungDosisStandar()` (baris ~1090-1160)
- **Multiplier tanah:** match($blok->jenis_tanah) — 9 variasi
- **Multiplier topografi:** match($blok->topografi) — 3 level
- **Multiplier waktu:** if/else berdasarkan jarak hari pemupukan terakhir

## 3. Daftar Multiplier yang Dinonaktifkan

| Multiplier | Nilai Lama | Status Baru | Alasan |
|---|---|---|---|
| Tanah Berpasir (Urea) | ×1.25 | NONAKTIF | Tidak ada sumber Pahan yang mendukung angka ini |
| Tanah Berpasir (KCl) | ×1.35 | NONAKTIF | Idem |
| Tanah Gambut (Urea) | ×0.70 | NONAKTIF | Idem |
| Tanah Gambut (KCl) | ×1.50 | NONAKTIF | Idem |
| Tanah Liat (Urea) | ×0.90 | NONAKTIF | Idem |
| Topografi Bergelombang | ×1.10 | NONAKTIF | Idem |
| Topografi Curam | ×1.20 | NONAKTIF | Idem |
| Waktu < 60 hari | ×0.75 | NONAKTIF — jadi "Tunda" | Dosis tahunan tidak berubah |
| Waktu > 120 hari | ×1.25 | NONAKTIF | Dosis tidak dinaikkan |

## 4. Masalah Agronomis

1. **Referensi tahun salah**: Kode menyebut "Pahan, 2015" padahal edisi yang diperiksa adalah 2013.
2. **Angka dosis tunggal dari kategori buatan**: TBM hanya 0.75, tidak mencerminkan rentang per tahun tanam.
3. **Multiplier tanpa validasi**: Angka ×0.75 dan ×1.25 diklaim dari prinsip nutrient balance tapi tanpa halaman/tabel spesifik.
4. **Confidence score salah makna**: Disebut "Keyakinan" tapi sebenarnya kelengkapan data.
5. **Tidak ada pemisahan kebutuhan tahunan vs aplikasi saat ini**: Ketika tunda, dosis = 0 (seharusnya kebutuhan tetap tercatat).
6. **Tidak ada fase TBM/TM eksplisit**: Hanya diturunkan dari umur (<3 = TBM).
7. **Curah hujan hanya kategori**: Tidak ada nilai numerik mm/bulan.

## 5. Masalah Teknis

1. Seluruh logika ada di satu class (`RbsService`) ~1100 baris.
2. Tidak ada unit test sebelumnya.
3. Tidak ada config terpisah untuk parameter dosis.
4. Tidak ada snapshot perhitungan — riwayat bisa berubah.

## 6. Risiko Kompatibilitas

- Kolom lama (`dosis_urea`, `dosis_kcl`, `total_urea`, `total_kcl`) tetap diisi dari estimasi baru.
- View yang menampilkan kolom lama tetap berfungsi.
- Histori rekomendasi lama tetap tersimpan dengan `versi_mesin_rekomendasi = legacy-v1`.
- Kolom `confidence_score` dan `confidence_label` tetap diisi (dengan skor keandalan baru).

## 7. Rencana Migrasi

1. Tambah kolom baru via migration (tidak mengubah/hapus kolom lama).
2. Update service untuk menggunakan tabel dosis Pahan.
3. Nonaktifkan multiplier (disimpan di config sebagai legacy).
4. Rekomendasi lama diberi label `versi_mesin_rekomendasi = legacy-v1`.
5. Analisis baru menggunakan mesin `pahan-v2`.
6. Data lama tidak diubah.
