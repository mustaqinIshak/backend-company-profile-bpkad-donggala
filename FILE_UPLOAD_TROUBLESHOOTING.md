# File Upload Troubleshooting Guide

## Overview
Panduan ini membantu mengatasi masalah "The file dokumen failed to upload" pada endpoint file upload Layanan.

## Kemungkinan Penyebab dan Solusi

### 1. **Storage Directory Tidak Terbuat**
**Gejala:** Upload PDF selalu gagal untuk semua file

**Solusi:**
```bash
# Pastikan direktori storage ada
mkdir -p storage/app/public/layanan
chmod 755 storage/app/public/layanan

# Atau gunakan artisan command
php artisan storage:create-directories
```

### 2. **Disk Storage Penuh**
**Gejala:** Upload PDF besar gagal, tapi PDF kecil berhasil

**Solusi:**
```bash
# Cek penggunaan disk
df -h /
df -h /path/to/storage

# Bersihkan file lama jika perlu
find storage/app/public/layanan -type f -mtime +30 -delete
```

### 3. **File Permissions Error**
**Gejala:** Upload gagal di beberapa server tapi berhasil di server lain

**Solusi:**
```bash
# Set permissions yang benar
chmod -R 755 storage/
chown -R www-data:www-data storage/

# Untuk Armbian dengan user tertentu
sudo chown -R $USER:$USER storage/
sudo chmod -R 775 storage/app/public/
```

### 4. **MIME Type Detection Gagal**
**Gejala:** File PDF besar gagal karena MIME type tidak terdeteksi

**Solusi:**
Pastikan `fileinfo` extension aktif di PHP:
```bash
# Di Armbian, cek PHP version dulu
php -v

# Install PHP extension jika belum ada
sudo apt-get install php-fileinfo
sudo systemctl restart php-fpm

# Verify extension
php -m | grep fileinfo
```

### 5. **Maximum Upload Size di PHP**
**Gejala:** File di atas ~5-10MB selalu gagal

**Solusi:**
Edit `php.ini`:
```bash
# Cari file php.ini
php -r "echo php_ini_loaded_file();"

# Ubah nilai ini:
# upload_max_filesize = 30M
# post_max_size = 40M

sudo nano /etc/php/8.2/fpm/php.ini
# atau
sudo nano /etc/php/8.2/cli/php.ini

# Restart PHP
sudo systemctl restart php-fpm
```

### 6. **Nginx Configuration Issue**
**Gejala:** Upload di atas limit nginx gagal

Di `nginx.conf`:
```nginx
http {
    client_max_body_size 50M;
}
```

Restart nginx:
```bash
sudo systemctl restart nginx
```

### 7. **Temporary Upload Directory Issue**
**Gejala:** Upload gagal di environment tertentu

**Solusi:**
```bash
# Cek upload_tmp_dir di php.ini
php -i | grep upload_tmp_dir

# Pastikan direktori ada dan writable
mkdir -p /tmp/php-upload
chmod 1777 /tmp/php-upload

# Edit php.ini
upload_tmp_dir = /tmp/php-upload
```

## Melihat Log Error Detail

### Di Armbian Linux:
```bash
# Lihat log Laravel
cd /path/to/backend-company-profile-bpkad-donggala
tail -50 storage/logs/laravel.log

# Filter error tertentu
tail -100 storage/logs/laravel.log | grep -i "upload\|file\|dokumen\|error"

# Real-time monitoring
tail -f storage/logs/laravel.log

# Lihat log PHP-FPM
sudo tail -50 /var/log/php8.2-fpm.log
```

### Di Windows (untuk development):
```powershell
# PowerShell
Get-Content -Path storage\logs\laravel.log -Tail 50

# Real-time
Get-Content -Path storage\logs\laravel.log -Wait
```

## Testing Upload dengan Curl

```bash
# Test upload file dengan curl
curl -X POST http://api.example.com/api/layanans \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "tipe=sekretariat" \
  -F "judul=Test Document" \
  -F "deskripsi=Test Upload" \
  -F "tahun_apbd=2024" \
  -F "file_dokumen=@/path/to/file.pdf"

# Dengan verbose output
curl -X POST http://api.example.com/api/layanans \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "tipe=sekretariat" \
  -F "judul=Test Document" \
  -F "deskripsi=Test Upload" \
  -F "tahun_apbd=2024" \
  -F "file_dokumen=@/path/to/file.pdf" \
  -v
```

## Checklist Debugging

Saat upload gagal, cek:
- [ ] `storage/app/public/layanan/` folder ada dan writable
- [ ] PHP `upload_max_filesize` cukup besar (min 20MB)
- [ ] PHP `post_max_size` lebih besar dari `upload_max_filesize`
- [ ] Disk storage tidak penuh (`df -h`)
- [ ] File permissions benar (`chmod 755 storage/`)
- [ ] PHP `fileinfo` extension aktif
- [ ] Nginx `client_max_body_size` cukup besar (min 30M)
- [ ] Laravel storage link sudah dibuat (`php artisan storage:link`)
- [ ] Log file untuk error detail (`storage/logs/laravel.log`)

## Quick Fix Commands

Jalankan semua ini untuk fix mayoritas issues:

```bash
# 1. Create storage directories
mkdir -p storage/app/public/layanan

# 2. Set correct permissions
chmod -R 755 storage/
chown -R www-data:www-data storage/

# 3. Create storage symlink
php artisan storage:link

# 4. Create storage directories via artisan
php artisan storage:create-directories

# 5. Restart services
sudo systemctl restart php-fpm
sudo systemctl restart nginx
```

## Untuk Development (Local Testing)

Jika mau test file upload yang gagal:
```bash
# Buat test file PDF besar
dd if=/dev/urandom of=test-large.bin bs=1M count=10

# Convert to PDF (jika ada ghostscript)
gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=test.pdf test-large.bin

# Atau buat PDF sederhana dari text
echo "Test PDF content" | enscript -p test.pdf

# Test upload
curl -F "file_dokumen=@test.pdf" http://localhost:8000/api/layanans
```
