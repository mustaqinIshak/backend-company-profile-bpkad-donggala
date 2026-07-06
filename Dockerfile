FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    zip \
    unzip \
    curl \
    git \
    shadow

# Install PHP extensions yang dibutuhkan Laravel
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Buat user non-root agar file ownership sesuai
RUN groupmod -g 1000 www-data && usermod -u 1000 www-data

WORKDIR /var/www/html

# Copy seluruh kode aplikasi
COPY --chown=www-data:www-data . .

# Install dependensi PHP (tanpa paket dev, optimasi autoloader)
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Set hak akses direktori yang perlu ditulis
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY --chown=www-data:www-data docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
