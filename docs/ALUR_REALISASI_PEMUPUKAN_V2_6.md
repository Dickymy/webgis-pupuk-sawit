# Alur Realisasi Pemupukan — Pahan v2.6

## Cara Mencatat Realisasi

1. Buka halaman **Detail Analisis RBS** untuk blok yang ingin dicatat
2. Klik tombol **"Catat Realisasi Tahap 1"** (atau tombol sesuai status)
3. Isi form:
   - Tanggal realisasi
   - Jumlah Urea dan KCl yang sebenarnya diaplikasikan
   - Status: SELESAI atau SEBAGIAN
   - Catatan (opsional, wajib jika melebihi rencana)
4. Klik **Simpan Realisasi**

## Realisasi Sebagian

Jika hanya sebagian dosis yang diaplikasikan:
- Pilih status **SEBAGIAN**
- Sistem akan menampilkan sisa yang belum terpenuhi
- Tombol berubah menjadi **"Lanjutkan Realisasi Tahap 1"**
- Admin dapat menambahkan realisasi lanjutan sampai Tahap 1 terpenuhi

## Pembatalan

- Klik **"Batalkan Realisasi"** pada halaman detail realisasi
- Record TIDAK dihapus — status berubah menjadi BATAL
- Jumlah yang dibatalkan tidak dihitung dalam ringkasan
- Status tahap akan direcalculate otomatis

## Kelebihan Realisasi

### Melebihi Rencana Tahap (tapi masih di bawah tahunan)
- Centang checkbox konfirmasi
- Wajib isi catatan alasan
- Tetap dapat disimpan

### Melebihi Kebutuhan Tahunan
- Ditolak secara default
- Untuk melanjutkan:
  - Centang checkbox override
  - Isi alasan override (wajib)
  - Record ditandai `override_annual_limit = true`
  - Muncul di audit

## Tahap 2

### Syarat Tahap 2 Aktif
1. Tahap 1 sudah SELESAI (bukan SEBAGIAN)
2. Interval minimal 60 hari sejak realisasi terakhir Tahap 1
3. Kelayakan aplikasi terpenuhi (curah hujan 100-250 mm)

### Jumlah Tahap 2
- Sisa kebutuhan tahunan aktual (bukan flat 50%)
- Contoh: Tahunan 544 kg, Tahap 1 realisasi 280 kg → Tahap 2 = 264 kg

## Interval 60 Hari

- Dihitung dari tanggal realisasi Tahap 1 terakhir
- Sebelum 60 hari: status = MENUNGGU_INTERVAL
- Setelah 60 hari dan kelayakan terpenuhi: TAHAP_2_SIAP

## Hubungan Rekomendasi dan Program Tahunan

- Setiap realisasi terkait `rekomendasi_rbs_id` spesifik
- Field `tahun_program` memastikan realisasi tidak tercampur antar tahun
- Realisasi dari rekomendasi berbeda tidak dicampur dalam satu ringkasan

## Histori Snapshot

- Perubahan luas/SPH blok SETELAH analisis tidak mempengaruhi laporan historis
- Laporan lama tetap menampilkan data saat analisis dibuat
- Laporan baru menggunakan snapshot baru
