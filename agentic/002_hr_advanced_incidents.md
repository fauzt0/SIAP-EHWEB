# Task 002: Advanced HR Incidents, Justifications, and Pre-Payroll Report

## Objective
Enhance the Human Resources module to handle complex worker incidents, support uploading medical certificates/justifications, and generate a monthly pre-payroll report.

---

## Recommended Model
- **Model:** `Sonnet 4.6` or `GPT-5.5`
- **Reason:** Requires complex relational database design, multiple status states (pending, approved, rejected), business workflows, and file attachments.

---

## Technical Specifications

### 1. Database Migrations
Modify or create tables for incidents:
- **`hr_incidences` updates:**
  - Ensure columns for `incidence_type` (Enum/Varchar: 'delay', 'sick_leave', 'unjustified_absence', 'paid_permit', 'unpaid_permit').
  - Add `justification_path` (VARCHAR 255, NULL) for attached files (PDFs/Images).
  - Add `approved_by` (INT FK to users, NULL), `resolved_at` (DATETIME, NULL), and `notes` (TEXT).
  - Add `status` (Enum: 'pending', 'approved', 'rejected').

### 2. File Upload for Justifications
- Create an upload interface in the incidence modal/form.
- Move files to a secure directory: `WRITEPATH . 'uploads/hr/justifications/'` (since these are sensitive personal data, keep them outside `public_html`).
- Implement a controller route to download these files securely, checking permission `hr.view`.

### 3. Pre-Payroll Monthly Report (`app/Controllers/HR/HrReportController.php`)
- Create a new view/endpoint that lists workers and groups their active monthly incidents:
  - Total delays (retardos)
  - Total sickness absences (faltas por enfermedad)
  - Unjustified absences (faltas injustificadas)
  - Approved permits
- Allow filtering by Month/Year and Branch/Location (`org_branches.id`).
- Export option to CSV/Excel or table-striped print view.

### 4. PDF Resignation/Settlement Generation
- Integrate PDF formatting (using existing PDF library, e.g., Dompdf) in `WorkerSettlementController` to output a clean settlement/resignation layout.

---

## Security & Architectural Standards
- **MVC & Transactions:** If inserting multiple records or updating stocks/vacations, encapsulate transaction blocks in the Model, NEVER in the Controller (§9 of `DOCUMENTACION_TECNICA.md`).
- **AJAX & CSRF:** Endpoints must be AJAX-only and handle CSRF rotation (§1 & §2).
- **Bitácora:** Log each approval/rejection or file upload (§8).
- **Permissions:** Guard endpoints with `permission:hr.manage` or similar.
