#!/usr/bin/env bash
# Compara HEAD con origin de la rama actual. Hace fetch; no modifica el árbol de trabajo.
set -u

branch="$(git branch --show-current 2>/dev/null || true)"
if [ -z "$branch" ]; then
  echo "RESULTADO: no hay una rama actual (HEAD detached)."
  exit 1
fi

if ! git fetch origin "$branch"; then
  echo "RESULTADO: git fetch falló. Revisa red y acceso a origin."
  exit 1
fi

remote="origin/$branch"
if ! git rev-parse --verify --quiet "$remote" >/dev/null; then
  echo "RESULTADO: origin no tiene la rama $branch."
  exit 1
fi

echo "RAMA: $branch"
echo "STATUS: $(git status -sb)"
echo "LOCAL:  $(git log -1 --oneline HEAD)"
echo "REMOTO: $(git log -1 --oneline "$remote")"

missing="$(git log --oneline HEAD.."$remote")"
ahead="$(git log --oneline "$remote"..HEAD)"

echo "FALTAN EN ESTE CLON:"
if [ -z "$missing" ]; then
  echo "(ninguno)"
else
  echo "$missing"
fi

echo "SOLO EN ESTE CLON (no en origin):"
if [ -z "$ahead" ]; then
  echo "(ninguno)"
else
  echo "$ahead"
fi

dirty=0
if [ -n "$(git status --porcelain)" ]; then
  dirty=1
fi

if [ "$dirty" -eq 1 ]; then
  echo "RESULTADO: hay cambios locales sin commit. Guarda o descarta antes de sincronizar (commit, stash o restore)."
  exit 0
fi

if [ -n "$missing" ] && [ -n "$ahead" ]; then
  echo "RESULTADO: ramas divergidas (cada lado tiene commits que el otro no). Elige integración: merge, rebase o descartar commits locales; no uses pull --ff-only."
  exit 0
fi

if [ -n "$missing" ]; then
  echo "RESULTADO: detrás de origin; listo para pull --ff-only."
  echo "COMANDO: git pull --ff-only origin $branch"
  exit 0
fi

if [ -n "$ahead" ]; then
  echo "RESULTADO: este clon tiene commits que origin no tiene. Haz push o rebase/reset según convenga; no hagas pull --ff-only."
  exit 0
fi

echo "RESULTADO: al día con origin/$branch."
exit 0
