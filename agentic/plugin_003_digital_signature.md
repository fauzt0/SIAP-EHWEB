# Plugin 003 — Sistema de Firma Digital de Documentos

> **Slug:** `digital-signature` | **Prefijo DB:** `plg_dsig_` | **Versión:** 1.0.0
> **Referencia:** `ARQUITECTURA_PLUGIN_SYSTEM.md`
> **Ruta base:** `/nat/plugins/signatures/`

---

## 1. Alcance y Objetivo

Permite solicitar y registrar firmas digitales sobre documentos PDF generados en el ERP (contratos HR, cotizaciones, acuerdos con proveedores). Diseñado en dos niveles:

- **Nivel 1 (v1) — Firma con código OTP:** El firmante recibe un email con un enlace único + OTP. Al confirmar, el sistema registra su consentimiento, IP, user-agent y timestamp. El PDF se sella con un texto de "Firmado digitalmente por X el día Y".
- **Nivel 2 (v2) — Firma con certificado .e-firma (SAT México):** Integración con el archivo `.cer` + `.key` + contraseña del SAT para firmar con clave privada real (PKI).

---

## 2. Base de Datos

### `plg_dsig_documents` — Documentos en proceso de firma

```sql
id                INT AI PK
title             VARCHAR(255)
document_type     ENUM('hr_contract','quotation','supplier_agreement','custom')
reference_type    VARCHAR(100) NULL    -- 'hr_contracts','plg_quot_quotations'
reference_id      INT NULL             -- ID del documento fuente
original_pdf_path VARCHAR(500)         -- ruta al PDF generado (WRITEPATH)
signed_pdf_path   VARCHAR(500) NULL    -- ruta al PDF firmado (generado tras todas las firmas)
status            ENUM('draft','pending','partially_signed','completed','rejected','expired')
expires_at        DATETIME NULL
created_by        INT FK users.id
created_at, updated_at, deleted_at
```

### `plg_dsig_signers` — Firmantes requeridos

```sql
id                INT AI PK
document_id       INT FK plg_dsig_documents.id (CASCADE)
signer_name       VARCHAR(150)
signer_email      VARCHAR(150)
signer_role       VARCHAR(100)         -- "Representante Legal", "Cliente", "Testigo"
user_id           INT NULL FK users.id -- si el firmante es usuario del sistema
otp_token         VARCHAR(64) NULL     -- hash del token enviado
otp_expires_at    DATETIME NULL
signed_at         DATETIME NULL
signed_ip         VARCHAR(45) NULL
signed_ua         VARCHAR(500) NULL    -- user agent
signature_method  ENUM('otp_email','efirma_sat','manual') DEFAULT 'otp_email'
status            ENUM('pending','signed','rejected','bounced')
notes             TEXT NULL
```

### `plg_dsig_audit_trail` — Trazabilidad completa

```sql
id                INT AI PK
document_id       INT FK plg_dsig_documents.id
signer_id         INT NULL FK plg_dsig_signers.id
event             VARCHAR(100)        -- 'document_created','email_sent','link_opened','otp_verified','signed','rejected'
ip_address        VARCHAR(45)
user_agent        TEXT NULL
metadata          JSON NULL           -- datos adicionales del evento
created_at        DATETIME
```

---

## 3. Flujo de Firma (v1 — OTP Email)

```
1. Usuario crea solicitud → elige documento (PDF existente) + firmantes
2. Sistema guarda en plg_dsig_documents + plg_dsig_signers
3. Sistema envía email a cada firmante con:
   - Resumen del documento
   - Enlace único: /firma/{token} (ruta pública sin autenticación Shield)
   - OTP de 6 dígitos (expira en 24h)
4. Firmante abre enlace → ve resumen + PDF embebido (iframe)
5. Firmante ingresa OTP → sistema valida
6. Si válido: registra firma en plg_dsig_signers + plg_dsig_audit_trail
7. Cuando TODOS los firmantes han firmado → genera PDF sellado
8. Notifica al creador vía AlertService::dispatch()
```

---

## 4. Módulo de firma pública (sin login Shield)

La ruta de firma es **pública** (fuera del grupo `session`):

