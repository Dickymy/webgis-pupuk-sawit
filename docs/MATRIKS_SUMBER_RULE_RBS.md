# Matriks Sumber Rule Based SawitGIS

Dokumen ini menjelaskan sumber dan batas keputusan sistem agar hasil dapat ditelusuri saat pengujian maupun sidang.

## Batas keputusan

SawitGIS tidak menyatakan bahwa pengamatan visual merupakan diagnosis pasti kekurangan hara. Gejala daun digunakan sebagai penyaringan awal. Dosis Urea dan KCl tidak dinaikkan atau diturunkan karena gejala; kebutuhan tahunan tetap dihitung menurut umur dan fase tanaman menggunakan Iyung Pahan (2013).

```text
fakta observasi
-> rule gejala daun dan curah hujan
-> dosis Urea/KCl menurut umur dan fase
-> pemeriksaan kesiapan
-> rekomendasi dan pelaksanaan
```

Forward chaining adalah metode mesin untuk menelusuri fakta dan rule IF-THEN. Isi premis serta kesimpulannya tetap harus memiliki sumber agronomi.

## Rule sistem aktif

| Kode | Fakta | Kesimpulan yang diizinkan | Sumber | Batas interpretasi |
|---|---|---|---|---|
| `VIS-N-01` | Daun bagian bawah menguning | Dapat berkaitan dengan kekurangan nitrogen | Barus dkk. (2025) | Bukan diagnosis pasti dan tidak menaikkan dosis Urea |
| `VIS-K-02` | Bercak kuning atau transparan pada daun tua | Dapat berkaitan dengan kekurangan kalium | Barus dkk. (2025) | Perlu konfirmasi riwayat atau analisis daun |
| `VIS-MG-01` | Tepi daun tua pada bagian terbuka menguning | Dapat berkaitan dengan kekurangan magnesium | Barus dkk. (2025) | Tidak menentukan pupuk atau dosis otomatis |
| `VIS-B-01` | Daun muda berbentuk kait atau memendek | Dapat berkaitan dengan kekurangan boron | Barus dkk. (2025); PPKS (2023) | Tidak menentukan Borax atau dosis otomatis |
| `WAKTU-HUJAN-RENDAH` | Hujan di bawah 60 mm/bulan | Pemupukan ditunda | Pradiko dkk. (2021) | Menentukan waktu, bukan dosis |
| `WAKTU-HUJAN-OPTIMAL` | Hujan 100-250 mm/bulan | Waktu mendukung jika syarat lain terpenuhi | Barus dkk. (2025) | Drainase, kelembapan, dan interval tetap diperiksa |
| `WAKTU-HUJAN-TINGGI` | Hujan di atas 300 mm/bulan | Pemupukan ditunda | Pradiko dkk. (2021) | Menentukan waktu, bukan dosis |

Jika tidak ada rule gejala yang sesuai, hasilnya adalah “tidak ditemukan gejala daun yang sesuai dengan rule aktif”, bukan “seluruh unsur hara sudah cukup”.

## Perhitungan dosis

| Komponen | Masukan | Keluaran | Sumber |
|---|---|---|---|
| Urea | Umur, fase, luas, dan SPH | Rentang, estimasi tahunan, dan total per blok | Iyung Pahan (2013) |
| KCl | Umur, fase, luas, dan SPH | Rentang, estimasi tahunan, dan total per blok | Iyung Pahan (2013) |

Dosis dihitung di `PahanDoseReferenceService` dan `FertilizationCalculationService`. Rule visual tidak boleh berisi angka dosis Urea atau KCl.

## Parameter pendukung

- Drainase dan kelembapan digunakan sebagai pengaman waktu aplikasi.
- Riwayat pemupukan digunakan untuk interval serta urutan tahap.
- Musim membantu menjelaskan data hujan.
- Foto hanya menjadi dokumentasi observasi.
- Jenis tanah dan topografi menjadi identitas blok, bukan pengali dosis.
- Gangguan lain dan catatan lapangan tidak langsung menentukan pupuk.

## Adaptasi perancangan sistem

Hal berikut harus dijelaskan sebagai keputusan desain, bukan kutipan langsung:

1. Estimasi operasional menggunakan nilai tengah dari rentang dosis.
2. Kebutuhan tahunan saat ini dibagi menjadi dua tahap 50:50.
3. Interval minimum antartahap ditetapkan 120 hari.
4. Pengamatan visual hanya menghasilkan indikasi awal.

Adaptasi tersebut sebaiknya dimasukkan dalam lembar validasi ahli atau penyuluh.

## Sumber utama

1. Iyung Pahan (2013), *Panduan Lengkap Kelapa Sawit*. Digunakan untuk rentang dosis berdasarkan umur dan fase.
2. Barus, Hutagalung, Syarovy, dan Fauzi (2025), “Teknik Pemupukan Tanaman Menghasilkan Kelapa Sawit Menggunakan Prinsip Empat Tepat (4T)”. DOI: https://doi.org/10.22302/iopri.war.warta.v30i1.129
3. Pradiko, Rahutomo, Siregar, dan Darlan (2021), “Rekomendasi Waktu Pemupukan untuk 22 Zona Perkebunan Kelapa Sawit di Indonesia Berdasarkan Pola Curah Hujan”. DOI: https://doi.org/10.22302/iopri.war.warta.v26i2.48
4. PPKS (2023), artikel gejala defisiensi dan toksisitas boron pada kelapa sawit: https://warta.iopri.org/index.php/Warta/article/view/105
5. Russell dan Norvig, *Artificial Intelligence: A Modern Approach*. Digunakan untuk dasar forward chaining.

## Bukti yang perlu disiapkan

- Foto identitas buku Iyung Pahan (2013).
- Foto tabel dosis yang benar-benar ditranskripsikan ke aplikasi.
- Tabel perbandingan angka buku dengan `config/fertilization.php`.
- Lembar validasi rule dan adaptasi sistem.
- Hasil pengujian rule terpenuhi, tidak terpenuhi, data kurang, hujan ekstrem, dan interval belum cukup.

Kalimat ringkas untuk sidang:

> Sistem menggunakan forward chaining untuk mencocokkan fakta observasi dengan rule IF-THEN. Gejala visual menghasilkan indikasi awal, kebutuhan Urea dan KCl dihitung berdasarkan umur serta fase dari Iyung Pahan (2013), sedangkan data hujan menentukan kesiapan waktu pemupukan.
