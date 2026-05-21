#!/usr/bin/env bash
# Zero-downtime-ish deploy script. Run as the application user (www-data).
# Usage: cd /var/www/dms-app && ./deploy/deploy.sh
set -euo pipefail

cd "$(dirname "$0")/.."

echo "→ Enabling maintenance mode"
php artisan down --retry=10 --render="errors::503" || true

echo "→ Pulling latest code"
git fetch --all --prune
git reset --hard origin/main

echo "→ Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "→ Installing Node dependencies & building frontend"
npm ci --no-audit --no-fund
npm run build

echo "→ Running database migrations"
php artisan migrate --force

echo "→ Refreshing production caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "→ Restarting queue worker"
sudo systemctl restart dms-queue || true

echo "→ Reloading PHP-FPM (opcache reset)"
sudo systemctl reload php8.3-fpm || true

echo "→ Disabling maintenance mode"
php artisan up

echo "✓ Deploy complete."
