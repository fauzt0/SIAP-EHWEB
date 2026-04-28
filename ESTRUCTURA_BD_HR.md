# Estructura de Base de Datos - Módulo de Recursos Humanos (HR)

Este documento detalla la arquitectura de tablas, campos y relaciones del módulo de Recursos Humanos y Contratos. Todas las tablas incluyen soporte para **Soft Deletes** y **Timestamps** automáticos.

## 1. Tablas Maestras (Core)

### `hr_profiles` (Perfiles de Trabajadores)
Almacena la información personal y de identidad del capital humano.
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT(11) PK | Identificador único autoincrementable. |
| `user_id` | INT(11) FK | Relación opcional con `users.id` (si tiene acceso al sistema). |
| `first_name` | VARCHAR(100) | Nombre(s) manuales (requerido si no hay `user_id`). |
| `last_name` | VARCHAR(100) | Apellidos manuales (requerido si no hay `user_id`). |
| `nationality` | VARCHAR(50) | Nacionalidad del trabajador. |
| `curp` | VARCHAR(18) | Clave Única de Registro de Población (Único). |
| `rfc` | VARCHAR(13) | Registro Federal de Contribuyentes (Único). |
| `tax_regime` | VARCHAR(150) | Régimen fiscal del SAT. |
| `nss` | VARCHAR(11) | Número de Seguridad Social (Único). |
| `birth_date` | DATE | Fecha de nacimiento. |
| `gender` | ENUM(M,F,O) | Género: Masculino, Femenino, Otro. |
| `marital_status` | ENUM | soltero, casado, divorciado, viudo, union_libre. |
| `phone_personal` | VARCHAR(20) | Teléfono personal de contacto. |
| `personal_email` | VARCHAR(255) | Correo electrónico personal. |
| `address_full` | TEXT | Dirección completa de domicilio. |
| `bank_name` | VARCHAR(100) | Nombre de la institución bancaria. |
| `bank_clabe` | VARCHAR(18) | CLABE Interbancaria (18 dígitos). |
| `bank_account` | VARCHAR(50) | Número de cuenta para depósitos. |
| `emergency_contact_name` | VARCHAR(255) | Nombre del contacto de emergencia. |
| `emergency_contact_phone`| VARCHAR(20) | Teléfono del contacto de emergencia. |
| `beneficiaries` | TEXT | Listado o descripción de beneficiarios. |
| `created_at` | DATETIME | Fecha de registro. |
| `updated_at` | DATETIME | Última modificación. |
| `deleted_at` | DATETIME | Fecha de borrado lógico (Soft Delete). |

### `hr_employment_data` (Información Laboral y Nómina)
Contiene la relación laboral activa y configuraciones de pago.
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `profile_id` | INT(11) PK/FK | Relación 1:1 con `hr_profiles.id`. |
| `employee_number` | VARCHAR(20) | Número de empleado único. |
| `corporate_email` | VARCHAR(255) | Email institucional del trabajador. |
| `department_id` | INT(11) FK | Relación con `hr_cat_departments.id`. |
| `job_id` | INT(11) FK | Relación con `hr_cat_jobs.id`. |
| `location_id` | INT(11) FK | Relación con `org_branches.id` (Sucursal). |
| `direct_manager_id` | INT(11) FK | Relación con `hr_profiles.id` (Jefe directo). |
| `current_salary` | DECIMAL(15,2) | Salario base mensual bruto. |
| `daily_salary` | DECIMAL(15,2) | Salario Diario Integrado (SDI). |
| `payroll_type` | ENUM | quincenal, mensual, semanal. |
| `payment_method` | ENUM | transferencia, efectivo, cheque. |
| `alimony_percent` | DECIMAL(5,2) | Porcentaje de pensión alimenticia (si aplica). |
| `alimony_fixed_amount`| DECIMAL(15,2) | Monto fijo de pensión alimenticia. |
| `isr_retention` | DECIMAL(15,2) | Retención estimada de ISR. |
| `imss_fee` | DECIMAL(15,2) | Cuota obrera del IMSS. |
| `infonavit_contribution`| DECIMAL(15,2) | Descuento de crédito Infonavit. |
| `afore_contribution` | DECIMAL(15,2) | Aportación a la Afore. |
| `benefits_infonavit` | TINYINT(1) | Switch: Tiene prestación Infonavit. |
| `benefits_fonacot` | TINYINT(1) | Switch: Tiene prestación Fonacot. |
| `benefits_afore` | TINYINT(1) | Switch: Tiene prestación Afore. |
| `benefits_vacations` | TINYINT(1) | Switch: Tiene derecho a vacaciones LFT. |
| `hiring_date` | DATE | Fecha oficial de contratación. |
| `termination_date` | DATE | Fecha de baja definitiva. |
| `status` | ENUM | active, on_leave, terminated, suspended. |
| `worker_type` | ENUM | planta, temporal, proyecto, honorarios. |
| `notes` | TEXT | Comentarios adicionales. |
| `created_at` | DATETIME | Timestamp de creación. |
| `updated_at` | DATETIME | Timestamp de edición. |
| `deleted_at` | DATETIME | Timestamp de soft-delete. |

