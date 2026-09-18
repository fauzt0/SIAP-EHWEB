<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle ?? 'Recibo de nómina') ?>
<?php $this->endSection(); ?>

<?php
$response        = $response ?? [];
$entry           = $response['entry'] ?? [];
$company         = $response['company'] ?? null;
$paymentMethods  = $response['payment_methods'] ?? [];
$paymentStatuses = $response['payment_statuses'] ?? [];
$periodTypes     = $response['period_types'] ?? [];

$logoPath = $entry['branch_logo_path'] ?? null;
if (empty($logoPath) && $company && ! empty($company->logo_path)) {
    $logoPath = $company->logo_path;
}
$logoUrl  = (! empty($logoPath)) ? base_url($logoPath) : null;

$companyName    = $entry['branch_commercial_name'] ?? $entry['branch_name'] ?? ($company?->commercial_name ?? $company?->legal_name ?? '');
$companyLegal   = $entry['branch_legal_name'] ?? ($company?->legal_name ?? '');
$companyRfc     = $entry['branch_tax_id'] ?? ($company?->tax_id ?? '');
$companyAddress = $entry['branch_address'] ?? '';

$grossConcepts = (float) ($entry['base_salary'] ?? 0) + (float) ($entry['bonuses'] ?? 0) + (float) ($entry['benefits'] ?? 0);
$deductions    = (float) ($entry['imss_employee'] ?? 0) + (float) ($entry['infonavit'] ?? 0) + (float) ($entry['isr'] ?? 0) + (float) ($entry['other_deductions'] ?? 0);
$payStatus     = $entry['payment_status'] ?? 'pending';
$payColor      = $payStatus === 'paid' ? 'success' : ($payStatus === 'partial' ? 'info' : 'warning');
?>

<?php $this->section('main'); ?>
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">

<div class="print-toolbar no-print bg-white border-bottom p-3 mb-3">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <nav aria-label="breadcrumb" class="mb-0"><?= $breadcrumb ?? '' ?></nav>
        <div class="d-flex gap-2">
            <a href="<?= route_to('hr.payroll.show', (int) ($entry['period_id'] ?? 0)) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver al periodo
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Imprimir
            </button>
        </div>
    </div>
</div>

