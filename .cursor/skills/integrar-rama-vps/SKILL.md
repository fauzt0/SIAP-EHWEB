---
name: integrar-rama-vps
description: >-
  Compara una rama remota indicada (p. ej. cursor/agente-nube-841d) con el clon
  actual, integra con merge o pull ff-only si el árbol está limpio, y ejecuta
  composer install, php spark migrate y plugin:doctor cuando corresponda. Usar en
  el VPS de pruebas/desarrollo para traer trabajo de otro agente Cloud vía GitHub.
disable-model-invocation: true
---

# Integrar rama en VPS (código + migraciones)

Objetivo: en el servidor de pruebas o desarrollo, **traer la rama que indique la persona** (trabajo de un agente en la nube u otro programador), **comparar** contra el checkout actual e **integrar** sin pisar cambios locales no guardados. Después, completar el esquema de BD con migraciones del ERP y de plugins si hace falta.

No sustituye copias de `.env`, dumps ni seeders de datos: solo **Git + composer + migraciones Spark**.

## Rama a indicar

- Forma completa: `cursor/agente-nube-841d`
- Abreviada: `agente-nube-841d` → el script la normaliza a `cursor/agente-nube-841d`

La base de integración suele ser `feat/plugin-system-architecture`; la skill **no cambia de rama por defecto**: hace **merge `--ff-only`** de `origin/<rama-indicada>` sobre la rama actual. Si la persona pide quedar **en** la rama del agente, usar modo `checkout` (ver §3).

## 1. Diagnóstico (obligatorio primero)

Desde la raíz del repositorio:

```bash
bash .cursor/skills/integrar-rama-vps/scripts/check.sh cursor/agente-nube-841d
```

Interpretar `RESULTADO` y las secciones `COMMITS_EN_ORIGEN`, `ARCHIVOS_MIGRACION`, `ARCHIVOS_COMPOSER`.

| RESULTADO | Acción |
|-----------|--------|
| `listo para integrar (merge --ff-only)` | Puedes pasar a §2 con `apply.sh` |
| `al día; no hay commits nuevos en origin/<rama>` | Solo ejecutar migraciones si la BD pudiera estar atrás (`apply.sh --solo-migrate`) |
| `hay cambios locales sin commit` | No integres. Resume `git status -sb`; commit, stash o restore; espera decisión. |
| `merge --ff-only no es posible` | Resume commits divergentes; ofrece merge con commit, rebase, o cherry-pick; no fuerces. |
| `origin no tiene la rama` | Verifica nombre, `git fetch origin`, o que el agente haya hecho push. |
| `git fetch falló` | Red, credenciales GitHub en el VPS. |

## 2. Integrar y migrar

Solo si el árbol está limpio y `check.sh` lo permite:

```bash
bash .cursor/skills/integrar-rama-vps/scripts/apply.sh cursor/agente-nube-841d
```

Opciones:

```bash
# Solo migraciones + doctor (sin merge; BD pendiente)
bash .cursor/skills/integrar-rama-vps/scripts/apply.sh --solo-migrate

# Quedarse en la rama del agente (checkout + pull ff-only)
bash .cursor/skills/integrar-rama-vps/scripts/apply.sh --modo checkout cursor/agente-nube-841d
```

El script, si integró commits nuevos:

1. `git fetch origin <rama>`
2. **modo merge (default):** `git merge --ff-only origin/<rama>` en la rama actual  
3. **modo checkout:** `git checkout <rama>` y `git pull --ff-only origin <rama>`
4. Si cambió `composer.lock` → `composer install --no-interaction`
5. `php spark migrate`
6. `php spark migrate:status` (reportar pendientes)
7. `php spark plugin:doctor`

### Plugins

Si en los commits nuevos hay cambios bajo `plugins/<Plugin>/` o en manifiestos:

- `php spark plugin:action update <slug>` para cada plugin afectado que ya esté instalado  
- Tras instalar plugin nuevo: `php spark plugin:action install <slug>`

No ejecutar seeders ni tocar `.env` salvo petición explícita.

## 3. Qué reportar a la persona

- Rama actual del VPS y rama integrada  
- Commits que entraron (primeras líneas de `git log`)  
- Si hubo migraciones aplicadas o “sin pendientes”  
- Resultado de `plugin:doctor` (errores/advertencias)  
- Si BD vacía falló en `users` al migrar: una vez `php spark migrate -n CodeIgniter\\Shield -all`, luego `php spark migrate` (ver `agentic/REPLICAR_ENTORNO_CLOUD.md` §H)

## 4. Prohibido sin permiso explícito

`git reset --hard`, `git push --force`, `php spark migrate:rollback` en VPS compartido, commitear `.env`, borrar ramas remotas.

## Relación con `sync-rama`

| Skill | Uso |
|-------|-----|
| `sync-rama` | Solo la **rama actual** vs `origin` (pull ff-only). |
| `integrar-rama-vps` | Rama **indicada** (otro agente) → comparar, merge/ff-only o checkout, **migraciones**. |