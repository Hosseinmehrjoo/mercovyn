#!/usr/bin/env bash
# Validate canonical + hreflang on Mercovyn (live or staging).
# Usage: BASE_URL=https://www.mercovyn.com ./deploy/seo-canonical-test.sh
set -euo pipefail

BASE_URL="${BASE_URL:-https://www.mercovyn.com}"
BASE_URL="${BASE_URL%/}"

PATHS=(
  "/"
  "/products/"
  "/contact/"
  "/ru/"
  "/ru/products/"
  "/ru/contact/"
  "/hy/"
  "/hy/products/"
  "/hy/contact/"
)

fail=0

check_redirect() {
  local from="$1"
  local expect="$2"
  local out code url
  out=$(curl -sS -o /dev/null -w '%{http_code} %{redirect_url}' --max-redirs 0 "$from" 2>/dev/null || true)
  code=$(echo "$out" | awk '{print $1}')
  url=$(echo "$out" | cut -d' ' -f2-)
  if [[ "$code" != "301" && "$code" != "308" ]]; then
    echo "FAIL redirect $from -> expected 301, got $code (url: $url)"
    fail=1
    return
  fi
  if [[ "$url" != "$expect" ]]; then
    echo "FAIL redirect $from -> $url (expected $expect)"
    fail=1
    return
  fi
  echo "OK redirect $from -> $url"
}

norm_path() {
  local u="$1"
  if [[ "$u" == */ ]]; then
    echo "$u"
  else
    echo "${u}/"
  fi
}

echo "=== Redirect checks ==="
check_redirect "http://mercovyn.com/" "https://www.mercovyn.com/"
check_redirect "https://mercovyn.com/products/?foo=1" "https://www.mercovyn.com/products/?foo=1"
check_redirect "http://www.mercovyn.com/hy/contact/" "https://www.mercovyn.com/hy/contact/"

echo ""
echo "=== HTML canonical + hreflang ==="
for p in "${PATHS[@]}"; do
  url="${BASE_URL}${p}"
  html=$(curl -sS -L --max-time 25 "$url" || true)
  if [[ -z "$html" ]]; then
    echo "FAIL $url: empty response"
    fail=1
    continue
  fi
  canon_count=$(grep -oi '<link rel="canonical"' <<<"$html" | wc -l)
  canon=$(grep -oiE '<link rel="canonical" href="[^"]+"' <<<"$html" | head -1 | sed -E 's/.*href="([^"]+)".*/\1/')
  if [[ "$canon_count" -ne 1 ]]; then
    echo "FAIL $url: canonical count=$canon_count"
    fail=1
    continue
  fi
  if [[ "$canon" != https://www.mercovyn.com/* ]]; then
    echo "FAIL $url: canonical=$canon (expected https://www.mercovyn.com/...)"
    fail=1
    continue
  fi
  if [[ "$(norm_path "$canon")" != "$(norm_path "$url")" ]]; then
    echo "FAIL $url: self-canonical mismatch canonical=$canon"
    fail=1
    continue
  fi
  hreflangs=$(grep -oiE 'hreflang="[^"]+"' <<<"$html" | sort -u | wc -l)
  if [[ "$hreflangs" -lt 4 ]]; then
    echo "WARN $url: only $hreflangs hreflang tags"
  fi
  echo "OK $url canonical=$canon"
done

echo ""
if [[ "$fail" -ne 0 ]]; then
  echo "Some checks failed."
  exit 1
fi
echo "All checks passed."