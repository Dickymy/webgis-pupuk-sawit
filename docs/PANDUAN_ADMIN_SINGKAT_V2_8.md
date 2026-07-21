# Panduan Admin SawitGIS — v2.8

## Alur Kerja Utama

```
1. Tambah Anggota → 2. Tambah Blok → 3. Isi Kondisi → 4. Jalankan Analisis
→ 5. Lihat Rekomendasi → 6. Catat Realisasi → 7. Pantau → 8. Cetak Laporan
```

## Hal Baru di v2.8

### Program Pemupukan Otomatis

Setiap blok lahan kini memiliki "program pemupukan" tahunan. Program ini:
- Dibuat otomatis saat analisis pertama dijalankan
- Mengelompokkan semua aktivitas pupuk dalam satu tahun
- Otomatis selesai jika kebutuhan tahunan terpenuhi

**Yang perlu diketahui**: Anda tidak perlu membuat program secara manual. Sistem otomatis mengelolanya.

### Rekomendasi Historis Tidak Bisa Dipakai

Jika ada rekomendasi lama (bukan yang terbaru), tombol "Catat Realisasi" tidak akan muncul. Selalu gunakan rekomendasi terbaru.

### Laporan Lebih Akurat

Laporan sekarang menghitung subtotal berdasarkan jumlah pupuk yang benar-benar perlu dilakukan saat ini, bukan kebutuhan total tahunan.

## Langkah-langkah

### Menjalankan Analisis

1. Buka menu **Analisis RBS**
2. Pilih blok yang sudah punya data kondisi
3. Klik **Jalankan Analisis**
4. Lihat rekomendasi yang muncul

### Mencatat Realisasi

1. Buka detail rekomendasi terbaru
2. Jika tombol "Catat Realisasi" aktif, klik
3. Isi tanggal dan jumlah aktual yang diaplikasikan
4. Pilih status: Selesai atau Sebagian
5. Simpan

### Memantau Tahap Berikutnya

Setelah Tahap 1 selesai:
- Sistem otomatis menunggu 60 hari
- Setelah 60 hari, Tahap 2 akan siap
- Jika curah hujan tidak sesuai, pemupukan ditunda otomatis

### Mencetak Laporan

1. Buka menu **Laporan**
2. Filter sesuai kebutuhan
3. Klik blok untuk detail
4. Klik **Unduh PDF** untuk mencetak

## Tanya Jawab Singkat

**T: Kenapa tombol realisasi tidak muncul?**
A: Mungkin karena tahap belum siap, curah hujan tidak sesuai, atau kebutuhan tahunan sudah terpenuhi. Lihat alasan di halaman detail.

**T: Kenapa subtotal laporan berbeda dari sebelumnya?**
A: Laporan sekarang menghitung berdasarkan jumlah tahap aktif saat ini, bukan kebutuhan total tahunan yang mungkin sudah sebagian terpenuhi.

**T: Apa itu "Program Selesai"?**
A: Artinya seluruh kebutuhan pupuk tahunan untuk blok tersebut sudah tercatat. Tidak ada lagi yang perlu diaplikasikan tahun ini.
