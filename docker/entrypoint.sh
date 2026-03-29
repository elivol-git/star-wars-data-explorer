#!/bin/bash
set -e

cd /var/www

echo "Running as:"
whoami

echo "Fixing permissions..."

# 🔥 MUST match php-fpm user
chown -R www-data:www-data /var/www || true

# Create required dirs
mkdir -p storage/logs
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
mkdir -p database

touch storage/logs/laravel.log
touch database/database.sqlite

# Permissions
chmod -R 775 storage bootstrap/cache database

echo "Laravel setup..."

php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

php artisan migrate --force || true
php artisan swapi:sync || true

echo "Starting services..."

php-fpm &
nginx -g "daemon off;"