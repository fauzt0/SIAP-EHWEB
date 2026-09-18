# Plugin 002 — Administrador de Cotizaciones Avanzadas

> **Slug:** `adv-quotations` | **Prefijo DB:** `plg_quot_` | **Versión:** 1.0.0
> **Referencia:** `ARQUITECTURA_PLUGIN_SYSTEM.md`
> **Ruta base:** `/nat/plugins/quotations/`

---

## 1. Alcance y Objetivo

Módulo de generación de cotizaciones profesionales con:
- Builder de cotización multi-ítem vinculado al **catálogo de productos** del ERP
- PDF de cotización con logotipo de empresa/sucursal (usa `PdfLibrary` del ERP)
- Flujo de aprobación: Borrador → Enviada → Aprobada / Rechazada / Vencida
- Versioning: cada revisión crea una nueva versión (árbol de cotizaciones)
- Vinculación a **clientes CRM** (cuando plugin CRM esté instalado) o campo de texto libre
- Conversión de cotización aprobada a **Orden de Compra** (integración con `fin_purchase_orders`)
- Firma digital (si plugin `digital-signature` está activo) o aprobación por email

---

## 2. Base de Datos

### `plg_quot_quotations` — Cotizaciones

```sql
id                INT AI PK
quote_number      VARCHAR(20) UNIQUE   -- "COT-2026-0001"
title             VARCHAR(255)
parent_id         INT NULL FK self.id  -- para versioning (COT-2026-0001 v2)
version           TINYINT DEFAULT 1
status            ENUM('draft','sent','approved','rejected','expired','converted')
client_id         INT NULL             -- FK futura a crm_clients.id
client_name       VARCHAR(255) NULL    -- texto libre si no hay CRM
client_email      VARCHAR(150) NULL
client_rfc        VARCHAR(20) NULL
branch_id         INT NULL FK org_branches.id
currency          CHAR(3) DEFAULT 'MXN'
subtotal          DECIMAL(14,2) DEFAULT 0
discount_amount   DECIMAL(14,2) DEFAULT 0
tax_amount        DECIMAL(14,2) DEFAULT 0
total             DECIMAL(14,2) DEFAULT 0
notes             TEXT NULL            -- notas internas
terms             TEXT NULL            -- términos y condiciones
valid_until       DATE NULL
sent_at           DATETIME NULL
approved_at       DATETIME NULL
rejected_at       DATETIME NULL
rejection_reason  TEXT NULL
converted_to_po   INT NULL FK fin_purchase_orders.id
created_by        INT FK users.id
approved_by       INT NULL FK users.id
created_at, updated_at, deleted_at
```

### `plg_quot_items` — Líneas de cotización

```sql
id                INT AI PK
quotation_id      INT FK plg_quot_quotations.id (CASCADE DELETE)
catalog_product_id INT NULL FK catalog_products.id
description       VARCHAR(500)         -- nombre del producto o descripción libre
quantity          DECIMAL(10,2)
unit              VARCHAR(20) DEFAULT 'pza'
unit_price        DECIMAL(12,2)
discount_percent  DECIMAL(5,2) DEFAULT 0
line_total        DECIMAL(14,2)        -- cantidad * precio * (1 - descuento)
notes             TEXT NULL
sort_order        TINYINT DEFAULT 0
```

### `plg_quot_activities` — Historial de actividades

```sql
id                INT AI PK
quotation_id      INT FK plg_quot_quotations.id
user_id           INT FK users.id
action            VARCHAR(100)         -- 'created','sent','approved','revised','note_added'
notes             VARCHAR(500) NULL
created_at        DATETIME
```

### `plg_quot_email_log` — Correos enviados

```sql
id                INT AI PK
quotation_id      INT FK plg_quot_quotations.id
sent_to           VARCHAR(150)
sent_at           DATETIME
mailer_id         VARCHAR(100) NULL    -- ID del mensaje en AppMailer
status            ENUM('sent','failed','opened') DEFAULT 'sent'
```

