#!/bin/bash
# ByPass API - Git-based deploy script for Hostinger
# Usage: ./deploy.sh
#
# Prerequisites:
#   - SSH access configured to Hostinger
#   - Git repo initialized on server with remote pointing to GitHub
#   - Deploy key added to GitHub repo
#   - .env configured on server
#   - Set environment variables or edit default values below

set -e

# === CONFIGURATION ===
HOST="${HOSTINGER_HOST:-nl-srv-web1323.main-hosting.eu}"
USER="${HOSTINGER_USER:-u566067487}"
PORT="${HOSTINGER_SSH_PORT:-65002}"
REMOTE_PATH="${HOSTINGER_API_PATH:-/home/u566067487/domains/jobs-conseil.host/public_html/bypass-api}"
BRANCH="${BRANCH:-main}"

# PHP 8.2 binary path on Hostinger (default php is 7.4)
PHP_BIN="${PHP_BIN:-/usr/bin/php8.2}"
COMPOSER_BIN="$PHP_BIN /usr/local/bin/composer"

echo "=== ByPass API Deploy ==="
echo "Target: ${USER}@${HOST}:${PORT}"
echo "Path:   ${REMOTE_PATH}"
echo "PHP:    ${PHP_BIN}"
echo ""

# 1. Pull latest from GitHub
echo "[1/3] Pulling latest code from GitHub..."
ssh -p "${PORT}" "${USER}@${HOST}" "cd ${REMOTE_PATH} && git pull origin ${BRANCH}"

# 2. Install dependencies with PHP 8.2
echo "[2/3] Installing production dependencies..."
ssh -p "${PORT}" "${USER}@${HOST}" "cd ${REMOTE_PATH} && ${COMPOSER_BIN} install --no-dev --no-interaction --prefer-dist --optimize-autoloader"

# 3. Run post-deploy commands
echo "[3/3] Running post-deploy commands..."
ssh -p "${PORT}" "${USER}@${HOST}" "cd ${REMOTE_PATH} && ${PHP_BIN} artisan migrate --force && ${PHP_BIN} artisan config:cache && ${PHP_BIN} artisan route:cache && ${PHP_BIN} artisan view:cache && ${PHP_BIN} artisan storage:link 2>/dev/null; true"

echo ""
echo "Deploy complete!"
echo "Verify: curl https://jobs-conseil.host/bypass-api/api/health"
