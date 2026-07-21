# Keterbatasan yang Diketahui — SawitGIS v2.9

## Pernyataan Penting

Aplikasi SawitGIS adalah **Sistem Pendukung Keputusan** (Decision Support System), bukan pengganti keahlian agronomis. Rekomendasi yang dihasilkan bersifat estimasi.

## Keterbatasan Teknis

### 1. Rekomendasi adalah Estimasi
- Dosis didasarkan pada kisaran referensi Pahan (2013), bukan analisis tanah laboratorium
- Strategi estimasi menggunakan nilai tengah (midpoint) dari kisaran min–max
- Kondisi aktual lapangan mungkin berbeda dari asumsi model

### 2. Bukan Pengganti Analisis Laboratorium
- pH tanah diukur secara sederhana (kertas lakmus / pH meter portable)
- Tidak ada analisis unsur hara tanah lengkap
- Tidak ada analisis jaringan daun (leaf analysis)

### 3. Cuaca Otomatis Bergantung Koneksi
- Data curah hujan otomatis dari Open-Meteo memerlukan internet
- Jika tidak ada koneksi, curah hujan harus dimasukkan manual
- Data cuaca adalah estimasi grid, bukan pengukuran di titik lahan

### 4. Hasil Lapangan Memerlukan Pertimbangan Penyuluh
- Rekomendasi harus dikonsultasikan dengan penyuluh pertanian
- Faktor lokal (sejarah lahan, varietas, pola hujan spesifik) tidak sepenuhnya tercakup
- Keputusan pemupukan final tetap di tangan pengelola kebun

### 5. Ketepatan Bergantung Kelengkapan Data
- Skor keandalan mengukur kelengkapan data, bukan akurasi agronomis
- Data observasi visual bersifat subjektif
- Rekomendasi dengan skor rendah hanya sebagai panduan kasar

### 6. Multiplier Tidak Diaktifkan
- Faktor koreksi tanah, topografi, dan waktu belum memiliki sumber yang cukup kuat
- Sistem hanya menggunakan dosis dasar Pahan (2013)

### 7. Satu Admin
- Tidak ada sistem multi-user / multi-role
- Tidak ada audit trail per pengguna
- Cocok untuk kelompok tani tunggal

### 8. Peta Bergantung Layanan Tile
- OpenStreetMap dan ESRI memerlukan koneksi internet
- Jika layanan tile gagal, peta tidak tampil tapi data tetap aman
- Polygon GeoJSON tetap tersimpan lokal

## Rekomendasi Penggunaan

1. Gunakan sebagai **alat bantu perencanaan**, bukan keputusan final
2. Selalu konfirmasi dengan penyuluh sebelum aplikasi pupuk besar
3. Lengkapi data observasi untuk meningkatkan skor keandalan
4. Backup database secara rutin sebelum pengujian
5. Catat perbedaan antara rekomendasi dan praktik lapangan
