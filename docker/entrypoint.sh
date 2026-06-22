#!/usr/bin/env bash
#
# Container entrypoint. Runs with the runtime environment present, so this is
# where Laravel caches are (re)built and storage permissions are ensured.
#
set -e
cd /var/www/html

# Trust extra CA certificates (e.g. internal SMTP/mail CA, MinIO CA). Mount them
# into the directory below (any *.crt / *.pem). They are installed into the system
# trust store so PHP, Symfony Mailer and TLS clients trust them — no code change.
CA_SRC="${EXTRA_CA_DIR:-/certs}"
if [ -d "$CA_SRC" ]; then
    installed=0
    for f in "$CA_SRC"/*.crt "$CA_SRC"/*.pem; do
        [ -f "$f" ] || continue
        cp "$f" "/usr/local/share/ca-certificates/$(basename "${f%.*}").crt"
        installed=1
    done
    if [ "$installed" = 1 ]; then
        update-ca-certificates || true
        echo "Installed extra CA certificate(s) from $CA_SRC into the system trust store."
    fi
fi

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
