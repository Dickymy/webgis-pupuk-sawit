# ARSITEKTUR RBS PAHAN v2.3

## Alur Analisis

```
Tanggal Observasi
    → PlantContextService: umur + fase observasi
    → ObservationCompletenessService: cek kelengkapan data
    → PahanDoseReferenceService: dosis per pokok (dari umur+fase observasi)
    → FertilizationCalculationService: total kebutuhan tahunan
    → FertilizationWindowService: kelayakan waktu aplikasi
    → Forward Chaining: evaluasi rule
    → SupportingFertilizerSanitizer: sanitasi pupuk pendukung
    → FertilizationScheduleService: jadwal (jika layak)
    → RecommendationReliabilityService: skor keandalan
    → Simpan dengan histori
```

## Service Architecture

```
RbsService (orchestrator)
├── PlantContextService
│   └── PlantAgeService
├── ObservationCompletenessService
├── PahanDoseReferenceService
│   └── PlantPhaseResolver
├── FertilizationCalculationService
├── FertilizationWindowService
├── FertilizationScheduleService
├── SupportingFertilizerSanitizer
└── RecommendationReliabilityService
```

## Pemisahan Status (Dua Dimensi)

```
┌─────────────────────────────────┐
│ STATUS KONDISI TANAMAN          │
│ (PlantConditionStatus)          │
│                                 │
│ Sumber: Rule DIAGNOSIS_VISUAL   │
│ Menjawab: "Bagaimana kondisi    │
│            tanaman saat ini?"   │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│ STATUS KELAYAKAN APLIKASI       │
│ (ApplicationFeasibilityStatus)  │
│                                 │
│ Sumber: FertilizationWindow     │
│ Menjawab: "Bolehkah memupuk    │
│            sekarang?"           │
└─────────────────────────────────┘
```

Keduanya INDEPENDEN. Contoh valid:
- Gejala Berat + Layak Dijadwalkan → Boleh memupuk meski kondisi buruk
- Kondisi Normal + Tunda Hujan Tinggi → Tidak boleh memupuk meski kondisi baik

## Jadwal Pemupukan (v2.3)

```
Tahap 1: 50% dosis tahunan
  Status: "Rencana"
  Waktu: Saat curah hujan 100-250 mm/bulan

Tahap 2: 50% dosis tahunan
  Status: "Menunggu Realisasi Tahap 1"
  Waktu: Minimal 60 hari setelah realisasi Tahap 1
```

Yang DIHAPUS:
- ❌ Pembagian 70/30 (Darurat) dan 60/40 (Segera)
- ❌ Penetapan Maret/September otomatis
- ❌ Pemisahan Urea-KCl 2-3 minggu (bukan aturan Pahan)
- ❌ Tahap kedua selalu 6 bulan

## Kebutuhan Tahunan vs Aplikasi Saat Ini

```
KEBUTUHAN TAHUNAN (selalu terisi):
├── urea_min_kg_per_pokok_tahun
├── urea_max_kg_per_pokok_tahun
├── urea_estimasi_kg_per_pokok_tahun
├── urea_total_estimasi_tahunan
└── urea_karung_estimasi_tahunan

APLIKASI SAAT INI (0 jika ditunda):
├── urea_aplikasi_saat_ini
└── kcl_aplikasi_saat_ini
```

## Database Fields (Legacy vs v2.3)

| Field Legacy | Status | Field v2.3 |
|--------------|--------|------------|
| `dosis_urea` | Dipertahankan | `urea_estimasi_kg_per_pokok_tahun` |
| `total_urea` | Dipertahankan | `urea_total_estimasi_tahunan` |
| `total_kcl` | Dipertahankan | `kcl_total_estimasi_tahunan` |
| `status_kebutuhan_dominan` | Legacy only | `status_kondisi_tanaman` + `status_kelayakan_aplikasi` |
