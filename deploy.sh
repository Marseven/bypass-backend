#!/bin/bash
# ByPass API - Manual deploy script for Hostinger
# Usage: ./deploy.sh
#
# Prerequisites:
#   - SSH access configured to Hostinger
#   - Set environment variables or edit default values below

set -e

# === CONFIGURATION ===
HOST="${HOSTINGER_HOST:-nl-srv-web1323.main-hosting.eu}"
USER="${HOSTINGER_USER:-u566067487}"
PORT="${HOSTINGER_SSH_PORT:-65002}"
REMOTE_PATH="${HOSTINGER_API_PATH:-/home/u566067487/domains/jobs-conseil.host/public_html/bypass-api}"

echo "=== ByPass API Deploy ==="
echo "Target: ${USER}@${HOST}:${PORT}"
echo "Path:   ${REMOTE_PATH}"
echo ""

# 1. Install production dependencies locally
echo "[1/4] Installing production dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 2. Rsync to server (no --delete to preserve .env and storage)
echo "[2/4] Syncing files to server..."
rsync -avz \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='tests' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/app/imports/*' \
    --exclude='.env' \
    --exclude='node_modules' \
    --exclude='deploy.sh' \
    --exclude='.husky' \
    -e "ssh -p ${PORT}" \
    ./ "${USER}@${HOST}:${REMOTE_PATH}/"

# 3. Run remote commands
echo "[3/4] Running post-deploy commands on server..."
ssh -p "${PORT}" "${USER}@${HOST}" "cd ${REMOTE_PATH} && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan queue:restart"

# 4. Done
echo "[4/4] Deploy complete!"
echo ""
echo "Verify:"
echo "  curl https://jobs-conseil.host/bypass-api/api/health"
echo "  curl https://bypass-api.jobs-conseil.host/api/health  (si sous-domaine cree)"
