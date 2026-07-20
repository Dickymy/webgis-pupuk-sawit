# REVISI PAHAN v2.3

## File Baru

| File | Deskripsi |
|------|-----------|
| `app/Services/PlantContextService.php` | Konteks tanaman historis (umur+fase pada tanggal observasi) |
| `app/Services/FertilizationScheduleService.php` | Jadwal pemupukan baru (50/50, tanpa bulan otomatis) |
| `app/Services/SupportingFertilizerSanitizer.php` | Sanitasi dosis pupuk pendukung |
| `app/Enums/RuleType.php` | Enum jenis rule |
| `app/Enums/SeverityLevel.php` | Enum tingkat keparahan |
| `app/Console/Commands/FinalizePahanV2_3.php` | Command audit & finalisasi |
| `database/migrations/2026_07_20_200000_*.php` | Migration kolom tahunan |
| `.github/workflows/tests.yml` | GitHub Actions CI |

## File Diubah

| File | Perubahan |
|------|-----------|
| `app/Services/RbsService.php` | PlantContextService, jadwal baru, sanitizer, fix typo, fix duplikat key |
| `app/Http/Controllers/BlokLahanController.php` | Auto-set fase |
| `app/Http/Controllers/DashboardController.php` | `fase_label` bukan `fase_tanaman` |
| `app/Models/RekomendasiRbs.php` | Fillable v2.3 fields |
| `config/fertilization.php` | `engine_version` → `pahan-v2.3` |

## Perubahan Perilaku

### Fase Historis
- **Sebelum**: Fase blok saat ini dipakai untuk semua analisis
- **Sesudah**: Fase ditentukan dari umur pada tanggal observasi

### Jadwal Pemupukan
- **Sebelum**: 70/30 (Darurat), 60/40 (Segera), Maret/September otomatis
- **Sesudah**: 50/50 default, tahap 2 menunggu realisasi tahap 1, tanpa bulan otomatis

### Status Kondisi vs Kelayakan
- **Sebelum**: Kondisi berat otomatis menunda
- **Sesudah**: Kondisi dan kelayakan terpisah penuh. Penundaan hanya dari FertilizationWindowService.

### Pupuk Pendukung
- **Sebelum**: Semua angka dosis ditampilkan
- **Sesudah**: Angka hanya tampil jika tervalidasi sumber/ahli

### Kebutuhan Tahunan
- **Sebelum**: `total_urea`/`total_kcl` = 0 saat ditunda
- **Sesudah**: Kebutuhan tahunan selalu tersedia, aplikasi saat ini = 0 saat ditunda
