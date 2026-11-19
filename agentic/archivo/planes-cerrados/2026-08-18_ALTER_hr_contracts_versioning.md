# Esquema de Base de Datos — 2026-08-18
## RRHH: Versionado de contratos y árbol de forks

**Framework:** CodeIgniter 4 (Migraciones)  
**Motor:** MySQL / MariaDB (InnoDB)  
**Cotejamiento:** `utf8mb4_unicode_ci`  
**Uso:** Exportar / recrear entidades en Lucidchart (Lucidapp).

### Reglas generales
- PK `id`: `INT(11) UNSIGNED AUTO_INCREMENT`.
- FK: `INT(11) UNSIGNED` (mismo tipo que la PK referenciada).
- Soft deletes: `created_at`, `updated_at`, `deleted_at` (DATETIME, `deleted_at` NULL).
- Fechas de negocio: `DATE` en ISO `YYYY-MM-DD`.

### Leyenda Lucid
- **ALTER** = columnas nuevas sobre tabla ya existente. No recrear la tabla.
- **YA EXISTE** = contexto / relaciones; no migrar de nuevo.
- **SEED** = datos iniciales insertados por migración si el catálogo está vacío.

---

## Diagrama de relaciones (Lucid / ER)

```
org_company_profile 1 ──< org_branches
org_branches 1 ──< hr_employment_data (location_id)

hr_profiles 1 ──< hr_employment_data
hr_profiles 1 ──< hr_contracts
hr_profiles 1 ──< hr_worker_schedules

hr_cat_contract_templates 1 ──< hr_contracts (template_id, SET NULL)
hr_cat_contract_types 1 ──< hr_contracts (contract_type_id, SET NULL)
hr_contracts 1 ──< hr_contracts (parent_contract_id, self-FK fork tree)

hr_cat_shifts 1 ──< hr_worker_schedules
```

---

## YA EXISTE (no crear, solo relacionar)

### `hr_profiles`
- Datos personales del trabajador; opcional `user_id` → `users.id`.

### `hr_employment_data`
- PK `profile_id` (FK `hr_profiles.id` CASCADE/CASCADE).
- `location_id` INT UNSIGNED NULL → FK `org_branches.id` (sucursal del trabajador).
- Campos salariales: `current_salary`, `daily_salary`, `payroll_type`, `hiring_date`, etc.

### `org_branches`
- `commercial_name`, `legal_name`, `tax_id`, `address`, `logo_path`, `phone`, `email`, `website_url`.
- Editable desde módulo Organization (`/nat/organization/branches`).

### `hr_cat_contract_templates`
- `name`, `content` (LONGTEXT HTML con `{{variables}}`), `base_model`, `is_default`, `header_logo`, `footer_text`.

### `hr_cat_shifts` [YA EXISTE — SEED opcional]
- `name` VARCHAR(100)
- `start_time` TIME
- `end_time` TIME
- `grace_period` INT DEFAULT 0
- `work_days` VARCHAR(50) — días 1-7 separados por coma (1=Lun … 7=Dom)
- Soft deletes: Sí

### `hr_worker_schedules` [YA EXISTE]
- `id` INT UNSIGNED AI PK
- `profile_id` INT UNSIGNED FK → `hr_profiles.id` CASCADE/CASCADE
- `shift_id` INT UNSIGNED FK → `hr_cat_shifts.id` CASCADE/CASCADE
- `start_date` DATE — vigencia del horario asignado
- Soft deletes: Sí

---

## ALTER — `hr_contracts`

Tabla existente ampliada para versionado y árbol de forks.

| Columna | Tipo | Null | Default | Descripción |
|---------|------|------|---------|-------------|
| `parent_contract_id` | INT(11) UNSIGNED | Sí | NULL | FK self → `hr_contracts.id` ON DELETE SET NULL. Contrato del que deriva (fork). |
| `version` | INT(11) UNSIGNED | No | 1 | Número secuencial de versión por trabajador. |
| `change_source` | ENUM('auto','manual','fork') | No | 'auto' | Origen: alta/cambio automático, edición manual, fork explícito. |

### Columnas previas (referencia Lucid)
| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | INT UNSIGNED AI PK | |
| `profile_id` | INT UNSIGNED FK | Trabajador |
| `template_id` | INT UNSIGNED FK NULL | Plantilla usada |
| `contract_type_id` | INT UNSIGNED FK NULL | Tipo de contrato |
| `content_snapshot` | LONGTEXT | HTML renderizado con variables sustituidas |
| `reason` | VARCHAR(255) NULL | Motivo del cambio / comentario |
| `file_path` | VARCHAR(255) NULL | Ruta relativa al PDF persistido |
| `status` | ENUM('active','archived','draft') | Solo uno `active` por trabajador |
| timestamps + `deleted_at` | DATETIME | Soft delete |

### Índices recomendados (Lucid)
- `idx_hr_contracts_profile_version` (`profile_id`, `version` DESC)
- `idx_hr_contracts_parent` (`parent_contract_id`)

### Reglas de negocio
1. Al crear una nueva versión se archivan (`status=archived`) los contratos `active` del mismo `profile_id`.
2. Cambios en datos del trabajador (contacto, nómina, sucursal, horario) generan fork automático (`change_source=fork`).
3. Edición manual desde `/nat/hr/contracts/generate/{profileId}` crea versión con `change_source=manual` o `fork` si deriva de un contrato existente.
4. Logo en PDF: sucursal (`org_branches.logo_path`) → plantilla (`header_logo`) → empresa (`org_company_profile.logo_path`).

---

## SEED — Turnos base (`hr_cat_shifts`)

Insertados solo si el catálogo está vacío al migrar:

| name | start_time | end_time | work_days |
|------|------------|----------|-----------|
| Matutino (L-V) | 08:00 | 17:00 | 1,2,3,4,5 |
| Vespertino (L-V) | 14:00 | 22:00 | 1,2,3,4,5 |
| Mixto (L-S) | 09:00 | 18:00 | 1,2,3,4,5,6 |

---

## Migración

**Archivo:** `app/Database/Migrations/HR/2026-08-18-000001_HrContractVersioning.php`

```bash
php spark migrate
```

---

## Módulos relacionados

| Ruta | Función |
|------|---------|
| `/nat/hr/workers` | CRUD trabajadores + sucursal + horario |
| `/nat/hr/contracts/templates` | Plantillas de contrato |
| `/nat/hr/contracts/history/{profileId}` | Árbol y listado de versiones |
| `/nat/hr/contracts/generate/{profileId}` | Generación / edición manual + PDF |
| `/nat/hr/catalogs` | Catálogo de turnos |
| `/nat/organization/branches` | Datos fiscales y logo por sucursal |
