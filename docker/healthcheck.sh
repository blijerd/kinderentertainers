#!/bin/sh
set -eu

role_file="/tmp/kinderentertainers-runtime-role"
role="web"

if [ -f "$role_file" ]; then
    role="$(tr -d '[:space:]' < "$role_file")"
fi

# Worker/scheduler do not listen on :80. Mark the container healthy when PHP
# can still boot, so Coolify can replace stale siblings instead of keeping an
# old process that executes another image's compiled Blade.
if [ "$role" != "web" ]; then
    php -r 'exit(is_file("/var/www/html/vendor/autoload.php") ? 0 : 1);'
    exit 0
fi

# TrustHosts rejects bare 127.0.0.1 with HTTP 400; send Host from APP_URL.
host="$(php -r 'echo parse_url(getenv("APP_URL") ?: "http://127.0.0.1", PHP_URL_HOST) ?: "127.0.0.1";')"
curl -fsS -H "Host: ${host}" http://127.0.0.1/up >/dev/null