---

## 3. Modelos

### `QuotationModel`
- `DataTableTrait`
- `$useSoftDeletes = true`
- `generateQuoteNumber(int $year)` → `COT-{YEAR}-{XXXX}` con padding
- `getWithItems(int $id)` → JOIN con `plg_quot_items` + `catalog_products`
- `getDashboardStats()` → count por status + total cotizado del mes
- `createRevision(int $parentId)` → crea copia con `version + 1`
- **Transacción §9:** `createWithItems(array $data, array $items): int|false`

### `QuotationItemModel`
- `$table = 'plg_quot_items'`
- `recalculate(int $quotationId)` → suma líneas y actualiza `subtotal`, `tax_amount`, `total` en la cotización padre

---

## 4. Controladores y Rutas

| Método | Ruta | Alias | Permiso |
|--------|------|-------|---------|
| `index()` GET | `/nat/plugins/quotations` | `quotes.index` | `quotes.access` |
| `list_ajax()` POST | `list_ajax` | `quotes.list_ajax` | `quotes.access` |
| `show(id)` GET | `show/(:num)` | `quotes.show` | `quotes.access` |
| `create()` GET | `create` | `quotes.create` | `quotes.manage` |
| `store()` POST | `store` | `quotes.store` | `quotes.manage` |
| `update(id)` POST | `update/(:num)` | `quotes.update` | `quotes.manage` |
| `delete(id)` POST | `delete/(:num)` | `quotes.delete` | `quotes.delete` |
| `revise(id)` POST | `revise/(:num)` | `quotes.revise` | `quotes.manage` |
| `send(id)` POST | `send/(:num)` | `quotes.send` | `quotes.manage` |
| `approve(id)` POST | `approve/(:num)` | `quotes.approve` | `quotes.approve` |
| `reject(id)` POST | `reject/(:num)` | `quotes.reject` | `quotes.approve` |
| `convert_po(id)` POST | `convert-po/(:num)` | `quotes.convert_po` | `quotes.approve` |
| `pdf(id)` GET | `pdf/(:num)` | `quotes.pdf` | `quotes.access` |
| `product_search()` POST | `product-search` | `quotes.product_search` | `quotes.manage` |

---

## 5. Vistas

### `quotations_index.php`
- **5 cards:** Borradores, Enviadas, Aprobadas mes, Rechazadas, Monto total aprobado
- DataTable con filtros: cliente, estatus, rango de fechas, sucursal
- Botones de acción condicionales (`plugin_can()`)

### `quotation_builder.php` (crear/editar)
- Formulario superior: cliente (Select2 si hay CRM, texto libre si no), sucursal, válida hasta, notas, términos
- **Tabla dinámica de ítems** (JS puro, sin recargar):
  - Búsqueda de producto del catálogo via `product_search_ajax()`
  - Campos: descripción, cantidad, unidad, precio unitario, descuento %, subtotal calculado
  - Agregar/eliminar filas dinámicamente
  - Cálculo en tiempo real: subtotal, IVA (16%), total
- Vista previa del PDF embebida (iframe hacia `quotes.pdf`)
- Botón "Guardar borrador" y "Guardar y enviar"

### `quotation_show.php`
- Timeline de actividades (historial)
- Botones contextuales según status (Enviar, Aprobar, Rechazar, Revisar, Convertir a OC)
- PDF embebido
- Tab de correos enviados
- Si plugin `digital-signature` activo: botón "Solicitar firma digital"

---

## 6. Integración con ERP Base

- **`catalog_products`** → búsqueda de productos para líneas de cotización
- **`org_branches`** → logotipo y datos de la sucursal en el PDF
- **`fin_purchase_orders`** → conversión de cotización aprobada a OC
- **AppMailer** → envío del PDF de cotización por email
- **CRM Plugin** → vinculación a cliente (cuando esté instalado)
- **Digital Signature Plugin** → flujo de firma si está disponible

