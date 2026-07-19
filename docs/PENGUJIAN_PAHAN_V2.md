# Dokumentasi Pengujian — Pahan V2

## Perintah Menjalankan Test

```bash
php artisan test
```

Atau filter spesifik:
```bash
php artisan test --filter=PahanDoseReferenceTest
```

## Hasil Test

```
Tests: 24 passed (63 assertions)
Duration: ~1s
```

## Daftar Test Case

### 15.1 Referensi Dosis (10 test)

| # | Test | Validasi |
|---|------|----------|
| 1 | TBM tahun ke-1 | Urea 0.50–0.70, KCl 0.75–1.25 |
| 2 | TBM tahun ke-2 | Urea 0.70–0.85, KCl 1.00–1.75 |
| 3 | TBM tahun ke-3 | Urea 0.90–1.25, KCl 1.20–2.25 |
| 4 | TM umur 3 tahun | Age group 3-5, Urea 0.90–1.75 |
| 5 | TM umur 5 tahun | Age group 3-5, KCl 1.20–2.50 |
| 6 | TM umur 6 tahun | Age group 6-15, Urea 1.00–3.00, KCl 1.50–3.50 |
| 7 | TM umur 15 tahun | Age group 6-15 |
| 8 | TM umur 16 tahun | Age group 16+, Urea 1.50–2.50, KCl 1.50–2.25 |
| 9 | Umur 3 tahun tanpa fase | needs_phase_verification = true, phase = null |
| 10 | Estimasi midpoint benar | (1.00 + 3.00) / 2 = 2.00 |

### 15.2 Curah Hujan (5 test)

| # | Test | Validasi |
|---|------|----------|
| 1 | 99 mm/bulan | status = TUNDA_HUJAN_RENDAH, layak = false |
| 2 | 100 mm/bulan | layak = true |
| 3 | 250 mm/bulan | layak = true |
| 4 | 251 mm/bulan | status = TUNDA_HUJAN_TINGGI, layak = false |
| 5 | Data kosong (null) | status = PERLU_VERIFIKASI_DATA |

### 15.3 Interval Pemupukan (3 test)

| # | Test | Validasi |
|---|------|----------|
| 1 | 59 hari | status = TUNDA_INTERVAL, layak = false |
| 2 | 60 hari | layak = true |
| 3 | 130 hari | layak = true, terlambat = true, TANPA multiplier 1.25 |

### 15.4 Perhitungan (4 test)

| # | Test | Validasi |
|---|------|----------|
| 1 | Jumlah pokok | 5.0 Ha × 136 SPH = 680 pokok |
| 2 | Total min/max/estimasi | 1.00×680=680, 3.00×680=2040, 2.00×680=1360 |
| 3 | Karung 50 kg | 1360/50 = 27.2 (desimal), ceil = 28 (bulat) |
| 4 | Tidak ada multiplier | Tanah berbeda + topografi berbeda = dosis SAMA |

## Coverage

| Area | Tercakup |
|------|----------|
| PahanDoseReferenceService | ✅ Semua age group |
| PlantPhaseResolver | ✅ Via doseReference test |
| FertilizationWindowService | ✅ Hujan + interval + drainase |
| FertilizationCalculationService | ✅ Total + karung |
| RecommendationReliabilityService | ⚠️ Indirect (via integration) |
| RbsService integration | ⚠️ Memerlukan database fixture |

## Yang Belum Di-test (Memerlukan Feature Test)

- Simpan rekomendasi ke database via RbsService
- Histori rekomendasi tetap tersimpan
- Laporan PDF generate tanpa error
- Route lama tetap berjalan
- Dashboard mapData lengkap

## Cara Menambah Test

Test file: `tests/Unit/PahanDoseReferenceTest.php`

Contoh menambah test baru:
```php
public function test_nama_case(): void
{
    $blok = $this->makeBlok([...]);
    $result = $this->service->getDoseReference($blok);
    $this->assertEquals(expected, $result['...']);
}
```
