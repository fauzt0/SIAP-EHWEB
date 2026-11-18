#!/usr/bin/env bash
# Compara HEAD con origin/<rama-indicada>. Hace fetch; no modifica el árbol.
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

TARGET_RAW="${1:-}"
if [ -z "$TARGET_RAW" ]; then
  echo "Uso: check.sh <rama>   ej. cursor/agente-nube-841d o agente-nube-841d"
  exit 1
fi

TARGET="$(normalize_branch "$TARGET_RAW")"
CURRENT="$(git branch --show-current 2>/dev/null || true)"

if [ -z "$CURRENT" ]; then
  echo "RESULTADO: no hay rama actual (HEAD detached)."
  exit 1
fi

if ! git fetch origin "$TARGET"; then
  echo "RESULTADO: git fetch falló. Revisa red y acceso a origin."
  exit 1
fi

REMOTE="origin/$TARGET"
if ! git rev-parse --verify --quiet "$REMOTE" >/dev/null; then
  echo "RESULTADO: origin no tiene la rama $TARGET."
  exit 1
fi

echo "RAMA_ACTUAL: $CURRENT"
echo "RAMA_OBJETIVO: $TARGET"
echo "STATUS: $(git status -sb)"
echo "HEAD_LOCAL:  $(git log -1 --oneline HEAD)"
echo "HEAD_REMOTO: $(git log -1 --oneline "$REMOTE")"

echo "COMMITS_EN_ORIGEN (en $TARGET que no están en HEAD):"
missing="$(git log --oneline HEAD.."$REMOTE")"
if [ -z "$missing" ]; then
  echo "(ninguno)"
else
  echo "$missing"
fi

echo "COMMITS_SOLO_LOCAL (en HEAD que no están en $TARGET):"
ahead="$(git log --oneline "$REMOTE"..HEAD)"
if [ -z "$ahead" ]; then
  echo "(ninguno)"
else
  echo "$ahead"
fi

echo "ARCHIVOS_MIGRACION (diff HEAD...$REMOTE):"
mig="$(git diff --name-only HEAD..."$REMOTE" | grep -E 'Database/Migrations/|plugins/.*/Database/Migrations/' || true)"
if [ -z "$mig" ]; then
  echo "(ninguno o rama al día)"
else
  echo "$mig"
fi

echo "ARCHIVOS_COMPOSER (diff HEAD...$REMOTE):"
comp="$(git diff --name-only HEAD..."$REMOTE" | grep -E '^composer\.(json|lock)$' || true)"
if [ -z "$comp" ]; then
  echo "(sin cambios en composer)"
else
  echo "$comp"
fi

dirty=0
if [ -n "$(git status --porcelain)" ]; then
  dirty=1
fi

if [ "$dirty" -eq 1 ]; then
  echo "RESULTADO: hay cambios locales sin commit. Guarda o descarta antes de integrar."
  exit 0
fi

if [ -z "$missing" ]; then
  if [ -n "$mig" ]; then
    echo "RESULTADO: al día en commits; revisar si hace falta migrate (apply.sh --solo-migrate)."
  else
    echo "RESULTADO: al día con origin/$TARGET en commits."
  fi
  exit 0
fi

if git merge-base --is-ancestor HEAD "$REMOTE" 2>/dev/null; then
  echo "RESULTADO: listo para integrar (merge --ff-only)."
  echo "COMANDO: bash .cursor/skills/integrar-rama-vps/scripts/apply.sh $TARGET"
  exit 0
fi

if [ "$CURRENT" = "$TARGET" ]; then
  echo "RESULTADO: listo para integrar (git pull --ff-only origin $TARGET)."
  echo "COMANDO: bash .cursor/skills/integrar-rama-vps/scripts/apply.sh $TARGET"
  exit 0
fi

echo "RESULTADO: merge --ff-only no es posible (historias divergentes o merge commit necesario)."
echo "COMANDO_SUGERIDO: git fetch origin $TARGET && git merge --no-ff origin/$TARGET   # solo con aprobación explícita"
exit 0