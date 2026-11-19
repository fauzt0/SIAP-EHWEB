# Planes cerrados (archivo)

Especificaciones y prompts de agente **ya implementados** en el código base (commits ~2026-09 a 2026-10).  
No usar como prompt de ejecución salvo auditoría histórica.

**Fuentes vigentes:** `DOCUMENTACION_TECNICA.md`, `TODO.md`, `agentic/INDICE.md`.

| Archivo | Módulo / tema | Cierre aproximado |
|---------|---------------|-------------------|
| `001_branch_legal_documents.md` | Documentos legales sucursales | Task 001 |
| `003_catalog_suppliers*.md` | Proveedores fase inicial (4 partes) | CRUD + UI principal |
| `PLAN_PROVEEDORES_CATALOGO.md` | Duplicado del plan 003 (raíz) | Archivado |
| `004_sistema_alertas_notificaciones.md` | Alertas core | Sustituido por doc §21 + `AlertController` |
| `005_refactor_db_transactions.md` | Transacciones en modelos | Sprint §9 |
| `006`–`008` | OC, email, recurrentes, fixes | Módulo Financial proveedores |
| `009` | Bitácora global admin | `/nat/admin/audit-log` |
| `010` | Categorías proveedores | `SupplierCategoryController` |
| `011` | Nóminas RRHH | Payroll module |
| `2026-08-18_*.md` | Notas de migración one-shot | Migraciones aplicadas |
| `pendientes.md` | Lista informal pre-TODO | Reemplazado por `TODO.md` |