#!/bin/bash
set -e

cd /var/www

# Install dependencies (only if missing)
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -d "node_modules" ]; then
    npm install
fi

# Permissions
chmod -R 777 storage bootstrap/cache

# Laravel setup
php artisan key:generate || true
php artisan migrate --force || true
php artisan swapi:sync || true

# Start services
php-fpm &
npm run dev &

# Keep container alive
tail -f /dev/null
