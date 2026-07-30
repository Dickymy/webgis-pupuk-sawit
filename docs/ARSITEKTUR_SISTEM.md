# Arsitektur Sistem SawitGIS

Dokumen ini adalah rujukan teknis keadaan aplikasi terbaru. Catatan revisi versi lama tidak menjadi acuan implementasi.

## Modul

- Data kebun: `Anggota`, `BlokLahan`, polygon GeoJSON, luas, SPH, tahun tanam, fase, tanah, dan topografi.
- Observasi: `KondisiLahan`, gejala daun, hujan, musim, drainase, kelembapan, gangguan lapangan, tanggal pemupukan terakhir, dan foto.
- Rule Based: `RuleBaseLanjutan`, pengelolaan rule oleh admin, evaluasi fakta, sumber, status validasi, dan prioritas.
- Rekomendasi: `RekomendasiRbs`, snapshot data, dosis tahunan, kesiapan, tahap aktif, alasan, dan jejak rule.
- Pelaksanaan: `ProgramPemupukan`, `RealisasiPemupukan`, serta `RekomendasiOperasionalHistory`.
- Laporan: ringkasan rekomendasi, realisasi, filter, detail, dan PDF.

## Alur analisis

```text
Blok dan observasi terbaru
        |
        v
Kecukupan fakta minimum
        |
        v
Rule aktif diurutkan menurut prioritas
        |
        v
Forward chaining sampai tidak ada fakta baru
        |
        +--> indikasi kondisi daun
        +--> keputusan waktu berbasis hujan
        |
        v
Dosis tahunan dari umur dan fase
        |
        v
Jumlah pokok = luas x SPH
        |
        v
Kesiapan lapangan dan interval
        |
        v
Tahap aktif, rekomendasi, dan laporan
```

## Rule sistem

Seeder kanonik berada di `database/seeders/RuleBaseSeeder.php`. Sistem awal berisi:

- Empat rule gejala daun: nitrogen, kalium, magnesium, dan boron.
- Tiga rule curah hujan: terlalu rendah, mendukung, dan terlalu tinggi.

Admin dapat menambah dan mengedit rule melalui aplikasi. Rule baru disimpan nonaktif sampai sumber dan konflik diperiksa. Seeder tidak menimpa rule yang sudah ada agar perubahan admin tetap tersimpan.

## Batas tanggung jawab

- `RbsService` mengevaluasi rule, tetapi tidak menjadi sumber angka dosis.
- `PahanDoseReferenceService` adalah satu-satunya sumber rentang dosis Urea dan KCl.
- `FertilizationWindowService` memeriksa waktu aplikasi.
- `CurrentApplicationCalculator` dan `FertilizationRealizationService` mengelola tahap serta sisa kebutuhan.
- Controller mengatur request dan response; perhitungan bisnis ditempatkan di service.
- Blade hanya menyajikan data dan tidak boleh menjadi sumber keputusan bisnis.

## Status utama

Status kondisi tanaman dan status kesiapan pemupukan adalah dua hal berbeda. Kondisi daun dapat menunjukkan gejala, sedangkan pelaksanaan masih dapat ditunda karena hujan atau interval. Tampilan, peta, laporan, dan realisasi harus membaca status yang sama dari model rekomendasi terbaru.

## Data historis

Rekomendasi menyimpan snapshot agar laporan lama tidak berubah saat data blok diperbarui. Rekomendasi historis tidak boleh digunakan untuk mencatat realisasi baru. Realisasi harus terhubung ke program dan rekomendasi aktif pada tahun yang sama.

## Aturan perubahan

Saat menambah fitur:

1. Jangan memasukkan angka dosis ke rule visual.
2. Jangan menambah pilihan observasi yang tidak dapat dipetakan ke rule atau tindakan yang jelas.
3. Tambahkan validasi request dan pengujian untuk perubahan alur.
4. Pertahankan label awam pada antarmuka; kode teknis hanya untuk penyimpanan.
5. Jalankan Pint, seluruh test, build, dan health check sebelum commit.
