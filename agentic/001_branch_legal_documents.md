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

## Security & Rules
- **AJAX and CSRF:** Ensure the fetch/form request renews the CSRF token (§2 of `DOCUMENTACION_TECNICA.md`).
- **Bitácora:** Log the activity inside the controller upon successful upload (§8 of `DOCUMENTACION_TECNICA.md`):
  ```php
  (new UserActivityLogsModel())->logActivity('upload_branch_docs', 'Uploaded legal documents for Branch ID: ' . $id);
  ```