```php
// En Config/Routes.php del plugin:
$routes->get('/firma/(:segment)', 'SignatureController::publicSign/$1', ['as' => 'dsig.public.sign']);
$routes->post('/firma/verify', 'SignatureController::publicVerify', ['as' => 'dsig.public.verify']);
```

Esta vista usa un **layout simplificado** (sin sidebar, con el logo de la empresa).

---

## 5. Generación del PDF Sellado

Usa `PdfLibrary` del ERP base:
1. Carga el PDF original
2. Añade una página adicional al final: "Constancia de firma digital" con:
   - Nombre del documento
   - Tabla de firmantes: nombre, email, IP, fecha/hora, método
   - Código hash SHA-256 del PDF original (para verificación de integridad)
   - QR con URL de verificación pública (futuro)

---

## 6. Integración con otros plugins

- **Plugin Cotizaciones (002):** Botón "Solicitar firma" en `quotation_show.php` → crea solicitud de firma del PDF de cotización
- **HR Contratos (ERP base):** Botón "Solicitar firma" en `worker_contract_generate.php` → firma del contrato laboral
- **Plugin Servidores (001):** Firma de acuerdos de SLA o contratos de servicio (futuro)

Hook usado: `PluginHookRegistry::addAction('quotation.show.actions', [self::class, 'addSignButton'])` cuando el plugin está activo.

---

## 7. Permisos

```json
"permissions": {
  "dsig.access":    "Ver listado de documentos en proceso de firma",
  "dsig.manage":    "Crear solicitudes de firma y gestionar firmantes",
  "dsig.delete":    "Cancelar/eliminar solicitudes de firma"
}
```

---

## 8. Alertas Automáticas

| Detector | Condición |
|----------|-----------|
| `_detectPendingSignatures` | Documentos `pending` hace más de 48h |
| `_detectExpiredSignatures` | Documentos con `expires_at < now()` en status pendiente |

---

## 9. Cumplimiento §1–§21

| § | Aplicación en este plugin |
|---|--------------------------|
| §1 | `assertAjaxPost()` en todos los POST internos. Las rutas públicas de firma (`/firma/*`) no tienen guard AJAX |
| §2 | CSRF en vistas internas. Las rutas públicas de firma usan token propio en el formulario OTP |
| §3 | Alias `dsig.*` en todas las rutas. Solo `route_to()` en vistas |
| §7 | Rutas internas con `permission:dsig.*`. Rutas públicas de firma sin Shield (son de firmantes externos) |
| §8 | `logActivity` en create_signature_request, send_to_signers, document_signed, document_rejected |
| §9 | `DocumentModel::createWithSigners()` con transacción en modelo |
| §12 | FontAwesome: `fa-signature`, `fa-file-signature`, `fa-check-double`, `fa-envelope` |
| §13 | Breadcrumbs en vistas internas |
| §14 | Vistas internas extienden `$layout`. Vistas públicas de firma usan layout simplificado propio |
| §15 | `jsonWithCsrf()` en todos los POST internos |
| §17 | `notifyShow()` para feedback. Nunca `alert()` |
| §18 | PDFs firmados en `WRITEPATH/uploads/plugins/digital-signature/` (privados) |
| §19 | `useSoftDeletes = true` en `SignatureDocumentModel` |
| §21 | Detectores de alertas con formato estándar del ERP |

---

## Prompt para el agente implementador

```
Actúa como un desarrollador experto en CodeIgniter 4. Lee y ejecuta exactamente el plan:
@agentic/plugin_003_digital_signature.md

Lee también:
- @DOCUMENTACION_TECNICA.md (§1–§21 obligatorios)
- @agentic/ARQUITECTURA_PLUGIN_SYSTEM.md
- @agentic/plugin_000_plugin_system_core.md (prerequisito ya instalado)

Prefix de tablas: plg_dsig_
Namespace: Plugins\DigitalSignature\

Importante: las rutas públicas /firma/* van FUERA del grupo session de Shield.
Los PDFs se guardan en WRITEPATH, nunca en public_html (son privados).

Al terminar, añade tu reporte al final de @agentic/AUDITORIA_ARQUITECTURA.md.
```
