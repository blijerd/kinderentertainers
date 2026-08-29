#!/bin/sh
set -eu

cd /var/www/html

run_php_as_www_data() {
    if command -v gosu >/dev/null 2>&1 && id www-data >/dev/null 2>&1; then
        gosu www-data php "$@"
    else
        php "$@"
    fi
}

exec_php_as_www_data() {
    if command -v gosu >/dev/null 2>&1 && id www-data >/dev/null 2>&1; then
        exec gosu www-data php "$@"
    else
        exec php "$@"
    fi
}

prepare_directories() {
    mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/app/vite-build-cache/assets \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs

    ln -snf ../storage/app/public public_html/storage

    if [ -f bootstrap/build-ref ]; then
        build_ref="$(tr -cd 'A-Za-z0-9._-' < bootstrap/build-ref)"
        if [ "${#build_ref}" -ge 4 ] && [ "${#build_ref}" -le 16 ]; then
            mkdir -p "storage/framework/views/${build_ref}"
            chown www-data:www-data "storage/framework/views/${build_ref}" 2>/dev/null || true
        fi
    fi
}

wait_for_database() {
    if [ "${WAIT_FOR_DB:-true}" != "true" ]; then
        return 0
    fi

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
}

rebuild_web_caches() {
    # Repair ownership from the web role only. Worker/scheduler share storage
    # and must not recursively chown while web is writing compiled views.
    chown -R www-data:www-data storage bootstrap/cache public_html/storage 2>/dev/null || true
    run_php_as_www_data artisan config:clear
    run_php_as_www_data artisan route:clear
    run_php_as_www_data artisan event:clear
    run_php_as_www_data artisan clear-compiled
    run_php_as_www_data artisan config:cache
    run_php_as_www_data artisan route:cache
    find storage/framework/views -maxdepth 1 -type f \( -name '*.php' -o -name '*.blade.php' \) -delete 2>/dev/null || true
    run_php_as_www_data artisan view:cache
    chown -R www-data:www-data storage/framework/views bootstrap/cache 2>/dev/null || true
}

rebuild_web_caches_under_lock() {
    lock_file=storage/framework/optimize-on-boot.lock
    mkdir -p storage/framework
    touch "$lock_file"

    if command -v flock >/dev/null 2>&1; then
        (
            flock -x 9
            rebuild_web_caches
        ) 9>"$lock_file"
    else
        rebuild_web_caches
    fi
}

should_run_migrations() {
    role="${1:-}"

    case "${RUN_MIGRATIONS:-auto}" in
        true|1|yes|on)
            return 0
            ;;
        false|0|no|off)
            return 1
            ;;
        auto)
            case "$role" in
                web|start-production|php-fpm)
                    return 0
                    ;;
                *)
                    return 1
                    ;;
            esac
            ;;
        *)
            echo "Invalid RUN_MIGRATIONS value: ${RUN_MIGRATIONS}. Use auto, true or false." >&2
            exit 1
            ;;
    esac
}

should_sync_content() {
    role="${1:-}"

    case "${SYNC_CONTENT_ON_BOOT:-auto}" in
        true|1|yes|on)
            return 0
            ;;
        false|0|no|off)
            return 1
            ;;
        auto)
            case "$role" in
                web|start-production|php-fpm)
                    return 0
                    ;;
                *)
                    return 1
                    ;;
            esac
            ;;
        *)
            echo "Invalid SYNC_CONTENT_ON_BOOT value: ${SYNC_CONTENT_ON_BOOT}. Use auto, true or false." >&2
            exit 1
            ;;
    esac
}

run_boot_migrations() {
    lock_file=storage/framework/migrate-on-boot.lock
    mkdir -p storage/framework
    touch "$lock_file"

    migrate_once() {
        echo "Running database migrations..."
        run_php_as_www_data artisan migrate --force
    }

    if command -v flock >/dev/null 2>&1; then
        (
            flock -x 9
            migrate_once
        ) 9>"$lock_file"
    else
        migrate_once
    fi
}

