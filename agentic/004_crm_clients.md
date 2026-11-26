# Task 004: CRM & Clients Module — implementado (2026-11-20)

Módulo núcleo ERP (no es plugin). Compatible con el motor de plugins vía hooks y `ClientCommerceBridge`.

## Tablas

- `crm_clients` — alta de clientes, estatus lead/prospect/active/inactive/churned, `plugin_ref`
- `crm_client_contacts` — buyer / technical / billing / admin
- `crm_billing_profiles` — razón social, RFC, régimen, CFDI
- `crm_followups` — bitácora versionada (árbol `parent_followup_id`, `version`, vigente/archivada)
- `crm_followup_services` — productos/servicios involucrados en cada revisión
- `crm_plugin_links` — enlace futuro plugin (servidores, etc.) ↔ cliente / orden / servicio

## Ventas (enlace)

Migraciones sobre `sales_orders`, `sales_order_items`, `customer_services` + tablas `sales_quotes` / `sales_quote_items`.

Ciclos: `one_time`, `monthly`, `semiannual`, `annual`/`yearly`.

- Venta única → un recibo + prefactura del periodo contratado.
- Recurrente → un recibo por periodo (`SalesReceiptScheduler`, `php spark sales:generate-receipts`).
- Cobranza en ficha CRM (aprox. del mes siguiente); reportes contables fuera del CRM.
- OC del cliente (`sales_customer_pos`) con partidas individuales y documentos de orden.
- Seguimiento por contacto: llamada/soporte/WhatsApp/ticket/email + producto + estatus.

## Permisos Shield

`crm.access`, `crm.manage`, `crm.delete` (+ `sales.access` / `sales.create` / `sales.quotes` / `sales.pos` / `sales.documents`).

Plantillas PDF: `admin.sales-templates` (Organización → Plantillas PDF). Logos: sucursal (`org_branches.logo_path`) o empresa (`org_company_profile.logo_path`). Marcas de catálogo: `catalog_brands`.

## Hooks cableados (sin implementar plugins)

`crm.client.show.tabs`, `crm.followup.created`, `sales.order.show.tabs`, `sales.quote.show.tabs`, `sales.order.created`, `sales.order.item.prepare`, `sales.receipt.issued`, `sales.receipt.paid`, `sales.customer_po.created`, `sales.document.html` — `PluginHookRegistry::wiredHooks()`.

POS (`nat/sales/pos`): venta directa o cotización (estatus borrador/enviada, actualizar, convertir a venta). PDF cotización/orden/recibo con vista previa y descarga.

Puente: `App\Services\ClientCommerceBridge`.
