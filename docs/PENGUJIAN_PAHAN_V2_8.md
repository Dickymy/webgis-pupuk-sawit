# Pengujian Pahan v2.8

## Test Otomatis

### Feature Tests

| Test | Status | Deskripsi |
|------|--------|-----------|
| RbsProgramIntegrationTest | ⬜ | Analisis membuat program dan menghubungkan rekomendasi |
| RealisasiProgramConsistencyTest | ⬜ | Realisasi memakai program yang sama dengan rekomendasi |
| ProgramActiveUniquenessTest | ⬜ | Database constraint mencegah program aktif ganda |
| ProgramLifecycleTest | ⬜ | Program otomatis SELESAI saat kebutuhan terpenuhi |
| HistoricalRecommendationRejectionTest | ⬜ | Rekomendasi historis tidak bisa mencatat realisasi |
| OperationalStageTransitionHistoryTest | ⬜ | Histori transisi tahap tercatat |
| ProgramFingerprintTest | ⬜ | Fingerprint memasukkan program_pemupukan_id |
| LaporanNonLegacyDecisionTest | ⬜ | Subtotal tidak memakai status legacy |
| TrueLegacySchemaUpgradeV28Test | ⬜ | Migration rollback dan migrate ulang aman |

### Menjalankan Test

```bash
php artisan test
php artisan test --filter=RbsProgramIntegrationTest
php artisan test --filter=ProgramLifecycleTest
php artisan test --filter=TrueLegacySchemaUpgradeV28Test
php artisan test --filter=LaporanNonLegacyDecisionTest
```

### Audit

```bash
php artisan sawit:finalize-pahan-v2-8 --dry-run
```

## Verifikasi Manual

| # | Langkah | Status |
|---|---------|--------|
| 1 | Login admin | ⬜ |
| 2 | Periksa alur kerja dashboard | ⬜ |
| 3 | Tambah anggota dan blok | ⬜ |
| 4 | Isi kondisi | ⬜ |
| 5 | Jalankan analisis | ⬜ |
| 6 | Pastikan rekomendasi terhubung ke program | ⬜ |
| 7 | Catat realisasi sebagian | ⬜ |
| 8 | Lihat tindakan berikutnya | ⬜ |
| 9 | Selesaikan Tahap 1 | ⬜ |
| 10 | Pastikan pesan menunggu 60 hari | ⬜ |
| 11 | Jalankan Tahap 2 | ⬜ |
| 12 | Selesaikan program | ⬜ |
| 13 | Pastikan program SELESAI | ⬜ |
| 14 | Buka rekomendasi historis dan pastikan realisasi ditolak | ⬜ |
| 15 | Buka laporan dan pastikan subtotal memakai aplikasi saat ini | ⬜ |
| 16 | Uji tampilan ponsel dan desktop | ⬜ |
| 17 | Unduh PDF | ⬜ |
| 18 | Jalankan audit v2.8 | ⬜ |
