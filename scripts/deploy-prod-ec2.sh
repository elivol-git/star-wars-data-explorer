#!/usr/bin/env bash
set -euo pipefail

LOCAL_PATH="${LOCAL_PATH:-/mnt/c/projects/star-wars-data-explorer}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/aws-starwars.pem}"
EC2_USER_HOST="${EC2_USER_HOST:-ubuntu@16.171.145.213}"
REMOTE_PATH="${REMOTE_PATH:-/home/ubuntu/starwars}"
SECRET_ARN="${SECRET_ARN:-arn:aws:secretsmanager:eu-north-1:078238935621:secret:rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0-QGTzsj}"

cd "$LOCAL_PATH"

echo "=========================================="
echo "📤 Syncing code to EC2..."
echo "=========================================="

rsync -avz \
  --exclude=node_modules \
  --exclude=vendor \
  --exclude=.git \
  --exclude=.phpunit.result.cache \
  --exclude=.idea \
  --exclude=.claude \
  --exclude=storage/framework/views \
  --exclude=storage/framework/cache \
  --exclude=storage/framework/sessions \
  --exclude=storage/logs \
  --exclude=bootstrap/cache \
  --exclude=docker/dev \
  --exclude=docker/ssl \
  --exclude=docker/mysql \
  --exclude=public/build \
  -e "ssh -i $SSH_KEY" ./ "$EC2_USER_HOST:$REMOTE_PATH"

echo "✅ Sync complete"

if [ -z "${DB_PASSWORD:-}" ]; then
  if command -v aws >/dev/null 2>&1; then
    echo ""
    echo "🔑 Fetching DB_PASSWORD from AWS Secrets Manager..."
    AWS_RESPONSE="$(aws secretsmanager get-secret-value \
      --secret-id "$SECRET_ARN" \
      --region eu-north-1 2>&1)"

    if [ $? -ne 0 ]; then
      echo "ERROR: Failed to fetch secret from Secrets Manager: $AWS_RESPONSE"
      exit 1
    fi

    DB_PASSWORD="$(echo "$AWS_RESPONSE" | python3 -c '
import json, sys
try:
    response = json.load(sys.stdin)
    secret_string = json.loads(response.get("SecretString", "{}"))
    password = secret_string.get("password", "")
    if not password:
        print("ERROR: password field not found", file=sys.stderr)
        sys.exit(1)
    print(password)
except Exception as e:
    print(f"ERROR: {e}", file=sys.stderr)
    sys.exit(1)
')"

    if [ $? -ne 0 ] || [ -z "$DB_PASSWORD" ]; then
      echo "ERROR: Failed to extract password from AWS response."
      exit 1
    fi
    echo "✅ Password fetched"
  else
    echo "ERROR: DB_PASSWORD is not set and aws CLI is unavailable."
    echo "Set DB_PASSWORD in your shell for this run, or install aws CLI v2."
    exit 1
  fi
fi

echo ""
echo "=========================================="
echo "🚀 Running deployment on EC2..."
echo "=========================================="

ssh -i "$SSH_KEY" "$EC2_USER_HOST" DB_PASSWORD="$DB_PASSWORD" REMOTE_PATH="$REMOTE_PATH" bash -s <<'DEPLOY_SCRIPT'
set -euo pipefail

cd "$REMOTE_PATH"

echo "🔧 Pre-deployment checks..."
docker --version
docker compose version

if [ ! -f global-bundle.pem ]; then
  echo "📥 Fetching RDS certificate..."
  curl -fsSL -o global-bundle.pem https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem
fi

echo "🧪 Testing DB connectivity..."
docker run --rm --network host -e DB_HOST=planets.cn2eau4c0ak7.eu-north-1.rds.amazonaws.com -e DB_PORT=3306 \
  php:8.3-cli sh -lc 'php -r "$h=getenv(\"DB_HOST\");$p=(int)getenv(\"DB_PORT\");$s=@fsockopen($h,$p,$e,$es,8); if($s){echo \"DB TCP OK\n\"; fclose($s);} else {fwrite(STDERR, \"DB TCP FAIL: $e $es\n\"); exit(1);} "'

echo ""
echo "📦 Running deploy-server.sh..."
bash "$REMOTE_PATH/scripts/deploy-server.sh"

DEPLOY_SCRIPT

echo ""
echo "✅ Deployment complete!"
