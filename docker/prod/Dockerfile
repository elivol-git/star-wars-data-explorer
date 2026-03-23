# ---------------------------
# Production Dockerfile
# ---------------------------
FROM php:8.3-fpm

# ---------------------------
# Install dependencies
# ---------------------------
RUN apt-get update && apt-get install -y \
    nginx git unzip curl libzip-dev zip nodejs npm \
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
RUN npm install \
 && npm run build

# ---------------------------
# Clear caches
# ---------------------------
RUN php artisan config:clear \
 && php artisan cache:clear \
 && php artisan route:clear \
 && php artisan view:clear

# ---------------------------
# Permissions
# ---------------------------
RUN mkdir -p storage/logs \
 && chmod -R 777 storage bootstrap/cache \
 && chmod -R 777 public

# ---------------------------
# Enable PHP errors for debugging
# ---------------------------
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "display_startup_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# ---------------------------
# Nginx config
# ---------------------------
COPY docker/prod/nginx.conf /etc/nginx/conf.d/default.conf

# Expose port expected by Render
EXPOSE 10000

# ---------------------------
# Entrypoint: PHP-FPM foreground (Render expects single process)
# ---------------------------
CMD ["php-fpm", "-F"]