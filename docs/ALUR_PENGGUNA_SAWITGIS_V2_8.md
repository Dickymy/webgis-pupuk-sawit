# Alur Pengguna SawitGIS — v2.8

## Alur Kerja Utama (Stepper)

```
┌────────────┐    ┌──────────┐    ┌─────────────┐    ┌────────────────┐
│ 1. Tambah  │───►│ 2. Tambah│───►│ 3. Isi      │───►│ 4. Jalankan    │
│   Anggota  │    │   Blok   │    │   Kondisi   │    │    Analisis    │
└────────────┘    └──────────┘    └─────────────┘    └────────────────┘
                                                              │
┌────────────┐    ┌──────────────┐    ┌──────────────┐       ▼
│ 8. Cetak   │◄───│ 7. Pantau    │◄───│ 6. Catat     │◄──┌────────────┐
│   Laporan  │    │   Tahap      │    │   Realisasi  │   │ 5. Lihat   │
└────────────┘    └──────────────┘    └──────────────┘   │  Rekomendasi│
                                                          └────────────┘
```

## Detail Setiap Langkah

### 1. Tambah Anggota
- Isi nama, nomor HP, alamat
- Setiap anggota bisa punya banyak blok lahan

### 2. Tambah Blok Lahan
- Isi nama blok, luas, SPH, tahun tanam
- Upload GeoJSON untuk peta (opsional)
- Pilih fase tanaman jika umur = 3 tahun

### 3. Isi Kondisi Lahan
- Observasi lapangan: warna daun, pH, kelembaban, drainase
- Data curah hujan (otomatis dari API atau manual)
- Minimal 1 field harus terisi untuk analisis

### 4. Jalankan Analisis
- Klik tombol "Jalankan Analisis" pada halaman RBS
- Sistem mengevaluasi rule, menghitung dosis, menentukan tahap aktif
- Program pemupukan otomatis dibuat jika belum ada

### 5. Lihat Rekomendasi
- Ringkasan: kondisi tanaman, kelayakan, tahap aktif
- Jumlah pupuk: kebutuhan tahunan, tahap aktif, sisa, karung
- Alasan dan petunjuk
- Detail teknis (bisa disembunyikan)

### 6. Catat Realisasi
- Hanya tersedia jika tahap siap dan ada jumlah > 0
- Isi tanggal, jumlah aktual Urea dan KCl
- Status: Selesai atau Sebagian
- Override: hanya jika jumlah melebihi rencana

### 7. Pantau Tahap Berikutnya
- Setelah Tahap 1: tunggu minimal 60 hari
- Setelah Tahap 2: program selesai jika kebutuhan terpenuhi
- Sistem otomatis menampilkan status berikutnya

### 8. Cetak Laporan
- Filter: kondisi tanaman, kelayakan, status tahap, status program
- Subtotal per anggota
- Grand total berdasarkan jumlah tahap aktif saat ini
- Unduh PDF per blok

## Tindakan Berikutnya (Contoh Pesan)

| Situasi | Pesan |
|---------|-------|
| Tahap 1 siap | "Catat realisasi Tahap 1" |
| Tahap 1 selesai | "Tahap 2 dapat dilakukan mulai [tanggal]" |
| Curah hujan tinggi | "Pemupukan ditunda karena curah hujan terlalu tinggi" |
| Program selesai | "Program pemupukan tahun ini telah selesai" |
| Belum ada kondisi | "Isi kondisi lahan terlebih dahulu" |
