FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    nginx git unzip curl libzip-dev zip \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache \
 && echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "display_startup_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini \
 && echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

RUN chown -R www-data:www-data /var/www \
 && chmod -R 775 storage bootstrap/cache

COPY docker/prod/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 10000

CMD service nginx start && php-fpm