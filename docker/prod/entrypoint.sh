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

    SECRET_ARN="${SECRET_ARN:-arn:aws:secretsmanager:eu-north-1:078238935621:secret:rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0-QGTzsj}"
    REGION="eu-north-1"

    AWS_RESPONSE=$(aws secretsmanager get-secret-value \
        --secret-id "$SECRET_ARN" \
        --region "$REGION" 2>&1)

    if [ $? -eq 0 ]; then
        DB_PASSWORD=$(echo "$AWS_RESPONSE" | php << 'PHPEOF'
<?php
$json = json_decode(file_get_contents('php://stdin'), true);
if (!isset($json['SecretString'])) {
    fwrite(STDERR, "ERROR: SecretString not found in AWS response\n");
    exit(1);
}
$secret = json_decode($json['SecretString'], true);
if (!isset($secret['password'])) {
    fwrite(STDERR, "ERROR: password field not found in SecretString\n");
    exit(1);
}
echo $secret['password'];
?>
PHPEOF
)
        if [ $? -ne 0 ] || [ -z "$DB_PASSWORD" ]; then
            echo "❌ Failed to extract password from AWS response"
            echo "AWS Response: $AWS_RESPONSE"
            exit 1
        fi
        export DB_PASSWORD
        echo "✅ Password fetched (length: ${#DB_PASSWORD})"
    else
        echo "❌ Failed to fetch from Secrets Manager: $AWS_RESPONSE"
        exit 1
    fi
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
