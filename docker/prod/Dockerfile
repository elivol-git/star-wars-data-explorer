FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    nginx git unzip curl libzip-dev zip \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy project
COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN php artisan config:clear \
 && php artisan cache:clear \
 && php artisan route:clear \
 && php artisan view:clear

# Permissions (use 777 for debugging)
RUN mkdir -p storage/logs \
 && chmod -R 777 storage bootstrap/cache

# Enable PHP errors
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "display_startup_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# Nginx
COPY docker/prod/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 10000

CMD service nginx start && php-fpm