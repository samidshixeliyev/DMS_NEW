#!/usr/bin/env bash
#
# Container entrypoint. Runs with the runtime environment present, so this is
# where Laravel caches are (re)built and storage permissions are ensured.
#
set -e
cd /var/www/html

# Ensure writable runtime dirs exist (a mounted storage volume starts empty).
mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ -z "${APP_KEY:-}" ]; then
    echo "WARNING: APP_KEY is empty — set it in the environment (php artisan key:generate locally and copy the value)."
fi

# Rebuild caches from the current environment.
php artisan package:discover --ansi || true
php artisan config:clear || true
php artisan cache:clear || true

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache  || true
fi

exec "$@"
