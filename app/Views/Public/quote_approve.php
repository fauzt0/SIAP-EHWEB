<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aprobar cotización <?= esc($quote->quote_number) ?></title>
  <link rel="stylesheet" href="<?= base_url('bootstrap/css/app.css') ?>">
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width:560px">
    <div class="card">
      <div class="card-body">
        <h1 class="h4">Cotización <?= esc($quote->quote_number) ?></h1>
        <p class="text-muted">Total: $<?= number_format((float)$quote->total_amount, 2) ?> <?= esc($quote->currency ?? 'MXN') ?></p>
        <?php if (($quote->status ?? '') === 'approved'): ?>
          <div class="alert alert-success">Esta cotización ya fue aprobada.</div>
        <?php else: ?>
          <p>Al confirmar, autoriza al equipo comercial a continuar con la orden de servicio / proyecto.</p>
          <button class="btn btn-primary" id="btn-approve">Aprobar cotización</button>
          <div id="msg" class="mt-3"></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script>
  document.getElementById('btn-approve')?.addEventListener('click', async ()=>{
    const r = await fetch('<?= site_url('p/quote/' . $token . '/approve') ?>', {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'}});
    const j = await r.json();
    document.getElementById('msg').textContent = j.message || '';
  });
  </script>
</body>
</html>
