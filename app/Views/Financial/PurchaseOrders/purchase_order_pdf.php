<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra <?= esc($po->po_number) ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #2c3e50; line-height: 1.6; margin: 0; padding: 0; }
        .header { border-bottom: 3px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; text-align: center; }
        .header h1 { font-size: 18pt; color: #1a5276; margin: 0 0 5px 0; text-transform: uppercase; }
        .header .subtitle { font-size: 8pt; color: #7f8c8d; }
        .po-info { background: #f8f9fa; border-left: 4px solid #3498db; padding: 10px 15px; margin-bottom: 18px; }
        .po-info table { width: 100%; border-collapse: collapse; }
        .po-info td { padding: 3px 8px; font-size: 9pt; vertical-align: top; }
        .po-info .label { font-weight: bold; color: #1a5276; width: 130px; }
        .supplier-box { border: 1px solid #dcdfe3; padding: 10px 15px; margin-bottom: 18px; border-radius: 3px; }
        .supplier-box h3 { font-size: 10pt; margin: 0 0 6px 0; color: #1a5276; }
        .supplier-box p { margin: 2px 0; font-size: 9pt; }
        .badge { display: inline-block; padding: 2px 8px; font-size: 7.5pt; font-weight: bold; border-radius: 8px; color: #fff; text-transform: uppercase; }
        .badge-secondary { background: #95a5a6; }
        .badge-info { background: #3498db; }
        .badge-success { background: #27ae60; }
        .badge-primary { background: #2980b9; }
        .badge-danger { background: #e74c3c; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 9pt; }
        table.items thead th { background: #1a5276; color: #fff; padding: 7px 10px; text-align: left; font-weight: bold; }
        table.items tbody td { padding: 6px 10px; border-bottom: 1px solid #ecf0f1; }
        table.items tbody tr:nth-child(even) { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10pt; }
        .totals td { padding: 3px 10px; }
        .totals .label-cell { text-align: right; font-weight: bold; width: 80%; }
        .totals .value-cell { text-align: right; width: 20%; font-weight: bold; }
        .totals .grand-total { border-top: 2px solid #2c3e50; font-size: 11pt; padding-top: 5px; }
        .notes { border-top: 1px solid #dcdfe3; padding-top: 10px; margin-top: 10px; font-size: 8.5pt; color: #7f8c8d; }
        .notes h4 { font-size: 9pt; margin: 0 0 4px 0; color: #2c3e50; }
        .footer { position: fixed; bottom: 0; width: 100%; border-top: 1px solid #dcdfe3; padding-top: 6px; font-size: 7pt; color: #bdc3c7; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <h1>Orden de Compra</h1>
    <div class="subtitle">Documento generado por el sistema ERP</div>
</div>

<?php if (! empty($po->branch_commercial_name) || ! empty($po->branch_name)): ?>
<div class="supplier-box" style="margin-bottom:12px;">
    <h3>Comprador / Sucursal</h3>
    <p><strong><?= esc($po->branch_commercial_name ?? $po->branch_name) ?></strong></p>
    <?php if (! empty($po->branch_legal_name)): ?>
    <p>Razón social: <?= esc($po->branch_legal_name) ?></p>
    <?php endif; ?>
    <?php if (! empty($po->branch_tax_id)): ?>
    <p>RFC: <?= esc($po->branch_tax_id) ?></p>
    <?php endif; ?>
    <?php if (! empty($po->branch_address)): ?>
    <p><?= esc($po->branch_address) ?></p>
    <?php endif; ?>
    <?php if (! empty($po->branch_phone) || ! empty($po->branch_email)): ?>
    <p>
        <?= ! empty($po->branch_phone) ? 'Tel: ' . esc($po->branch_phone) : '' ?>
        <?= ! empty($po->branch_email) ? ' · ' . esc($po->branch_email) : '' ?>
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="po-info">
    <table>
        <tr><td class="label">N° de Orden:</td><td><strong><?= esc($po->po_number) ?></strong></td></tr>
        <tr><td class="label">Fecha de Emisión:</td><td><?= !empty($po->issue_date) ? date('d/m/Y', strtotime($po->issue_date)) : '—' ?></td></tr>
        <tr><td class="label">Fecha de Entrega:</td><td><?= !empty($po->delivery_date) ? date('d/m/Y', strtotime($po->delivery_date)) : '—' ?></td></tr>
        <tr><td class="label">Moneda:</td><td><?= esc($po->currency ?? 'MXN') ?></td></tr>
        <tr>
            <td class="label">Estatus:</td>
            <td>
                <?php
                    $statusClass = $po->status ?? 'draft';
                    $labels = ['draft'=>'Borrador','sent'=>'Enviada','approved'=>'Aprobada','completed'=>'Completada','cancelled'=>'Cancelada'];
                    $badgeMap = ['draft'=>'secondary','sent'=>'info','approved'=>'success','completed'=>'primary','cancelled'=>'danger'];
                ?>
                <span class="badge badge-<?= $badgeMap[$statusClass] ?? 'secondary' ?>"><?= $labels[$statusClass] ?? ucfirst($statusClass) ?></span>
            </td>
        </tr>
    </table>
</div>

<div class="supplier-box">
    <h3>Proveedor</h3>
    <p><strong><?= esc($po->supplier_name ?? '—') ?></strong></p>
    <?php if (!empty($po->tax_id)): ?><p>RFC: <?= esc($po->tax_id) ?></p><?php endif; ?>
</div>

<?php $items = $po->items ?? []; ?>
<?php if (!empty($items)): ?>
<table class="items">
    <thead>
        <tr>
            <th style="width:8%;">#</th>
            <th style="width:44%;">Descripción</th>
            <th style="width:12%;" class="text-center">Cant.</th>
            <th style="width:18%;" class="text-right">P. Unit.</th>
            <th style="width:18%;" class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; $subtotal = 0; ?>
        <?php foreach ($items as $item): ?>
            <?php $total = (float) ($item->total_price ?? ($item->quantity * $item->unit_price)); $subtotal += $total; ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= esc($item->description ?? '—') ?></td>
                <td class="text-center"><?= (int) ($item->quantity ?? 1) ?></td>
                <td class="text-right"><?= number_format((float) ($item->unit_price ?? 0), 2) ?></td>
                <td class="text-right"><?= number_format($total, 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<table class="totals">
    <tr><td class="label-cell">Subtotal:</td><td class="value-cell"><?= number_format($subtotal, 2) ?></td></tr>
    <tr><td class="label-cell">Total <?= esc($po->currency ?? 'MXN') ?>:</td><td class="value-cell grand-total"><?= number_format((float) ($po->total_amount ?? $subtotal), 2) ?></td></tr>
</table>
<?php else: ?>
<p style="text-align:center; color:#bdc3c7; padding:30px 0;"><em>Esta orden no contiene partidas.</em></p>
<?php endif; ?>

<?php if (!empty($po->notes)): ?>
<div class="notes">
    <h4>Notas y Observaciones</h4>
    <p><?= nl2br(esc($po->notes)) ?></p>
</div>
<?php endif; ?>

<div class="footer">Documento generado el <?= date('d/m/Y H:i') ?> — ERP / Sistema Integral</div>

</body>
</html>
