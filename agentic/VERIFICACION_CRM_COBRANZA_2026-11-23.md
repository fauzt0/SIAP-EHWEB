# CRM ventas: recibos recurrentes, cobranza y seguimiento

Fecha: 2026-11-23 (America/Mexico_City). Rama: `cursor/agente-nube-841d`.

## Rama `notas-internas-demo`

No tenía commits únicos frente a `agente-nube`. El remoto ya no existía. Se eliminó la rama local y el trabajo continúa aquí.

## Comportamiento de recibos (prefactura)

| Tipo de partida | Recibo | Prefactura | Cobranza |
|-----------------|--------|------------|----------|
| `one_time` (físico, digital, servicio único) | Uno solo, con el periodo contratado | `billing_invoices` en borrador | Se cobra ese recibo |
| `monthly` / `semiannual` / `yearly` | Uno por periodo de renovación | Borrador por cada recibo | Sin recibo del periodo no hay cobro |

Al **crear la orden** se emite el paquete inicial (únicos + primer periodo de cada recurrente) y una **OC del cliente** (`OCC-YYYY-NNNN`) con cada producto como línea independiente (`line_code`).

La **revisión del mes** (`php spark sales:generate-receipts` o botón en Recibos) genera recibos de servicios cuyo `next_due_date` entra en el horizonte (hasta fin del mes siguiente). No duplica el mismo `service_id + period_label`.

Los reportes formales de ingresos van en Contabilidad; el CRM muestra cobranza abierta y **aproximado del siguiente mes**.

## Gestión por producto

Aunque entren en una sola orden, cada partida tiene `line_code`, `product_type` (physical / digital / service), `fulfillment_status` y `plugin_ref`. Se puede generar otra OC del cliente con un subconjunto de partidas y adjuntar documentos a la orden.

Dominios y certificados se tratan como productos digitales del catálogo. Hosting/VPS lo aportará un plugin (`plugin_ref`, `sales.order.item.prepare`, `crm.client.show.tabs`).

## Seguimiento CRM

Cada lead/prospecto/cliente tiene bitácora. Cada contacto puede registrarse con canal (llamada de venta, soporte, WhatsApp, ticket, email), incidencia, producto/servicio y estatus (abierto / en proceso / en espera / cerrado).

Desde la ficha se crean cotizaciones nuevas (`sales.quotes.store`) y se ven productos asociados.

## Alertas (permisos del módulo)

- Leads/prospectos sin movimiento
- Seguimientos abiertos > 5 días
- Recibos pendientes / vencidos
- Servicios por renovar
- Partidas sin activar / sin procesar o enviar

## Hooks

`sales.order.created`, `sales.order.item.prepare` (filtro), `sales.receipt.issued`, `sales.receipt.paid`, `sales.customer_po.created`, `sales.order.documents`, `sales.collections.forecast`, `crm.followup.created`.

## Comandos VPS

```bash
git pull origin cursor/agente-nube-841d
php spark migrate
php tests/smoke_crm_sales.php
php tests/smoke_crm_sales_db.php
php spark sales:generate-receipts
```
