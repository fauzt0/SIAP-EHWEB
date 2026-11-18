#!/usr/bin/env bash
# Integra origin/<rama> o hace checkout+pull; luego composer/migrate/doctor si procede.
set -u

normalize_branch() {
  local b="${1:-}"
  b="${b#refs/heads/}"
  b="${b#origin/}"
  if [[ "$b" != */* ]] && [[ "$b" == *-841d ]]; then
    echo "cursor/$b"
  else
    echo "$b"
  fi
}

MODE="merge"
SOLO_MIGRATE=0
TARGET_RAW=""

while [ $# -gt 0 ]; do
  case "$1" in
    --modo)
      MODE="${2:-merge}"
      shift 2
      ;;
    --solo-migrate)
      SOLO_MIGRATE=1
      shift
      ;;
    -*)
      echo "Opción desconocida: $1"
      exit 1
      ;;
    *)
      TARGET_RAW="$1"
      shift
      ;;
  esac
done

if [ "$SOLO_MIGRATE" -eq 0 ] && [ -z "$TARGET_RAW" ]; then
  echo "Uso: apply.sh [--modo merge|checkout] [--solo-migrate] <rama>"
  exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
  echo "RESULTADO: árbol sucio. Commit, stash o restore antes de apply."
  git status -sb
  exit 1
fi

TARGET=""
if [ -n "$TARGET_RAW" ]; then
  TARGET="$(normalize_branch "$TARGET_RAW")"
fi

BEFORE_HEAD="$(git rev-parse HEAD)"
INTEGRATED=0

if [ "$SOLO_MIGRATE" -eq 0 ]; then
  if ! git fetch origin "$TARGET"; then
    echo "RESULTADO: git fetch falló."
    exit 1
  fi

  REMOTE="origin/$TARGET"
  CURRENT="$(git branch --show-current)"

  if [ "$MODE" = "checkout" ]; then
    if [ "$CURRENT" != "$TARGET" ]; then
      git checkout "$TARGET" || exit 1
    fi
    git pull --ff-only origin "$TARGET" || exit 1
    INTEGRATED=1
  elif [ "$CURRENT" = "$TARGET" ]; then
    git pull --ff-only origin "$TARGET" || exit 1
    INTEGRATED=1
  else
  if git merge-base --is-ancestor HEAD "$REMOTE" 2>/dev/null; then
      git merge --ff-only "$REMOTE" || exit 1
      INTEGRATED=1
    else
      echo "RESULTADO: merge --ff-only no posible. Ejecuta check.sh y pide merge manual."
      exit 1
    fi
  fi
fi

AFTER_HEAD="$(git rev-parse HEAD)"
if [ "$BEFORE_HEAD" != "$AFTER_HEAD" ]; then
  INTEGRATED=1
  echo "INTEGRADO: commits nuevos aplicados."
  git log --oneline "$BEFORE_HEAD..$AFTER_HEAD" | head -20
else
  echo "INTEGRADO: sin commits nuevos (rama ya contenía origin/$TARGET)."
fi

if [ "$INTEGRATED" -eq 1 ] || [ "$SOLO_MIGRATE" -eq 1 ]; then
  if git diff --name-only "$BEFORE_HEAD" "$AFTER_HEAD" 2>/dev/null | grep -q '^composer\.lock$'; then
    echo "Ejecutando: composer install"
    if command -v composer >/dev/null 2>&1; then
      composer install --no-interaction || exit 1
    elif [ -f composer.phar ]; then
      php composer.phar install --no-interaction || exit 1
    else
      echo "AVISO: composer no encontrado; instálalo antes de continuar en producción."
    fi
  fi

  if command -v php >/dev/null 2>&1 && [ -f spark ]; then
    echo "Ejecutando: php spark migrate"
    if ! php spark migrate; then
      echo "AVISO: migrate falló. Si la BD está vacía y falta users, prueba:"
      echo "  php spark migrate -n CodeIgniter\\Shield -all && php spark migrate"
      exit 1
    fi
    echo "--- migrate:status (últimas líneas) ---"
    php spark migrate:status 2>/dev/null | tail -15
    echo "Ejecutando: php spark plugin:doctor"
    php spark plugin:doctor || true
  else
    echo "AVISO: php/spark no disponible en este entorno; migrate y doctor pendientes en el VPS."
  fi
fi

echo "RESULTADO: apply terminado."
exit 0