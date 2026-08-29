#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"

cd "$ROOT_DIR"

command -v php >/dev/null 2>&1 || {
    echo "php is required for publish_framework_assets.sh" >&2
    exit 1
}

export APP_ENV="${APP_ENV:-production}"
export APP_KEY="${APP_KEY:-base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=}"

# Livewire 3 force-publishes assets internally, while newer signatures may expose --force directly.
if php artisan livewire:publish --help | grep -q -- '--force'; then
    php artisan livewire:publish --assets --force --ansi
else
    php artisan livewire:publish --assets --ansi
fi

php artisan filament:assets --ansi
