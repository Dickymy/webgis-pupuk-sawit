# ARSITEKTUR RBS PAHAN v2.4

## Alur Final RbsService (Orchestrator)

```
ambil observasi (KondisiLahan terbaru)
→ plant context historis (PlantContextService)
→ verifikasi fase (umur=3 tanpa fase → PERLU_VERIFIKASI_FASE)
→ kelengkapan data minimal (kondisiCukupMinimal)
→ diagnosis capability (ObservationCompletenessService)
→ kebutuhan tahunan (AnnualFertilizerSnapshotBuilder)
→ evaluasi rule jika cukup (Forward Chaining + Rule Chaining)
→ kondisi tanaman (tentukanStatusKondisiTanaman — hanya DIAGNOSIS_VISUAL)
→ kelayakan aplikasi (FertilizationWindowService)
→ aplikasi saat ini (0 jika tidak layak)
→ jadwal jika layak (FertilizationScheduleService — kosong jika tidak layak)
→ sanitizer pupuk pendukung (SupportingFertilizerSanitizer)
→ keandalan data (RecommendationReliabilityService)
→ simpan snapshot (simpanDenganHistori + fingerprint SHA-256)
```

## Service Map

| Service | Tanggung Jawab |
|---------|---------------|
| `PlantContextService` | Umur + fase pada tanggal observasi |
| `PlantAgeService` | Hitung umur pada tanggal tertentu |
| `PlantPhaseResolver` | Resolve fase dari blok (untuk current state, bukan analisis) |
| `PahanDoseReferenceService` | Lookup dosis dari tabel Pahan 2013 |
| `FertilizationCalculationService` | Hitung total (dosis × pokok) |
| `AnnualFertilizerSnapshotBuilder` | Kebutuhan tahunan + aplikasi saat ini |
| `FertilizationWindowService` | Kelayakan waktu aplikasi |
| `FertilizationScheduleService` | Jadwal 2 tahap 50/50 |
| `ObservationCompletenessService` | Syarat minimum diagnosis |
| `RecommendationReliabilityService` | Skor kelengkapan & keandalan |
| `SupportingFertilizerSanitizer` | Filter dosis pupuk pendukung |

## Pemisahan Status

### Status Kondisi Tanaman (PlantConditionStatus)
- Sumber: Rule DIAGNOSIS_VISUAL + tingkat_keparahan
- Nilai: NORMAL_VISUAL, TERINDIKASI_DEFISIENSI_RINGAN, TERINDIKASI_DEFISIENSI, GEJALA_BERAT, PERLU_VERIFIKASI, BELUM_DIOBSERVASI

### Status Kelayakan Aplikasi (ApplicationFeasibilityStatus)
- Sumber: FertilizationWindowService (curah hujan, interval, drainase)
- Nilai: LAYAK_DIJADWALKAN, TUNDA_HUJAN_RENDAH, TUNDA_HUJAN_TINGGI, TUNDA_INTERVAL, TUNDA_DRAINASE, PERLU_VERIFIKASI_DATA, TERLAMBAT_PERLU_DIJADWALKAN

### status_kebutuhan_dominan (LEGACY)
- Hanya untuk kompatibilitas histori
- TIDAK boleh digunakan untuk keputusan operasional

## Kebijakan Dosis

- Rentang dosis: Pahan 2013, Tabel 9.13 & 9.14
- Strategi estimasi: midpoint (default)
- Multiplier tanah/topografi/waktu: NONAKTIF
- Curah hujan layak: 100–250 mm/bulan
- Interval minimum: 60 hari
- Jadwal: 2 tahap × 50% (tidak Maret/September otomatis)
- Karung: ceil(total ÷ 50)
