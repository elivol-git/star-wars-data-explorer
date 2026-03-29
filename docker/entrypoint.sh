#!/bin/bash
set -e

cd /var/www

echo "Running as:"
whoami

echo "Fixing Laravel folders..."

# 🔥 Create ALL required directories (CRITICAL)
mkdir -p storage/logs
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
mkdir -p database

# Files
touch storage/logs/laravel.log
touch database/database.sqlite

# 🔥 Permissions (simple + reliable)
chmod -R 777 storage bootstrap/cache database

echo "Clearing Laravel cache..."

php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

echo "Running migrations..."

php artisan migrate --force || true
php artisan swapi:sync || true

echo "Starting services..."

php-fpm &
nginx -g "daemon off;"