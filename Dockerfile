# syntax=docker/dockerfile:1
#
# DMS — single production image: Apache + mod_php (PHP 8.2), with the SQL Server
# driver (msodbcsql17 + pdo_sqlsrv) and Supervisor running Apache + Laravel
# scheduler + queue worker together. Frontend assets are built in a Node stage.
#
# Serves Laravel's public/ on port 80. Put your own (host) nginx in front and
# proxy to the published port — this image intentionally ships no nginx.

# ---------------------------------------------------------------------------
# Stage 1 — build frontend assets (Vite + Tailwind v4)
# ---------------------------------------------------------------------------
FROM node:20-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2 — PHP / Apache runtime
# ---------------------------------------------------------------------------
FROM php:8.2-apache AS app

ENV DEBIAN_FRONTEND=noninteractive \
    ACCEPT_EULA=Y \
    COMPOSER_ALLOW_SUPERUSER=1

# Base system libs, PHP extensions, Apache modules, Supervisor.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        gnupg2 ca-certificates apt-transport-https curl unzip \
        $PHPIZE_DEPS \
        libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
        unixodbc-dev supervisor; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" pdo zip intl bcmath opcache pcntl gd exif; \
    a2enmod rewrite headers; \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf; \
    apt-get clean; rm -rf /var/lib/apt/lists/*

# Microsoft SQL Server driver: ODBC 17 + pdo_sqlsrv/sqlsrv (Debian 12 bookworm).
# ODBC 17 is used to match the previous environment: unlike ODBC 18 it does NOT
# force Encrypt=yes, so connections to a self-signed SQL Server work without TLS
# config. Microsoft's repo key is SHA1-signed (modern apt/sqv rejects it), so the
# repo is marked trusted to skip signature verification for this controlled build.
RUN set -eux; \
    echo "deb [trusted=yes] https://packages.microsoft.com/debian/12/prod bookworm main" \
        > /etc/apt/sources.list.d/mssql-release.list; \
    apt-get update; \
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql17; \
    # Pin to 5.12.0 — the latest sqlsrv requires PHP >= 8.3; 5.12.0 supports 8.2.
    pecl install sqlsrv-5.12.0 pdo_sqlsrv-5.12.0; \
    docker-php-ext-enable sqlsrv pdo_sqlsrv; \
    apt-get clean; rm -rf /var/lib/apt/lists/*

# Composer (from the official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# App config: PHP ini, Apache vhost, Supervisor, entrypoint
COPY docker/php.ini      /usr/local/etc/php/conf.d/zz-dms.ini
COPY docker/vhost.conf   /etc/apache2/sites-available/000-default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# Install PHP deps first (better layer caching), without running artisan scripts
# (no .env at build time — caching happens in the entrypoint at runtime).
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Application code + built assets
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
