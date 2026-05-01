FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip nginx supervisor awscli mariadb-client nodejs npm \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /root/.aws

WORKDIR /var/www

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN npm ci && npm run build

RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/prod/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/prod/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/prod/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n"]
