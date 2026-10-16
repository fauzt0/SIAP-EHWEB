#!/usr/bin/env bash
# Compara HEAD con origin de la rama actual. No hace pull ni modifica el árbol.
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
echo "FALTAN EN ESTE CLON:"
missing="$(git log --oneline HEAD.."$remote")"
if [ -z "$missing" ]; then
  echo "(ninguno)"
else
  echo "$missing"
fi

dirty=0
if [ -n "$(git status --porcelain)" ]; then
  dirty=1
fi

if [ "$dirty" -eq 1 ] && [ -n "$missing" ]; then
  echo "RESULTADO: hay cambios locales sin commit y además faltan commits de origin. No hagas pull hasta guardarlos. Luego: git pull --ff-only origin $branch"
  exit 0
fi

if [ "$dirty" -eq 1 ]; then
  echo "RESULTADO: hay cambios locales sin commit. El clon no se compara para pull hasta guardarlos."
  exit 0
fi

if [ -n "$missing" ]; then
  echo "RESULTADO: este clon va detrás de origin. Para traerlos: git pull --ff-only origin $branch"
  exit 0
fi

ahead="$(git log --oneline "$remote"..HEAD)"
if [ -n "$ahead" ]; then
  echo "RESULTADO: este clon tiene commits que origin no tiene. No hagas pull --ff-only hasta resolverlo."
  exit 0
fi

echo "RESULTADO: al día con origin/$branch."
exit 0
