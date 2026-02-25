#!/bin/bash
# ByPass API - Git-based deploy script (run on server)
# Usage: bash deploy.sh
#
# Prerequisites:
#   - Run directly on the Hostinger server via SSH
#   - Git repo initialized with HTTPS remote (public repo, no auth needed)
#   - .env configured on server

set -e

# === CONFIGURATION ===
REMOTE_PATH="/home/u566067487/domains/jobs-conseil.host/public_html/bypass-api"
BRANCH="${BRANCH:-main}"
PHP_BIN="/opt/alt/php82/usr/bin/php"
COMPOSER_BIN="$PHP_BIN /usr/local/bin/composer"

echo "=== ByPass API Deploy ==="
echo "Path:   ${REMOTE_PATH}"
echo "PHP:    ${PHP_BIN}"
echo ""

cd "${REMOTE_PATH}"

# 1. Pull latest from GitHub (HTTPS, no auth needed for public repo)
echo "[1/3] Pulling latest code from GitHub..."
git pull origin "${BRANCH}"

# 2. Install dependencies with PHP 8.2
echo "[2/3] Installing production dependencies..."
${COMPOSER_BIN} install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 3. Run post-deploy commands
echo "[3/3] Running post-deploy commands..."
${PHP_BIN} artisan migrate --force
${PHP_BIN} artisan config:cache
${PHP_BIN} artisan route:cache
${PHP_BIN} artisan view:cache
${PHP_BIN} artisan storage:link 2>/dev/null; true

echo ""
echo "Deploy complete!"
echo "Verify: curl https://jobs-conseil.host/bypass-api/api/health"
