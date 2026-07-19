# Revisi Aplikasi SawitGIS — Alignment dengan Pahan (2013)

## Ringkasan Perubahan

Revisi ini menyelaraskan sistem perhitungan dosis pupuk dengan buku:

> Iyung Pahan. 2013. *Panduan Lengkap Kelapa Sawit*. Cetakan XI. Jakarta: Penebar Swadaya.

Perubahan utama:
1. Dosis mengikuti **rentang referensi** dari Tabel 9.13 & 9.14 (hal. 163-164)
2. **Multiplier tanah, topografi, dan waktu dinonaktifkan**
3. Fase **TBM/TM** ditambahkan sebagai field eksplisit
4. Curah hujan numerik (mm/bulan) menentukan **kelayakan waktu**, bukan dosis
5. Confidence score diganti menjadi **Skor Kelengkapan & Keandalan Data**
6. Pemisahan jelas antara **kebutuhan tahunan** dan **dosis aplikasi saat ini**

---

## Arsitektur Lama

```
RbsService (monolith ~1100 baris)
  └── hitungDosisStandar()
        baseDosis × multiplierTanah × multiplierTopografi × multiplierWaktu
```

## Arsitektur Baru

```
RbsService (orchestrator)
  ├── PlantPhaseResolver          → TBM/TM, verifikasi umur 3 tahun
  ├── PahanDoseReferenceService   → Rentang dosis dari tabel config
  ├── FertilizationWindowService  → Kelayakan waktu (hujan, interval, drainase)
  ├── FertilizationCalculationService → Total kebutuhan, karung
  └── RecommendationReliabilityService → Skor keandalan data (0-100)
```

## Rumus Baru

### Dosis Per Pokok (kg/pokok/tahun)
```
estimasi = (minimum + maksimum) / 2    [strategi: midpoint]
```

Rentang dari config/fertilization.php (Pahan 2013, Tabel 9.13 & 9.14):

| Fase | Umur | Urea min | Urea max | KCl min | KCl max |
|------|------|----------|----------|---------|---------|
| TBM | Th-1 | 0.50 | 0.70 | 0.75 | 1.25 |
| TBM | Th-2 | 0.70 | 0.85 | 1.00 | 1.75 |
| TBM | Th-3 | 0.90 | 1.25 | 1.20 | 2.25 |
| TM | 3-5 | 0.90 | 1.75 | 1.20 | 2.50 |
| TM | 6-15 | 1.00 | 3.00 | 1.50 | 3.50 |
| TM | 16+ | 1.50 | 2.50 | 1.50 | 2.25 |

### Total Kebutuhan
```
jumlah_pokok = luas_ha × SPH
total_estimasi = estimasi_per_pokok × jumlah_pokok
karung = total_estimasi / 50
```

### Kelayakan Waktu Aplikasi (TIDAK mengubah dosis)
- Curah hujan < 100 mm/bulan → TUNDA
- Curah hujan > 250 mm/bulan → TUNDA
- Interval < 60 hari → TUNDA
- Terlambat > 120 hari → Ditandai, TANPA menaikkan dosis

## Alasan Multiplier Dinonaktifkan

Multiplier jenis tanah, topografi, dan waktu yang digunakan sebelumnya:
- **Bukan** berasal langsung dari buku Pahan (2013)
- Diklaim adaptasi dari Fairhurst & Hardter (2003) tanpa halaman spesifik
- Mengubah dosis secara otomatis tanpa validasi ahli

Multiplier disimpan di `config/fertilization.php` → `legacy_multipliers` dengan status `enabled: false`.

## Perbedaan Kebutuhan Tahunan vs Aplikasi Saat Ini

| Aspek | Kebutuhan Tahunan | Aplikasi Saat Ini |
|-------|-------------------|-------------------|
| Sumber | Tabel Pahan | Kebutuhan tahunan × evaluasi kelayakan |
| Berubah saat tunda? | TIDAK | Ya (menjadi 0) |
| Ditampilkan? | Selalu | Tergantung status kelayakan |
| Kolom DB | urea_estimasi_kg_per_pokok_tahun | dosis_urea (kolom lama) |

## Arti Skor Keandalan Data

Skor 0-100 BUKAN menyatakan akurasi agronomis. Skor hanya menggambarkan:
- Kelengkapan identitas blok (15)
- Fase tanaman terverifikasi (10)
- pH dan metode pengukuran (10)
- Curah hujan bulanan + periode (15)
- Tanggal pemupukan terakhir (10)
- Data visual: daun, pelepah (15)
- Drainase, gulma, hama (10)
- Rule terpicu bersumber (10)
- Validasi ahli/lab (5)

## Batasan Sistem

- Rekomendasi ini adalah **estimasi awal** berbasis data blok dan observasi visual
- Bukan pengganti analisis laboratorium tanah/daun atau keputusan ahli agronomi
- Perhitungan kuantitatif dibatasi pada Urea dan MOP/KCl
- Unsur P, Mg, B, dan unsur lain tetap dapat diperlukan sesuai kondisi tanaman

## File yang Berubah

| File | Fungsi Perubahan |
|------|------------------|
| `config/fertilization.php` | BARU — konfigurasi dosis, batas, bobot |
| `app/Services/PlantPhaseResolver.php` | BARU — resolusi fase TBM/TM |
| `app/Services/PahanDoseReferenceService.php` | BARU — referensi dosis Pahan |
| `app/Services/FertilizationWindowService.php` | BARU — kelayakan waktu |
| `app/Services/FertilizationCalculationService.php` | BARU — perhitungan total |
| `app/Services/RecommendationReliabilityService.php` | BARU — skor keandalan |
| `app/Services/RbsService.php` | DIUBAH — integrasi service baru |
| `app/Models/BlokLahan.php` | DIUBAH — tambah fase_tanaman |
| `app/Models/KondisiLahan.php` | DIUBAH — tambah field curah hujan numerik |
| `app/Models/RekomendasiRbs.php` | DIUBAH — tambah kolom snapshot |
| `app/Models/RuleBaseLanjutan.php` | DIUBAH — tambah field provenance |
| `database/migrations/2026_07_20_*` | BARU — 4 migration aman |
| `tests/Unit/PahanDoseReferenceTest.php` | BARU — 22 unit test |
