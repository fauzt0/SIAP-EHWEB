<?php $this->extend($layout); ?>

<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>

<?php
$report           = $report ?? [];
$money            = $report['money'] ?? [];
$suppliers        = $report['suppliers'] ?? [];
$reminders        = $report['reminders'] ?? [];
$openOrders       = $report['open_orders'] ?? [];
$poStatus         = $report['po_status'] ?? [];
$supplierOptions  = $supplierOptions ?? [];
$selectedSupplier = (int) ($selectedSupplier ?? 0);
$poLabels         = $poLabels ?? [];
$reminderLabels   = $reminderLabels ?? [];
$expenseLabels    = $expenseLabels ?? [];

if (! function_exists('supplier_report_money')) {
    function supplier_report_money(float $amount, string $currency): string
    {
        $prefix = $currency !== '' ? $currency . ' ' : '';

        return $prefix . number_format($amount, 2);
    }

    function supplier_report_percent(?float $percent): string
    {
        return $percent === null ? '—' : number_format($percent, 1) . '%';
    }

    function supplier_report_date(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }
        $stamp = strtotime($date);

        return $stamp ? date('d/m/Y', $stamp) : $date;
    }

    function supplier_report_amounts(array $byCurrency): string
    {
        if ($byCurrency === []) {
            return '—';
        }
        $parts = [];
        foreach ($byCurrency as $currency => $amount) {
            $parts[] = $currency . ' ' . number_format((float) $amount, 2);
        }

        return implode(' · ', $parts);
    }
}

$exportQuery = $selectedSupplier > 0 ? '?supplier_id=' . $selectedSupplier : '';
?>

<?php $this->section('main') ?>

