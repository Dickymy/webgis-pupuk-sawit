# TRACEABILITY RULE BASE — PAHAN-V2 FINAL

**Referensi Utama:** Pahan, I. 2013. *Panduan Lengkap Kelapa Sawit*. Cetakan XI. Jakarta: Penebar Swadaya.

---

## Tabel Traceability Rule

| Kode Rule | Jenis Rule | Kondisi Utama | Kesimpulan | Sumber | Halaman | Tabel | Status Validasi |
|-----------|-----------|---------------|------------|--------|---------|-------|----------------|
| VIS-N-01 | DIAGNOSIS_VISUAL | Warna daun: Kuning Merata + Defisiensi: N | Defisiensi Nitrogen — Klorosis Umum | Pahan 2013 | 152-153 | 9.5 | TERVERIFIKASI_SUMBER |
| VIS-N-02 | DIAGNOSIS_VISUAL | Warna daun: Hijau Pucat + Defisiensi: N | Defisiensi Nitrogen Ringan — Pertumbuhan Lambat | Pahan 2013 | 152-153 | 9.5 | TERVERIFIKASI_SUMBER |
| VIS-K-01 | DIAGNOSIS_VISUAL | Warna daun: Oranye/Kemerahan + Defisiensi: K | Defisiensi Kalium — Orange Frond (OF) | Pahan 2013 | 152-153 | 9.5 | TERVERIFIKASI_SUMBER |
| VIS-K-02 | DIAGNOSIS_VISUAL | Warna daun: Kuning Tepi + Defisiensi: K | Defisiensi Kalium Sedang — Marginal Chlorosis | Pahan 2013 | 152-153 | 9.5 | TERVERIFIKASI_SUMBER |
| VIS-MG-01 | DIAGNOSIS_VISUAL | Warna daun: Kuning Antar Tulang + Defisiensi: Mg | Defisiensi Magnesium — Interveinal Chlorosis | Pahan 2013 | 152-153 | 9.5 | TERVERIFIKASI_SUMBER |
| VIS-B-01 | DIAGNOSIS_VISUAL | Pelepah: Pertumbuhan Terhambat + Defisiensi: B | Defisiensi Boron — Pucuk Abnormal | Pahan 2013 | 152-153 | 9.5 | TERVERIFIKASI_SUMBER |
| VIS-P-01 | DIAGNOSIS_VISUAL | Warna daun: Coklat Ujung + Defisiensi: P | Defisiensi Fosfor — Nekrosis Ujung Daun | Pahan 2013 | 152-153 | 9.5 | TERVERIFIKASI_SUMBER |
| VIS-FE-01 | DIAGNOSIS_VISUAL | Warna daun: Kuning Antar Tulang + Defisiensi: Fe + pH ≥ 6.5 | Defisiensi Besi (Fe) — pH Terlalu Tinggi | Pahan 2013 | 152-153 | 9.5 | TERVERIFIKASI_SUMBER |
| VIS-ZN-01 | DIAGNOSIS_VISUAL | Warna daun: Kuning Merata + Defisiensi: Zn | Defisiensi Seng (Zn) — Klorosis Daun Muda | — | — | — | **PERLU_VALIDASI_AHLI** (NONAKTIF) |
| VIS-BK-01 | DIAGNOSIS_VISUAL | Tandan: Rontok Prematur | Defisiensi B atau K — Rontok Tandan | Pahan 2013 | 152-153 | 9.5 | TERVERIFIKASI_SUMBER |
| TANAH-PH-01 | PEMBATAS_APLIKASI | pH: 3.0–4.5 | pH Sangat Masam — Penghambatan Penyerapan | Pahan 2013 | 155-157 | — | TERVERIFIKASI_SUMBER |
| TANAH-PH-02 | PEMBATAS_APLIKASI | pH: 4.5–5.5 | pH Masam — Efisiensi Pupuk Rendah | Pahan 2013 | 155-157 | — | TERVERIFIKASI_SUMBER |
| LINGK-DR-01 | PEMBATAS_APLIKASI | Drainase: Buruk — Tergenang | Waterlogging — Tunda Pupuk | Pahan 2013 | 157-159 | — | TERVERIFIKASI_SUMBER |
| LINGK-KER-01 | PEMBATAS_APLIKASI | Kelembaban: Sangat Kering | Cekaman Kekeringan — Tunda Pupuk | Pahan 2013 | 157-159 | — | TERVERIFIKASI_SUMBER |
| LINGK-KER-02 | PEMBATAS_APLIKASI | Musim: Kemarau + Kelembaban: Kering | Kemarau — Penyesuaian Jadwal | Pahan 2013 | 157-159 | — | **PERLU_VALIDASI_AHLI** |
| LINGK-OPT-01 | NORMAL | Musim: Hujan + Kelembaban: Normal | Kondisi Optimal Pemupukan | Pahan 2013 | 157-159 | — | TERVERIFIKASI_SUMBER |
| UMUR-TBM-01 | DIAGNOSIS_VISUAL | Umur: < 3 thn + Warna: Kuning Merata | TBM Defisiensi N | Pahan 2013 | 163-164 | 9.13 | TERVERIFIKASI_SUMBER |
| UMUR-TUA-01 | PERINGATAN_DATA | Umur: > 25 thn | Evaluasi Kelayakan Peremajaan | Pahan 2013 | 163-164 | — | **PERLU_VALIDASI_AHLI** |
| NORMAL-01 | NORMAL | Warna: Hijau Normal + pH: 5.5-6.5 + Drainase: Baik | Kondisi Optimal — Pemupukan Standar | Pahan 2013 | 163-164 | 9.13, 9.14 | TERVERIFIKASI_SUMBER |

---

## Referensi Tabel Dosis

| Tabel | Halaman | Deskripsi | Digunakan di |
|-------|---------|-----------|--------------|
| 9.5 | 152-153 | Gejala visual defisiensi unsur hara | Rule diagnosis VIS-* |
| 9.13 | 163 | Dosis pupuk Urea per fase/umur | PahanDoseReferenceService (config) |
| 9.14 | 164 | Dosis pupuk KCl per fase/umur | PahanDoseReferenceService (config) |
| — | 157-159 | Waktu pemupukan & curah hujan | FertilizationWindowService |

---

## Catatan

1. Rule dengan status `PERLU_VALIDASI_AHLI` tidak menghasilkan dosis kuantitatif final.
2. Dosis Urea dan KCl HANYA berasal dari `PahanDoseReferenceService` (Tabel 9.13/9.14).
3. Rule diagnosis menentukan indikasi, jenis pupuk, dan prioritas — bukan dosis angka.
4. Rule VIS-ZN-01 dinonaktifkan sampai ada validasi ahli atau sumber ilmiah tambahan.
5. Skor kelengkapan data menggambarkan kualitas data, bukan kepastian agronomis.

---

## Disclaimer Akademik

> Aplikasi ini merupakan implementasi Rule-Based System untuk keperluan skripsi.
> Rekomendasi yang dihasilkan bersifat estimasi berbasis literatur dan memerlukan
> validasi ahli agronomi sebelum diterapkan di lapangan. Dosis referensi mengikuti
> Pahan (2013) yang merupakan panduan umum — kondisi aktual di lapangan dapat
> memerlukan penyesuaian berdasarkan analisis tanah dan daun.
