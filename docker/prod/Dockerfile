# ---------------------------
# Production Dockerfile
# ---------------------------
FROM php:8.3-fpm

# ---------------------------
# Install dependencies
# ---------------------------
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip nodejs npm \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ---------------------------
# Composer
# ---------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---------------------------
# Set working directory
# ---------------------------
WORKDIR /var/www

# ---------------------------
# Copy project
# ---------------------------
COPY . .

# ---------------------------
# Install PHP dependencies
# ---------------------------
RUN composer install --no-dev --optimize-autoloader

# ---------------------------
# Build frontend assets
# ---------------------------
RUN npm ci \
 && npm run build

# ---------------------------
# Create SQLite DB (AFTER copy!)
# ---------------------------
RUN mkdir -p database \
 && touch database/database.sqlite

# ---------------------------
# Laravel cache clear
# ---------------------------
RUN php artisan config:clear \
 && php artisan cache:clear \
 && php artisan route:clear \
 && php artisan view:clear

# ---------------------------
# Permissions (single place)
# ---------------------------
RUN chmod -R 777 storage bootstrap/cache database

# ---------------------------
# Enable PHP errors (debug only)
# ---------------------------
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "display_startup_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# ---------------------------
# Expose port for Laravel serve
# ---------------------------
EXPOSE 10000

# ---------------------------
# Start Laravel (NOT php-fpm!)
# ---------------------------
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]