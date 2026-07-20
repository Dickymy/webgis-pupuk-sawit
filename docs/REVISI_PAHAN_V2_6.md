# Revisi Pahan v2.6 — Finalisasi Menyeluruh

## Ringkasan

Pahan v2.6 menyelesaikan seluruh masalah implementasi terbuka dari v2.5:
- CRUD realisasi pemupukan melalui UI
- Validasi ketat realisasi (over-plan, annual limit, interval)
- Fix logika "Tahap 1 Selesai" (bukan hanya keberadaan record)
- CurrentApplicationCalculator menangani TAHAP_1_SEBAGIAN
- FertilizationScheduleService menggunakan active_stage
- RecommendationOperationalRefreshService
- Penghapusan ketergantungan status legacy dari tampilan
- Snapshot pada seluruh laporan historis

## Perubahan Database

### Migration v2.6: `upgrade_realisasi_pemupukans_for_v26`
- `tahun_program` (SMALLINT, nullable) — tahun program pemupukan
- `confirmed_over_plan` (BOOLEAN, default false) — konfirmasi over-plan
- `override_annual_limit` (BOOLEAN, default false) — override batas tahunan
- `override_reason` (TEXT, nullable) — alasan override

### Fix Migration v2.5 Rollback
- `dropForeign(['blok_lahan_id'])` sebelum `dropColumn()`
- Handling kolom Urea/KCl lama secara terpisah

## Perubahan Service

### FertilizationRealizationService
- `tahap_1_selesai` berdasarkan status SELESAI atau total >= rencana (toleransi 0.01 kg)
- `tahap_1_sebagian` = ada record tapi belum selesai
- Realisasi BATAL tidak dihitung
- Filter berdasarkan `tahun_program` atau `rekomendasi_rbs_id`

### CurrentApplicationCalculator
- Handle `TAHAP_1_SEBAGIAN`: aplikasi saat ini = sisa rencana Tahap 1
- Tidak pindah ke Tahap 2 jika salah satu pupuk belum terpenuhi

### FertilizationScheduleService
- Jadwal menggunakan `active_stage` dari CurrentApplicationCalculator
- Nama tahap: "Tahap 1" / "Tahap 2" / "Lanjutan Realisasi Tahap 1"
- Jadwal KOSONG jika status MENUNGGU/SELESAI
- Dosis per pokok dari `jumlah_pokok_snapshot`

### RecommendationOperationalRefreshService (BARU)
- Dipanggil setelah realisasi dibuat/diubah/dibatalkan
- Update field operasional tanpa mengubah diagnosis
- Fingerprint diperbarui otomatis

## Perubahan View

### Banner Utama
- Menggunakan `status_kondisi_tanaman`, bukan `status_kebutuhan_dominan`

### Penundaan
- Berdasarkan `status_kelayakan_aplikasi`, bukan status legacy

### Laporan & PDF
- Menggunakan `luas_ha_snapshot`, `sph_snapshot`, `jumlah_pokok_snapshot`
- Fallback ke data blok hanya untuk record legacy

## Referensi
Pahan, I. 2013. Panduan Lengkap Kelapa Sawit. Cetakan XI. Jakarta: Penebar Swadaya.
