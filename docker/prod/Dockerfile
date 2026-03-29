FROM php:8.3-fpm

# ---------------------------
# Install dependencies
# ---------------------------
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip nodejs npm nginx \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ---------------------------
# Composer
# ---------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---------------------------
# Working directory
# ---------------------------
WORKDIR /var/www

# ---------------------------
# Copy project
# ---------------------------
COPY . .

# ---------------------------
# Install PHP deps
# ---------------------------
RUN composer install --no-dev --optimize-autoloader

# ---------------------------
# Build frontend (Vite)
# ---------------------------
RUN rm -rf node_modules public/build \
 && npm ci \
 && npm run build

# ---------------------------
# Prepare Laravel dirs
# ---------------------------
RUN mkdir -p storage/logs \
 && mkdir -p storage/framework/cache/data \
 && mkdir -p storage/framework/sessions \
 && mkdir -p storage/framework/views \
 && mkdir -p bootstrap/cache \
 && mkdir -p database \
 && touch database/database.sqlite

# ---------------------------
# Clear caches
# ---------------------------
RUN php artisan config:clear \
 && php artisan cache:clear \
 && php artisan route:clear \
 && php artisan view:clear

# ---------------------------
# ✅ CORRECT PERMISSIONS (NOT ROOT HACK)
# ---------------------------
RUN chown -R www-data:www-data /var/www \
 && chmod -R 775 storage bootstrap/cache database

# ---------------------------
# PHP debug (optional)
# ---------------------------
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "display_startup_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# ---------------------------
# Nginx config
# ---------------------------
COPY docker/prod/nginx.conf /etc/nginx/conf.d/default.conf

# ---------------------------
# Expose port
# ---------------------------
EXPOSE 10000