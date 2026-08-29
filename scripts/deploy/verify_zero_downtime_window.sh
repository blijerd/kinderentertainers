#!/usr/bin/env bash
# Continuously poll a public host during a Coolify rolling web redeploy.
# Fails if any sample returns a non-success HTTP status (including 502).
set -euo pipefail

BASE_URL="${BASE_URL:-${1:-}}"
INTERVAL_SECONDS="${INTERVAL_SECONDS:-2}"
MAX_SECONDS="${MAX_SECONDS:-900}"
PATHS="${PATHS:-/ /up}"

if [[ -z "${BASE_URL}" ]]; then
    echo "Usage: BASE_URL=https://example.tld $0" >&2
    echo "   or: $0 https://example.tld" >&2
    exit 64
fi

BASE_URL="${BASE_URL%/}"

if ! [[ "${INTERVAL_SECONDS}" =~ ^[0-9]+$ ]] || [[ "${INTERVAL_SECONDS}" -lt 1 ]]; then
    echo "INTERVAL_SECONDS must be a positive integer." >&2
    exit 64
fi

if ! [[ "${MAX_SECONDS}" =~ ^[0-9]+$ ]] || [[ "${MAX_SECONDS}" -lt 1 ]]; then
    echo "MAX_SECONDS must be a positive integer." >&2
    exit 64
fi

started_at="$(date +%s)"
deadline=$((started_at + MAX_SECONDS))
samples=0
failures=0

echo "Zero-downtime probe against ${BASE_URL}"
echo "Paths:${PATHS} interval=${INTERVAL_SECONDS}s max=${MAX_SECONDS}s"
echo "Start: $(date -u +%Y-%m-%dT%H:%M:%SZ)"

while true; do
    now="$(date +%s)"
    if (( now >= deadline )); then
        break
    fi

    for path in ${PATHS}; do
        url="${BASE_URL}${path}"
        code="$(curl -sS -o /dev/null -w '%{http_code}' --connect-timeout 5 --max-time 15 "${url}" || true)"
        samples=$((samples + 1))

        if [[ ! "${code}" =~ ^[23][0-9][0-9]$ ]]; then
            failures=$((failures + 1))
            echo "$(date -u +%Y-%m-%dT%H:%M:%SZ) FAIL ${url} HTTP ${code:-000}" >&2
            echo "Zero-downtime probe failed after ${samples} samples (${failures} failures)." >&2
            exit 1
        fi

        echo "$(date -u +%Y-%m-%dT%H:%M:%SZ) ok ${url} HTTP ${code}"
    done

    sleep "${INTERVAL_SECONDS}"
done

echo "Zero-downtime probe passed: ${samples} samples, 0 failures over ${MAX_SECONDS}s."
exit 0