---

## 7. Permisos

```json
"permissions": {
  "quotes.access":  "Ver listado y detalle de cotizaciones",
  "quotes.manage":  "Crear y editar cotizaciones",
  "quotes.approve": "Aprobar o rechazar cotizaciones",
  "quotes.delete":  "Eliminar cotizaciones",
  "quotes.send":    "Enviar cotizaciones por email"
}
```

---

## 8. Alertas Automáticas

| Detector | Condición | Tipo |
|----------|-----------|------|
| `_detectExpiredQuotes` | Cotizaciones `sent` con `valid_until < hoy` | `warning` |
| `_detectPendingApproval` | Cotizaciones `sent` hace más de 3 días sin respuesta | `info` |

---

## 9. Cumplimiento §1–§21

| § | Aplicación en este plugin |
|---|--------------------------|
| §1 | `assertAjaxPost()` en store, update, delete, revise, send, approve, reject, convert_po, product_search |
| §2 | CSRF hidden en todas las vistas. `xhr.dt` en DataTable. Fetch con header CSRF + renovación en respuesta. `jsonWithCsrf()` |
| §3 | Alias `quotes.*` en todas las rutas. Solo `route_to()` en vistas |
| §4 | `url_is('nat/plugins/quotations*')` en ítems del sidebar |
| §5/§16 | `DataTableTrait` en `QuotationModel`. `$column_order` y `$column_search` definidos |
| §6 | `isDataTable` guard en `quotations_index.php` |
| §7 | Rutas con `permission:quotes.*`. Botones con `plugin_can()` en vistas |
| §8 | `logActivity` en store (create_quotation), update, delete, send, approve, reject, convert_po |
| §9 | `QuotationModel::createWithItems()` con `transStart/Complete`. Nunca transacciones en el controlador |
| §10 | Select2 con `dropdownParent` en modales del builder |
| §11 | `<input type="date">` para `valid_until`. `d/m/Y` en DataTable. ISO en DB |
| §12 | FontAwesome: `fa-file-invoice`, `fa-check`, `fa-times`, `fa-paper-plane`, `fa-sync` |
| §13 | Breadcrumbs en todas las vistas de página completa |
| §14 | Vistas extienden `$layout` con secciones `title`, `main`, `scripts` |
| §15 | `renderLayout()` para GET. `setOutputSuccess()`/`setOutputError()` + `jsonWithCsrf()` para POST |
| §17 | `notifyShow()` para todo feedback AJAX. Nunca `alert()` |
| §18 | No aplica (sin uploads en este plugin) |
| §19 | `useSoftDeletes = true`, `protectFields = true`, `allowedFields` explícito en `QuotationModel` |
| §20 | Estados de cotización como constante en el modelo: `const STATUSES = [...]` |
| §21 | Detectores usan array con estructura de alerta estándar del ERP. `_safeDetect()` en `_detectAll()` |

---

## Prompt para el agente implementador

```
Actúa como un desarrollador experto en CodeIgniter 4. Lee y ejecuta exactamente el plan:
@agentic/plugin_002_advanced_quotations.md

Lee también:
- @DOCUMENTACION_TECNICA.md (§1–§21 obligatorios)
- @agentic/ARQUITECTURA_PLUGIN_SYSTEM.md (cómo se integra un plugin)
- @agentic/plugin_000_plugin_system_core.md (prerequisito ya instalado)

El Plugin System Core (plugin_000) ya debe estar instalado antes de implementar este plugin.

Tu clase principal extiende BasePlugin de app/Libraries/PluginSystem/BasePlugin.php.
Prefix de tablas: plg_quot_
Namespace: Plugins\AdvancedQuotations\

Cumple TODOS los §1–§21 de la sección de Cumplimiento al final del plan.
Al terminar, añade tu reporte al final de @agentic/AUDITORIA_ARQUITECTURA.md.
```
