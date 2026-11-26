<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= esc($response['po']->po_number ?? 'OC cliente') ?></title>
  <style>
    body { font-family: system-ui, sans-serif; margin: 2rem; color: #222; }
    h1 { font-size: 1.4rem; margin-bottom: .25rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { border: 1px solid #ccc; padding: .4rem .6rem; font-size: .9rem; }
    th { background: #f4f6f9; text-align: left; }
    .muted { color: #666; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body>
  <?php $po = $response['po']; ?>
  <p class="no-print"><button onclick="window.print()">Imprimir</button></p>
  <h1>Orden de compra del cliente <?= esc($po->po_number) ?></h1>
  <p class="muted">Cliente: <?= esc($po->company_name ?? '') ?> · <?= esc($po->client_code ?? '') ?></p>
  <p class="muted">Orden de venta: <?= esc($po->order_number ?? ('#' . $po->order_id)) ?></p>
  <table>
    <thead>
      <tr><th>Línea</th><th>Producto / servicio</th><th>Ciclo</th><th>Cant.</th><th>Precio</th></tr>
    </thead>
    <tbody>
      <?php foreach ($po->items ?? [] as $item): ?>
      <tr>
        <td><?= esc($item->line_code ?? '—') ?></td>
        <td><?= esc($item->item_name) ?></td>
        <td><?= esc($item->billing_cycle ?? '—') ?></td>
        <td><?= (int) $item->quantity ?></td>
        <td>$<?= number_format((float) $item->unit_price, 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p><strong>Total:</strong> $<?= number_format((float) $po->total_amount, 2) ?> <?= esc($po->currency ?? 'MXN') ?></p>
  <?php if (! empty($po->notes)): ?><p><?= esc($po->notes) ?></p><?php endif; ?>
</body>
</html>
