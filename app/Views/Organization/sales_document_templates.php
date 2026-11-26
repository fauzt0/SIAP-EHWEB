<?php $this->extend($layout); ?>
<?php $this->section('title'); echo esc($headTitle); $this->endSection(); ?>
<?php $this->section('main'); ?>
<main class="content">
  <div class="container-fluid p-0">
    <div class="row mb-3">
      <div class="col">
        <h1 class="h3"><i class="fas fa-file-pdf me-2 text-danger"></i><?= esc($pageTitle) ?></h1>
        <p class="text-muted mb-0">El logo se toma de la sucursal del documento; si no hay, se usa el de la empresa (Organización → Perfil matriz / Sucursales). Marcas de catálogo se administran en Productos.</p>
      </div>
    </div>
    <div class="row">
      <?php
      $kinds = ['quote'=>'Cotización','order'=>'Venta','receipt'=>'Recibo'];
      foreach ($response['templates'] ?? [] as $tpl): ?>
      <div class="col-lg-4 mb-3">
        <form class="card tpl-form" data-id="<?= (int)$tpl->id ?>">
          <div class="card-header"><strong><?= esc($kinds[$tpl->doc_kind] ?? $tpl->doc_kind) ?></strong></div>
          <div class="card-body">
            <label class="form-label">Título del documento</label>
            <input class="form-control mb-2" name="title" value="<?= esc($tpl->title) ?>">
            <label class="form-label">Papel</label>
            <select class="form-select mb-2" name="paper">
              <option value="Letter" <?= ($tpl->paper??'')==='Letter'?'selected':'' ?>>Carta</option>
              <option value="A4" <?= ($tpl->paper??'')==='A4'?'selected':'' ?>>A4</option>
            </select>
            <label class="form-label">Sucursal por defecto (logo)</label>
            <select class="form-select mb-2" name="default_branch_id">
              <option value="">Empresa / matriz</option>
              <?php foreach ($response['branches'] ?? [] as $b): ?>
                <option value="<?= (int)$b->id ?>" <?= (int)($tpl->default_branch_id??0)===(int)$b->id?'selected':'' ?>><?= esc($b->name) ?></option>
              <?php endforeach; ?>
            </select>
            <label class="form-label">Marca de agua</label>
            <input class="form-control mb-2" name="watermark_text" value="<?= esc($tpl->watermark_text ?? '') ?>">
            <label class="form-label">Nota legal</label>
            <textarea class="form-control mb-2" name="legal_note" rows="3"><?= esc($tpl->legal_note ?? '') ?></textarea>
            <label class="form-label">HTML extra encabezado</label>
            <textarea class="form-control mb-2" name="header_extra_html" rows="2"><?= esc($tpl->header_extra_html ?? '') ?></textarea>
            <label class="form-label">HTML extra pie</label>
            <textarea class="form-control mb-2" name="footer_extra_html" rows="2"><?= esc($tpl->footer_extra_html ?? '') ?></textarea>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="show_logo" value="1" <?= (int)$tpl->show_logo===1?'checked':'' ?>><label class="form-check-label">Mostrar logo</label></div>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="active" value="1" <?= (int)$tpl->active===1?'checked':'' ?>><label class="form-check-label">Activa</label></div>
            <?php if (!empty($response['canManage'])): ?>
            <button class="btn btn-primary btn-sm">Guardar</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>
<?php $this->endSection(); ?>
<?php $this->section('scripts'); ?>
<script>
const csrfName = '<?= csrf_token() ?>';
function csrfVal(){ return document.getElementById('csrf_token').value; }
function csrfHdr(h){ const n=h.get('<?= csrf_header() ?>'); if(n) document.getElementById('csrf_token').value=n; }
function ajaxHeaders(){ return {'X-Requested-With':'XMLHttpRequest','<?= csrf_header() ?>': csrfVal()}; }
document.querySelectorAll('.tpl-form').forEach(form=>{
  form.addEventListener('submit', async ev=>{
    ev.preventDefault();
    const fd = new FormData(form);
    fd.append(csrfName, csrfVal());
    if(!form.querySelector('[name=show_logo]').checked) fd.set('show_logo','');
    if(!form.querySelector('[name=active]').checked) fd.set('active','');
    const r = await fetch('<?= site_url('nat/organization/sales-templates/update') ?>/' + form.dataset.id, {method:'POST', headers: ajaxHeaders(), body: fd});
    csrfHdr(r.headers);
    const j = await r.json();
    notifyShow(j.message, j.success?'success':'danger');
  });
});
</script>
<?php $this->endSection(); ?>
