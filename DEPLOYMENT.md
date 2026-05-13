# Panduan Deployment & Optimasi Keamanan Aplikasi

Dokumen ini berisi panduan teknis langkah demi langkah untuk melakukan deployment aplikasi **Backend REST API BPKAD Kabupaten Donggala (Laravel 11)** ke server production, beserta konfigurasi keamanannya.

---

## 1. Persiapan Server (Prasyarat)

Pastikan server (VPS / Dedicated Server) Anda telah terpasang:
- **OS**: Linux (Ubuntu 22.04 / 24.04 atau distribusi linux lainnya yang direkomendasikan).
- **Web Server**: Nginx atau Apache.
- **PHP**: Versi 8.2 atau lebih baru beserta ekstensi standar Laravel (BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL).
- **Database**: MySQL 8.0+ / MariaDB 10.3+ / PostgreSQL (atau SQLite).
- **Composer**: Untuk manajemen dependensi PHP.
- **Git** (opsional): Jika Anda menarik kode naskah langsung dari repositori.

---

## 2. Langkah-Langkah Deployment

### A. Upload / Clone Kode
Pindahkan file kode ke server (misal di direktori `/var/www/bpkad-api`):
```bash
cd /var/www
git clone <url-repo-anda> bpkad-api
cd bpkad-api
```

### B. Install Dependencies
Jalankan Composer dengan mode production (tanpa paket development dan dengan optimasi autoloader):
```bash
composer install --optimize-autoloader --no-dev
```

### C. Konfigurasi Environment (`.env`)
Gunakan `.env.production` sebagai dasar konfigurasi:
```bash
cp .env.production .env
```
Buka file `.env` dan perbarui nilainya. **Perhatikan pengaturan WAJIB berikut:**
```env
APP_ENV=production
APP_DEBUG=false             # SANGAT PENTING: Harus false di lingkup production
APP_URL=https://api.bpkad-donggala.go.id  # Gunakan HTTPS dan domain API Anda

# Konfigurasi Database (Contoh menggunakan MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_produksi
DB_USERNAME=user_database_produksi
DB_PASSWORD=password_database_produksi

# SANGAT PENTING: Ubah kredensial default admin!
ADMIN_EMAIL=admin_asli@bpkad-donggala.go.id
ADMIN_PASSWORD=password_super_kuat_123!
```

**Generate Application Key (Ganti kunci di .env):**
```bash
php artisan key:generate --force
```

### D. Migrasi Database & Seeding
Menjalankan migrasi dan inisialisasi master data. Bendera `--force` digunakan pada production.
```bash
php artisan migrate --force --seed
```

### E. Storage Link
Membuat publikasi symbolic link agar file yang diunggah dapat diakses dari Web:
```bash
php artisan storage:link
```

### F. Optimasi Cache Laravel
Untuk performa aplikasi yang lebih memadai di production:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
*(Catatan: Setiap kali melakukan perubahan file `.env` atau konfigurasi, Anda wajib menjalankan `php artisan config:clear` dan `config:cache` kembali).*

---

## 3. Konfigurasi Keamanan (Security Setup)

### A. Hak Akses (Permissions) & Ownership
Web server (misalnya Nginx/Apache yang berjalan memalui user `www-data`) harus memiliki hak tulis pada `storage` dan `bootstrap/cache`.
```bash
sudo chown -R www-data:www-data /var/www/bpkad-api
sudo find /var/www/bpkad-api -type f -exec chmod 644 {} \;
sudo find /var/www/bpkad-api -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/bpkad-api/storage
sudo chmod -R 775 /var/www/bpkad-api/bootstrap/cache
```

### B. Konfigurasi Web Server (Document Root)
Atur direktori akar (Document Root) aplikasi Laravel langsung menunjuk ke folder `public`, **Bukan** ke sub-folder luar. Ini mencegah akses langsung ke file konfigurasi seperti `.env`.

**Contoh Ringkas Konfigurasi Nginx:**
```nginx
server {
    listen 80;
    server_name api.bpkad-donggala.go.id;
    root /var/www/bpkad-api/public; # SANGAT PENTING: Menunjuk ke folder public!

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Blokir akses ke file tersembunyi seperti .env atau .git
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Mencegah eksekusi PHP di folder storage (Keamanan Upload File)
    location ~* /storage/.*\.php$ {
        deny all;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### C. Sertifikat SSL / HTTPS (Wajib)
Karena aplikasi menggunakan skema pertukaran token bearer (`Authorization: Bearer <token>`), lalu lintas jaringannya rentan dicuri bila tak dienkripsi. Gunakan layanan seperti Let's Encrypt (Certbot) untuk memasang SSL pada Web Server Anda.
```bash
sudo certbot --nginx -d api.bpkad-donggala.go.id
```

### D. Perlindungan Limit Request (Rate Limiting)
Aplikasi bawaan Laravel telah dibatasi secara default. Namun, Anda harus memperkecil kuota login untuk mencegah serangan pemaksaan sanding (Brute Force).
Di berkas `routes/api.php`, pastikan endpoint login memiliki pembatasan ketat (misal 5 login tiap menit per alamat IP).
```php
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
```

### E. Manajemen Kebijakan CORS (Cross-Origin Resource Sharing)
Ubah entri izin asal (Origins) pada berkas `config/cors.php` dengan hanya memperbolehkan domain aplikasi Front-End resmi mengakses API ini.
Cari dan ubah parameter `allowed_origins`:
```php
// Jangan gunakan pola '*' (allow-all)
'allowed_origins' => ['https://bpkad-donggala.go.id', 'https://www.bpkad-donggala.go.id'],
```

### F. Ganti Kredensial Default!
Segera setelah aplikasi dapat diakses, pastikan user bawaan (yang mungkin dibuat saat seeder berjalan) telah diperbarui password-nya dari nilai `admin123` menuju sandi kompleks berstandar (mengandung karakter huruf, angka, kapital, dan spesial).