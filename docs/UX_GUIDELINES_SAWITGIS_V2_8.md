# UX Guidelines SawitGIS — v2.8

## Prinsip Utama

1. **Satu tombol utama per halaman** — tombol paling relevan untuk langkah berikutnya
2. **Jangan tampilkan kode teknis** — gunakan label yang mudah dipahami
3. **Bahasa sederhana** — istilah yang familiar bagi admin kelompok tani
4. **Konsistensi warna** — status yang sama = warna yang sama di semua halaman
5. **Mobile friendly** — tombol minimal 44px, form satu kolom di ponsel

## Penggantian Istilah

| Kode Internal | Tampilan Pengguna |
|---------------|-------------------|
| `TAHAP_1_SIAP` | Tahap 1 Siap Dilaksanakan |
| `TAHAP_1_SEBAGIAN` | Tahap 1 Direalisasikan Sebagian |
| `MENUNGGU_INTERVAL` | Menunggu 60 Hari |
| `MENUNGGU_KELAYAKAN` | Menunggu Kelayakan Aplikasi |
| `TAHAP_2_SIAP` | Tahap 2 Siap Dilaksanakan |
| `SELESAI_TAHUNAN` | Kebutuhan Tahunan Terpenuhi |
| `LAYAK_DIJADWALKAN` | Layak Dijadwalkan |
| `GEJALA_BERAT` | Gejala Berat Terdeteksi |
| Current Application | Pemupukan yang Perlu Dilakukan Sekarang |
| Annual Requirement | Kebutuhan Pupuk Tahunan |
| Operational History | Riwayat Perubahan Tahap |
| Eligibility | Kelayakan Pemupukan |
| Override Annual Limit | Izinkan Melebihi Kebutuhan Tahunan |

## Konsistensi Warna

| Warna | Makna | Contoh |
|-------|-------|--------|
| Hijau | Siap, layak, selesai | Tahap siap, program selesai |
| Kuning/Amber | Menunggu, perlu perhatian | Interval, sebagian |
| Merah | Tidak layak, error | Gejala berat, data salah |
| Biru | Informasi | Menunggu interval |

## Mobile Responsive

- Tombol: minimal tinggi 44px
- Form: satu kolom pada ponsel
- Tabel: berubah menjadi card pada layar kecil
- Tidak ada scroll horizontal untuk info penting
- Sidebar tidak menutupi konten

## Ukuran Verifikasi

- 375 × 812 (iPhone)
- 768 × 1024 (iPad)
- 1366 × 768 (Laptop)

## Empty State

Jangan hanya tampilkan "Data tidak ditemukan". Contoh:

```
Belum ada data kondisi lahan.
Isi kondisi lahan terlebih dahulu agar analisis dapat dijalankan.
[Tombol: Isi Kondisi Lahan]
```

## Pesan Validasi

Format:
- **Apa yang salah**: deskripsi masalah
- **Mengapa salah**: penjelasan
- **Apa yang harus dilakukan**: solusi

Contoh:
```
Status Selesai tidak dapat dipilih karena jumlah realisasi belum memenuhi rencana tahap.
Urea: 25.00 / 50.00 kg. Tambah realisasi sebagian atau ubah status ke Sebagian.
```
