# Panduan Deploy Laravel ke InfinityFree (Gratis)

## Persiapan

### 1. Daftar InfinityFree
1. Buka https://www.infinityfree.com
2. Klik "Sign Up" → isi email & password
3. Setelah login, klik "Create Account" (hosting account)
4. Pilih subdomain gratis, misal: `rbs-sawit.infinityfreeapp.com`
5. Tunggu beberapa menit sampai status "Active"

### 2. Catat Informasi Penting
Dari panel InfinityFree, catat:
- **FTP Host**: ftpupload.net (atau yang tertera)
- **FTP Username**: (tertera di panel)
- **FTP Password**: (password hosting kamu)
- **MySQL Host**: (tertera di panel, biasanya sql3xx.infinityfree.com)

---

## Setup Database

### 3. Buat Database
1. Di VistaPanel → "MySQL Databases"
2. Buat database baru (nama otomatis ditambah prefix)
3. Catat: nama database, username, password, host

---

## Persiapan File di Lokal

### 4. Install Dependencies
Pastikan vendor/ sudah ada di lokal:
```bash
composer install --optimize-autoloader --no-dev
```

### 5. Generate APP_KEY (jika belum)
```bash
php artisan key:generate --show
```
Catat key-nya (format: base64:xxxxxxxxxxxx)

### 6. Buat file .env untuk production
Buat file `.env.production` dengan isi:
```env
APP_NAME="Aplikasi RBS Kelapa Sawit"
APP_ENV=production
APP_KEY=base64:PASTE_KEY_DISINI
APP_DEBUG=false
APP_URL=https://rbs-sawit.infinityfreeapp.com

LOG_CHANNEL=single

DB_CONNECTION=mysql
DB_HOST=PASTE_MYSQL_HOST_INFINITYFREE
DB_PORT=3306
DB_DATABASE=PASTE_NAMA_DATABASE
DB_USERNAME=PASTE_USERNAME_DATABASE
DB_PASSWORD=PASTE_PASSWORD_DATABASE

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

## Upload via FTP

### 7. Install FileZilla
Download di: https://filezilla-project.org/

### 8. Connect ke FTP
- Host: ftpupload.net
- Username: (FTP username dari panel)
- Password: (FTP password dari panel)
- Port: 21

### 9. Upload Semua File
Upload SELURUH isi project ke folder `htdocs/`:

```
htdocs/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/          ← WAJIB upload (besar, sabar ya)
├── .env             ← rename .env.production jadi .env
├── .htaccess        ← copy dari deploy/htaccess-root.txt
├── artisan
├── composer.json
└── ... (semua file lainnya)
```

PENTING: 
- Taruh file `.htaccess` (dari deploy/htaccess-root.txt) di ROOT htdocs/
- Rename `.env.production` jadi `.env` di server
- Folder `vendor/` HARUS ikut diupload (bisa 50-100MB, sabar)

### 10. Set Permission Storage
Di VistaPanel → File Manager:
- Folder `storage/` → set permission 775
- Folder `bootstrap/cache/` → set permission 775

---

## Jalankan Migration

### 11. Buat Route Sementara untuk Migration
Karena InfinityFree TIDAK ada SSH, buat route sementara.

Tambahkan di `routes/web.php`:
```php
// HAPUS SETELAH SELESAI SETUP!
Route::get('/setup-database', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);
        return 'Migration & Seeding berhasil!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});
```

### 12. Akses URL Setup
Buka di browser: `https://rbs-sawit.infinityfreeapp.com/setup-database`

Tunggu sampai muncul "Migration & Seeding berhasil!"

### 13. HAPUS Route Setup!
Setelah berhasil, SEGERA hapus route `/setup-database` dari `routes/web.php` dan upload ulang file tersebut. Ini untuk keamanan.

---

## Verifikasi

### 14. Test Aplikasi
Buka: `https://rbs-sawit.infinityfreeapp.com`

Cek:
- [ ] Halaman login muncul
- [ ] Bisa login dengan akun admin (dari AdminSeeder)
- [ ] Bisa input data blok lahan
- [ ] Bisa lihat rekomendasi RBS
- [ ] Export PDF berfungsi (DomPDF)

---

## Troubleshooting

### Error 500
- Cek file `.env` sudah benar
- Cek permission `storage/` dan `bootstrap/cache/`
- Pastikan `APP_DEBUG=true` sementara untuk lihat error detail

### Halaman Blank
- Pastikan `.htaccess` ada di root htdocs/
- Cek mod_rewrite aktif (biasanya sudah aktif di InfinityFree)

### Database Error
- Pastikan MySQL host yang dipakai benar (bukan localhost!)
- InfinityFree pakai host khusus seperti `sql3xx.infinityfree.com`

### Upload Lambat
- Folder `vendor/` memang besar (~50-100MB)
- Bisa compress jadi .zip → upload → extract via File Manager di VistaPanel

---

## Catatan Penting

- InfinityFree punya daily hit limit (~50.000 hits/hari) — lebih dari cukup untuk skripsi
- Website mungkin agak lambat di first load — ini normal untuk free hosting
- Backup database rutin via phpMyAdmin (VistaPanel → phpMyAdmin → Export)
- JANGAN simpan data sensitif/rahasia di free hosting
