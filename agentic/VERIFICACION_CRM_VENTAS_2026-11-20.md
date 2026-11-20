# Verificación CRM y Ventas

Fecha: 2026-11-20 (America/Mexico_City). Rama: `cursor/agente-nube-841d`. **No se tocó código de plugins.**

## Hallazgo inicial

No existía `app/Controllers/CRM/`. Había migraciones de ventas (2026-03/04) ligadas a `users.id`, sin UI ni clientes empresariales.

## Entregado

| Capacidad | Estado |
|-----------|--------|
| Alta de clientes, contactos, perfiles fiscales | OK |
| Estatus lead / prospecto / activo / inactivo / baja | OK |
| Offcanvas de ficha | OK |
| Bitácora en árbol (versiones, fork, comentarios, servicios) | OK |
| Cotizaciones y conversión a orden | OK |
| Venta única y recurrente (mensual, semestral, anual) | OK |
| `customer_services` + próximo cobro | OK |
| Permisos Shield + bitácora `logActivity` | OK |
| Migraciones | `2026-11-20-160000/160100/160200` |
| Hooks plugins (solo núcleo) | `crm.client.show.tabs`, `sales.order.show.tabs`, `sales.quote.show.tabs` |
| Puente | `ClientCommerceBridge` + `crm_plugin_links` |

## Pruebas locales

`php tests/smoke_crm_sales.php` y `php tests/smoke_crm_sales_db.php` en verde tras migrate.

## Fuera de alcance (pedido)

Plugins ServerAdmin/CRM chatbot, POS de mostrador, facturación SAT XML.