sync_vite_cache_if_enabled() {
    case "${SYNC_VITE_BUILD_ASSET_CACHE_ON_BOOT:-true}" in
        true|1|yes|on)
            if [ -x ./scripts/deploy/sync_vite_build_asset_cache.sh ]; then
                ./scripts/deploy/sync_vite_build_asset_cache.sh
            fi
            ;;
        false|0|no|off)
            ;;
        *)
            echo "Invalid SYNC_VITE_BUILD_ASSET_CACHE_ON_BOOT value: ${SYNC_VITE_BUILD_ASSET_CACHE_ON_BOOT}. Use true or false." >&2
            exit 1
            ;;
    esac
}

publish_framework_assets_if_enabled() {
    case "${PUBLISH_FRAMEWORK_ASSETS_ON_BOOT:-true}" in
        true|1|yes|on)
            if [ -x ./scripts/deploy/publish_framework_assets.sh ]; then
                ./scripts/deploy/publish_framework_assets.sh
            fi
            ;;
        false|0|no|off)
            ;;
        *)
            echo "Invalid PUBLISH_FRAMEWORK_ASSETS_ON_BOOT value: ${PUBLISH_FRAMEWORK_ASSETS_ON_BOOT}. Use true or false." >&2
            exit 1
            ;;
    esac
}

prepare_laravel() {
    role="${1:-}"
    rebuild_shared_view_cache="${2:-false}"

    prepare_directories
    wait_for_database

    if [ "$rebuild_shared_view_cache" != "true" ]; then
        chown -R www-data:www-data bootstrap/cache 2>/dev/null || true
    fi

    if [ "${RUN_STORAGE_LINK:-true}" = "true" ]; then
        run_php_as_www_data artisan storage:link --force || true
    fi

    if should_run_migrations "$role"; then
        run_boot_migrations
    fi

    if should_sync_content "$role"; then
        echo "Syncing repository content..."
        run_php_as_www_data artisan content:sync
    fi

    if [ "$rebuild_shared_view_cache" = "true" ] && [ "${RUN_OPTIMIZE:-true}" = "true" ]; then
        rebuild_web_caches_under_lock
        publish_framework_assets_if_enabled
        sync_vite_cache_if_enabled
    elif [ "${RUN_OPTIMIZE:-false}" = "true" ]; then
        run_php_as_www_data artisan config:cache
        run_php_as_www_data artisan route:cache
    fi
}

if [ "${KINDERENTERTAINERS_RUNTIME_COMMAND:-}" != "" ]; then
    case "$KINDERENTERTAINERS_RUNTIME_COMMAND" in
        web|worker|scheduler)
            set -- "$KINDERENTERTAINERS_RUNTIME_COMMAND"
            ;;
        *)
            echo "Invalid KINDERENTERTAINERS_RUNTIME_COMMAND: $KINDERENTERTAINERS_RUNTIME_COMMAND. Expected web, worker or scheduler." >&2
            exit 64
            ;;
    esac
fi

if [ "$#" -eq 0 ]; then
    set -- php-fpm
fi

if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

role="$1"
case "$role" in
    start-production)
        role="web"
        ;;
esac
printf '%s' "$role" > /tmp/kinderentertainers-runtime-role

case "$1" in
    web|start-production)
        prepare_laravel web true
        exec supervisord -c /etc/supervisor/conf.d/laravel.conf
        ;;
    worker)
        prepare_laravel worker false
        shift
        exec_php_as_www_data artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-time=3600 "$@"
        ;;
    scheduler)
        prepare_laravel scheduler false
        exec_php_as_www_data artisan schedule:work
        ;;
    php-fpm)
        prepare_laravel php-fpm false
        exec "$@"
        ;;
    php|artisan)
        prepare_directories
        wait_for_database
        exec_php_as_www_data "$@"
        ;;
    *)
        prepare_directories
        exec "$@"
        ;;
esac
