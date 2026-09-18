# Esquema de Base de Datos — 2026-08-18
## RRHH: Corrección versionado de contratos (fix producción)

**Framework:** CodeIgniter 4 (Migraciones)  
**Motor:** MySQL / MariaDB (InnoDB)  
**Cotejamiento:** `utf8mb4_unicode_ci`  
**Uso:** Exportar / recrear entidades en Lucidchart (Lucidapp).

### Leyenda Lucid
- **ALTER** = columnas modificadas sobre tabla existente. No recrear la tabla.
- **YA EXISTE** = contexto; no migrar de nuevo.

---

## Diagrama (sin cambios estructurales nuevos)

```
hr_profiles 1 ──< hr_contracts
hr_contracts 1 ──< hr_contracts (parent_contract_id, self-FK fork chain)
```

---

## ALTER — `hr_contracts` (migración 2026-08-18-000002)

### Datos (backfill en migración, no manual)

| Acción | Descripción |
|--------|-------------|
| Backfill `version` | NULL → 1; luego recalcular 1..N por `profile_id` orden `created_at ASC, id ASC` |
| Backfill `parent_contract_id` | Cadena lineal: versión N apunta a N-1 (legado plano → cadena temporal) |
| Backfill `status` | Un solo `active` por `profile_id` (mayor `created_at`, desempate `id DESC`); resto `archived` |
| Backfill `change_source` | NULL → `'auto'` |

### Columnas (MODIFY)

| Columna | Tipo | Null | Default |
|---------|------|------|---------|
| `version` | INT(11) UNSIGNED | **NO** | 1 |
| `change_source` | ENUM('auto','manual','fork') | **NO** | 'auto' |

### Índices

| Nombre | Columnas | Notas |
|--------|----------|-------|
| `idx_hr_contracts_profile_version` | (`profile_id`, `version`) | CREATE si no existe |
| `fk_hr_contracts_parent` | (`parent_contract_id`) | YA EXISTE vía FK; no duplicar índice |

### Restricción de negocio (código, no UNIQUE parcial MySQL)

- Máximo **1** fila `status='active'` por `profile_id` (enforced en `WorkerContractModel::createVersion` con `FOR UPDATE` + archive).
- No se crea UNIQUE `(profile_id, status)` porque MySQL no soporta UNIQUE parcial nativo sin índice funcional.

---

## Migración

**Archivo:** `app/Database/Migrations/HR/2026-08-18-000002_HrContractVersioningFix.php`

```bash
php spark migrate
```

**Verificación:**

```sql
SHOW COLUMNS FROM hr_contracts LIKE 'version';
SHOW COLUMNS FROM hr_contracts LIKE 'change_source';

SELECT profile_id, SUM(status='active') AS actives
FROM hr_contracts WHERE deleted_at IS NULL
GROUP BY profile_id HAVING actives > 1;
-- Debe devolver 0 filas
```

---

## Relacionado (sin cambios en esta migración)

- `hr_cat_shifts`, `hr_worker_schedules` — YA EXISTEN
- `org_branches` — datos fiscales/logo (módulo Organization)
