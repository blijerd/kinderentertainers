#!/usr/bin/env sh
# Expand-only sync of fingerprinted Vite assets onto the shared storage volume.
# During Coolify rolling updates old+new web containers share Traefik briefly.
# Keeping previous hashes on shared storage prevents static.kinderentertainers.nl
# 404s when HTML from the new image is served while Traefik still routes some
# asset requests to the previous container (or vice versa).
set -eu

cd /var/www/html

release_assets="public_html/build/assets"
cache_root="storage/app/vite-build-cache"
cache_assets="${cache_root}/assets"
lock_file="storage/framework/vite-build-asset-cache.lock"

if [ ! -d "${release_assets}" ]; then
    echo "Vite release assets missing at ${release_assets}; skipping shared cache sync."
    exit 0
fi

mkdir -p "${cache_assets}" storage/framework
touch "${lock_file}"

sync_once() {
    # -a preserves modes; -n does not overwrite existing fingerprinted files.
    cp -an "${release_assets}/." "${cache_assets}/"

    # Ensure this container can serve every cached hash from the public path too
    # (covers local/dev and any proxy that still hits public_html/build).
    cp -an "${cache_assets}/." "${release_assets}/"

    if [ -f "public_html/build/manifest.json" ]; then
        cp -a "public_html/build/manifest.json" "${cache_root}/manifest.json"
    fi

    chown -R www-data:www-data "${cache_root}" 2>/dev/null || true
}

if command -v flock >/dev/null 2>&1; then
    (
        flock -x 9
        sync_once
    ) 9>"${lock_file}"
else
    sync_once
fi

echo "Synced Vite build assets into shared cache at ${cache_assets}."
