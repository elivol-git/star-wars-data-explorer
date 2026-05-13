#!/usr/bin/env bash
set -euo pipefail

LOCAL_PATH="${LOCAL_PATH:-/mnt/c/projects/star-wars-data-explorer}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/aws-starwars.pem}"
EC2_IP="${EC2_IP:-16.171.145.213}"
EC2_USER="${EC2_USER:-ubuntu}"
EC2_USER_HOST="${EC2_USER}@${EC2_IP}"
REMOTE_PATH="${REMOTE_PATH:-/home/ubuntu/starwars}"

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
  --exclude=public/hot \
  --exclude=public/images/entities \
  -e "ssh -i $SSH_KEY" ./ "$EC2_USER_HOST:$REMOTE_PATH"

echo "✅ Sync complete"
echo ""
echo "=========================================="
echo "🚀 Connecting to server..."
echo "=========================================="
echo "Ready to run: cd $REMOTE_PATH && bash scripts/deploy-server.sh"
echo ""

ssh -t -i "$SSH_KEY" "$EC2_USER_HOST" "cd $REMOTE_PATH && exec bash"
