# Traceability Rule Base — Pahan (2013)

Dokumen ini memetakan setiap rule dalam sistem ke sumber referensinya.

**Sumber Utama:** Iyung Pahan. 2013. *Panduan Lengkap Kelapa Sawit*. Cetakan XI. Jakarta: Penebar Swadaya.

---

## Daftar Rule dan Sumber

| Kode Rule | Kondisi (IF) | Kesimpulan (THEN) | Sumber | Tahun | Bab | Tabel/Halaman | Tingkat Bukti | Status Validasi |
|-----------|-------------|-------------------|--------|------:|----:|---------------|---------------|-----------------|
| VIS-N-01 | Warna daun = Kuning Merata, Defisiensi = N | Defisiensi Nitrogen — Klorosis Umum → Urea | Pahan | 2013 | 9 | Tabel 9.5 / hal. 145-148 | BUKU | TERVERIFIKASI_SUMBER |
| VIS-N-02 | Warna daun = Hijau Pucat, Defisiensi = N | Defisiensi Nitrogen Ringan → Urea | Pahan | 2013 | 9 | Tabel 9.5 / hal. 145-148 | BUKU | TERVERIFIKASI_SUMBER |
| VIS-K-01 | Warna daun = Oranye/Kemerahan, Defisiensi = K | Defisiensi Kalium — Orange Frond → KCl | Pahan | 2013 | 9 | Tabel 9.5 / hal. 145-148 | BUKU | TERVERIFIKASI_SUMBER |
| VIS-K-02 | Warna daun = Kuning Tepi, Defisiensi = K | Defisiensi Kalium Sedang → KCl | Pahan | 2013 | 9 | Tabel 9.5 / hal. 145-148 | BUKU | TERVERIFIKASI_SUMBER |
| VIS-MG-01 | Warna daun = Kuning Antar Tulang, Defisiensi = Mg | Defisiensi Magnesium → Kieserit | Pahan | 2013 | 9 | Tabel 9.5 / hal. 145-148 | BUKU | TERVERIFIKASI_SUMBER |
| VIS-B-01 | Pelepah = Pertumbuhan Terhambat, Defisiensi = B | Defisiensi Boron — Blind Pocket → Borax | Pahan | 2013 | 9 | Tabel 9.5 / hal. 145-148 | BUKU | TERVERIFIKASI_SUMBER |
| VIS-P-01 | Warna daun = Coklat Ujung, Defisiensi = P | Defisiensi Fosfor → TSP/SP-36 | Pahan | 2013 | 9 | Tabel 9.5 / hal. 145-148 | BUKU | TERVERIFIKASI_SUMBER |
| VIS-FE-01 | Warna daun = Kuning Antar Tulang, Defisiensi = Fe, pH ≥ 6.5 | Defisiensi Besi → FeSO4 | Pahan | 2013 | 9 | Tabel 9.5 / hal. 145-148 | BUKU | TERVERIFIKASI_SUMBER |
| VIS-ZN-01 | Warna daun = Kuning Merata, Defisiensi = Zn | Defisiensi Seng → ZnSO4 | Pahan | 2013 | 9 | — | ADAPTASI_PENELITI | PERLU_VALIDASI_AHLI |
| VIS-BK-01 | Tandan = Rontok Prematur | Defisiensi B atau K → Borax + KCl | Pahan | 2013 | 9 | Tabel 9.5 / hal. 145-148 | BUKU | TERVERIFIKASI_SUMBER |
| TANAH-PH-01 | pH 3.0–4.5 | pH Sangat Masam → Dolomit | Pahan | 2013 | 9 | hal. 155-157 | BUKU | TERVERIFIKASI_SUMBER |
| TANAH-PH-02 | pH 4.5–5.5 | pH Masam → Dolomit ringan | Pahan | 2013 | 9 | hal. 155-157 | BUKU | TERVERIFIKASI_SUMBER |
| LINGK-DR-01 | Drainase = Buruk — Tergenang | Waterlogging → Tunda pupuk tanah | Pahan | 2013 | 9 | hal. 157-159 | BUKU | TERVERIFIKASI_SUMBER |
| LINGK-KER-01 | Kelembaban = Sangat Kering | Cekaman Kekeringan → Tunda pupuk kimia | Pahan | 2013 | 9 | hal. 157-159 | BUKU | TERVERIFIKASI_SUMBER |
| LINGK-KER-02 | Musim = Kemarau, Kelembaban = Kering | Kemarau → Penyesuaian jadwal | Pahan | 2013 | 9 | hal. 157-159 | ADAPTASI_PENELITI | PERLU_VALIDASI_AHLI |
| LINGK-OPT-01 | Musim = Hujan, Kelembaban = Normal | Kondisi Optimal → Dosis penuh | Pahan | 2013 | 9 | hal. 157-159 | BUKU | TERVERIFIKASI_SUMBER |
| UMUR-TBM-01 | Kategori umur = Belum Menghasilkan, Warna daun = Kuning | TBM Defisiensi N → NPK formula TBM | Pahan | 2013 | 9 | Tabel 9.13 / hal. 163 | BUKU | TERVERIFIKASI_SUMBER |
| UMUR-TUA-01 | Kategori umur = Tua Renta | Evaluasi ekonomis replanting | Pahan | 2013 | 9 | hal. 163-164 | ADAPTASI_PENELITI | PERLU_VALIDASI_AHLI |
| NORMAL-01 | Warna = Hijau Normal, pH 5.5–6.5, Drainase = Baik | Kondisi Optimal → Pemupukan standar | Pahan | 2013 | 9 | Tabel 9.13, 9.14 / hal. 163-164 | BUKU | TERVERIFIKASI_SUMBER |

---

## Catatan

1. Rule dengan status **PERLU_VALIDASI_AHLI** tetap aktif dalam sistem tetapi dilabeli sebagai adaptasi peneliti.
2. Rule **VIS-ZN-01** (Defisiensi Zn) memerlukan sumber eksternal tambahan untuk dianggap terverifikasi.
3. Angka dosis pada field `dosis_anjuran` di tabel rule adalah panduan umum. Dosis kuantitatif Urea dan KCl untuk perhitungan total diambil dari `PahanDoseReferenceService` (Tabel 9.13 & 9.14).
4. Gejala visual dapat tumpang tindih antar jenis defisiensi. Sistem menggunakan kombinasi warna daun + dugaan defisiensi untuk meningkatkan spesifisitas.
5. Tabel 9.9 Pahan (kombinasi perlakuan percobaan) **tidak** digunakan sebagai satu-satunya dasar dosis.
