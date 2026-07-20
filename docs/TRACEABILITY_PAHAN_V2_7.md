# TRACEABILITY — Pahan v2.7

## Matriks Acceptance Criteria → Implementasi

| No | Criteria | Implementasi | Test |
|----|----------|-------------|------|
| 1 | Form realisasi tidak dapat dibuka saat tidak layak | `RealisasiEligibilityService.evaluate()` + Controller `create()` redirect | `RealisasiEligibilityTest` |
| 2 | Tidak ada asumsi `layak = true` palsu | Removed from Controller, uses real WindowService | Audit command check #3 |
| 3 | Tahap ditentukan server | `eligibility['active_stage']` di Controller `store()` | `RealisasiTamperedRequestTest::test_tampered_tahap_ignored` |
| 4 | Rencana dihitung server | `eligibility['urea_rencana_kg']` di Controller `store()` | `RealisasiTamperedRequestTest::test_tampered_rencana_ignored` |
| 5 | Tahun program dihitung server | `eligibility['tahun_program']` di Controller `store()` | `RealisasiTamperedRequestTest::test_tampered_tahun_program_ignored` |
| 6 | Manipulasi request diabaikan | Form tidak mengirim tahap/rencana/tahun; server ignore | `RealisasiTamperedRequestTest` (3 tests) |
| 7 | Status SELESAI ditolak jika jumlah belum memenuhi | `validateStatusSelesai()` di Controller | `RealisasiStatusSelesaiValidationTest` |
| 8 | Kumulatif sebagian dapat menyelesaikan tahap | `isTahapSelesai()` di FertilizationRealizationService | `RealisasiStatusSelesaiValidationTest::test_cumulative_sebagian_can_complete_stage` |
| 9 | Tahap 2 hanya setelah Tahap 1 selesai dan 60 hari | `CurrentApplicationCalculator` + `StoreRealisasiPemupukanRequest` | `RealisasiPartialFlowTest` |
| 10 | Realisasi antarprogram tidak tercampur | `program_pemupukan_id` pada RealisasiPemupukan | `ProgramPemupukanIsolationTest` |
| 11 | Hanya satu program aktif per blok per tahun | `ensureProgram()` logic + DB index | `ProgramPemupukanIsolationTest::test_same_program_reused` |
| 12 | Histori operasional tercatat | `recordOperationalHistory()` di Controller | `OperationalHistoryTest` (3 tests) |
| 13 | Fingerprint berubah saat realisasi berubah | `generateRefreshedFingerprint()` includes realisasi_aktif | Code audit + fingerprint tests |
| 14 | Status legacy tidak untuk keputusan utama | Views updated to use status_kondisi_tanaman | Audit command check #14 |
| 15 | Laporan memakai snapshot | Views use `*_snapshot` with fallback | Views inspection |
| 16 | Metode aplikasi memakai snapshot | `_hasil_rbs.blade.php` uses `rbs->umur_tanaman_snapshot` | Views inspection |
| 17 | PDF menampilkan histori realisasi | `laporan/pdf.blade.php` section added | PDF export test |
| 18 | Audit command v2.7 | `FinalizePahanV2_7.php` with 15 checks | CI workflow |
| 19 | Index source code konsisten | Updated to pahan-v2.7 | Manual |
| 20 | Build lulus | `npm run build` + `composer install` | CI |
| 21 | Pint lulus | `vendor/bin/pint --test` | CI |
| 22 | Test lulus | `php artisan test` (183 passed) | CI |
| 23 | Rollback dan migrate ulang lulus | CI rollback steps | CI |

## Risiko yang Membutuhkan Validasi Ahli Agronomi

1. Toleransi 0.01 kg untuk penentuan "selesai" — mungkin perlu disesuaikan
2. Split ratio 50/50 (Tahap 1/Tahap 2) — mungkin perlu kustomisasi per blok
3. Interval 60 hari — mungkin perlu penyesuaian musiman
4. Override tanpa approval multi-level
