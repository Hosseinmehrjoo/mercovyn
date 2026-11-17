#!/usr/bin/env bash
# Run Mercovyn locally (PHP built-in server)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
PORT="${PORT:-8765}"
HOST="${HOST:-127.0.0.1}"

if ! command -v php >/dev/null 2>&1; then
  echo "PHP not found. Install (Debian/Deepin):"
  echo "  sudo apt update && sudo apt install -y php-cli php-mysql php-mbstring php-xml php-curl"
  echo "Or use Docker: docker compose -f deploy/docker-compose.local.yml up --build"
  exit 1
fi

if [[ ! -f "$ROOT/backend/config.php" ]]; then
  if [[ -f "$ROOT/backend/config.sample.php" ]]; then
    cp "$ROOT/backend/config.sample.php" "$ROOT/backend/config.php"
    echo "Created backend/config.php from sample — set DB credentials if needed."
  else
    echo "Missing backend/config.php — run /backend/install.php after starting the server."
  fi
fi

echo "Mercovyn local: http://${HOST}:${PORT}/"
echo "Admin install:  http://${HOST}:${PORT}/backend/install.php (if not installed)"
echo "Press Ctrl+C to stop."
exec php -S "${HOST}:${PORT}" "$ROOT/deploy/router-dev.php"