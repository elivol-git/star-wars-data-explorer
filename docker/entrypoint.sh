#!/bin/bash
set -e

cd /var/www

echo "Running as:"
whoami

echo "Fixing permissions (FINAL FIX)..."

# 🔥 Remove broken dirs completely
rm -rf storage bootstrap/cache

# 🔥 Recreate everything clean
mkdir -p storage/logs
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
mkdir -p database

touch database/database.sqlite

chown -R www-data:www-data storage bootstrap/cache database || true
chmod -R 777 storage bootstrap/cache database

echo "Laravel setup..."

php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

php artisan migrate --force || true
php artisan swapi:sync || true

echo "Starting services..."

# 🔥 IMPORTANT: foreground php-fpm
php-fpm -F &
nginx -g "daemon off;"