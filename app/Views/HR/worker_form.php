<?php $this->extend($layout); ?>

<?php $this->section('title');
echo $pageTitle;
$this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<style>
.manual-name-fields {
    display: none;
}
</style>
<?php $this->endSection(); ?>

<?php $this->section('pageHeaderScripts'); ?>
<?php $this->endSection(); ?>

<?php
// Extraemos los datos del response para facilitar la lectura de la vista
$user       = $response['user']       ?? null;
$profile    = $response['profile']    ?? null;
$employment = $response['employment'] ?? null;
$isEdit     = $response['isEdit']     ?? false;
$autoEmployeeNumber = $response['autoEmployeeNumber'] ?? '';

$formAction = $isEdit
  ? route_to('hr.worker.update', $profile->id ?? 0)
  : route_to('hr.worker.create');

// Lista de regímenes fiscales comunes del SAT (simplificada)
$taxRegimes = [
    '601' => '601 - General de Ley Personas Morales',
    '603' => '603 - Personas Morales con Fines no Lucrativos',
    '605' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
    '606' => '606 - Arrendamiento',
    '608' => '608 - Demás ingresos',
    '612' => '612 - Personas Físicas con Actividades Empresariales y Profesionales',
    '616' => '616 - Sin obligaciones fiscales',
    '621' => '621 - Incorporación Fiscal',
    '626' => '626 - Régimen Simplificado de Confianza',
];
?>

