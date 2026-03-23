#!/bin/bash
set -e

cd /var/www

# Composer dependencies (install if missing)
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

# Permissions
chmod -R 777 storage bootstrap/cache

# Laravel setup
php artisan key:generate || true
php artisan migrate --force || true
php artisan swapi:sync || true

# Start PHP-FPM in foreground (Render expects a foreground process)
php-fpm -F
