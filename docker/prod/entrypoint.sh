#!/bin/sh
set -e

cd /var/www

echo "🚀 Starting Laravel production container..."

# ---------------------------
# Ensure required directories exist
# ---------------------------
mkdir -p storage/logs \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         bootstrap/cache

# ---------------------------
# Fix permissions (VERY important in prod)
# ---------------------------
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# ---------------------------
# Fetch DB password from Secrets Manager
# ---------------------------
if [ -z "$DB_PASSWORD" ]; then
    echo "📋 Fetching DB password from Secrets Manager..."
    SECRET=$(aws secretsmanager get-secret-value \
        --secret-id rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0 \
        --query SecretString \
        --output text \
        --region eu-north-1)
    DB_PASSWORD=$(echo "$SECRET" | sed -n 's/.*"password":"\([^"]*\)".*/\1/p')
    export DB_PASSWORD
fi

# ---------------------------
# Wait for DB (important in AWS/ECS/EC2 startup)
# ---------------------------
echo "⏳ Waiting for DB..."

until php -r "
try {
    \$pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE') . ';charset=utf8mb4',
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    );
    exit(0);
} catch (Throwable \$e) {
    exit(1);
}
"; do
  echo "DB not ready... retrying"
  sleep 3
done

echo "✅ DB is ready"

# ---------------------------
# Cache Laravel config safely
# IMPORTANT: do NOT fail container if already cached
# ---------------------------
echo "⚡ Caching Laravel config..."
php artisan config:clear || true
php artisan config:cache || true

php artisan route:cache || true
php artisan view:cache || true

# ---------------------------
# Run migrations safely
# ---------------------------
echo "🧱 Running migrations..."
php artisan migrate --force || true
php artisan swapi:sync || true

echo "🎯 Starting PHP-FPM..."

exec "$@"
