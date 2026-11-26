#!/usr/bin/env bash
# Por boot: MariaDB, .env local de demo, migraciones, seed mínimo, HTTP en 127.0.0.1:8080.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"
LOG="${ROOT}/writable/logs/cloud-start.log"
mkdir -p "${ROOT}/writable/logs"
exec >>"$LOG" 2>&1
echo "=== cloud-start $(date -Iseconds) ==="

DB_NAME="${SIAP_CLOUD_DB_NAME:-siap_ehweb}"
DB_USER="${SIAP_CLOUD_DB_USER:-siap}"
DB_PASS="${SIAP_CLOUD_DB_PASS:-siap_local_dev}"
APP_PORT="${SIAP_CLOUD_HTTP_PORT:-8080}"
BASE_URL="http://127.0.0.1:${APP_PORT}/"

sudo service mariadb start 2>/dev/null || true
for _ in $(seq 1 30); do
  if sudo mariadb -e "SELECT 1" &>/dev/null; then break; fi
  sleep 1
done

sudo mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
GRANT ALL ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

if [[ ! -f .env ]]; then
  cp env .env
  if ! grep -q '^CI_ENVIRONMENT' .env; then
    printf '\nCI_ENVIRONMENT = development\n' >> .env
  fi
fi

if ! grep -qE '^encryption\.key\s*=' .env 2>/dev/null; then
  php spark key:generate --force 2>/dev/null || true
fi

# TCP (127.0.0.1) evita error de socket en mysqli CLI.
sed -i \
  -e "s|^#* *database.default.hostname.*|database.default.hostname = 127.0.0.1|" \
  -e "s|^#* *database.default.database.*|database.default.database = ${DB_NAME}|" \
  -e "s|^#* *database.default.username.*|database.default.username = ${DB_USER}|" \
  -e "s|^#* *database.default.password.*|database.default.password = ${DB_PASS}|" \
  -e "s|^#* *database.default.DBDriver.*|database.default.DBDriver = MySQLi|" \
  -e "s|^#* *database.default.port.*|database.default.port = 3306|" \
  -e "s|^#* *app.baseURL.*|app.baseURL = \"${BASE_URL}\"|" \
  .env 2>/dev/null || true

chmod -R ug+rwX writable 2>/dev/null || true

if [[ -f vendor/autoload.php ]]; then
  php spark migrate -n "CodeIgniter\\Shield" -all 2>/dev/null || true
  php spark migrate 2>/dev/null || true
  php spark db:seed 'App\Database\Seeds\AgentCloudBootstrapSeeder' 2>/dev/null || true
fi

SESSION="siap_http"
if ! tmux -f /exec-daemon/tmux.portal.conf has-session -t "=${SESSION}" 2>/dev/null; then
  tmux -f /exec-daemon/tmux.portal.conf new-session -d -s "$SESSION" -c "$ROOT" -- bash -lc \
    "exec php -S 127.0.0.1:${APP_PORT} -t public_html"
fi

echo "cloud-start: MariaDB + HTTP http://127.0.0.1:${APP_PORT}/ (login /nat/login)"
echo "cloud-start: usuarios demo en UserSeeder (superadmin@especialistashosting.com / superadmin123)"