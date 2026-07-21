# Panduan Pengujian Lapangan — SawitGIS v2.9

## Tujuan Pengujian

Menguji kemudahan penggunaan dan kebergunaan sistem rekomendasi pemupukan SawitGIS pada kondisi nyata di lapangan. Pengujian bersifat terbatas dan tidak menggantikan rekomendasi penyuluh.

## Pengguna Uji

- Anggota kelompok tani Suluh Tani
- Penyuluh pertanian setempat
- Minimal 3–5 responden

## Perangkat

- Smartphone Android (Chrome)
- Laptop (opsional, untuk input data lebih nyaman)
- Koneksi internet (minimal 3G/4G)

## Data yang Dimasukkan

Untuk setiap blok lahan yang diuji:
1. Identitas blok (nama, luas, SPH, tahun tanam)
2. Observasi kondisi: warna daun, pH (jika ada alat), curah hujan
3. Kondisi fisik: drainase, gulma, hama
4. Realisasi pemupukan (jika sudah dilaksanakan)

## Cara Mencatat Masalah

Jika menemukan masalah saat menggunakan aplikasi:
1. Catat halaman/fitur yang bermasalah
2. Catat apa yang diharapkan vs apa yang terjadi
3. Screenshot jika memungkinkan
4. Laporkan ke peneliti

## Larangan Penting

- **JANGAN** menggunakan dosis rekomendasi sebagai keputusan final tanpa konsultasi penyuluh
- Rekomendasi sistem adalah **estimasi** berdasarkan data yang dimasukkan
- Hasil lapangan tetap memerlukan pertimbangan profesional
- Ketepatan bergantung kelengkapan data yang dimasukkan

## Cara Membandingkan Hasil Sistem dengan Lapangan

1. Catat rekomendasi sistem (kg Urea, kg KCl per blok)
2. Bandingkan dengan dosis yang biasa dipakai petani
3. Catat selisih dan diskusikan dengan penyuluh
4. Berikan feedback pada form uji pengguna

## Backup Sebelum Uji

```bash
php artisan sawit:backup-database
```

## Cara Membatalkan Realisasi Salah

Jika terjadi kesalahan pencatatan:
1. Buka menu Realisasi Pemupukan
2. Cari realisasi yang salah
3. Klik tombol "Batalkan"
4. Masukkan alasan pembatalan
5. Data yang dibatalkan tidak akan dihitung dalam total

## Setelah Pengujian

1. Kumpulkan form uji pengguna
2. Backup database: `php artisan sawit:backup-database`
3. Catat temuan dan masalah
4. Reset data demo jika diperlukan
