# REVISI PAHAN v2.4

## Perubahan Utama

### 1. Fase Historis Menjadi Dasar Dosis
- `hitungDosisStandar()` menerima `array $plantContext` (dari `PlantContextService`)
- Tidak lagi memanggil `PlantPhaseResolver::resolve($blok)` untuk analisis historis
- Umur 3 tanpa verifikasi fase → `PERLU_VERIFIKASI_FASE` (tidak menghasilkan dosis/jadwal)

### 2. Dead Code Dihapus
- `cekKecukupanData()` → diganti `ObservationCompletenessService`
- `hitungConfidence()` → diganti `RecommendationReliabilityService`
- `tentukanValiditasRekomendasi()` → diganti `RecommendationReliabilityService`
- `isDugaanUnsurSesuaiWarnaDaun()` → dihapus
- `cekKonsistensiData()` → dihapus
- `mappingVisualUnsur` property → dihapus

### 3. Status Legacy Tidak Mengendalikan Keputusan
- `status_kebutuhan_dominan` ditandai `// LEGACY ONLY` di semua penggunaan
- `tentukanCatatanDosis()` di-refactor: menerima `statusKelayakan`, `alasanKelayakan`, `statusKondisi`, `masalah`
- Catatan operasional hanya mengikuti `status_kelayakan_aplikasi`

### 4. Jadwal Kosong Saat Belum Layak
- `FertilizationScheduleService::generate()` → return `[]` jika tidak layak
- `jadwalDitunda()` dan `jadwalMenungguData()` dihapus
- Informasi penundaan hanya di `status_kelayakan_aplikasi` + `alasan_kelayakan`

### 5. AnnualFertilizerSnapshotBuilder
- Service baru untuk menghitung kebutuhan tahunan terpisah dari aplikasi
- Rumus: `jumlah pokok = luas × SPH`, `total = dosis × jumlah pokok`, `karung = ceil(total ÷ 50)`
- Digunakan pada semua jalur: rule terpicu, normal, data tidak cukup, dosis dasar
- Saat ditunda: `urea_aplikasi_saat_ini = 0`, kebutuhan tahunan tetap ada

### 6. Sanitizer Diperketat
- `isPupukUtama()`: pencocokan eksplisit (bukan substring)
- `isValidated()`: TERVERIFIKASI_SUMBER wajib `sumber_halaman` + `sumber_tabel`
- TERVERIFIKASI_AHLI wajib `catatan_validasi`

### 7. Singkatan Fase Dihilangkan dari Teks Pengguna
- PlantPhaseResolver pesan: "otomatis dikategorikan sebagai Tanaman Belum Menghasilkan"

### 8. PDF Diperbaiki Total
- Dua status terpisah: Kondisi Tanaman + Kelayakan Aplikasi
- Menggunakan `umur_tanaman_snapshot` dan `label_fase`
- Kebutuhan tahunan dari field tahunan (bukan `total_urea`/`total_kcl`)
- Aplikasi saat ini dari field `urea_aplikasi_saat_ini`/`kcl_aplikasi_saat_ini`
- Disclaimer sesuai spesifikasi

### 9. Dashboard Menggunakan Status Baru
- `DashboardController` menggunakan `status_kondisi_tanaman` dan `status_kelayakan_aplikasi`
- Statistik kondisi: 6 kategori
- Statistik kelayakan: 7 kategori

### 10. Engine Version
- `config/fertilization.php` → `'engine_version' => 'pahan-v2.4'`

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `app/Services/RbsService.php` | Refactor besar: plantContext, dead code removal, tentukanCatatanDosis |
| `app/Services/AnnualFertilizerSnapshotBuilder.php` | **BARU** |
| `app/Services/FertilizationScheduleService.php` | Return `[]` saat tidak layak |
| `app/Services/SupportingFertilizerSanitizer.php` | isPupukUtama eksplisit, isValidated ketat |
| `app/Services/PlantPhaseResolver.php` | Singkatan dihapus dari pesan |
| `app/Http/Controllers/DashboardController.php` | Status baru |
| `app/Console/Commands/FinalizePahanV2_4.php` | **BARU** — command audit |
| `config/fertilization.php` | engine_version → pahan-v2.4 |
| `resources/views/laporan/pdf.blade.php` | Refactor total |
| `.github/workflows/tests.yml` | Rollback + re-migrate test |
| `tests/Unit/AnnualFertilizerSnapshotBuilderTest.php` | **BARU** |
| `tests/Unit/FertilizationScheduleServiceTest.php` | Updated: empty array assertions |

## Referensi
Pahan, Iyung. 2013. *Panduan Lengkap Kelapa Sawit*. Cetakan XI. Jakarta: Penebar Swadaya.
