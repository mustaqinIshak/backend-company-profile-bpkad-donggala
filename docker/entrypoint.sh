#!/bin/sh

echo ">>> Menjalankan setup awal Laravel..."

# Generate APP_KEY jika belum ada
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    echo ">>> Membuat APP_KEY..."
    php artisan key:generate --force
fi

# Bersihkan config cache lama
php artisan config:clear

# Jalankan migrasi
echo ">>> Menjalankan migrasi database..."
php artisan migrate --force

# Seed hanya jika tabel admins masih kosong
echo ">>> Mengecek data awal..."
ADMIN_COUNT=$(php artisan tinker --execute="echo \App\\Models\\Admin::count();" 2>/dev/null | tail -1)
if [ "$ADMIN_COUNT" = "0" ]; then
    echo ">>> Menjalankan seeder..."
    php artisan db:seed --force
else
    echo ">>> Data sudah ada, seeder dilewati."
fi

# Buat storage directories
echo ">>> Membuat storage directories..."
mkdir -p /var/www/html/storage/logs 2>/dev/null || true
mkdir -p /var/www/html/storage/app/public/layanan 2>/dev/null || true
mkdir -p /var/www/html/storage/app/public/berita 2>/dev/null || true
mkdir -p /var/www/html/storage/app/public/jumbotron 2>/dev/null || true
mkdir -p /var/www/html/storage/app/public/profile 2>/dev/null || true
mkdir -p /var/www/html/storage/app/public/surat 2>/dev/null || true
mkdir -p /var/www/html/storage/app/public/tamu 2>/dev/null || true
mkdir -p /var/www/html/storage/framework/views 2>/dev/null || true
mkdir -p /var/www/html/storage/framework/cache 2>/dev/null || true

# Set proper permissions (ignore errors - permissions set at build time)
echo ">>> Setting storage permissions..."
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 755 /var/www/html/storage/app/public 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

# Buat storage link jika belum ada
if [ ! -L "/var/www/html/public/storage" ]; then
    echo ">>> Membuat storage link..."
    php artisan storage:link
fi

# Optimasi cache production
echo ">>> Mengoptimasi cache..."
php artisan config:cache
php artisan route:cache

echo ">>> Setup selesai. Menjalankan PHP-FPM..."
exec php-fpm
