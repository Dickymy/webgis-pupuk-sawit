# AUDIT PENYEMPURNAAN FINAL — SawitGIS Pahan v2.2

**Tanggal**: 20 Juli 2026  
**Versi mesin**: pahan-v2.2  
**Auditor**: Kiro AI  

---

## Temuan Audit

### 1. Umur Observasi vs Umur Saat Ini
- **Temuan**: `PahanDoseReferenceService.getDoseReference()` menggunakan `$blok->umur_tanaman` (umur saat ini) alih-alih umur pada tanggal observasi.
- **Dampak**: Analisis historis menghasilkan kelompok dosis yang salah.
- **Solusi**: Ditambahkan `getDoseReferenceForContext($blok, $umur, $fase)` yang menerima umur dan fase eksplisit. `RbsService` kini memanggil method ini dengan umur dari `PlantAgeService::calculateAgeAt()`.

### 2. Validasi Fase Tanaman Saat Input
- **Temuan**: Form blok lahan tidak memvalidasi konsistensi fase dengan umur di backend.
- **Dampak**: User bisa menyimpan kombinasi tidak logis (umur 10 tahun + TBM).
- **Solusi**: Dibuat `StoreBlokLahanRequest` dan `UpdateBlokLahanRequest` dengan validasi silang.

### 3. Singkatan TBM/TM pada Antarmuka
- **Temuan**: Blade views menampilkan "TBM" dan "TM" langsung ke pengguna.
- **File terdampak**: `blok_lahan/create.blade.php`, `blok_lahan/edit.blade.php`, `rbs/detail.blade.php`, `rbs/partials/_hasil_rbs.blade.php`, `kondisi_lahan/create.blade.php`, `kondisi_lahan/edit.blade.php`
- **Solusi**: Label diubah ke "Tanaman Belum Menghasilkan" / "Tanaman Menghasilkan". Enum `PlantPhase` sebagai sumber label terpusat.

### 4. Status Kondisi Tanaman dan Kelayakan Aplikasi Tercampur
- **Temuan**: `tentukanStatusKondisiTanaman()` menggunakan hierarki status lama (`Darurat/Segera/Normal/Tunda`) untuk menentukan kondisi tanaman. Rule PEMBATAS_APLIKASI dan SARAN_PENDUKUNG ikut mempengaruhi.
- **Solusi**: Method direfactor agar hanya melihat rule `jenis_rule = DIAGNOSIS_VISUAL` dan `tingkat_keparahan`. Enum `PlantConditionStatus` dan `ApplicationFeasibilityStatus` terpisah.

### 5. Jadwal Saat Data Belum Cukup
- **Temuan**: `hasilDataTidakCukup()` masih memanggil `generateJadwalPemupukan()`.
- **Solusi**: Jadwal dikosongkan (`[]`) dan aplikasi saat ini = 0 jika data tidak cukup.

### 6. Validasi Tanggal Pemupukan Terakhir
- **Temuan**: Validasi hanya `before_or_equal:today`, bukan `before_or_equal:tanggal_observasi`.
- **Solusi**: Form Request baru memvalidasi `tanggal_pemupukan_terakhir <= tanggal_observasi` dan `tanggal_observasi >= tahun_tanam`.

### 7. Pupuk Pendukung Tanpa Validasi
- **Temuan**: Beberapa rule masih memiliki angka dosis (kg) pada `dosis_anjuran` meskipun `status_validasi = PERLU_VALIDASI_AHLI`.
- **Solusi**: `PahanRuleBaseV2Seeder` sudah membersihkan teks dosis legacy untuk Urea/KCl. Audit command `sawit:finalize-pahan-v2-2` mendeteksi rule tersisa.

---

## File Terdampak

### Baru Dibuat
- `app/Enums/PlantPhase.php`
- `app/Enums/PlantConditionStatus.php`
- `app/Enums/ApplicationFeasibilityStatus.php`
- `app/Enums/RuleType.php`
- `app/Enums/SeverityLevel.php`
- `app/Http/Requests/StoreBlokLahanRequest.php`
- `app/Http/Requests/UpdateBlokLahanRequest.php`
- `app/Http/Requests/StoreKondisiLahanRequest.php`
- `app/Http/Requests/UpdateKondisiLahanRequest.php`
- `app/Console/Commands/FinalizePahanV2_2.php`
- `database/migrations/2026_07_20_000000_add_pahan_v2_2_columns_to_rekomendasi_rbs_table.php`

### Dimodifikasi
- `app/Services/PahanDoseReferenceService.php` — ditambah `getDoseReferenceForContext()`
- `app/Services/RbsService.php` — refactor: umur observasi, pemisahan status, jadwal kosong
- `app/Models/BlokLahan.php` — ditambah `getFaseLabelAttribute()`
- `app/Models/RekomendasiRbs.php` — ditambah label accessors via enum
- `app/Http/Controllers/BlokLahanController.php` — gunakan Form Request
- `app/Http/Controllers/KondisiLahanController.php` — gunakan Form Request
- `app/Http/Controllers/RbsController.php` — popup API gunakan label
- `config/fertilization.php` — versi engine → pahan-v2.2
- `resources/views/blok_lahan/create.blade.php` — label fase lengkap
- `resources/views/blok_lahan/edit.blade.php` — label fase lengkap
- `resources/views/rbs/detail.blade.php` — label fase lengkap
- `resources/views/rbs/partials/_hasil_rbs.blade.php` — hapus singkatan TBM

---

## Risiko

1. **Histori lama**: Rekomendasi lama tetap menyimpan `umur_tanaman_snapshot` dari versi sebelumnya. Tidak dihitung ulang.
2. **View lainnya**: Beberapa view minor mungkin masih menampilkan "TBM" dari variabel JavaScript DOM (id=banner-tbm). Ini adalah ID internal, bukan label yang ditampilkan ke user.
3. **Database**: Kolom `status_kebutuhan_dominan` masih menyimpan hierarki lama. Dipertahankan untuk kompatibilitas.

---

## Langkah Deployment

```bash
# 1. Jalankan migration
php artisan migrate

# 2. Audit data
php artisan sawit:finalize-pahan-v2-2 --dry-run

# 3. Jika aman, jalankan tanpa dry-run
php artisan sawit:finalize-pahan-v2-2

# 4. Update seeder provenance
php artisan db:seed --class=PahanRuleBaseV2Seeder

# 5. Clear cache
php artisan config:clear
php artisan view:clear
php artisan route:clear
```
