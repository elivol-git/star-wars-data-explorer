#!/usr/bin/env bash
set -euo pipefail

REMOTE_PATH="${REMOTE_PATH:-/home/ubuntu/starwars}"
DB_PASSWORD="${DB_PASSWORD:-}"
SECRET_ARN="${SECRET_ARN:-arn:aws:secretsmanager:eu-north-1:078238935621:secret:rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0-QGTzsj}"

echo "=========================================="
echo "🚀 Starting deployment on server"
echo "=========================================="
cd "$REMOTE_PATH"

# Fetch DB password if not already set
if [ -z "$DB_PASSWORD" ]; then
  echo "🔑 Fetching DB_PASSWORD from AWS Secrets Manager..."
  AWS_RESPONSE="$(aws secretsmanager get-secret-value \
    --secret-id "$SECRET_ARN" \
    --region eu-north-1 2>&1)"

  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to fetch secret from Secrets Manager"
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
fi

# Show what we're deploying
echo ""
echo "📍 Location: $REMOTE_PATH"
if [ -d .git ]; then
  echo "📝 Recent git commits:"
  git log --oneline -3
else
  echo "⚠️  No .git directory (code synced via rsync)"
fi

echo ""
echo "=========================================="
echo "🧹 Stopping containers..."
echo "=========================================="
docker compose -f docker-compose.yml -f docker-compose.prod.yml down || true

echo ""
echo "=========================================="
echo "🔨 Building and starting containers..."
echo "=========================================="
DB_PASSWORD="${DB_PASSWORD}" docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

echo ""
echo "⏳ Waiting for app to be healthy (this can take 90+ seconds)..."
MAX_WAIT=180
ELAPSED=0
while [ $ELAPSED -lt $MAX_WAIT ]; do
  if docker compose -f docker-compose.yml -f docker-compose.prod.yml ps --services --filter "status=running" | grep -q "^app$"; then
    STATUS=$(docker inspect planets_app --format='{{.State.Health.Status}}' 2>/dev/null || echo "unknown")
    if [ "$STATUS" = "healthy" ]; then
      echo "✅ App container is healthy"
      break
    else
      echo "⏳ App status: $STATUS (waiting...)"
    fi
  fi
  sleep 5
  ELAPSED=$((ELAPSED + 5))
done

if [ $ELAPSED -ge $MAX_WAIT ]; then
  echo "⚠️  App didn't become healthy in time. Checking logs..."
fi

echo ""
echo "=========================================="
echo "✅ Container status:"
echo "=========================================="
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps

echo ""
echo "=========================================="
echo "📋 App container logs (last 50 lines):"
echo "=========================================="
docker logs planets_app --tail 50 || true

echo ""
echo "=========================================="
echo "📋 Nginx container logs (last 50 lines):"
echo "=========================================="
docker logs planets_nginx --tail 50 || true

echo ""
echo "=========================================="
echo "✅ Deployment complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  - Check app health: curl http://localhost/api/health"
echo "  - View live logs: docker logs -f planets_app"
echo "  - Troubleshoot: docker compose -f docker-compose.yml -f docker-compose.prod.yml logs"
