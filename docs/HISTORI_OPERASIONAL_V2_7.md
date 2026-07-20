# HISTORI OPERASIONAL — Pahan v2.7

## Konsep

Setiap perubahan realisasi pemupukan mencatat snapshot state operasional pada tabel `rekomendasi_operasional_histories`. Histori ini **tidak pernah dihapus** dan berfungsi sebagai audit trail.

## Schema

```sql
rekomendasi_operasional_histories:
  id                              BIGINT PRIMARY KEY
  rekomendasi_rbs_id              FK → rekomendasi_rbs
  program_pemupukan_id            FK → program_pemupukans (nullable)
  event_type                      VARCHAR(50)
  active_stage                    TINYINT (nullable)
  status_stage                    VARCHAR(50) (nullable)
  urea_aplikasi_saat_ini          DECIMAL(10,2) (nullable)
  kcl_aplikasi_saat_ini           DECIMAL(10,2) (nullable)
  urea_sisa_tahunan               DECIMAL(10,2) (nullable)
  kcl_sisa_tahunan                DECIMAL(10,2) (nullable)
  tanggal_minimum_tahap_berikutnya DATE (nullable)
  alasan_tahap                    TEXT (nullable)
  analysis_fingerprint            VARCHAR(64) (nullable)
  source_realisasi_id             FK → realisasi_pemupukans (nullable)
  created_at                      TIMESTAMP
```

## Event Types

| Event | Trigger |
|-------|---------|
| ANALISIS_AWAL | Analisis RBS pertama kali dijalankan |
| REALISASI_DIBUAT | Realisasi baru dicatat |
| REALISASI_DIPERBARUI | Realisasi di-update |
| REALISASI_DIBATALKAN | Realisasi dibatalkan |
| TAHAP_1_SEBAGIAN | Tahap 1 direalisasikan sebagian |
| TAHAP_1_SELESAI | Tahap 1 selesai (jumlah memenuhi rencana) |
| TAHAP_2_SIAP | Tahap 2 siap diaplikasikan |
| PROGRAM_SELESAI | Kebutuhan tahunan terpenuhi |

## Alur Pencatatan

```
Controller::store() → refreshService → recordOperationalHistory(REALISASI_DIBUAT)
Controller::update() → refreshService → recordOperationalHistory(REALISASI_DIPERBARUI)
Controller::cancel() → refreshService → recordOperationalHistory(REALISASI_DIBATALKAN)
```

## Tampilan

Histori operasional ditampilkan pada:
- `realisasi_pemupukan/show.blade.php` — histori terkait realisasi tertentu
- `rbs/detail.blade.php` — histori lengkap per blok
- `laporan/show.blade.php` — histori pada laporan

## Aturan

1. Histori TIDAK PERNAH dihapus
2. Setiap entry adalah snapshot immutable
3. `analysis_fingerprint` pada histori = fingerprint rekomendasi SETELAH refresh
4. `source_realisasi_id` menunjukkan realisasi yang memicu perubahan
