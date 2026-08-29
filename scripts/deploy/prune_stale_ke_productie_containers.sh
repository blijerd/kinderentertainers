#!/usr/bin/env bash
# Remove superseded ke-productie web containers after a healthy replacement exists.
# Prevents Traefik from load-balancing hashed Vite assets across old+new images.
set -euo pipefail

LABEL_FILTER="${LABEL_FILTER:-label=coolify.resourceName=ke-productie}"
WAIT_SECONDS="${WAIT_SECONDS:-900}"
POLL_SECONDS="${POLL_SECONDS:-5}"
DRY_RUN="${DRY_RUN:-0}"
# Only accept a healthy container that started at/after this epoch (deploy queue time).
MIN_STARTED_EPOCH="${MIN_STARTED_EPOCH:-0}"

die() {
  echo "prune_stale_ke_productie_containers: $*" >&2
  exit 1
}

command -v docker >/dev/null 2>&1 || die "docker is required"

to_epoch() {
  # Portable-ish parse of docker StartedAt (RFC3339 / Go time).
  date -u -d "$1" +%s 2>/dev/null || date -u -j -f '%Y-%m-%dT%H:%M:%S' "${1%%.*}" +%s 2>/dev/null || echo 0
}

deadline=$(( $(date +%s) + WAIT_SECONDS ))
newest_healthy_id=""
newest_healthy_ts=0

echo "Waiting up to ${WAIT_SECONDS}s for a healthy ke-productie container (min started epoch ${MIN_STARTED_EPOCH})..."

while true; do
  newest_healthy_id=""
  newest_healthy_ts=0

  mapfile -t running < <(docker ps --filter "${LABEL_FILTER}" --filter status=running --format '{{.ID}}')
  for id in "${running[@]:-}"; do
    [[ -z "${id}" ]] && continue
    status="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "${id}" 2>/dev/null || echo missing)"
    [[ "${status}" == "healthy" ]] || continue
    started="$(docker inspect -f '{{.State.StartedAt}}' "${id}")"
    ts="$(to_epoch "${started}")"
    if (( MIN_STARTED_EPOCH > 0 && ts < MIN_STARTED_EPOCH )); then
      continue
    fi
    if (( ts >= newest_healthy_ts )); then
      newest_healthy_ts="${ts}"
      newest_healthy_id="${id}"
    fi
  done

  if [[ -n "${newest_healthy_id}" ]]; then
    break
  fi

  if (( $(date +%s) >= deadline )); then
    die "timed out waiting for a healthy ke-productie container"
  fi

  sleep "${POLL_SECONDS}"
done

echo "Newest healthy web container: ${newest_healthy_id} (started epoch ${newest_healthy_ts})"

mapfile -t running < <(docker ps --filter "${LABEL_FILTER}" --filter status=running --format '{{.ID}}')
stale=()
for id in "${running[@]:-}"; do
  [[ -z "${id}" || "${id}" == "${newest_healthy_id}" ]] && continue
  started="$(docker inspect -f '{{.State.StartedAt}}' "${id}")"
  ts="$(to_epoch "${started}")"
  # Only remove older siblings. Never touch a newer container that is still starting.
  if (( ts > 0 && ts < newest_healthy_ts )); then
    stale+=("${id}")
  else
    echo "Keeping non-stale sibling ${id} started=${started}"
  fi
done

if [[ ${#stale[@]} -eq 0 ]]; then
  echo "No stale ke-productie containers to remove."
  exit 0
fi

for id in "${stale[@]}"; do
  image="$(docker inspect -f '{{.Config.Image}}' "${id}" 2>/dev/null || echo unknown)"
  started="$(docker inspect -f '{{.State.StartedAt}}' "${id}" 2>/dev/null || echo unknown)"
  echo "Pruning stale container ${id} image=${image} started=${started}"
  if [[ "${DRY_RUN}" == "1" ]]; then
    continue
  fi
  docker stop "${id}" >/dev/null
  docker rm "${id}" >/dev/null || true
done

echo "Prune complete. Remaining:"
docker ps --filter "${LABEL_FILTER}" --format '{{.ID}} {{.Status}} {{.Image}}'
