# Panduan Deploy ke Rumahweb (rekomendasipupuk.xyz)

## Info Hosting
- Domain: rekomendasipupuk.xyz
- Username cPanel: rekn9152
- DB Name: rekn9152_skripsi
- DB User: rekn9152_admin
- DB Host: localhost

---

## Langkah Deploy

### 1. Siapkan File ZIP

Di laptop, buka folder project `E:\Skripsi\Aplikasi Skripsi\`:

1. Pastikan `vendor/` sudah ada. Jika belum, jalankan:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. ZIP **seluruh isi folder** project (BUKAN folder induknya).
   - Select All file & folder di dalam `Aplikasi Skripsi\`
   - Klik kanan → Send to → Compressed (zip)
   - Atau pakai 7-Zip / WinRAR

   Yang di-zip:
   - app/
   - bootstrap/
   - config/
   - database/
   - public/
   - resources/
   - routes/
   - storage/
   - vendor/
   - .env.production
   - .htaccess
   - artisan
   - composer.json
   - composer.lock
   - dll.

   JANGAN include:
   - .git/
   - node_modules/ (kalau ada)
   - .env (yang lokal)

### 2. Upload ke cPanel File Manager

1. Buka cPanel → File Manager
2. Masuk ke folder `public_html/`
3. **Hapus semua file default** yang ada di situ (biasanya ada index.html default)
4. Klik **Upload** → pilih file .zip kamu
5. Tunggu upload selesai
6. Klik kanan file .zip → **Extract**
7. Pastikan semua file ter-extract langsung di `public_html/` (bukan di subfolder)

Struktur akhir harus:
```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
├── .htaccess      ← redirect ke public/
├── .env           ← rename dari .env.production
├── artisan
└── ...
```

### 3. Setup .env

1. Di File Manager, rename `.env.production` → `.env`
2. Edit `.env` → isi password database kamu:
   ```
   DB_PASSWORD=password_yang_kamu_buat_tadi
   ```
3. Generate APP_KEY: 
   - Kalau ada SSH: `php artisan key:generate`
   - Kalau tidak: akses `https://rekomendasipupuk.xyz/setup-database` nanti otomatis

### 4. Set Permission

Di File Manager:
1. Klik kanan folder `storage/` → Change Permissions → set `0775` → centang Recursive → Save
2. Klik kanan folder `bootstrap/cache/` → Change Permissions → set `0775` → Save

### 5. Generate APP_KEY (via SSH atau Terminal di cPanel)

Kalau ada SSH/Terminal:
```bash
cd ~/public_html
php artisan key:generate
```

Kalau tidak ada SSH, tambahkan ini sementara di awal route setup-database
(sudah ditambahkan di routes/web.php).

### 6. Jalankan Migration

Buka browser: `https://rekomendasipupuk.xyz/setup-database`

Tunggu sampai muncul:
```
✅ Migration berhasil!
✅ Seeding berhasil!
✅ Cache cleared!
🎉 SETUP SELESAI!
```

### 7. HAPUS Route Setup!

Setelah migration berhasil, edit `routes/web.php`:
- Hapus blok route `/setup-database` di bagian paling bawah
- Upload ulang file `routes/web.php` ke server

### 8. Test Aplikasi

Buka: https://rekomendasipupuk.xyz

Login dengan:
- Username: (dari AdminSeeder)
- Password: (dari AdminSeeder)

---

## Troubleshooting

### Error 500
- Cek `.env` sudah benar (terutama DB credentials)
- Cek permission `storage/` dan `bootstrap/cache/`
- Sementara set `APP_DEBUG=true` untuk lihat error detail

### Halaman Blank / Not Found
- Pastikan `.htaccess` ada di root `public_html/`
- Cek `public/.htaccess` juga ada

### Database Error
- Pastikan user sudah di-assign ke database dengan ALL PRIVILEGES
- Cek nama database benar (dengan prefix `rekn9152_`)

### File terlalu besar untuk upload
- Upload via FTP pakai FileZilla
- Host: rekomendasipupuk.xyz
- Username: (cPanel username)
- Password: (cPanel password)
- Port: 21
