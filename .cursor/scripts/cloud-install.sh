#!/usr/bin/env bash
# Idempotent: deps del stack SIAP-EHWEB (PHP 8.3, MariaDB, Composer).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

if command -v php >/dev/null 2>&1 && php -r 'exit(version_compare(PHP_VERSION,"8.3.0","<")?1:0);'; then
  :
else
  sudo DEBIAN_FRONTEND=noninteractive apt-get update -qq
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    php8.3-cli php8.3-mysql php8.3-mbstring php8.3-intl php8.3-xml php8.3-curl php8.3-zip php8.3-gd \
    mariadb-server composer unzip curl
fi

if [[ -f composer.json ]]; then
  composer install --no-interaction
fi

echo "cloud-install: OK ($(php -r 'echo PHP_VERSION;' 2>/dev/null || echo no-php))"