---

## 2. Gestión de Contratos y Expediente

### `hr_contracts` (Historial de Contratos)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT(11) PK | Identificador único. |
| `profile_id` | INT(11) FK | Trabajador asociado (Vínculo a `hr_profiles`). |
| `template_id` | INT(11) FK | Plantilla utilizada (`hr_cat_contract_templates`). |
| `contract_type_id` | INT(11) FK | Tipo legal (`hr_cat_contract_types`). |
| `content_snapshot` | LONGTEXT | Contrato final renderizado (Snapshot inmutable). |
| `reason` | VARCHAR(255) | Motivo (Ej: "Alta", "Incremento Salarial"). |
| `file_path` | VARCHAR(255) | Ruta al PDF generado en el servidor. |
| `status` | ENUM | active, archived, draft. |
| `created_at` | DATETIME | Fecha de firma/generación. |
| `updated_at` | DATETIME | Timestamp de actualización. |
| `deleted_at` | DATETIME | Timestamp de soft-delete. |

### `hr_documents` (Expediente Digital)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT(11) PK | Identificador único. |
| `profile_id` | INT(11) FK | Trabajador dueño del archivo (`hr_profiles`). |
| `document_type_id` | INT(11) FK | Categoría (`hr_cat_document_types`). |
| `file_path` | VARCHAR(255) | Ruta física del archivo. |
| `notes` | TEXT | Descripción o vigencia del documento. |
| `created_at` | DATETIME | Fecha de subida. |
| `updated_at` | DATETIME | Última edición (metadatos/archivo). |
| `deleted_at` | DATETIME | Fecha de eliminación (Soft Delete). |

---

## 3. Catálogos de Configuración (`hr_cat_*`)

### `hr_cat_contract_templates`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT(11) PK | Identificador único. |
| `name` | VARCHAR(100) | Nombre de la plantilla (Ej: Contrato Planta). |
| `description` | TEXT | Uso previsto de la plantilla. |
| `content` | LONGTEXT | Cuerpo HTML con variables `{{...}}`. |
| `base_model` | ENUM | lft, modern, classic, corporate. |
| `is_default` | TINYINT(1) | Define si es la plantilla por defecto. |
| `header_logo` | VARCHAR(255) | Logo personalizado para el PDF. |
| `footer_text` | TEXT | Pie de página legal. |
| `created_at` / `updated_at` / `deleted_at` | DATETIME | Auditoría completa. |

### Otros Catálogos (Estructura Estándar)
Tablas: `hr_cat_departments`, `hr_cat_jobs`, `hr_cat_contract_types`, `hr_cat_document_types`.
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT(11) PK | Identificador único. |
| `name` | VARCHAR(100) | Nombre legible de la categoría. |
| `description` | TEXT | Descripción detallada (opcional). |
| `created_at` / `updated_at` / `deleted_at` | DATETIME | Auditoría completa. |

> [!IMPORTANT]
> **Integridad Referencial:** Todas las relaciones están protegidas con llaves foráneas (`FOREIGN KEY`) y acciones en cascada (`ON DELETE CASCADE` o `SET NULL`) según la lógica de negocio definida en las migraciones de evolución de Abril 2026.
