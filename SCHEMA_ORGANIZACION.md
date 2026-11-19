# Diccionario de datos — Organización

Referencia rápida de tablas del módulo **Organización** (matriz y sucursales). Para convenciones de desarrollo ver [DOCUMENTACION_TECNICA.md](DOCUMENTACION_TECNICA.md).

**Última revisión:** 2026-11-19 (America/Mexico_City)

---

## `org_company_profile`

Perfil legal de la empresa matriz (único registro operativo).

| Columna | Uso |
|---------|-----|
| `id` | PK |
| Datos legales / contacto | Razón social, RFC, dirección, teléfonos, email, sitio web |
| `logo_path` | Ruta relativa del logotipo matriz (`uploads/organization/`) |
| `active` | Estatus |
| `created_at`, `updated_at`, `deleted_at` | Timestamps / soft delete |

---

## `org_branches`

Sucursales o unidades de negocio comercializadoras.

| Columna | Uso |
|---------|-----|
| `id` | PK |
| `company_id` | FK → `org_company_profile.id` |
| `name`, `commercial_name`, `legal_name` | Identificación |
| `tax_id`, `branch_code` | RFC sucursal / código interno |
| `is_main` | Sucursal matriz (preselección en catálogo) |
| `address`, `phone`, `email`, `website_url` | Contacto |
| `pos_printer_name` | Impresora POS (si aplica) |
| `logo_path` | Logotipo sucursal |
| `csf_path`, `address_proof_path`, `incorporation_act_path` | Documentos legales (uploads públicos §18) |
| `active` | Estatus |
| `created_at`, `updated_at`, `deleted_at` | Timestamps / soft delete |

**Modelo:** `App\Models\Organization\OrgBranchModel` (`saveBranch()` centraliza uploads y transacciones).

---

## Relación con catálogo

- `catalog_products.business_unit_id` → `org_branches.id` (quién comercializa).
- `catalog_product_stock.org_branches_id` → existencia física por sucursal.

Ver [PLAN_ORGANIZACION_CATALOGO.md](PLAN_ORGANIZACION_CATALOGO.md) (hitos 4.5–4.6 cerrados; pendiente vista “stock crítico”).