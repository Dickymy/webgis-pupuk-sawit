# Alur Realisasi Pemupukan — Pahan v2.5

## Definisi

**Kebutuhan Tahunan**: Total pupuk yang dibutuhkan blok lahan selama satu tahun, dihitung dari dosis per pokok × jumlah pokok.

**Aplikasi Saat Ini**: Jumlah pupuk yang boleh diaplikasikan pada tahap aktif sekarang. BUKAN total kebutuhan tahunan.

## Alur Tahap 1

1. Analisis dilakukan → kebutuhan tahunan dihitung
2. Jika layak dan belum ada realisasi → **Aplikasi saat ini = 50% kebutuhan tahunan**
3. Pengguna melakukan pemupukan
4. Pengguna mencatat realisasi (bisa kurang/tepat/lebih dari rencana)

## Alur Tahap 2

Tahap 2 hanya siap jika:
- Tahap 1 memiliki tanggal realisasi
- Minimal 60 hari telah berlalu sejak realisasi Tahap 1
- Curah hujan numerik tersedia dan dalam rentang layak (100-250 mm/bulan)
- Kelayakan aplikasi terpenuhi
- Masih ada sisa kebutuhan tahunan

**Jumlah Tahap 2 = Sisa kebutuhan tahunan aktual**

### Contoh

```
Kebutuhan tahunan Urea = 544 kg
Rencana Tahap 1 = 272 kg (50%)
Realisasi Tahap 1 = 250 kg (kurang 22 kg)
Sisa Urea = 544 - 250 = 294 kg

Tahap 2 merekomendasikan: 294 kg (bukan 272 kg)
```

## Status Tahap

| Status | Makna |
|--------|-------|
| TAHAP_1_SIAP | Tahap 1 siap diaplikasikan (50%) |
| TAHAP_1_SEBAGIAN | Tahap 1 direalisasikan sebagian |
| MENUNGGU_INTERVAL | Menunggu 60 hari setelah Tahap 1 |
| MENUNGGU_KELAYAKAN | Menunggu kondisi layak |
| TAHAP_2_SIAP | Tahap 2 siap (sisa aktual) |
| SELESAI_TAHUNAN | Kebutuhan tahunan terpenuhi |
| PERLU_VERIFIKASI_REALISASI | Data realisasi perlu dicek |

## Realisasi Kurang/Lebih

- **Kurang**: Sisa ditambahkan ke Tahap 2
- **Tepat**: Tahap 2 = 50% seperti rencana
- **Lebih**: Tahap 2 berkurang, tidak boleh melebihi total tahunan tanpa konfirmasi

## Selesai Tahunan

Ketika total realisasi ≥ kebutuhan tahunan:
- Aplikasi saat ini = 0
- Jadwal kosong
- Status = SELESAI_TAHUNAN
