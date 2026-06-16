#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

ln -snf ../storage/app/public public_html/storage

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data bootstrap/cache storage public_html/storage
fi

if [ "${WAIT_FOR_DB:-true}" = "true" ]; then
    case "${DB_CONNECTION:-}" in
        pgsql)
            echo "Waiting for PostgreSQL at ${DB_HOST:-postgres}:${DB_PORT:-5432}..."
            until PGPASSWORD="${DB_PASSWORD:-}" pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" -d "${DB_DATABASE:-postgres}" >/dev/null 2>&1; do
                sleep 2
            done
            ;;
        mysql|mariadb)
            echo "Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
            until mysqladmin ping -h"${DB_HOST:-mysql}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" --password="${DB_PASSWORD:-}" --silent; do
                sleep 2
            done
            ;;
    esac
fi

if [ "${RUN_STORAGE_LINK:-true}" = "true" ]; then
    gosu www-data php artisan storage:link --force --relative || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    gosu www-data php artisan migrate --force
fi

if [ "${RUN_OPTIMIZE:-false}" = "true" ]; then
    gosu www-data php artisan optimize
fi

if [ "$#" -eq 0 ]; then
    set -- php-fpm
fi

if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

case "$1" in
    php-fpm)
        exec "$@"
        ;;
    php|artisan)
        exec gosu www-data "$@"
        ;;
    *)
        exec "$@"
        ;;
esac
