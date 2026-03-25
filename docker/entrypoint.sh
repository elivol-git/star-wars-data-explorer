#!/bin/bash
set -e

cd /var/www

# Permissions
chmod -R 777 storage bootstrap/cache database

# Laravel setup
php artisan migrate --force || true
php artisan swapi:sync || true

# Start PHP-FPM
php-fpm &

# Start Nginx (MAIN process)
nginx -g "daemon off;"