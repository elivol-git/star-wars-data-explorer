#!/bin/bash
# Deploy password extraction fix to production

set -e

SSH_KEY="${SSH_KEY:-$HOME/.ssh/aws-starwars.pem}"
EC2_USER_HOST="${EC2_USER_HOST:-ubuntu@16.171.145.213}"
REMOTE_PATH="${REMOTE_PATH:-/home/ubuntu/starwars}"

echo "=== Deploying RDS password fix ==="

# Sync code to EC2
echo "Syncing code to EC2..."
rsync -avz \
  --exclude=node_modules \
  --exclude=.git \
  --exclude=.idea \
  --exclude=storage/framework/views \
  --exclude=storage/logs \
  --exclude=bootstrap/cache \
  -e "ssh -i $SSH_KEY" ./ "$EC2_USER_HOST:$REMOTE_PATH"

# Rebuild and restart containers
ssh -i "$SSH_KEY" "$EC2_USER_HOST" bash -s <<'EOF'
set -e
cd /home/ubuntu/starwars

echo "Pulling latest code..."
git pull 2>/dev/null || echo "git pull skipped"

echo "Rebuilding Docker image..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml down planets_app
docker compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache app

echo "Starting container..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

echo "Waiting for app to start..."
sleep 10

echo "Checking app logs..."
docker logs planets_app --tail 50

echo "Done!"
EOF

echo "=== Deployment complete ==="
