#!/bin/bash
set -e

cd /var/www

chmod -R 777 storage bootstrap/cache

php artisan migrate --force || true
php artisan swapi:sync || true

# ✅ Serve app for Render
php artisan serve --host=0.0.0.0 --port=10000