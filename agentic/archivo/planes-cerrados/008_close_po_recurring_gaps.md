# Task 008 — Cierre de gaps: OC documentos/email + recurrentes

Fecha: 2026-09-18  
Alcance: completar pendientes post-007. **Sin commit. Sin migraciones nuevas. Sin ledgers. Sin parseo CFDI.**

Auditor: contrastar contra `DOCUMENTACION_TECNICA.md` (§1 AJAX, §2 CSRF dual Fetch+jqXHR, §5 DataTables, §7 Shield, §8 logs, §9 transacciones solo en modelos, §15 outputData, §17 notifyShow, §18 WRITEPATH, §19 validación en modelo).

---

## Qué se cerró

| Gap | Implementación |
|-----|----------------|
| SMTP estructura | `Config\Email` lee `email.*` desde `.env`; placeholders comentados en `env` y `.env`. Sin credenciales en código. `protocol=smtp` solo si hay host+user o `email.protocol`. |
| Cron generador | Comando existente `php spark financial:generate-recurring`. **El operador debe programar cron** (no se instaló crontab). |
| Editar / desactivar plantillas | `updateForSupplier` cableado. Rutas `templates.get`, `templates.update`, `templates.toggle`. Modal reutilizado. |
| Clic calendario | FullCalendar `eventClick` abre offcanvas del vencimiento. |
| Cards en montos | `getDashboardStats()` ahora SUM de pendiente/vencido/pagado + “por vencer”. Conteos se conservan como subtítulo. |
| `notify_days_before` | Alerta auto `_detectDueSoonRecurringExpenses` + card “Por vencer”. No duplica vencidos. |
| Subida múltiple | `document_file[]` + `getFileMultiple` en OC inbound/outbound y comprobantes de gasto. WRITEPATH. |
| Docs/email desde perfil proveedor | Tab OC: botones a `suppliers.po.index?open={id}` y `?tab=emails`. Offcanvas se autoabre. |
| Filtros vencimientos | Estatus, proveedor, sucursal, tipo, mes. |
| Cancelar / reprogramar periodo | `cancelOccurrence` / `rescheduleOccurrence` en modelo. No toca plantilla. |
| Restaurar OC | Checkbox papelera + `btn-restore-po` (ruta `suppliers.po.restore` ya existía). |
| Borrar comprobante de gasto | Soft-delete `fin_expense_documents`. |
| Offcanvas gasto | Muestra proveedor, servicio, sucursal. |
| TinyMCE | CDN cdnjs (mismo patrón HR), `license_key: gpl`. |

---

## Rutas nuevas (todas bajo `nat/suppliers`, session + permission)

```
GET  suppliers.recurring.templates.get      permission: catalog.access (grupo)
POST suppliers.recurring.templates.update   catalog.recurring-manage
POST suppliers.recurring.templates.toggle   catalog.recurring-manage
POST suppliers.recurring.cancel             catalog.recurring-manage
POST suppliers.recurring.reschedule         catalog.recurring-manage
POST suppliers.recurring.documents.delete   catalog.recurring-manage
```

Permisos **no** se inventaron; se reutilizan `catalog.po-documents`, `catalog.po-send-email`, `catalog.recurring-manage`.

---

## Archivos tocados (sin migraciones)

- `app/Config/Email.php`, `app/Config/Routes.php`, `env`, `.env` (solo comentarios SMTP)
- `app/Controllers/Financial/RecurringExpenseController.php`
- `app/Controllers/Financial/PurchaseOrderController.php`
- `app/Controllers/Financial/PurchaseOrderDocumentController.php`
- `app/Controllers/System/AlertController.php`
- `app/Models/Financial/FinExpenseModel.php`
- `app/Models/Financial/FinExpenseDocumentModel.php`
- `app/Models/Financial/FinPurchaseOrderModel.php`
- `app/Models/Financial/FinRecurringExpenseModel.php`
- `app/Commands/Financial/GenerateRecurringExpenses.php`
- `app/Services/AppMailer.php` (comentario)
- `app/Views/Financial/Recurring/recurring_index.php`
- `app/Views/Financial/PurchaseOrders/purchase_orders_index.php`
- `app/Views/Financial/Suppliers/suppliers_index.php`

---

## Checklist de auditoría

1. Ningún `transStart` en controladores.
2. Adjuntos en `WRITEPATH/uploads/financial/{po|expenses}/`, no en `FCPATH`.
3. XML inbound sigue siendo archivo histórico; no se parsea CFDI; no se adjunta a correo outbound.
4. `fin_ledger_entries` / `fin_payment_folios` no tocados.
5. CSRF: header en Fetch + `xhr.dt` / `refreshCsrf` dual.
6. DataTables: `column_order` sin `null` inicial.
7. Validación de plantilla/gasto en modelo (`updateForSupplier`, `cancelOccurrence`, `rescheduleOccurrence`, `storeDocument`).
8. `UserActivityLogsModel::logActivity` en mutaciones nuevas.
9. SMTP: no hay password en PHP; operador llena `.env`.
10. Cron **no** quedó instalado en el SO — documentar al operador.

---

## Pendiente de operador (fuera de código)

```
# .env
email.protocol = smtp
email.SMTPHost = ...
email.SMTPUser = ...
email.SMTPPass = ...
email.SMTPPort = 587
email.SMTPCrypto = tls

# crontab diario
0 6 * * * cd /home/admin/domains/sandbox.especialistashosting.com && php spark financial:generate-recurring
```

UI en HTTPS sigue detrás de HTTP Basic Auth (401) — la prueba de navegador no es evidencia de fallo de aplicación.

---

## Verificación ya hecha

- `php -l` en todos los PHP tocados: OK
- `php spark routes` registra las 6 rutas nuevas
- `php spark financial:generate-recurring --help` OK
- No se ejecutó el generador (mutaría `fin_expenses`)
