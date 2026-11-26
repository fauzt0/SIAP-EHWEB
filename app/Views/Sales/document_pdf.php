<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= esc($template->title ?? $kind) ?> <?= esc($doc['number'] ?? '') ?></title>
  <style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #2c3e50; }
    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .header-table td { vertical-align: top; }
    .logo { max-height: 58px; max-width: 170px; }
    h1 { font-size: 16pt; margin: 0; color: #1a5276; text-transform: uppercase; }
    .muted { color: #7f8c8d; font-size: 8pt; }
    .box { border: 1px solid #dcdfe3; padding: 8px 12px; margin-bottom: 12px; }
    table.items { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 10px; }
    table.items th { background: #1a5276; color: #fff; padding: 6px 8px; text-align: left; }
    table.items td { padding: 5px 8px; border-bottom: 1px solid #ecf0f1; }
    .right { text-align: right; }
    .totals { width: 40%; margin-left: 60%; }
    .totals td { padding: 3px 6px; }
    .grand { font-weight: bold; border-top: 2px solid #2c3e50; }
    .legal { font-size: 8pt; color: #7f8c8d; margin-top: 16px; border-top: 1px solid #eee; padding-top: 8px; }
  </style>
</head>
<body>
<?php
$company = $issuer['company'] ?? null;
$branch  = $issuer['branch'] ?? null;
$logoAbs = $issuer['logo_abs'] ?? null;
$showLogo = (int) ($template->show_logo ?? 1) === 1;
$kindLabel = $template->title ?? match ($kind) {
    'quote'   => 'Cotización',
    'order'   => 'Comprobante de venta',
    default   => 'Recibo',
};
$issuerName = $branch->commercial_name ?? $branch->name ?? $company->commercial_name ?? '';
$issuerLegal = $branch->legal_name ?? $company->legal_name ?? '';
$issuerRfc = $branch->tax_id ?? $company->tax_id ?? '';
$issuerAddr = $branch->address ?? '';
$issuerPhone = $branch->phone ?? $company->primary_phone ?? '';
$client = $doc['client'] ?? [];
$items = $doc['items'] ?? [];
?>
<table class="header-table">
  <tr>
    <td style="width:28%">
      <?php if ($showLogo && $logoAbs): ?>
        <img src="<?= esc($logoAbs) ?>" class="logo" alt="Logo">
      <?php endif; ?>
    </td>
    <td style="width:44%; text-align:center">
      <h1><?= esc($kindLabel) ?></h1>
      <div class="muted"><?= esc($doc['number'] ?? '') ?></div>
    </td>
    <td style="width:28%; text-align:right">
      <strong><?= esc($issuerName) ?></strong><br>
      <?php if ($issuerLegal): ?><span class="muted"><?= esc($issuerLegal) ?></span><br><?php endif; ?>
      <?php if ($issuerRfc): ?><span class="muted">RFC <?= esc($issuerRfc) ?></span><br><?php endif; ?>
      <?php if ($issuerPhone): ?><span class="muted"><?= esc($issuerPhone) ?></span><?php endif; ?>
    </td>
  </tr>
</table>
<?php if (! empty($template->header_extra_html)): ?>
  <div><?= $template->header_extra_html ?></div>
<?php endif; ?>
<?php if ($issuerAddr): ?><div class="muted" style="margin-bottom:8px"><?= esc($issuerAddr) ?></div><?php endif; ?>

<div class="box">
  <strong>Cliente</strong>
  <div><?= esc($client['name'] ?? '—') ?></div>
  <?php if (! empty($client['code'])): ?><div class="muted"><?= esc($client['code']) ?></div><?php endif; ?>
  <?php if (! empty($client['rfc'])): ?><div class="muted">RFC <?= esc($client['rfc']) ?></div><?php endif; ?>
  <?php if (! empty($client['email'])): ?><div class="muted"><?= esc($client['email']) ?></div><?php endif; ?>
</div>

<table class="items">
  <thead>
    <tr>
      <th>Descripción</th>
      <th>Ciclo</th>
      <th class="right">Cant.</th>
      <th class="right">Precio</th>
      <th class="right">Importe</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($items as $item):
      $qty = (int) ($item['quantity'] ?? $item->quantity ?? 1);
      $price = (float) ($item['unit_price'] ?? $item->unit_price ?? 0);
      $name = (string) ($item['item_name'] ?? $item->item_name ?? '');
      $cycle = (string) ($item['billing_cycle'] ?? $item->billing_cycle ?? '');
      ?>
    <tr>
      <td><?= esc($name) ?></td>
      <td><?= esc($cycle) ?></td>
      <td class="right"><?= $qty ?></td>
      <td class="right">$<?= number_format($price, 2) ?></td>
      <td class="right">$<?= number_format($qty * $price, 2) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<table class="totals">
  <tr><td>Subtotal</td><td class="right">$<?= number_format((float) ($doc['subtotal'] ?? 0), 2) ?></td></tr>
  <tr><td>IVA</td><td class="right">$<?= number_format((float) ($doc['tax_amount'] ?? 0), 2) ?></td></tr>
  <tr class="grand"><td>Total</td><td class="right">$<?= number_format((float) ($doc['total_amount'] ?? 0), 2) ?> <?= esc($doc['currency'] ?? 'MXN') ?></td></tr>
</table>

<?php if (! empty($doc['notes'])): ?>
  <p><strong>Notas:</strong> <?= esc($doc['notes']) ?></p>
<?php endif; ?>
<?php if (! empty($doc['valid_until'])): ?>
  <p class="muted">Vigente hasta <?= esc($doc['valid_until']) ?></p>
<?php endif; ?>
<?php if (! empty($doc['status'])): ?>
  <p class="muted">Estatus: <?= esc($doc['status']) ?><?php if (! empty($doc['period'])): ?> · Periodo <?= esc($doc['period']) ?><?php endif; ?></p>
<?php endif; ?>

<?php if (! empty($template->legal_note)): ?>
  <div class="legal"><?= nl2br(esc($template->legal_note)) ?></div>
<?php endif; ?>
<?php if (! empty($template->footer_extra_html)): ?>
  <div><?= $template->footer_extra_html ?></div>
<?php endif; ?>
</body>
</html>
