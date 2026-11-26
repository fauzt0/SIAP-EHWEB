# Pestaña **Environment** (Cursor Cloud) — SIAP-EHWEB

Configuración versionada en el repo para que un agente nuevo **no necesite subagentes** solo para instalar PHP/MariaDB.

## Qué hace

| Fase | Script | Contenido |
|------|--------|-----------|
| `install` (build / snapshot) | `.cursor/scripts/cloud-install.sh` | PHP 8.3 + extensiones, MariaDB, `composer install` |
| `start` (cada arranque del VM) | `.cursor/scripts/cloud-start.sh` | MariaDB, `.env` demo, `migrate`, `AgentCloudBootstrapSeeder`, `php -S` en **8080** |

Log de arranque: `writable/logs/cloud-start.log`.

## Copiar en la pestaña Environment

Si el entorno aún no lee el repo, pega **exactamente**:

```json
{
  "install": "bash .cursor/scripts/cloud-install.sh",
  "start": "bash .cursor/scripts/cloud-start.sh"
}
```

O enlaza el archivo del repo: ruta **`.cursor/environment.json`** (recomendado cuando Cursor ofrece “Use from repository”).

## Pasos en el dashboard

1. Abre el entorno del proyecto: [Environment en Cursor](https://cursor.com/dashboard/cloud-agents/environments) (el tuyo puede ser el personal ligado a `siap-ehweb`).
2. **Save** con el JSON de arriba (o apunta a `.cursor/environment.json` en la rama `feat/plugin-system-architecture` o `cursor/agente-nube-841d` tras merge).
3. **Trigger build** en esa rama para que el snapshot incluya `vendor/` y paquetes APT.
4. Activa el build exitoso para que nuevos agentes arranquen sin repetir `apt-get`.

## Probar sin build (agente ya en el VM)

```bash
bash .cursor/scripts/cloud-install.sh
bash .cursor/scripts/cloud-start.sh
curl -sI http://127.0.0.1:8080/nat/login | head -5
```

## URLs y login demo

| Recurso | Valor |
|---------|--------|
| Login Shield | `http://127.0.0.1:8080/nat/login` |
| Panel (tras login) | `http://127.0.0.1:8080/nat/` |
| Usuario | `superadmin@especialistashosting.com` / `superadmin123` |
| BD local | `siap_ehweb` @ `127.0.0.1` (usuario `siap`, solo dev cloud) |

Variables opcionales en el entorno (secrets del dashboard, **no** en git): `SIAP_CLOUD_DB_PASS`, `SIAP_CLOUD_HTTP_PORT`.

## Política de agente (cuota)

Leer siempre antes de trabajar:

- `AGENTS.md`
- `.cursor/rules/04-agente-modelos-y-subagentes.mdc`
- `.cursor/rules/05-solo-usuario-modelos-premium.mdc`

Sin pedido explícito del usuario: **no** `Task`/subagentes ni modelos premium.

## Documentación ampliada

Runbook completo: `agentic/REPLICAR_ENTORNO_CLOUD.md`.