---
name: sync-rama
description: >-
  Comprueba la rama actual frente a origin (fetch), resume commits faltantes o
  divergencias y ejecuta git pull --ff-only cuando el árbol está limpio. Usar
  cuando el usuario invoque /sync-rama, quiera traer cambios de GitHub (p. ej.
  de agentes cloud) o pregunte si el clon local está al día.
disable-model-invocation: true
---

# Sync rama

Sincroniza el clon con `origin` en la rama actual. No hagas commit ni push salvo que la persona lo pida explícitamente.

## 1. Diagnóstico

Desde la raíz del repositorio:

```bash
bash .cursor/skills/sync-rama/scripts/check.sh
```

Muestra la salida del script (RAMA, STATUS, LOCAL, REMOTO, listas de commits).

## 2. Según `RESULTADO`

| RESULTADO | Acción |
|-----------|--------|
| `detrás de origin; listo para pull --ff-only` | Ejecuta el `COMANDO` que imprimió el script (o `git pull --ff-only origin <rama>`). Confirma con `git status -sb` y resume qué commits entraron. |
| `al día con origin` | Indica que no hace falta pull. |
| `hay cambios locales sin commit` | No hagas pull. Resume qué archivos están sucios (`git status -sb`) y ofrece opciones: commit, `git stash`, o descartar. Espera decisión. |
| `ramas divergidas` | Explica commits solo locales vs solo en origin (ya listados). Ofrece merge (`git pull` sin ff-only), rebase (`git pull --rebase`), o conservar una línea; no elijas sin confirmación. |
| `commits que origin no tiene` | Explica que el remoto va detrás del clon: push, o rebase/reset si esos commits no deben publicarse. |
| `git fetch falló` / `origin no tiene la rama` | Diagnostica remoto, rama y credenciales; no fuerces pull. |

## 3. Después de un pull exitoso

- Si `composer.lock` cambió en los commits traídos, sugiere `composer install` (ejecútalo si la persona lo pide).
- No ejecutes `php spark migrate` salvo petición explícita.

## 4. Prohibido sin permiso explícito

`git reset --hard`, `git push --force`, borrar ramas remotas.
