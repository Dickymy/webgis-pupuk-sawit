# Panduan Pengujian SawitGIS

## Pengujian otomatis

Jalankan dari root proyek:

```bash
vendor/bin/pint --test
php artisan test
npm run build
php artisan sawit:health-check --dry-run
```

Semua perintah harus berhasil sebelum commit atau push.

## Skenario uji pengguna

Gunakan perangkat laptop dan telepon genggam. Nilai setiap pernyataan dari 1 (sangat sulit/tidak setuju) sampai 5 (sangat mudah/setuju).

| No. | Pernyataan | Nilai |
|---:|---|:---:|
| 1 | Login mudah dilakukan | |
| 2 | Blok mudah ditemukan pada daftar dan peta | |
| 3 | Form observasi mudah dipahami | |
| 4 | Istilah status mudah dibedakan | |
| 5 | Hasil rekomendasi mudah dipahami | |
| 6 | Dosis Urea dan KCl ditampilkan dengan jelas | |
| 7 | Alasan siap atau ditunda mudah dipahami | |
| 8 | Pencatatan realisasi mudah dilakukan | |
| 9 | Riwayat dan laporan mudah dibaca | |
| 10 | Tombol mudah dibedakan dari informasi biasa | |
| 11 | Tampilan nyaman digunakan melalui telepon genggam | |

Catat juga halaman, tindakan, hasil yang diharapkan, hasil yang terjadi, perangkat, browser, dan tangkapan layar saat menemukan masalah.

## Skenario data minimum

Uji setidaknya:

- blok tanpa observasi;
- daun normal;
- masing-masing dari empat gejala daun yang didukung;
- hujan rendah, mendukung, dan tinggi;
- blok yang masih menunggu interval;
- realisasi sebagian dan realisasi selesai;
- rule baru yang bentrok dengan rule aktif;
- unggah polygon yang tumpang tindih atau tidak valid;
- foto observasi ditambah, diganti, dan dihapus.

Pengujian lapangan menilai kemudahan penggunaan dan konsistensi sistem. Pengujian ini tidak membuktikan ketepatan agronomis tanpa pembandingan ahli atau data lapangan.