<main class="content">
    <div class="container-fluid p-0">

        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3">
                    <i class="fas fa-fw fa-chart-bar me-2 text-primary"></i><?= esc($pageTitle) ?>
                </h1>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <nav aria-label="breadcrumb">
                    <?= isset($breadcrumb) ? $breadcrumb : '' ?>
                </nav>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="get" action="<?= route_to('suppliers.reports.index') ?>" class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label for="filter-supplier" class="form-label">Proveedor</label>
                        <select class="form-select" id="filter-supplier" name="supplier_id">
                            <option value="">Todos los proveedores</option>
                            <?php foreach ($supplierOptions as $option): ?>
                                <option value="<?= (int) $option->id ?>" <?= $selectedSupplier === (int) $option->id ? 'selected' : '' ?>>
                                    <?= esc($option->commercial_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a class="btn btn-outline-secondary" href="<?= route_to('suppliers.reports.index') ?>">Limpiar</a>
                        <a class="btn btn-outline-success ms-auto" href="<?= route_to('suppliers.reports.export') . $exportQuery ?>">
                            <i class="fas fa-download me-1"></i>CSV
                        </a>
                    </div>
                </form>
                <p class="text-muted small mb-0 mt-3">
                    El porcentaje pagado sale de los gastos (pagado / total, sin cancelados).
                    Una orden de compra no registra abonos: “abierta” significa enviada o aprobada.
                    Los importes no se suman entre monedas.
                    Corte al <?= esc(supplier_report_date($report['today'] ?? date('Y-m-d'))) ?>.
                </p>
            </div>
        </div>

        <div class="row mb-3">
            <?php if ($money === []): ?>
                <div class="col-12">
                    <div class="card"><div class="card-body text-muted">No hay gastos registrados para este corte.</div></div>
                </div>
            <?php else: ?>
                <?php foreach ($money as $row): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="text-muted small">Pendiente <?= esc($row['currency']) ?></div>
                                <div class="h4 mb-1 text-danger"><?= esc(supplier_report_money((float) $row['outstanding'], $row['currency'])) ?></div>
                                <div class="small text-muted">Pagado <?= esc(supplier_report_money((float) $row['paid'], $row['currency'])) ?></div>
                                <div class="small">Porcentaje pagado: <strong><?= esc(supplier_report_percent($row['percent'])) ?></strong></div>
                                <div class="small text-muted"><?= (int) $row['count'] ?> gasto(s)</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Órdenes de compra por estatus</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Estatus</th>
                                <th class="text-end">Cantidad</th>
                                <th>Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($poLabels as $status => $label): ?>
                                <?php $cell = $poStatus[$status] ?? ['count' => 0, 'by_currency' => []]; ?>
                                <tr>
                                    <td><?= esc($label) ?></td>
                                    <td class="text-end"><?= (int) $cell['count'] ?></td>
                                    <td><?= esc(supplier_report_amounts($cell['by_currency'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Proveedores</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>Moneda</th>
                                <th class="text-end">Pendiente</th>
                                <th class="text-end">Pagado</th>
                                <th class="text-end">% pagado</th>
                                <th>Gastos</th>
                                <th>OC abiertas</th>
                                <th class="text-end">Servicios activos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($suppliers === []): ?>
                                <tr><td colspan="8" class="text-muted">No hay proveedores en este filtro.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($suppliers as $row): ?>
                                <tr>
                                    <td>
                                        <a href="<?= route_to('suppliers.show', $row['supplier_id']) ?>"><?= esc($row['name']) ?></a>
                                        <?php if (! $row['active']): ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['currency'] !== '' ? esc($row['currency']) : '—' ?></td>
                                    <td class="text-end"><?= esc(supplier_report_money((float) $row['outstanding'], (string) $row['currency'])) ?></td>
                                    <td class="text-end"><?= esc(supplier_report_money((float) $row['paid'], (string) $row['currency'])) ?></td>
                                    <td class="text-end"><?= esc(supplier_report_percent($row['percent'])) ?></td>
                                    <td class="small">
                                        <?= (int) $row['pending_n'] ?> pendientes,
                                        <?= (int) $row['partial_n'] ?> parciales,
                                        <?= (int) $row['paid_n'] ?> pagados
                                    </td>
                                    <td class="small">
                                        <?= (int) $row['po_open_count'] ?>
                                        <?php if ((float) $row['po_open_amount'] > 0): ?>
                                            · <?= esc(supplier_report_money((float) $row['po_open_amount'], (string) $row['currency'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= (int) $row['recurring_active'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <strong>Recordatorios de pago</strong>
                <a href="<?= route_to('suppliers.recurring.index') ?>">Ir a servicios recurrentes</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Aviso</th>
                                <th>Proveedor</th>
                                <th>Servicio</th>
                                <th>Vencimiento</th>
                                <th class="text-end">Pendiente</th>
                                <th>Estatus del gasto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reminders === []): ?>
                                <tr><td colspan="6" class="text-muted">No hay recordatorios en la ventana de aviso.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($reminders as $row): ?>
                                <?php
                                $kindClass = $row['kind'] === 'overdue' ? 'text-danger' : ($row['kind'] === 'ungenerated' ? 'text-warning' : 'text-info');
                                ?>
                                <tr>
                                    <td class="<?= esc($kindClass) ?>"><?= esc($reminderLabels[$row['kind']] ?? $row['kind']) ?></td>
                                    <td>
                                        <?php if ($row['supplier_id']): ?>
                                            <a href="<?= route_to('suppliers.show', $row['supplier_id']) ?>"><?= esc($row['supplier_name']) ?></a>
                                        <?php else: ?>
                                            <?= esc($row['supplier_name']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= esc($row['label']) ?></div>
                                        <div class="small text-muted"><?= esc($row['service_label']) ?></div>
                                    </td>
                                    <td><?= esc(supplier_report_date($row['due_date'])) ?></td>
                                    <td class="text-end"><?= esc(supplier_report_money((float) $row['outstanding'], (string) $row['currency'])) ?></td>
                                    <td><?= esc($expenseLabels[$row['status']] ?? $row['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <strong>Órdenes abiertas</strong>
                <a href="<?= route_to('suppliers.po.index') ?>">Ir a órdenes de compra</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Proveedor</th>
                                <th>Estatus</th>
                                <th>Emisión</th>
                                <th>Entrega</th>
                                <th class="text-end">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($openOrders === []): ?>
                                <tr><td colspan="6" class="text-muted">No hay órdenes enviadas o aprobadas.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($openOrders as $order): ?>
                                <tr>
                                    <td><a href="<?= route_to('suppliers.po.show', $order->id) ?>"><?= esc($order->po_number) ?></a></td>
                                    <td><?= esc((string) ($order->commercial_name ?? '')) ?></td>
                                    <td><?= esc($poLabels[$order->status] ?? (string) $order->status) ?></td>
                                    <td><?= esc(supplier_report_date($order->issue_date)) ?></td>
                                    <td><?= esc(supplier_report_date($order->delivery_date)) ?></td>
                                    <td class="text-end"><?= esc(supplier_report_money((float) $order->total_amount, (string) ($order->currency ?? ''))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<?php $this->endSection() ?>
