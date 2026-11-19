# Índice de documentación `agentic/`

**Última revisión:** 2026-11-19 (America/Mexico_City)  
**Seguimiento operativo:** [TODO.md](../TODO.md) en la raíz del repo.

---

## Documentación viva (consultar / mantener)

| Archivo | Rol |
|---------|-----|
| [AUDITORIA_ARQUITECTURA.md](AUDITORIA_ARQUITECTURA.md) | Checklist §1–§21, pilares permisos + bitácora, historial de reportes |
| [AUDITORIA_CUMPLIMIENTO_DOC_TECNICA.md](AUDITORIA_CUMPLIMIENTO_DOC_TECNICA.md) | Auditoría de cumplimiento vs `DOCUMENTACION_TECNICA.md` (vigente) |
| [archivo/auditorias/](archivo/auditorias/README.md) | Reportes y evaluaciones purgados (2026-09/10) |
| [REPLICAR_ENTORNO_CLOUD.md](REPLICAR_ENTORNO_CLOUD.md) | Runbook VPS/sandbox: clone, migrate, plugins, skills `sync-rama` / `integrar-rama-vps` |

---

## Planes abiertos (ejecutar cuando toque)

| Archivo | Módulo | Estado |
|---------|--------|--------|
| [002_hr_advanced_incidents.md](002_hr_advanced_incidents.md) | RRHH — incidencias avanzadas, justificaciones, pre-nómina | Pendiente |
| [004_crm_clients.md](004_crm_clients.md) | CRM / clientes | Pendiente (sin tablas `crm_*` en código) |

---

## Plugins (no depurar en esta pasada)

Specs y arquitectura de plugins: `plugin_*.md`, `ARQUITECTURA_PLUGIN_SYSTEM.md`, `AUDITORIA_PLUGIN_SYSTEM.md`, `PLUGIN_000_PROMPT.md`, `avance.md`, `SEGUIMIENTO_NOTAS_INTERNAS.md`. Seguimiento en [TODO.md](../TODO.md) sección plugins.

---

## Archivo histórico

Planes y prompts **ya implementados** o sustituidos por código + `DOCUMENTACION_TECNICA.md` §21:

→ Carpeta [`archivo/planes-cerrados/`](archivo/planes-cerrados/README.md)

No borrar: sirven como contexto de decisiones y commits históricos.

---

## Raíz del repositorio (`.md`)

| Archivo | Utilidad |
|---------|----------|
| [DOCUMENTACION_TECNICA.md](../DOCUMENTACION_TECNICA.md) | **Fuente de verdad** desarrollo |
| [TODO.md](../TODO.md) | Panel de tareas |
| [SCHEMA_ORGANIZACION.md](../SCHEMA_ORGANIZACION.md) | Diccionario org |
| [PLAN_ORGANIZACION_CATALOGO.md](../PLAN_ORGANIZACION_CATALOGO.md) | Hitos marcas/sucursales (casi todo cerrado) |
| [Tables_suppliers.md](../Tables_suppliers.md) | Esquema referencia proveedores/OC |
| [README.md](../README.md) | Notas de entorno + starter CI4 |
| [ESTATUS_ENTREGA.md](../ESTATUS_ENTREGA.md) | Handoff antiguo — ver aviso al inicio del archivo |
| [SISTEMA_ALERTA_NOTIFICACIONES.MD](../SISTEMA_ALERTA_NOTIFICACIONES.MD) | Legacy CI3 — ver aviso; vigente: doc §21 |