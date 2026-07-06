#!/bin/sh
set -e

echo ">>> Menjalankan setup awal Laravel..."

# Generate APP_KEY jika belum ada
if [ -z "$APP_KEY" ]; then
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
