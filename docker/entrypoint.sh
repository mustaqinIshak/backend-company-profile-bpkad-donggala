#!/bin/sh
set -e

echo ">>> Menjalankan setup awal Laravel..."

# Generate APP_KEY jika belum ada
# Deteksi langsung dari konten file .env apakah APP_KEY sudah terisi dengan format base64
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    echo ">>> Membuat APP_KEY..."
    php artisan key:generate --force
fi

# Bersihkan config cache lama agar .env selalu terbaca dengan benar
php artisan config:clear

# Jalankan migrasi (aman dijalankan berulang kali)
echo ">>> Menjalankan migrasi database..."
php artisan migrate --force

# Seed hanya jika tabel admins masih kosong (cegah duplikat data)
echo ">>> Mengecek data awal..."
ADMIN_COUNT=$(php artisan tinker --execute="echo \App\\Models\\Admin::count();" 2>/dev/null | tail -1)
if [ "$ADMIN_COUNT" = "0" ]; then
    echo ">>> Menjalankan seeder..."
    php artisan db:seed --force
else
    echo ">>> Data sudah ada, seeder dilewati."
fi

# Buat storage directories (jika belum ada)
echo ">>> Membuat storage directories..."
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public/layanan
mkdir -p /var/www/html/storage/app/public/berita
mkdir -p /var/www/html/storage/app/public/jumbotron
mkdir -p /var/www/html/storage/app/public/profile
mkdir -p /var/www/html/storage/app/public/surat
mkdir -p /var/www/html/storage/app/public/tamu
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache

# Set proper permissions (chmod only, skip chown if not root)
echo ">>> Setting storage permissions..."
chmod -R 775 /var/www/html/storage
chmod -R 755 /var/www/html/storage/app/public
chmod -R 775 /var/www/html/bootstrap/cache

# Note: chown will be done by Dockerfile at build time with proper user context
# Runtime chown is not possible as container runs as www-data (non-root)

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