<div class="container py-3">
    <div class="print-sheet bg-white shadow-sm p-4 p-md-5 mx-auto" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-3">
            <div>
                <?php if ($logoUrl): ?>
                <img src="<?= esc($logoUrl) ?>" alt="Logo" style="max-height: 70px; max-width: 200px;">
                <?php endif; ?>
            </div>
            <div class="text-end">
                <h2 class="h4 mb-1">Recibo de nómina</h2>
                <div class="text-muted small"><?= esc($entry['period_name'] ?? '') ?></div>
                <span class="badge bg-<?= $payColor ?> mt-1"><?= esc($paymentStatuses[$payStatus] ?? $payStatus) ?></span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-uppercase text-muted small">Empresa / Sucursal</h6>
                <div class="fw-semibold"><?= esc($companyName) ?></div>
                <?php if ($companyLegal): ?><div><?= esc($companyLegal) ?></div><?php endif; ?>
                <?php if ($companyRfc): ?><div>RFC: <?= esc($companyRfc) ?></div><?php endif; ?>
                <?php if ($companyAddress): ?><div class="small text-muted"><?= esc($companyAddress) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <h6 class="text-uppercase text-muted small">Trabajador</h6>
                <div class="fw-semibold"><?= esc($entry['worker_name'] ?? '') ?></div>
                <div>Puesto: <?= esc($entry['job_name'] ?? '—') ?></div>
                <div>Departamento: <?= esc($entry['department_name'] ?? '—') ?></div>
                <div>No. empleado: <?= esc($entry['employee_number'] ?? '—') ?></div>
                <?php if (! empty($entry['worker_rfc'])): ?><div>RFC: <?= esc($entry['worker_rfc']) ?></div><?php endif; ?>
                <?php if (! empty($entry['worker_nss'])): ?><div>NSS: <?= esc($entry['worker_nss']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="alert alert-light border mb-4">
            <strong>Periodo:</strong>
            <?= ! empty($entry['start_date']) ? date('d/m/Y', strtotime($entry['start_date'])) : '—' ?>
            —
            <?= ! empty($entry['end_date']) ? date('d/m/Y', strtotime($entry['end_date'])) : '—' ?>
            <span class="ms-3"><strong>Tipo:</strong> <?= esc($periodTypes[$entry['period_type'] ?? ''] ?? '') ?></span>
            <span class="ms-3"><strong>Días trabajados:</strong> <?= (int) ($entry['worked_days'] ?? 0) ?></span>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h6>Percepciones</h6>
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td>Salario base</td>
                            <td class="text-end">$<?= number_format((float) ($entry['base_salary'] ?? 0), 2) ?></td>
                        </tr>
                        <tr>
                            <td>Horas extra (<?= number_format((float) ($entry['overtime_hours'] ?? 0), 2) ?> h)</td>
                            <td class="text-end">—</td>
                        </tr>
                        <tr>
                            <td>Bonos</td>
                            <td class="text-end">$<?= number_format((float) ($entry['bonuses'] ?? 0), 2) ?></td>
                        </tr>
                        <tr>
                            <td>Prestaciones</td>
                            <td class="text-end">$<?= number_format((float) ($entry['benefits'] ?? 0), 2) ?></td>
                        </tr>
                        <tr class="fw-semibold">
                            <td>Total percepciones</td>
                            <td class="text-end">$<?= number_format((float) ($entry['gross_amount'] ?? $grossConcepts), 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Deducciones</h6>
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td>IMSS empleado</td>
                            <td class="text-end">$<?= number_format((float) ($entry['imss_employee'] ?? 0), 2) ?></td>
                        </tr>
                        <tr>
                            <td>INFONAVIT</td>
                            <td class="text-end">$<?= number_format((float) ($entry['infonavit'] ?? 0), 2) ?></td>
                        </tr>
                        <tr>
                            <td>ISR</td>
                            <td class="text-end">$<?= number_format((float) ($entry['isr'] ?? 0), 2) ?></td>
                        </tr>
                        <tr>
                            <td>Otras deducciones</td>
                            <td class="text-end">$<?= number_format((float) ($entry['other_deductions'] ?? 0), 2) ?></td>
                        </tr>
                        <tr class="fw-semibold">
                            <td>Total deducciones</td>
                            <td class="text-end">$<?= number_format($deductions, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-3 bg-primary text-white rounded d-flex justify-content-between align-items-center mt-2">
            <span class="h5 mb-0">Neto a recibir</span>
            <span class="h4 mb-0">$<?= number_format((float) ($entry['net_amount'] ?? 0), 2) ?></span>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="small text-muted">Método de pago</div>
                <div><?= esc($paymentMethods[$entry['payment_method'] ?? ''] ?? ($entry['payment_method'] ?? '—')) ?></div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">Estatus del pago</div>
                <div>
                    <?= esc($paymentStatuses[$payStatus] ?? $payStatus) ?>
                    <?php if ($payStatus === 'paid' && ! empty($entry['payment_date'])): ?>
                    — <?= date('d/m/Y', strtotime($entry['payment_date'])) ?>
                    <?php endif; ?>
                </div>
                <?php if (! empty($entry['payment_reference'])): ?>
                <div class="small">Referencia: <?= esc($entry['payment_reference']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (! empty($entry['notes'])): ?>
        <div class="mt-3 small text-muted">Notas: <?= esc($entry['notes']) ?></div>
        <?php endif; ?>

        <div class="row mt-5 pt-4">
            <div class="col-6 text-center">
                <div class="border-top pt-2 mx-auto" style="max-width: 220px;">Firma del trabajador</div>
            </div>
            <div class="col-6 text-center">
                <div class="border-top pt-2 mx-auto" style="max-width: 220px;">Sello / empresa</div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<?php $this->endSection(); ?>
