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

## Permisos Shield

`crm.access`, `crm.manage`, `crm.delete` (+ `sales.access` / `sales.create` / `sales.quotes`).

## Hooks cableados (sin implementar plugins)

`crm.client.show.tabs`, `sales.order.show.tabs`, `sales.quote.show.tabs` — `PluginHookRegistry::wiredHooks()`.

Puente: `App\Services\ClientCommerceBridge`.
