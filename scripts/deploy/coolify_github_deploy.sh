#!/usr/bin/env bash
# Forced-command helper for the GitHub Actions Coolify deploy key.
# Installed on production as /usr/local/sbin/kinderentertainers-coolify-github-deploy.sh
# Source of truth: scripts/deploy/coolify_github_deploy.sh
#
# Allowed SSH_ORIGINAL_COMMAND examples:
#   deploy
#   deploy --force
#   deploy --apps=web
#   deploy --apps=web,worker,scheduler --force
set -euo pipefail

readonly TOKEN_FILE="/root/.config/kinderentertainers/coolify-deploy-token"
readonly COOLIFY_API="http://127.0.0.1:8000/api/v1/deploy"

# Fill these after creating the Coolify applications. Empty values refuse to deploy.
readonly WEB_UUID=""
readonly WORKER_UUID=""
readonly SCHEDULER_UUID=""

die() {
  echo "kinderentertainers-coolify-github-deploy: $*" >&2
  exit 1
}

original="${SSH_ORIGINAL_COMMAND:-}"
if [[ -z "${original}" ]]; then
  die "missing SSH_ORIGINAL_COMMAND (interactive shell denied)"
fi

# Allow only a tight deploy argv charset (no shell metacharacters).
safe_re='^[a-z0-9_ =,.-]+$'
if [[ ! "${original}" =~ $safe_re ]]; then
  die "refusing unsafe SSH_ORIGINAL_COMMAND"
fi

# shellcheck disable=SC2086
set -- ${original}

if [[ "${1:-}" != "deploy" ]]; then
  die "only the 'deploy' command is allowed"
fi
shift

force="false"
apps="web,worker,scheduler"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --force)
      force="true"
      ;;
    --force=true|--force=1)
      force="true"
      ;;
    --force=false|--force=0)
      force="false"
      ;;
    --apps=*)
      apps="${1#--apps=}"
      ;;
    --apps)
      die "--apps requires --apps=web[,worker][,scheduler]"
      ;;
    *)
      die "unsupported argument: $1"
      ;;
  esac
  shift
done

if [[ ! "${apps}" =~ ^[a-z,]+$ ]]; then
  die "invalid --apps value"
fi

uuids=()
IFS=',' read -r -a app_parts <<< "${apps}"
for app in "${app_parts[@]}"; do
  case "${app}" in
    web|ke-productie)
      [[ -n "${WEB_UUID}" ]] || die "WEB_UUID is not configured; set it in this script after creating ke-productie"
      uuids+=("${WEB_UUID}")
      ;;
    worker|ke-worker)
      [[ -n "${WORKER_UUID}" ]] || die "WORKER_UUID is not configured; set it in this script after creating ke-worker"
      uuids+=("${WORKER_UUID}")
      ;;
    scheduler|ke-scheduler)
      [[ -n "${SCHEDULER_UUID}" ]] || die "SCHEDULER_UUID is not configured; set it in this script after creating ke-scheduler"
      uuids+=("${SCHEDULER_UUID}")
      ;;
    "")
      ;;
    *)
      die "app not allowlisted: ${app}"
      ;;
  esac
done

if [[ ${#uuids[@]} -eq 0 ]]; then
  die "no apps selected"
fi

# Deduplicate while preserving order.
unique_uuids=()
seen="|"
for uuid in "${uuids[@]}"; do
  case "${seen}" in
    *"|${uuid}|"*) continue ;;
  esac
  unique_uuids+=("${uuid}")
  seen="${seen}${uuid}|"
done

uuid_csv="$(IFS=','; echo "${unique_uuids[*]}")"

if [[ ! -f "${TOKEN_FILE}" ]]; then
  die "missing token file ${TOKEN_FILE}"
fi
token="$(tr -d '\r\n' < "${TOKEN_FILE}")"
if [[ -z "${token}" ]]; then
  die "empty token file"
fi

queued_at="$(date -u +%s)"

response="$(
  curl --fail --show-error --silent \
    --request GET \
    --header "Authorization: Bearer ${token}" \
    --get \
    --data-urlencode "uuid=${uuid_csv}" \
    --data-urlencode "force=${force}" \
    "${COOLIFY_API}"
)"

echo "Coolify deploy queued for ${uuid_csv} (force=${force})"
echo "${response}"

# After queuing a web deploy, wait for a healthy replacement started after this
# queue moment, then stop superseded ke-productie containers. Traefik otherwise
# keeps load-balancing hashed Vite assets across old+new images.
if [[ -n "${WEB_UUID}" && " ${unique_uuids[*]} " == *" ${WEB_UUID} "* ]]; then
  prune_script="/usr/local/sbin/prune_stale_ke_productie_containers.sh"
  if [[ ! -x "${prune_script}" ]]; then
    prune_script="$(cd "$(dirname "$0")" && pwd)/prune_stale_ke_productie_containers.sh"
  fi
  if [[ -x "${prune_script}" ]]; then
    # Run in the background so the GitHub Actions SSH step does not hit
    # connection/job timeouts while Coolify builds the replacement image.
    min_started=$((queued_at - 30))
    log_file="/var/log/kinderentertainers-coolify-prune.log"
    echo "Starting background prune waiter (min started epoch ${min_started}); log ${log_file}"
    nohup env \
      WAIT_SECONDS="${KE_COOLIFY_PRUNE_WAIT_SECONDS:-1200}" \
      POLL_SECONDS="${KE_COOLIFY_PRUNE_POLL_SECONDS:-5}" \
      MIN_STARTED_EPOCH="${min_started}" \
      "${prune_script}" >>"${log_file}" 2>&1 &
    echo "Background prune pid $!"
  else
    echo "prune helper missing (${prune_script}); skipping stale container cleanup." >&2
  fi
fi
