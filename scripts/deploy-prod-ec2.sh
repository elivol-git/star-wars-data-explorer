#!/usr/bin/env bash
set -euo pipefail

LOCAL_PATH="${LOCAL_PATH:-/mnt/c/projects/star-wars-data-explorer}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/aws-starwars.pem}"
EC2_USER_HOST="${EC2_USER_HOST:-ubuntu@16.171.145.213}"
REMOTE_PATH="${REMOTE_PATH:-/home/ubuntu/starwars}"
SECRET_ARN="${SECRET_ARN:-arn:aws:secretsmanager:eu-north-1:078238935621:secret:rds!db-7e5ad50b-88ae-4554-ad3e-f6dbe758b9d0-QGTzsj}"

cd "$LOCAL_PATH"

if [ -z "${DB_PASSWORD:-}" ]; then
  if command -v aws >/dev/null 2>&1; then
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
  else
    echo "ERROR: DB_PASSWORD is not set and aws CLI is unavailable."
    echo "Set DB_PASSWORD in your shell for this run, or install aws CLI v2."
    exit 1
  fi
fi

rsync -avz \
  --exclude=node_modules \
  --exclude=.git \
  --exclude=.idea \
  --exclude=storage/framework/views \
  --exclude=storage/logs \
  --exclude=bootstrap/cache \
  -e "ssh -i $SSH_KEY" ./ "$EC2_USER_HOST:$REMOTE_PATH"

ssh -i "$SSH_KEY" "$EC2_USER_HOST" DB_PASSWORD="$DB_PASSWORD" bash -s <<EOF
set -euo pipefail
cd "$REMOTE_PATH"

docker --version
docker compose version

if [ ! -f global-bundle.pem ]; then
  curl -fsSL -o global-bundle.pem https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem
fi

docker run --rm --network host -e DB_HOST=planets.cn2eau4c0ak7.eu-north-1.rds.amazonaws.com -e DB_PORT=3306 \
  php:8.3-cli sh -lc 'php -r "$h=getenv(\"DB_HOST\");$p=(int)getenv(\"DB_PORT\");$s=@fsockopen($h,$p,$e,$es,8); if($s){echo \"DB TCP OK\n\"; fclose($s);} else {fwrite(STDERR, \"DB TCP FAIL: $e $es\n\"); exit(1);} "'

DB_PASSWORD="\$DB_PASSWORD" docker compose -f docker-compose.yml -f docker-compose.prod.yml down && \
  DB_PASSWORD="\$DB_PASSWORD" docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
docker logs planets_app --tail 80
docker logs planets_nginx --tail 80

docker exec planets_app php artisan config:clear
docker exec planets_app php artisan cache:clear
docker exec planets_app php artisan migrate --force
docker exec planets_app php artisan swapi:sync
EOF
