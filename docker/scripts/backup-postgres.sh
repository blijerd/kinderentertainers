#!/bin/sh
set -euo pipefail

stamp=$(date -u +%Y%m%dT%H%M%SZ)
out_dir=${BACKUP_DIR:-./storage/backups}
mkdir -p "$out_dir"

file="${out_dir}/kinderentertainers-${stamp}.sql.gz"

pg_dump \
    --host="${DB_HOST:-127.0.0.1}" \
    --port="${DB_PORT:-5432}" \
    --username="${DB_USERNAME:-kinderentertainers}" \
    --dbname="${DB_DATABASE:-kinderentertainers}" \
    --no-owner \
    --format=plain \
    | gzip > "$file"

echo "Backup geschreven naar ${file}"
