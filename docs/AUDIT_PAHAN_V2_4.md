# AUDIT PAHAN v2.4

## Audit Dead Code

```bash
rg -n "cekKecukupanData|hitungConfidence|tentukanValiditasRekomendasi|kondisiCukup" app tests
```

**Hasil:** Hanya komentar pada `RbsService.php` line ~168-176 (penjelasan apa yang dihapus).
Nol referensi aktif.

## Audit Singkatan Fase di Blade

```bash
rg -n --glob="*.blade.php" "\bTBM\b|\bTM\b" resources/views
```

**Hasil:** Nol kemunculan singkatan TBM/TM pada teks pengguna di Blade.
Catatan: Kode internal (value database, PHP logic) tetap menggunakan TBM/TM.

## Audit status_kebutuhan_dominan

```bash
rg -n "status_kebutuhan_dominan" app resources tests
```

**Hasil:**
- `RbsService.php`: Ditandai `// LEGACY ONLY — kompatibilitas histori, bukan keputusan operasional`
- `RekomendasiRbs.php`: Accessor `getWarnaBadgeAttribute()` dan `getLabelStatusAttribute()` (kompatibilitas UI lama)
- `RbsController.php` & `LaporanController.php`: Filter query (kompatibilitas), BUKAN keputusan dosis/jadwal
- `DashboardController.php`: Legacy stats untuk backward compatibility (digandakan dari status baru)

**Kesimpulan:** `status_kebutuhan_dominan` TIDAK lagi digunakan untuk:
- ❌ Menentukan dosis aplikasi
- ❌ Menentukan jadwal
- ❌ Menentukan catatan operasional
- ❌ Banner utama PDF
- ❌ Statistik utama dashboard (sudah diganti status baru)

## Verifikasi Kebijakan

| Kebijakan | Status |
|-----------|--------|
| Rentang dosis Pahan dipertahankan | ✅ |
| Multiplier lama nonaktif | ✅ (`legacy_multipliers.enabled = false`) |
| Curah hujan layak 100–250 mm/bulan | ✅ |
| Interval minimum 60 hari | ✅ |
| Kebutuhan tahunan terpisah dari aplikasi | ✅ (AnnualFertilizerSnapshotBuilder) |
| Status kondisi terpisah dari kelayakan | ✅ (PlantConditionStatus vs ApplicationFeasibilityStatus) |
