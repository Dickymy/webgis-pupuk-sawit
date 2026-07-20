# TRACEABILITY RULE PAHAN v2.3

## Referensi Utama

| Komponen | Sumber | Halaman/Tabel |
|----------|--------|---------------|
| Dosis Urea & KCl | Pahan, 2013. Bab 9 | Tabel 9.13 & 9.14, hal. 163-164 |
| Kelayakan Waktu (Curah Hujan) | Pahan, 2013. Bab 9 | hal. 157-159 |
| Interval Aplikasi (60 hari) | Pahan, 2013. Bab 9 | hal. 157 |
| Fase TBM/TM | Pahan, 2013. Bab 9 | Tabel 9.5, hal. 152-153 |

## Mapping Dosis → Config

```
config/fertilization.php → dose_reference
```

| Fase | Umur | Urea (kg/pokok/thn) | KCl (kg/pokok/thn) |
|------|------|--------------------|--------------------|
| TBM | 1 | 0.50 – 0.70 | 0.75 – 1.25 |
| TBM | 2 | 0.70 – 0.85 | 1.00 – 1.75 |
| TBM | 3 | 0.90 – 1.25 | 1.20 – 2.25 |
| TM | 3-5 | 0.90 – 1.75 | 1.20 – 2.50 |
| TM | 6-15 | 1.00 – 3.00 | 1.50 – 3.50 |
| TM | 16+ | 1.50 – 2.50 | 1.50 – 2.25 |

## Mapping Status → Enum

### PlantConditionStatus (hanya dari DIAGNOSIS_VISUAL)

| Kode Internal | Label Tampilan | Dari |
|---------------|----------------|------|
| NORMAL_VISUAL | Kondisi Visual Normal | tingkat_keparahan = NORMAL |
| TERINDIKASI_DEFISIENSI_RINGAN | Terindikasi Defisiensi Ringan | tingkat_keparahan = RINGAN |
| TERINDIKASI_DEFISIENSI | Terindikasi Defisiensi | tingkat_keparahan = SEDANG |
| GEJALA_BERAT | Gejala Berat | tingkat_keparahan = BERAT |

### ApplicationFeasibilityStatus (hanya dari FertilizationWindowService)

| Kode Internal | Label Tampilan | Dari |
|---------------|----------------|------|
| LAYAK_DIJADWALKAN | Layak Dijadwalkan | Curah hujan 100-250mm, interval ≥ 60 hari |
| TUNDA_HUJAN_RENDAH | Ditunda karena Curah Hujan Rendah | < 100mm/bulan |
| TUNDA_HUJAN_TINGGI | Ditunda karena Curah Hujan Tinggi | > 250mm/bulan |
| TUNDA_INTERVAL | Ditunda karena Interval Terlalu Dekat | < 60 hari |
| TUNDA_DRAINASE | Ditunda karena Drainase Bermasalah | Buruk — Tergenang |

## Pupuk Pendukung — Status Validasi

Angka dosis pupuk pendukung hanya tampil jika:
1. `status_validasi = TERVERIFIKASI_SUMBER` + metadata lengkap (judul, penulis, tahun)
2. `status_validasi = TERVERIFIKASI_AHLI` + divalidasi_oleh + tanggal_validasi

Jika belum valid → tampilkan pesan umum tanpa angka.
