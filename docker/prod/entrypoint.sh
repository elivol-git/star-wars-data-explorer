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
echo "DEBUG: DB_PASSWORD='$DB_PASSWORD' (length: ${#DB_PASSWORD})"
if [ -z "$DB_PASSWORD" ]; then
    echo "Fetching DB password from Secrets Manager..."

    SECRET_ARN="${SECRET_ARN:-arn:aws:secretsmanager:eu-north-1:078238935621:secret:rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0-QGTzsj}"
    REGION="eu-north-1"

    echo "Calling AWS: aws secretsmanager get-secret-value --secret-id $SECRET_ARN --region $REGION"
    AWS_RESPONSE=$(aws secretsmanager get-secret-value \
        --secret-id "$SECRET_ARN" \
        --region "$REGION" 2>&1)
    AWS_EXIT=$?
    echo "AWS exit code: $AWS_EXIT"

    if [ $AWS_EXIT -eq 0 ]; then
        echo "Parsing JSON response with PHP..."
        DB_PASSWORD=$(echo "$AWS_RESPONSE" | php -r '
$json = json_decode(file_get_contents("php://stdin"), true);
if (!isset($json["SecretString"])) {
    fwrite(STDERR, "ERROR: SecretString not found\n");
    exit(1);
}
$secret = json_decode($json["SecretString"], true);
if (!isset($secret["password"])) {
    fwrite(STDERR, "ERROR: password field not found\n");
    exit(1);
}
echo $secret["password"];
' 2>&1)
        PHP_EXIT=$?
        echo "PHP exit code: $PHP_EXIT"
        echo "Extracted password length: ${#DB_PASSWORD}"

        if [ $PHP_EXIT -ne 0 ] || [ -z "$DB_PASSWORD" ]; then
            echo "ERROR: Failed to extract password"
            echo "PHP Error: $DB_PASSWORD"
            exit 1
        fi
        export DB_PASSWORD
        echo "Password fetched successfully"
    else
        echo "ERROR: AWS call failed"
        echo "AWS Error: $AWS_RESPONSE"
        exit 1
    fi
else
    echo "DB_PASSWORD already set, skipping Secrets Manager fetch"
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
