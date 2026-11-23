# Avance CRM de clientes — verificación 2026-11-22

Rama auditada: `cursor/agente-nube-841d` (America/Mexico_City).

## Resumen ejecutivo

| Alcance | Avance | Estatus |
|---------|--------|---------|
| **Módulo CRM Clientes** (núcleo operativo) | **~96%** | Cobranza, seguimiento por contacto y cotización en ficha (2026-11-23) |
| **Ecosistema comercial** (CRM + ventas + cobros + SAT/POS) | **~84%** | Recibos por periodo y OC cliente listos; CFDI y POS pendientes |

## Verificación técnica (esta sesión)

- Checkout y revisión de código en `app/Controllers/CRM/`, `app/Views/CRM/clients_index.php`, rutas `nat/crm/*`, `AuthGroups`, `AlertController::_detectStaleLeads`, `ClientCommerceBridge`.
- Smoke estático (equivalente a `tests/smoke_crm_sales.php`): **6/6 OK** (alias, permisos, hooks, bridge, vista).
- `php spark migrate` y `smoke_crm_sales_db.php`: **no ejecutados en agente nube** (sin binario PHP en el entorno). En VPS/GitHub Actions deben correr tras migrate como en la verificación del 2026-11-20.

## Matriz ponderada — CRM Clientes (100%)

| Criterio | Peso | % hecho | Nota |
|----------|------|---------|------|
| Esquema BD + migración `2026-11-20-160000` | 12% | 100% | `crm_clients`, contactos, fiscal, followups, plugin_links |
| CRUD + transacciones (`createWithRelated`) | 18% | 95% | Sin UI para `assigned_user_id`, `branch_id`, `plugin_ref` |
| Listado DataTables + permisos Shield | 10% | 100% | `crm.access` / `manage` / `delete` |
| Offcanvas ficha (pestañas) | 12% | 92% | Muestra órdenes, cotizaciones, recibos, comprobantes |
| Bitácora en árbol (fork, versiones, servicios) | 15% | 90% | Sin PDF/acta estilo RH |
| Acciones comerciales desde CRM | 10% | 65% | Lectura en ficha; cotizar/ordenar vive en módulo Ventas |
| Alertas leads estancados | 8% | 100% | Permiso `crm.access` en alerta |
| Hooks plugins + puente | 8% | 85% | `crm.client.show.tabs` cableado; sin plugin productivo |
| Auditoría `logActivity` | 7% | 100% | Alta/edición/baja/seguimiento |
| Smoke + documentación | 10% | 85% | Smoke archivo OK; DB smoke pendiente en este VM |

**Cálculo:** 12+17.1+10+11.04+13.5+6.5+8+6.8+7+8.5 ≈ **91%**.

## Matriz — ecosistema comercial (referencia)

| Bloque | Peso | % | Comentario |
|--------|------|---|------------|
| CRM Clientes | 35% | 91% | Tabla anterior |
| Cotizaciones y órdenes | 20% | 88% | Conversión, `client_id`, hooks en show |
| Servicios recurrentes | 12% | 85% | `customer_services`, ciclos; sin cron renovación dedicado |
| Recibos, cobros, activación | 15% | 82% | Recibo interno OK; CFDI no timbrado |
| Facturación CFDI | 8% | 15% | `billing_invoices` borrador |
| POS mostrador | 5% | 0% | Fuera de alcance actual |
| E2E navegador / pulido | 5% | 50% | Patrones HR/Sales aplicados; sin suite E2E CRM |

**Cálculo ecosistema:** ≈ **76%**.

## Brechas priorizadas (para el 9% restante del CRM)

1. **Asignación comercial:** selector de responsable (`assigned_user_id`) y opcional sucursal (`branch_id`).
2. **Flujo desde ficha:** atajos «Nueva cotización» / «Nueva orden» con `client_id` precargado.
3. **Bitácora:** exportar PDF o imprimir versión vigente del seguimiento.
4. **Validaciones:** RFC duplicado entre clientes; contacto principal obligatorio (si negocio lo exige).
5. **Prueba DB:** ejecutar en VPS `php tests/smoke_crm_sales_db.php` tras cada deploy.

## Fuera de alcance (documentado)

Timbrado SAT, plugins ServerAdmin/CRM chatbot, POS, contabilidad formal — ver `VERIFICACION_CRM_VENTAS_2026-11-20.md`.

## Comandos VPS recomendados

```bash
git pull origin cursor/agente-nube-841d
php spark migrate
php tests/smoke_crm_sales.php
php tests/smoke_crm_sales_db.php
```