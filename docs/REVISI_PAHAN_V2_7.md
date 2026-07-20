# REVISI PAHAN v2.7 — Penutupan Celah Final

## Ringkasan

Versi 2.7 menutup seluruh celah validasi realisasi, manipulasi request, status selesai palsu, pencampuran realisasi antarprogram, histori operasional, fingerprint, dan laporan historis.

## Perubahan Utama

### 1. Validasi Kelayakan Realisasi (RealisasiEligibilityService)
- Form realisasi hanya dibuka jika status: TAHAP_1_SIAP, TAHAP_1_SEBAGIAN, atau TAHAP_2_SIAP
- Akses URL create langsung tetap ditolak
- Tombol realisasi disembunyikan saat tidak layak

### 2. Server Menentukan Rencana, Tahap, dan Tahun
- Form tidak lagi mengirim: tahap, tahun_program, urea_rencana_kg, kcl_rencana_kg
- Server menghitung ulang dari rekomendasi dan status realisasi
- Input palsu dari browser diabaikan

### 3. Validasi Status SELESAI
- Status SELESAI hanya diterima jika total kumulatif >= rencana tahap - toleransi 0.01 kg
- Urea dan KCl dievaluasi independen
- Beberapa record SEBAGIAN yang totalnya memenuhi rencana boleh menyelesaikan tahap

### 4. Program Pemupukan Tahunan
- Tabel `program_pemupukans` (uuid, blok, tahun, status)
- Satu blok hanya satu program aktif per tahun
- Realisasi terisolasi dalam program
- Program otomatis dibuat saat realisasi pertama

### 5. Histori Operasional
- Tabel `rekomendasi_operasional_histories`
- Dicatat setiap: create, update, cancel realisasi
- Snapshot: active_stage, status_stage, sisa tahunan, fingerprint
- Histori tidak pernah dihapus

### 6. Fingerprint Diperkuat
- Memasukkan: program_pemupukan_id, realisasi aktif (id, tahap, tanggal, jumlah, status, override)
- Data realisasi diurutkan stabil sebelum hashing
- Perubahan tanggal/jumlah/status/override/pembatalan → fingerprint berubah

### 7. Laporan Menggunakan Snapshot
- Umur dan fase menggunakan snapshot (bukan data blok terkini)
- Metode aplikasi memakai umur/fase snapshot
- Status legacy tidak digunakan untuk warna atau keputusan utama
- PDF menampilkan riwayat realisasi lengkap

## File Baru
- `app/Models/ProgramPemupukan.php`
- `app/Models/RekomendasiOperasionalHistory.php`
- `app/Services/RealisasiEligibilityService.php`
- `app/Console/Commands/FinalizePahanV2_7.php`
- `database/migrations/2026_07_22_000001_create_program_pemupukans_table.php`
- `database/migrations/2026_07_22_000002_create_rekomendasi_operasional_histories_table.php`

## File Dimodifikasi
- `app/Http/Controllers/RealisasiPemupukanController.php`
- `app/Http/Controllers/LaporanController.php`
- `app/Http/Requests/StoreRealisasiPemupukanRequest.php`
- `app/Models/RealisasiPemupukan.php`
- `app/Models/RekomendasiRbs.php`
- `app/Models/BlokLahan.php`
- `app/Services/FertilizationRealizationService.php`
- `app/Services/RecommendationOperationalRefreshService.php`
- `config/fertilization.php`
- `resources/views/realisasi_pemupukan/create.blade.php`
- `resources/views/rbs/detail.blade.php`
- `resources/views/rbs/partials/_hasil_rbs.blade.php`
- `resources/views/laporan/show.blade.php`
- `resources/views/laporan/pdf.blade.php`

## Referensi
Pahan, Iyung. 2013. *Panduan Lengkap Kelapa Sawit*. Cetakan XI. Jakarta: Penebar Swadaya.
