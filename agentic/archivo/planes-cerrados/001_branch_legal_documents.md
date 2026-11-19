# Task 001: Implement Legal Document Upload for Branches

## Objective
Replace the disabled placeholder buttons in the Branch edit form (`branch_form.php`) with a working file upload interface for three mandatory legal documents:
1. **CSF** (Constancia de Situación Fiscal)
2. **Proof of Address** (Comprobante de Domicilio)
3. **Constitutive Act** (Acta Constitutiva)

---

## Recommended Model
- **Model:** `Gemini 3.5 Flash` (High) or `GPT-5.4 Mini`
- **Reason:** This is a straightforward task involving form modifications, standard file uploads, and basic database migrations.

---

## Database Changes
Create a migration to add three nullable columns to the `org_branches` table:
- `csf_path` (VARCHAR 255, NULL)
- `address_proof_path` (VARCHAR 255, NULL)
- `incorporation_act_path` (VARCHAR 255, NULL)

---

## Code Requirements

### 1. View / Frontend (`app/Views/Organization/branch_form.php`)
- Replace the disabled placeholder button with three file inputs.
- Ensure the form tag has `enctype="multipart/form-data"`.
- Display a clickable badge/link to download/view the file if it already exists (e.g., `uploads/organization/csf_xxxxx.pdf`), using target="_blank".
- Use FontAwesome icons as per the project standards (§12 of `DOCUMENTACION_TECNICA.md`).

### 2. Controller (`app/Controllers/Organization/OrganizationController.php`)
- Update `saveBranch()` to process the uploaded files.
- Adhere to the upload standards (§18 of `DOCUMENTACION_TECNICA.md`):
  - Check file validity: `$file->isValid() && !$file->hasMoved()`.
  - Use random naming: `$file->getRandomName()`.
  - Save to directory `FCPATH . 'uploads/organization/'`.
  - Store the relative path in the database (e.g., `uploads/organization/filename.pdf`).
  - Delete old files if they are replaced.

### 3. Model (`app/Models/Organization/OrgBranchModel.php`)
- Add the new columns to `$allowedFields`.
- Add validation rules if needed.

---

## Security & Rules — Cumplimiento TOTAL con DOCUMENTACION_TECNICA.md

| § | Estándar | Aplicación en esta tarea |
|---|----------|--------------------------|
| §1 | AJAX backend guard | `saveBranch()` debe llamar `$this->assertAjaxPost()` al inicio |
| §2 | CSRF | El `<form>` en `branch_form.php` ya tiene el input hidden CSRF. El fetch de subida debe inyectar el token en header `<?= csrf_header() ?>` y actualizar el DOM con el nuevo token de la respuesta |
| §3 | Rutas con alias | No crear nuevas rutas sin `'as' => '...'`. Usar `route_to()` en vistas, nunca `site_url()` |
| §4 | Sidebar activo | No aplica (no se añaden nuevas entradas al sidebar) |
| §5/§16 | DataTableTrait | No aplica a este endpoint de upload |
| §6 | DataTables Guard | No aplica |
| §7 | Permisos Shield | La ruta `saveBranch` ya tiene `permission:admin.manage-organization`. Verificar que aplica al procesar archivos |
| §8 | Bitácora | `$this->activityLogModel->logActivity('upload_branch_docs', 'Subió documentos legales para sucursal ID: ' . $savedId)` — registrar cada archivo que se sube o reemplaza con su nombre |
| §9 | Transacciones en modelo | `saveBranch()` en `OrgBranchModel` debe manejar los 3 archivos junto con los datos dentro de la misma operación del modelo, no en el controlador |
| §10 | Select2 | No aplica |
| §11 | Fechas | No aplica a este módulo |
| §12 | FontAwesome | Usar `fas fa-file-pdf`, `fas fa-upload`, `fas fa-check-circle` para los botones de documento |
| §13 | Breadcrumbs | No se crean nuevas vistas de página completa; el form ya existe |
| §14 | Vistas/Layouts | No se crea vista nueva; solo se modifica `branch_form.php` existente |
| §15 | viewData/outputData | El controlador usa `$this->setOutputSuccess()` / `$this->setOutputError()` y devuelve `$this->jsonWithCsrf($this->outputData)` |
| §17 | notifyShow | El JS del form ya usa `notifyShow(res.message, 'success')`. Verificar que el handler del upload lo use también (no `alert()`) |
| §18 | Uploads | `FCPATH . 'uploads/organization/'` · `$file->getRandomName()` · ruta relativa en DB · eliminar archivo anterior si existe |
| §19 | Soft Delete / allowedFields | Añadir `csf_path`, `address_proof_path`, `incorporation_act_path` a `$allowedFields` en `OrgBranchModel` |
| §20 | Catálogos estáticos | No aplica |
| §21 | Sistema de alertas | No aplica a este módulo directamente |
