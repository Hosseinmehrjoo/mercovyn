#!/usr/bin/env bash
# Upload site videos to production (177MB+ — use rsync resume).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SITE_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
VIDEO="${1:-${SITE_ROOT}/assets/images/uploads/videos/mercovyn-factory.mp4}"
REMOTE="${MERCovYN_SSH:-root@mercovyn.com}"
REMOTE_ROOT="${MERCovYN_REMOTE_ROOT:-/opt/mercovyn}"
REMOTE_DIR="${REMOTE_ROOT}/assets/images/uploads/videos/"

if [[ ! -f "${VIDEO}" ]]; then
  echo "فایل ویدیو پیدا نشد: ${VIDEO}"
  exit 1
fi

echo "→ ${REMOTE}:${REMOTE_DIR}"
echo "   ($(du -h "${VIDEO}" | cut -f1))"

ssh "${REMOTE}" "mkdir -p '${REMOTE_DIR}' && chown -R root:www-data '${REMOTE_ROOT}/assets/images/uploads' 2>/dev/null || true"

rsync -avP --partial --inplace \
  "${VIDEO}" \
  "${REMOTE}:${REMOTE_DIR}"

BASENAME="$(basename "${VIDEO}")"
ssh "${REMOTE}" "chmod 644 '${REMOTE_DIR}${BASENAME}'" 2>/dev/null || true

echo ""
echo "آدرس در پنل / دیتابیس:"
echo "  assets/images/uploads/videos/${BASENAME}"
echo ""
echo "URL:"
echo "  https://mercovyn.com/assets/images/uploads/videos/${BASENAME}"