<?php $this->section('main') ?>
<main class="content">
  <div class="container-fluid p-0">

    <!-- Encabezado + Breadcrumb -->
    <div class="row mb-2 mb-xl-3">
      <div class="col-auto d-none d-sm-block">
        <h1 class="h3 mb-3"><?= esc($pageTitle) ?></h1>
      </div>
      <div class="col-auto ms-auto text-end mt-n1">
        <nav aria-label="breadcrumb">
          <?= isset($breadcrumb) ? $breadcrumb : '' ?>
        </nav>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-user-tie me-2 text-primary"></i>
              <?= $isEdit ? 'Edición de Trabajador: ' . esc(($user->first_name ?? $profile->first_name) . ' ' . ($user->last_name ?? $profile->last_name)) : 'Alta de Nuevo Trabajador' ?>
            </h5>
          </div>
          <div class="card-body">

            <!-- Tabs de secciones del formulario -->
            <ul class="nav nav-tabs nav-justified mb-4" id="workerFormTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-link-personal" data-bs-toggle="tab"
                  data-bs-target="#form-tab-personal" type="button" role="tab">
                  <i class="fas fa-id-card fa-fw me-1"></i> Personales
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-link-laboral" data-bs-toggle="tab"
                  data-bs-target="#form-tab-laboral" type="button" role="tab">
                  <i class="fas fa-briefcase fa-fw me-1"></i> Laborales
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-link-nomina" data-bs-toggle="tab"
                  data-bs-target="#form-tab-nomina" type="button" role="tab">
                  <i class="fas fa-money-check-alt fa-fw me-1"></i> Nómina y Prest.
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-link-bancario" data-bs-toggle="tab"
                  data-bs-target="#form-tab-bancario" type="button" role="tab">
                  <i class="fas fa-university fa-fw me-1"></i> Bancarios
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-link-emergencia" data-bs-toggle="tab"
                  data-bs-target="#form-tab-emergencia" type="button" role="tab">
                  <i class="fas fa-first-aid fa-fw me-1"></i> Emergencias
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-link-documentacion" data-bs-toggle="tab"
                  data-bs-target="#form-tab-documentacion" type="button" role="tab">
                  <i class="fas fa-file-alt fa-fw me-1"></i> Documentación
                </button>
              </li>
            </ul>

            <!-- Área de errores globales del formulario -->
            <div id="form-worker-errors" class="alert alert-danger alert-outline alert-dismissible" role="alert" style="display:none;">
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              <div class="alert-icon"><i class="fas fa-fw fa-bell"></i></div>
              <div class="alert-message">
                <strong id="form-worker-error-title">Error de validación</strong>
                <div id="form-worker-error-body"></div>
              </div>
            </div>

            <form id="formWorker" novalidate>
              <?= csrf_field() ?>

              <!-- ================================================================ -->
              <!-- TAB 1: DATOS PERSONALES -->
              <!-- ================================================================ -->
              <div class="tab-content" id="workerFormTabContent">
                <div class="tab-pane fade show active" id="form-tab-personal" role="tabpanel">

                  <!-- Selector de usuario del sistema -->
                  <div class="row mb-3 align-items-center">
                    <label class="col-sm-3 col-form-label fw-semibold" for="user_id">Vincular Usuario</label>
                    <div class="col-sm-9">
                      <select class="form-select" name="user_id" id="user_id">
                        <option value="">— Ninguno (Registro Independiente) —</option>
                        <?php if ($isEdit && !empty($user)): ?>
                            <option value="<?= $user->id ?>" selected><?= esc($user->first_name . ' ' . $user->last_name) ?> (<?= esc($user->username) ?>)</option>
                        <?php endif; ?>
                        <?php foreach ($response['users'] ?? [] as $u): ?>
                          <option value="<?= $u->id ?>">
                            <?= esc($u->first_name . ' ' . $u->last_name) ?> (<?= esc($u->username) ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <div class="form-text">Si se selecciona un usuario, tomará su nombre y foto de perfil para el ERP. Si se deja en blanco, deberá capturar el nombre manualmente.</div>
                    </div>
                  </div>
                  <hr>

                  <div class="row g-3">
                    <!-- Nombres manuales (Solo visibles si no hay user_id) -->
                    <div class="col-md-6 manual-name-fields">
                      <label class="form-label fw-semibold" for="first_name">Nombre(s) <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="first_name" id="first_name"
                        value="<?= esc($profile->first_name ?? '') ?>" placeholder="Juan">
                    </div>
                    <div class="col-md-6 manual-name-fields">
                      <label class="form-label fw-semibold" for="last_name">Apellidos <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="last_name" id="last_name"
                        value="<?= esc($profile->last_name ?? '') ?>" placeholder="Pérez López">
                    </div>

                    <!-- Nacionalidad -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="nationality">Nacionalidad</label>
                      <input type="text" class="form-control" name="nationality" id="nationality"
                        value="<?= esc($profile->nationality ?? 'Mexicana') ?>">
                    </div>
                    <!-- Fecha de nacimiento -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="birth_date">Fecha de Nacimiento <span class="text-danger">*</span></label>
                      <input type="date" class="form-control" name="birth_date" id="birth_date"
                        value="<?= esc($profile->birth_date ?? '') ?>" required>
                    </div>

                    <!-- Género -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="gender">Género <span class="text-danger">*</span></label>
                      <select class="form-select" name="gender" id="gender" required>
                        <option value="">— Seleccione —</option>
                        <option value="M" <?= ($profile->gender ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= ($profile->gender ?? '') === 'F' ? 'selected' : '' ?>>Femenino</option>
                        <option value="O" <?= ($profile->gender ?? '') === 'O' ? 'selected' : '' ?>>Otro</option>
                      </select>
                    </div>
                    <!-- Estado Civil -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="marital_status">Estado Civil <span class="text-danger">*</span></label>
                      <select class="form-select" name="marital_status" id="marital_status" required>
                        <option value="">— Seleccione —</option>
                        <?php foreach (['soltero' => 'Soltero/a','casado' => 'Casado/a','divorciado' => 'Divorciado/a','viudo' => 'Viudo/a','union_libre' => 'Unión Libre'] as $val => $lbl): ?>
                          <option value="<?= $val ?>" <?= ($profile->marital_status ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <!-- CURP -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="curp">CURP <span class="text-danger">*</span></label>
                      <input type="text" class="form-control text-uppercase" name="curp" id="curp"
                        maxlength="18" placeholder="XXXX000000XXXXXX00"
                        value="<?= esc($profile->curp ?? '') ?>" required>
                    </div>
                    <!-- RFC -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="rfc">RFC <span class="text-danger">*</span></label>
                      <input type="text" class="form-control text-uppercase" name="rfc" id="rfc"
                        maxlength="13" placeholder="XXXX000000XXX"
                        value="<?= esc($profile->rfc ?? '') ?>" required>
                    </div>

                    <!-- Régimen Fiscal -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="tax_regime">Régimen Fiscal SAT</label>
                      <select class="form-select" name="tax_regime" id="tax_regime">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($taxRegimes as $val => $lbl): ?>
                          <option value="<?= $val ?>" <?= ($profile->tax_regime ?? '') == $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <!-- NSS -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="nss">Número de Seguridad Social (NSS)</label>
                      <input type="text" class="form-control" name="nss" id="nss"
                        maxlength="11" placeholder="00000000000"
                        value="<?= esc($profile->nss ?? '') ?>">
                    </div>

                    <!-- Email Personal -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="personal_email">Email Personal</label>
                      <input type="email" class="form-control" name="personal_email" id="personal_email"
                        placeholder="correo@personal.com"
                        value="<?= esc($profile->personal_email ?? '') ?>">
                    </div>
                    <!-- Teléfono personal -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="phone_personal">Teléfono Celular/Local</label>
                      <input type="tel" class="form-control" name="phone_personal" id="phone_personal"
                        placeholder="(000) 000-0000"
                        value="<?= esc($profile->phone_personal ?? '') ?>">
                    </div>

                    <!-- Dirección -->
                    <div class="col-12">
                      <label class="form-label fw-semibold" for="address_full">Domicilio Completo</label>
                      <textarea class="form-control" name="address_full" id="address_full" rows="2"
                        placeholder="Calle, Nº, Colonia, Municipio, Estado, CP"><?= esc($profile->address_full ?? '') ?></textarea>
                    </div>
                  </div>
                </div>

                <!-- ================================================================ -->
                <!-- TAB 2: DATOS LABORALES -->
                <!-- ================================================================ -->
                <div class="tab-pane fade" id="form-tab-laboral" role="tabpanel">
                  <div class="row g-3">
                    <!-- Número de Empleado -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="employee_number">Nº de Empleado <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="employee_number" id="employee_number"
                        maxlength="20" placeholder="Ej. EMP-0001"
                        value="<?= esc($employment->employee_number ?? $autoEmployeeNumber) ?>" required readonly>
                        <div class="form-text">Autogenerado por el sistema.</div>
                    </div>
                    <!-- Fecha de ingreso -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="hiring_date">Fecha de Ingreso <span class="text-danger">*</span></label>
                      <input type="date" class="form-control" name="hiring_date" id="hiring_date"
                        value="<?= esc($employment->hiring_date ?? '') ?>" required>
                    </div>

                    <!-- Tipo de Trabajador -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="worker_type">Tipo de Trabajador</label>
                      <select class="form-select" name="worker_type" id="worker_type">
                        <?php foreach (['planta' => 'De Planta','temporal' => 'Temporal','proyecto' => 'Por Proyecto','honorarios' => 'Honorarios'] as $val => $lbl): ?>
                          <option value="<?= $val ?>" <?= ($employment->worker_type ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <!-- Jefe Directo -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="direct_manager_id">Jefe Directo</label>
                      <select class="form-select" name="direct_manager_id" id="direct_manager_id">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($response['managers'] ?? [] as $m): ?>
                          <option value="<?= $m->id ?>" <?= ($employment->direct_manager_id ?? '') == $m->id ? 'selected' : '' ?>>
                            <?= esc($m->first_name . ' ' . $m->last_name) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <!-- Departamento -->
                    <div class="col-md-4">
                      <label class="form-label fw-semibold" for="department_id">Departamento</label>
                      <select class="form-select" name="department_id" id="department_id">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($response['departments'] ?? [] as $dept): ?>
                          <option value="<?= $dept->id ?>" <?= ($employment->department_id ?? '') == $dept->id ? 'selected' : '' ?>>
                            <?= esc($dept->name) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <!-- Puesto -->
                    <div class="col-md-4">
                      <label class="form-label fw-semibold" for="job_id">Puesto</label>
                      <select class="form-select" name="job_id" id="job_id">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($response['jobs'] ?? [] as $job): ?>
                          <option value="<?= $job->id ?>" <?= ($employment->job_id ?? '') == $job->id ? 'selected' : '' ?>>
                            <?= esc($job->name) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <!-- Sucursal -->
                    <div class="col-md-4">
                      <label class="form-label fw-semibold" for="location_id">Sucursal / Ubicación</label>
                      <select class="form-select" name="location_id" id="location_id">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($response['locations'] ?? [] as $loc): ?>
                          <option value="<?= $loc->id ?>" <?= ($employment->location_id ?? '') == $loc->id ? 'selected' : '' ?>>
                            <?= esc($loc->name) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <!-- Email Corporativo -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="corporate_email">Email Corporativo</label>
                      <input type="email" class="form-control" name="corporate_email" id="corporate_email"
                        placeholder="usuario@empresa.com"
                        value="<?= esc($employment->corporate_email ?? '') ?>">
                    </div>
                    <!-- Estatus (solo en edición) -->
                    <?php if ($isEdit): ?>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="status">Estatus Laboral <span class="text-danger">*</span></label>
                      <select class="form-select" name="status" id="status" required>
                        <?php foreach (['active' => 'Activo','on_leave' => 'En Permiso','terminated' => 'Baja','suspended' => 'Suspendido'] as $val => $lbl): ?>
                          <option value="<?= $val ?>" <?= ($employment->status ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="termination_date">Fecha de Baja (Si aplica)</label>
                      <input type="date" class="form-control" name="termination_date" id="termination_date"
                        value="<?= esc($employment->termination_date ?? '') ?>">
                    </div>
                    <?php endif; ?>

                    <!-- Notas internas -->
                    <div class="col-12">
                      <label class="form-label fw-semibold" for="notes">Notas Internas</label>
                      <textarea class="form-control" name="notes" id="notes" rows="2"
                        placeholder="Observaciones, historial relevante..."><?= esc($employment->notes ?? '') ?></textarea>
                    </div>
                  </div>
                </div>

                <!-- ================================================================ -->
                <!-- TAB 3: NÓMINA Y PRESTACIONES -->
                <!-- ================================================================ -->
                <div class="tab-pane fade" id="form-tab-nomina" role="tabpanel">
                  <div class="row g-3">
                    <h6 class="text-uppercase text-muted fw-bold mb-0">Información Salarial</h6>
                    <hr class="mt-2 mb-3">
                    
                    <!-- Salario Base Mensual -->
                    <div class="col-md-4">
                      <label class="form-label fw-semibold" for="current_salary">Salario Base Mensual <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="current_salary" id="current_salary"
                          min="0" step="0.01" placeholder="0.00"
                          value="<?= esc($employment->current_salary ?? '') ?>" required>
                      </div>
                    </div>
                    <!-- Salario Diario -->
                    <div class="col-md-4">
                      <label class="form-label fw-semibold" for="daily_salary">Salario Diario (SDI) <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="daily_salary" id="daily_salary"
                          min="0" step="0.01" placeholder="0.00"
                          value="<?= esc($employment->daily_salary ?? '') ?>" required>
                      </div>
                    </div>
                    <!-- Tipo de Nómina -->
                    <div class="col-md-4">
                      <label class="form-label fw-semibold" for="payroll_type">Tipo de Nómina</label>
                      <select class="form-select" name="payroll_type" id="payroll_type">
                        <?php foreach (['quincenal' => 'Quincenal','mensual' => 'Mensual','semanal' => 'Semanal'] as $val => $lbl): ?>
                          <option value="<?= $val ?>" <?= ($employment->payroll_type ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <!-- Forma de Pago -->
                    <div class="col-md-4">
                      <label class="form-label fw-semibold" for="payment_method">Forma de Pago</label>
                      <select class="form-select" name="payment_method" id="payment_method">
                        <?php foreach (['transferencia' => 'Transferencia','efectivo' => 'Efectivo','cheque' => 'Cheque'] as $val => $lbl): ?>
                          <option value="<?= $val ?>" <?= ($employment->payment_method ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-0 mt-4">Retenciones Fijas por Nómina</h6>
                    <hr class="mt-2 mb-3">

                    <div class="col-md-3">
                      <label class="form-label fw-semibold" for="isr_retention">Retención ISR</label>
                      <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="isr_retention" id="isr_retention"
                          min="0" step="0.01" value="<?= esc($employment->isr_retention ?? '0.00') ?>">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-semibold" for="imss_fee">Cuota IMSS</label>
                      <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="imss_fee" id="imss_fee"
                          min="0" step="0.01" value="<?= esc($employment->imss_fee ?? '0.00') ?>">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-semibold" for="infonavit_contribution">Aportación Infonavit</label>
                      <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="infonavit_contribution" id="infonavit_contribution"
                          min="0" step="0.01" value="<?= esc($employment->infonavit_contribution ?? '0.00') ?>">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label fw-semibold" for="afore_contribution">Aportación Afore</label>
                      <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="afore_contribution" id="afore_contribution"
                          min="0" step="0.01" value="<?= esc($employment->afore_contribution ?? '0.00') ?>">
                      </div>
                    </div>
                    
                    <!-- Pensión Alimenticia -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="alimony_percent">Pensión Alimenticia (%)</label>
                      <div class="input-group">
                        <input type="number" class="form-control" name="alimony_percent" id="alimony_percent"
                          min="0" max="100" step="0.01" value="<?= esc($employment->alimony_percent ?? '0.00') ?>">
                        <span class="input-group-text">%</span>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="alimony_fixed_amount">Pensión Alimenticia (Fijo)</label>
                      <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="alimony_fixed_amount" id="alimony_fixed_amount"
                          min="0" step="0.01" value="<?= esc($employment->alimony_fixed_amount ?? '0.00') ?>">
                      </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-0 mt-4">Prestaciones Activas</h6>
                    <hr class="mt-2 mb-3">
                    
                    <div class="col-12 d-flex gap-4 flex-wrap">
                        <div class="form-check form-switch form-switch-md">
                          <input class="form-check-input" type="checkbox" role="switch" id="benefits_infonavit" name="benefits_infonavit" <?= !empty($employment->benefits_infonavit) ? 'checked' : '' ?>>
                          <label class="form-check-label fw-semibold" for="benefits_infonavit">Infonavit</label>
                        </div>
                        <div class="form-check form-switch form-switch-md">
                          <input class="form-check-input" type="checkbox" role="switch" id="benefits_fonacot" name="benefits_fonacot" <?= !empty($employment->benefits_fonacot) ? 'checked' : '' ?>>
                          <label class="form-check-label fw-semibold" for="benefits_fonacot">Fonacot</label>
                        </div>
                        <div class="form-check form-switch form-switch-md">
                          <input class="form-check-input" type="checkbox" role="switch" id="benefits_afore" name="benefits_afore" <?= !empty($employment->benefits_afore) ? 'checked' : '' ?>>
                          <label class="form-check-label fw-semibold" for="benefits_afore">Afore</label>
                        </div>
                        <div class="form-check form-switch form-switch-md">
                          <input class="form-check-input" type="checkbox" role="switch" id="benefits_vacations" name="benefits_vacations" <?= !isset($employment->benefits_vacations) || !empty($employment->benefits_vacations) ? 'checked' : '' ?>>
                          <label class="form-check-label fw-semibold" for="benefits_vacations">Vacaciones (LFT)</label>
                        </div>
                    </div>
                  </div>
                </div>

                <!-- ================================================================ -->
                <!-- TAB 4: DATOS BANCARIOS -->
                <!-- ================================================================ -->
                <div class="tab-pane fade" id="form-tab-bancario" role="tabpanel">
                  <div class="row g-3">
                    <div class="col-12">
                      <div class="alert alert-info alert-outline">
                        <div class="alert-icon"><i class="fas fa-info-circle"></i></div>
                        <div class="alert-message">
                          Esta información se usa para el depósito de nómina. Manéjela con confidencialidad.
                        </div>
                      </div>
                    </div>
                    <!-- Banco -->
                    <div class="col-md-12">
                      <label class="form-label fw-semibold" for="bank_name">Institución Bancaria</label>
                      <input type="text" class="form-control" name="bank_name" id="bank_name"
                        placeholder="Ej. BBVA, Santander, Banamex..."
                        value="<?= esc($profile->bank_name ?? '') ?>">
                    </div>
                    <!-- CLABE -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="bank_clabe">CLABE Interbancaria</label>
                      <input type="text" class="form-control font-monospace" name="bank_clabe" id="bank_clabe"
                        maxlength="18" placeholder="000000000000000000"
                        value="<?= esc($profile->bank_clabe ?? '') ?>">
                      <div class="form-text">18 dígitos numéricos.</div>
                    </div>
                    <!-- Cuenta o Tarjeta -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="bank_account">Número de Cuenta o Tarjeta de Débito</label>
                      <input type="text" class="form-control font-monospace" name="bank_account" id="bank_account"
                        maxlength="20" placeholder="1234567890"
                        value="<?= esc($profile->bank_account ?? '') ?>">
                    </div>
                  </div>
                </div>

                <!-- ================================================================ -->
                <!-- TAB 5: CONTACTO DE EMERGENCIA -->
                <!-- ================================================================ -->
                <div class="tab-pane fade" id="form-tab-emergencia" role="tabpanel">
                  <div class="row g-3">
                    <!-- Nombre del contacto -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="emergency_contact_name">Nombre del Contacto Principal</label>
                      <input type="text" class="form-control" name="emergency_contact_name" id="emergency_contact_name"
                        placeholder="Nombre completo"
                        value="<?= esc($profile->emergency_contact_name ?? '') ?>">
                    </div>
                    <!-- Teléfono del contacto -->
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="emergency_contact_phone">Teléfono de Emergencia</label>
                      <input type="tel" class="form-control" name="emergency_contact_phone" id="emergency_contact_phone"
                        placeholder="(000) 000-0000"
                        value="<?= esc($profile->emergency_contact_phone ?? '') ?>">
                    </div>
                    <!-- Beneficiarios -->
                    <div class="col-12">
                      <label class="form-label fw-semibold" for="beneficiaries">Beneficiarios Legales</label>
                      <textarea class="form-control" name="beneficiaries" id="beneficiaries" rows="3"
                        placeholder="Nombre completo - Parentesco - Porcentaje (%)"><?= esc($profile->beneficiaries ?? '') ?></textarea>
                      <div class="form-text">Especifique quiénes son los beneficiarios de este trabajador.</div>
                    </div>
                  </div>
                </div>

                <!-- ================================================================ -->
                <!-- TAB 6: DOCUMENTACIÓN (EXPEDIENTE DIGITAL) -->
                <!-- ================================================================ -->
                <div class="tab-pane fade" id="form-tab-documentacion" role="tabpanel">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-folder-open me-2 text-primary"></i>Archivos del Expediente</h6>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="searchDocuments" class="form-control border-start-0" placeholder="Buscar documentos...">
                        </div>
                        <div class="form-check form-switch pt-1 ms-2">
                            <input class="form-check-input" type="checkbox" id="showDeletedDocs">
                            <label class="form-check-label small fw-semibold" for="showDeletedDocs">Ver eliminados</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="btnAddDocument">
                          <i class="fas fa-plus me-1"></i> Agregar Documento
                        </button>
                    </div>
                  </div>
                  
                  <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle" id="tableDocuments">
                      <thead class="bg-light text-center">
                        <tr>
                          <th style="width: 200px;">Tipo de Documento</th>
                          <th>Archivo</th>
                          <th>Notas / Descripción</th>
                          <th style="width: 100px;">Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (!empty($response['documents'])): ?>
                          <?php foreach ($response['documents'] as $doc): 
                                $isDeleted = !empty($doc->deleted_at);
                          ?>
                            <tr class="document-row <?= $isDeleted ? 'table-light text-muted opacity-75 d-none' : '' ?>" 
                                data-id="<?= $doc->id ?>" 
                                data-deleted="<?= $isDeleted ? '1' : '0' ?>"
                                data-type="<?= esc(strtolower($doc->type_name)) ?>"
                                data-notes="<?= esc(strtolower($doc->notes)) ?>"
                                data-filename="<?= esc(strtolower(basename($doc->file_path))) ?>">
                              <td>
                                <span class="badge <?= $isDeleted ? 'bg-secondary' : 'bg-light text-dark border' ?>"><?= esc($doc->type_name) ?></span>
                                <?php if ($isDeleted): ?>
                                    <span class="badge bg-danger ms-1">Eliminado</span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <div class="d-flex align-items-center">
                                  <i class="fas <?= $isDeleted ? 'fa-file-archive text-muted' : 'fa-file-alt text-primary' ?> me-2"></i>
                                  <span class="small text-truncate" style="max-width: 150px;"><?= basename($doc->file_path) ?></span>
                                  <a href="<?= base_url('nat/hr/documents/download/' . $doc->id) ?>?action=view" target="_blank" class="btn btn-link btn-sm ms-auto py-0 <?= $isDeleted ? 'disabled text-muted' : '' ?>">
                                    <i class="fas fa-eye"></i> Ver
                                  </a>
                                </div>
                              </td>
                              <td>
                                <span class="small <?= $isDeleted ? 'fst-italic' : 'text-muted' ?>"><?= esc($doc->notes ?: '(Sin notas)') ?></span>
                              </td>
                              <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($isDeleted): ?>
                                        <button type="button" class="btn btn-outline-success btnRestoreDoc" data-id="<?= $doc->id ?>" title="Restaurar">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-warning btnEditDoc" 
                                            data-id="<?= $doc->id ?>" 
                                            data-type-id="<?= $doc->document_type_id ?>"
                                            data-notes="<?= esc($doc->notes) ?>"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btnDeleteDoc" data-id="<?= $doc->id ?>" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                        <tr class="empty-row" style="<?= !empty($response['documents']) ? 'display:none;' : '' ?>">
                          <td colspan="4" class="text-center text-muted py-4">
                            No se han agregado nuevos documentos.
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div class="form-text mt-2">
                    <i class="fas fa-info-circle me-1"></i> Formatos permitidos: PDF, JPG, PNG. Tamaño máximo: 5MB por archivo.
                  </div>
                </div>

              </div><!-- /tab-content -->

              <!-- Botones de acción del formulario -->
              <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <a href="<?= route_to('hr.workers') ?>" class="btn btn-outline-secondary">
                  <i class="fas fa-arrow-left me-1"></i> Cancelar y Regresar
                </a>
                <button type="button" class="btn btn-primary" id="btnSaveWorker">
                  <i class="fas fa-save me-1"></i>
                  <?= $isEdit ? 'Guardar Cambios' : 'Registrar Trabajador' ?>
                </button>
              </div>

            </form><!-- /formWorker -->

          </div><!-- /card-body -->
        </div><!-- /card -->
      </div>
    </div>

  </div>
</main>
<!-- MODAL: EDITAR DOCUMENTO EXISTENTE -->
<div class="modal fade" id="modalEditDoc" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold">Editar Documento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditDoc">
            <input type="hidden" id="edit_doc_id">
            <div class="mb-3">
                <label class="form-label fw-semibold">Tipo de Documento</label>
                <select id="edit_doc_type" class="form-select" required>
                    <?php if (!empty($response['document_types'])): ?>
                        <?php foreach ($response['document_types'] as $type): ?>
                            <option value="<?= $type->id ?>"><?= esc($type->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Notas / Descripción</label>
                <textarea id="edit_doc_notes" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold">Remplazar Archivo (Opcional)</label>
                <input type="file" id="edit_doc_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-text small text-warning mt-2">
                    <i class="fas fa-exclamation-triangle me-1"></i> Al subir un nuevo archivo, el anterior será eliminado permanentemente.
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnUpdateDoc">Actualizar Documento</button>
      </div>
    </div>
  </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('pageFooterScripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // PERSISTENCIA DE PESTAÑAS (Stay in same tab after reload)
  const lastTab = localStorage.getItem('activeWorkerTab');
  if (lastTab) {
      const triggerEl = document.querySelector(`button[data-bs-target="${lastTab}"]`);
      if (triggerEl) {
          // Usar un pequeño delay para asegurar que Bootstrap esté listo
          setTimeout(() => {
              const tab = bootstrap.Tab.getOrCreateInstance(triggerEl);
              tab.show();
          }, 100);
      }
  }
  // Guardar tab activo al cambiar
  document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(btn => {
      btn.addEventListener('shown.bs.tab', (e) => {
          localStorage.setItem('activeWorkerTab', e.target.getAttribute('data-bs-target'));
      });
  });

  const form        = document.getElementById('formWorker');
  const btnSave     = document.getElementById('btnSaveWorker');
  const errBox      = document.getElementById('form-worker-errors');
  const errTitle    = document.getElementById('form-worker-error-title');
  const errBody     = document.getElementById('form-worker-error-body');
  const isEdit      = <?= $isEdit ? 'true' : 'false' ?>;
  const formAction  = '<?= $formAction ?>';
  const userIdSelect = document.getElementById('user_id');
  const btnAddDoc     = document.getElementById('btnAddDocument');
  const tableDocsBody = document.querySelector('#tableDocuments tbody');
  
  // Catálogo de tipos de documentos para JS
  const documentTypes = <?= json_encode($response['document_types'] ?? []) ?>;
  // Inicializar Select2 para selectores principales
  if ($('#user_id').length) {
    $('#user_id').select2({
      theme: 'bootstrap-5',
      placeholder: '— Registro Independiente (Sin Usuario) —',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#user_id').parent()
    });
  }

  if ($('#direct_manager_id').length) {
    $('#direct_manager_id').select2({
      theme: 'bootstrap-5',
      placeholder: '— Sin asignar —',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#direct_manager_id').parent()
    });
  }

  // --- LÓGICA DE DOCUMENTACIÓN DINÁMICA ---
  if (btnAddDoc) {
    btnAddDoc.addEventListener('click', function() {
      // Quitar fila de "vacío" si existe
      const emptyRow = tableDocsBody.querySelector('.empty-row');
      if (emptyRow) emptyRow.remove();

      const rowId = Date.now();
      const tr = document.createElement('tr');
      tr.id = `doc-row-${rowId}`;
      
      // Construir opciones del select desde el catálogo
      let typeOptions = '<option value="">— Seleccione Tipo —</option>';
      documentTypes.forEach(type => {
        typeOptions += `<option value="${type.id}">${type.name}</option>`;
      });

      tr.innerHTML = `
        <td>
          <select name="document_type_ids[]" class="form-select form-select-sm" required>
            ${typeOptions}
          </select>
        </td>
        <td>
          <input type="file" name="document_files[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
        </td>
        <td>
          <input type="text" name="document_notes[]" class="form-control form-control-sm" placeholder="Ej. Vigencia 2025">
        </td>
        <td class="text-center">
          <div class="btn-group btn-group-sm">
            ${isEdit ? `
              <button type="button" class="btn btn-outline-success btn-save-new-doc" title="Subir ahora">
                <i class="fas fa-upload"></i>
              </button>
            ` : ''}
            <button type="button" class="btn btn-outline-danger btn-remove-doc" title="Quitar">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </td>
      `;

      tableDocsBody.appendChild(tr);

      // Evento para eliminar la fila
      tr.querySelector('.btn-remove-doc').addEventListener('click', function() {
        tr.remove();
        if (tableDocsBody.querySelectorAll('tr').length === 0) {
          tableDocsBody.innerHTML = `
            <tr class="empty-row">
              <td colspan="4" class="text-center text-muted py-4">
                No se han agregado nuevos documentos.
              </td>
            </tr>
          `;
        }
      });
    });
  }

  // --- LÓGICA DE BÚSQUEDA Y FILTRO DE DOCUMENTOS ---
  const searchInput = document.getElementById('searchDocuments');
  const showDeletedSwitch = document.getElementById('showDeletedDocs');
  function filterDocuments() {
    const term = searchInput.value.toLowerCase();
    const showDeleted = showDeletedSwitch.checked;
    const rows = document.querySelectorAll('.document-row');
    
    rows.forEach(row => {
        const isDeleted = row.getAttribute('data-deleted') === '1';
        const type = row.getAttribute('data-type') || '';
        const notes = row.getAttribute('data-notes') || '';
        const filename = row.getAttribute('data-filename') || '';
        
        const matchesSearch = type.includes(term) || notes.includes(term) || filename.includes(term);
        const matchesStatus = showDeleted || !isDeleted;
        
        if (matchesSearch && matchesStatus) {
            row.classList.remove('d-none');
        } else {
            row.classList.add('d-none');
        }
    });
  }

  if (searchInput) searchInput.addEventListener('input', filterDocuments);
  if (showDeletedSwitch) showDeletedSwitch.addEventListener('change', filterDocuments);

  // --- LÓGICA DE ACCIONES (ELIMINAR / RESTAURAR / EDITAR) ---
  document.addEventListener('click', function(e) {
      const target = e.target.closest('button');
      if (!target) return;

      // SUBIR DOCUMENTO INDIVIDUAL (NUEVO)
      if (target.classList.contains('btn-save-new-doc')) {
          const tr = target.closest('tr');
          const typeSelect = tr.querySelector('select[name="document_type_ids[]"]');
          const fileInput  = tr.querySelector('input[name="document_files[]"]');
          const notesInput = tr.querySelector('input[name="document_notes[]"]');

          if (!fileInput.files.length) {
              alert('Por favor seleccione un archivo.');
              return;
          }

          target.disabled = true;
          target.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

          const formData = new FormData();
          formData.append('document_type_id', typeSelect.value);
          formData.append('document_file', fileInput.files[0]);
          formData.append('notes', notesInput.value);
          formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

          fetch('<?= route_to('hr.worker.add_document_ajax', $response['profile']->id ?? 0) ?>', {
              method: 'POST',
              body: formData,
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
          .then(r => r.json())
          .then(resp => {
              if (resp.success) {
                  if (typeof notifyShow === 'function') notifyShow(resp.message, 'success');
                  
                  // Inyectar la nueva fila real en la tabla
                  const doc = resp.document;
                  const newRowHtml = `
                    <tr class="document-row" 
                        data-id="${doc.id}" 
                        data-deleted="0"
                        data-type="${doc.type_name.toLowerCase()}"
                        data-notes="${(doc.notes || '').toLowerCase()}"
                        data-filename="${doc.file_path.toLowerCase()}">
                      <td>
                        <span class="badge bg-light text-dark border">${doc.type_name}</span>
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <i class="fas fa-file-alt text-primary me-2"></i>
                          <span class="small text-truncate" style="max-width: 150px;">${doc.file_path}</span>
                          <a href="<?= base_url('nat/hr/documents/download/') ?>${doc.id}?action=view" target="_blank" class="btn btn-link btn-sm ms-auto py-0">
                            <i class="fas fa-eye"></i> Ver
                          </a>
                        </div>
                      </td>
                      <td>
                        <span class="small text-muted">${doc.notes || '(Sin notas)'}</span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-warning btnEditDoc" 
                                data-id="${doc.id}" 
                                data-type-id="${typeSelect.value}"
                                data-notes="${notesInput.value}"
                                title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btnDeleteDoc" data-id="${doc.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                      </td>
                    </tr>
                  `;
                  
                  // Insertar al inicio del tbody (o antes de la primera fila)
                  tableDocsBody.insertAdjacentHTML('afterbegin', newRowHtml);
                  
                  // Eliminar la fila temporal de carga
                  tr.remove();
                  
                  if (tableDocsBody.querySelectorAll('tr').length === 0) {
                      // (No debería pasar aquí ya que acabamos de agregar una, pero por consistencia)
                  }
              } else {
                  alert(resp.message);
                  target.disabled = false;
                  target.innerHTML = '<i class="fas fa-upload"></i>';
              }
          });
      }

      // ELIMINAR (Soft Delete)
      if (target.classList.contains('btnDeleteDoc')) {
          const id = target.getAttribute('data-id');
          if (!confirm('¿Estás seguro de que deseas eliminar este documento?')) return;
          
          const formData = new FormData();
          formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

          fetch('<?= base_url('nat/hr/documents/delete/') ?>' + id, { 
              method: 'POST',
              body: formData,
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
              .then(r => r.json())
              .then(resp => {
                  if (resp.success) {
                      location.reload(); // Recarga simple para reflejar cambios de estado
                  } else {
                      alert(resp.message);
                  }
              });
      }

      // RESTAURAR
      if (target.classList.contains('btnRestoreDoc')) {
          const id = target.getAttribute('data-id');
          const formData = new FormData();
          formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

          fetch('<?= base_url('nat/hr/documents/restore/') ?>' + id, { 
              method: 'POST',
              body: formData,
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
              .then(r => r.json())
              .then(resp => {
                  if (resp.success) {
                      location.reload();
                  } else {
                      alert(resp.message);
                  }
              });
      }

      // ABRIR MODAL EDITAR
      if (target.classList.contains('btnEditDoc')) {
          const id = target.getAttribute('data-id');
          const typeId = target.getAttribute('data-type-id');
          const notes = target.getAttribute('data-notes');
          
          document.getElementById('edit_doc_id').value = id;
          document.getElementById('edit_doc_type').value = typeId;
          document.getElementById('edit_doc_notes').value = notes;
          
          const modal = new bootstrap.Modal(document.getElementById('modalEditDoc'));
          modal.show();
      }
  });

  // ACTUALIZAR DOCUMENTO (AJAX)
  document.getElementById('btnUpdateDoc')?.addEventListener('click', function() {
      const id = document.getElementById('edit_doc_id').value;
      const typeId = document.getElementById('edit_doc_type').value;
      const notes = document.getElementById('edit_doc_notes').value;

      const formData = new FormData();
      formData.append('document_type_id', typeId);
      formData.append('notes', notes);
      formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

      const fileInput = document.getElementById('edit_doc_file');
      if (fileInput && fileInput.files.length > 0) {
          formData.append('document_file', fileInput.files[0]);
      }

      fetch('<?= base_url('nat/hr/documents/update/') ?>' + id, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.json())
      .then(resp => {
          if (resp.success) {
              location.reload();
          } else {
              alert(resp.message);
          }
      });
  });


  // Lógica para mostrar/ocultar Nombre Manual
  function toggleManualNames() {
      if (!userIdSelect) return;
      const manualFields = document.querySelectorAll('.manual-name-fields');
      const val = userIdSelect.value;
      if (val === '') {
          manualFields.forEach(el => el.style.display = 'block');
          document.getElementById('first_name')?.setAttribute('required', 'required');
          document.getElementById('last_name')?.setAttribute('required', 'required');
      } else {
          manualFields.forEach(el => el.style.display = 'none');
          document.getElementById('first_name')?.removeAttribute('required');
          document.getElementById('last_name')?.removeAttribute('required');
      }
  }

  if (userIdSelect) {
      // Trigger en cambio usando jQuery por el select2
      $('#user_id').on('change', toggleManualNames);
      // Inicializar estado
      toggleManualNames();
  }

  if (!btnSave || !form) return;

  /**
   * validateAllTabs()
   * Recorre todos los campos requeridos del formulario independientemente de la pestaña activa.
   * Si encuentra un campo vacío, muestra una notificación descriptiva, navega al tab correcto
   * y enfoca el campo. Retorna true si todo es válido.
   */
  function validateAllTabs() {
    // Mapa: id del tab-pane → etiqueta legible
    const tabLabels = {
      'form-tab-personal'   : 'Datos Personales',
      'form-tab-laboral'    : 'Datos Laborales',
      'form-tab-nomina'     : 'Nómina y Prestaciones',
      'form-tab-bancario'   : 'Datos Bancarios',
      'form-tab-emergencia' : 'Emergencias',
    };

    // Mapa: id del campo → etiqueta legible para el usuario
    const fieldLabels = {
      'first_name'     : 'Nombre(s)',
      'last_name'      : 'Apellidos',
      'birth_date'     : 'Fecha de Nacimiento',
      'gender'         : 'Género',
      'marital_status' : 'Estado Civil',
      'curp'           : 'CURP',
      'rfc'            : 'RFC',
      'employee_number': 'Nº de Empleado',
      'hiring_date'    : 'Fecha de Ingreso',
      'worker_type'    : 'Tipo de Trabajador',
      'current_salary' : 'Salario Base Mensual',
      'daily_salary'   : 'Salario Diario (SDI)',
      'payroll_type'   : 'Tipo de Nómina',
      'payment_method' : 'Forma de Pago',
    };

    const requiredFields = form.querySelectorAll('[required]');

    for (const field of requiredFields) {
      // Solo omitir campos ocultos POR LÓGICA DE NEGOCIO (ej. nombres manuales cuando hay user_id),
      // NO por estar en un tab inactivo. Revisamos si algún ancestro directo tiene display:none
      // pero ignoramos los tab-pane (que están ocultos por Bootstrap normalmente).
      let isHiddenByLogic = false;
      let parent = field.parentElement;
      while (parent && parent !== form) {
        // Si el padre está oculto Y no es un tab-pane (Bootstrap lo oculta con .fade, no display:none directo)
        if (parent.style.display === 'none' && !parent.classList.contains('tab-pane')) {
          isHiddenByLogic = true;
          break;
        }
        parent = parent.parentElement;
      }
      if (isHiddenByLogic) continue;

      const isEmpty = field.value === null || field.value.trim() === '';
      if (!isEmpty) continue;

      // Encontrar en qué tab-pane está este campo
      const pane = field.closest('.tab-pane');
      const paneId   = pane ? pane.id : null;
      const paneLabel = paneId ? (tabLabels[paneId] || 'formulario') : 'formulario';

      // Obtener el label legible del campo
      const fieldLabel = fieldLabels[field.id] || field.previousElementSibling?.textContent?.trim()?.replace(' *', '') || field.name || 'un campo requerido';

      // Mostrar notificación
      if (typeof notifyShow === 'function') {
        notifyShow(`El campo <strong>${fieldLabel}</strong> es obligatorio en la pestaña <strong>${paneLabel}</strong>.`, 'warning');
      }

      // Navegar al tab que contiene el error
      if (paneId) {
        const tabTrigger = document.querySelector(`[data-bs-target="#${paneId}"]`);
        if (tabTrigger) bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
      }

      // Marcar el campo como inválido visualmente y enfocar
      field.classList.add('is-invalid');
      setTimeout(() => field.focus(), 200);

      // Quitar la marca en cuanto el usuario empiece a escribir
      field.addEventListener('input', () => field.classList.remove('is-invalid'), { once: true });
      field.addEventListener('change', () => field.classList.remove('is-invalid'), { once: true });

      return false;
    }
    return true;
  }

  btnSave.addEventListener('click', function (e) {
    e.preventDefault();

    // Validación multi-tab con feedback descriptivo
    if (!validateAllTabs()) return;

    // Limpiar errores previos del área de alertas
    errBox.style.display = 'none';
    errBody.innerHTML    = '';

    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

    const formData = new FormData(form);

    fetch(formAction, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData
    })
    .then(response => {
      // Renovar CSRF tras cada POST
      const newCsrfHash = response.headers.get('<?= csrf_header() ?>');
      if (newCsrfHash) {
        const csrfInput = form.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) csrfInput.value = newCsrfHash;
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        if (typeof notifyShow === 'function') notifyShow(data.message, 'success');
        
        // Si es edición, nos quedamos en la página (reload para refrescar datos)
        // Si es nuevo, redireccionamos al listado
        const targetUrl = isEdit ? window.location.href : '<?= route_to('hr.workers') ?>';
        
        setTimeout(() => { 
          window.location.href = targetUrl; 
        }, 900);
      } else {
        // Mostrar errores del servidor en el área de alertas
        let errStr = data.error || data.message || 'Revise los campos enviados.';
        if (typeof errStr === 'object') {
          errStr = Object.values(errStr).join('<br>');
        }
        errTitle.textContent = data.message || 'Error de validación';
        errBody.innerHTML    = errStr;
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    })
    .catch(error => {
      errTitle.textContent = 'Error de conexión';
      errBody.innerHTML    = 'Hubo un problema al comunicarse con el servidor. Intente nuevamente.';
      errBox.style.display = 'block';
      console.error(error);
    })
    .finally(() => {
      btnSave.disabled = false;
      btnSave.innerHTML = '<i class="fas fa-save me-1"></i><?= $isEdit ? 'Guardar Cambios' : 'Registrar Trabajador' ?>';
    });
  });
});
</script>
<?php $this->endSection(); ?>
