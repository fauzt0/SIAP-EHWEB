# Diccionario de Datos: Módulo de Organización

Este documento detalla la estructura y propósito de las tablas principales que conforman el núcleo organizacional del ERP. Sirve como referencia técnica para entender la jerarquía y permisos del sistema.

## 1. org_company_profile
Almacena los datos legales y comerciales de la empresa matriz o "holding". Solo debe existir un registro activo que represente a la entidad principal.

| Campo | Tipo | Nulable | Descripción |
|-------|------|---------|-------------|
| `id` | `INT(11) UNSIGNED` | NO (PK) | Identificador único |
| `commercial_name` | `VARCHAR(255)` | NO | Nombre comercial principal |
| `legal_name` | `VARCHAR(255)` | SI | Razón Social legal |
| `tax_id` | `VARCHAR(20)` | SI | RFC / Número de Identificación Fiscal |
| `logo_path` | `VARCHAR(255)` | SI | Ruta del logotipo principal |
| `favicon_path` | `VARCHAR(255)` | SI | Ruta del icono (favicon) |
| `primary_email` | `VARCHAR(100)` | NO | Correo electrónico principal de contacto |
| `primary_phone` | `VARCHAR(20)` | NO | Teléfono principal de contacto |
| `website_url` | `VARCHAR(255)` | SI | Sitio web principal |
| `created_at` | `DATETIME` | SI | Fecha de creación |
| `updated_at` | `DATETIME` | SI | Última actualización |
| `deleted_at` | `DATETIME` | SI | Soft delete |

## 2. org_branches
Representa a las sub-empresas, divisiones o sucursales físicas/digitales (Ej: Especialistas Hosting, Especialistas Web). 

| Campo | Tipo | Nulable | Descripción |
|-------|------|---------|-------------|
| `id` | `INT(11) UNSIGNED` | NO (PK) | Identificador único |
| `company_id` | `INT(11) UNSIGNED` | NO (FK) | ID de la matriz (`org_company_profile.id`) |
| `name` | `VARCHAR(100)` | NO | Nombre de la sucursal o unidad de negocio |
| `branch_code` | `VARCHAR(10)` | NO | Código único corto (ej. MAT01) |
| `is_main` | `TINYINT(1)` | NO | 1 si es la sucursal o división principal |
| `address` | `TEXT` | SI | Dirección completa |
| `phone` | `VARCHAR(20)` | SI | Teléfono específico de esta sucursal |
| `email` | `VARCHAR(100)` | SI | Correo específico |
| `pos_printer_name` | `VARCHAR(100)` | SI | Nombre de la impresora de tickets (Punto de Venta) |
| `logo_path` | `VARCHAR(255)` | SI | **[NUEVO]** Logo específico de esta sub-empresa |
| `active` | `TINYINT(1)` | NO | Estado de activación (1=Activo, 0=Inactivo) |
| `created_at` | `DATETIME` | SI | Fecha de creación |
| `updated_at` | `DATETIME` | SI | Última actualización |
| `deleted_at` | `DATETIME` | SI | Soft delete |

## 3. org_branch_users
Tabla pivot que relaciona a los usuarios del sistema (`users`) con las sucursales (`org_branches`) a las que tienen acceso. Esto permite un aislamiento de datos a nivel comercial.

| Campo | Tipo | Nulable | Descripción |
|-------|------|---------|-------------|
|
 `id` | `INT(11) UNSIGNED` | NO (PK) | Identificador único |
| `branch_id` | `INT(11) UNSIGNED` | NO (FK) | ID de la sucursal (`org_branches.id`) |
| `user_id` | `INT(11) UNSIGNED` | NO (FK) | ID del usuario (`users.id`) |
| `is_manager` | `TINYINT(1)` | NO | 1 si es el gerente de la sucursal |
| `assigned_at` | `TIMESTAMP` | NO | Fecha en la que se asignó al usuario a la sucursal |



*(Nota: Esta tabla puede requerir migración adicional dependiendo del motor de Auth, pero conceptualmente gestiona el acceso multitenant-lite).*

## 4. app_settings
Almacena configuraciones globales y variables del sistema en formato clave-valor.

| Campo | Tipo | Nulable | Descripción |
|-------|------|---------|-------------|
| `id` | `INT(11) UNSIGNED` | NO (PK) | Identificador único |
| `key` | `VARCHAR(100)` | NO (UNI) | Clave única (ej. `app_timezone`) |
| `value` | `TEXT` | SI | Valor almacenado (puede ser un JSON stringificado) |
| `group` | `VARCHAR(50)` | NO | Agrupación para interfaz (ej. `general`, `mail`, `appearance`) |
| `description` | `VARCHAR(255)` | SI | Breve nota sobre qué hace esta configuración |

## Relaciones en otros módulos
- **Catálogo (`catalog_products`):** El campo `business_unit_id` enlaza a `org_branches.id` para determinar qué sub-empresa comercializa un producto específico.
- **Inventario (`catalog_product_stock`):** Vincula las existencias físicas a un `org_branches.id` específico.
