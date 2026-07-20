# Arsitektur RBS — Pahan v2.5

## Diagram Service

```
RbsService (Orchestrator)
├── PlantContextService          → Konteks tanaman (umur, fase) pada tanggal observasi
├── ObservationCompletenessService → Kelengkapan data observasi
├── PahanDoseReferenceService    → Dosis referensi dari tabel Pahan 2013
├── FertilizationCalculationService → Hitung total dari dosis × pokok
├── AnnualFertilizerSnapshotBuilder → Kebutuhan tahunan + snapshot luas/SPH
├── FertilizationWindowService   → Kelayakan waktu aplikasi
├── FertilizationRealizationService → Data realisasi pemupukan (BARU v2.5)
├── CurrentApplicationCalculator → Jumlah tahap aktif saat ini (BARU v2.5)
├── FertilizationScheduleService → Jadwal pemupukan per tahap
├── SupportingFertilizerSanitizer → Sanitasi pupuk pendukung
└── RecommendationReliabilityService → Skor keandalan data
```

## Pemisahan Tanggung Jawab (v2.5)

| Service | Tanggung Jawab |
|---------|---------------|
| AnnualFertilizerSnapshotBuilder | Kebutuhan tahunan (min/max/estimasi) |
| CurrentApplicationCalculator | Jumlah tahap aktif saat ini |
| FertilizationRealizationService | Query dan analisis data realisasi |

## Dependency yang Dihapus

- `PlantPhaseResolver` — tidak lagi di-inject di constructor (diganti PlantContextService)
- `PlantAgeService` — tidak lagi di-inject di constructor (diganti PlantContextService)

## Flow Analisis

1. Ambil kondisi terbaru
2. Resolve konteks tanaman (PlantContextService)
3. Evaluasi kelengkapan (ObservationCompletenessService)
4. Cek verifikasi fase (umur=3)
5. Evaluasi rule (Forward Chaining)
6. Hitung dosis (PahanDoseReferenceService)
7. Build annual snapshot (AnnualFertilizerSnapshotBuilder)
8. Ambil realisasi (FertilizationRealizationService)
9. Hitung aplikasi saat ini (CurrentApplicationCalculator)
10. Generate jadwal (FertilizationScheduleService)
11. Simpan dengan histori dan fingerprint
