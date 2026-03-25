<?php $this->extend($layout) ?>

<?php $this->section('title') ?>
  <?= esc($pageTitle) ?>
<?php $this->endSection() ?>

<?php $this->section('main') ?>
<main class="content">
  <div class="container-fluid p-0">

    <div class="row mb-2 mb-xl-3">
      <div class="col-auto d-none d-sm-block">
        <h3><strong><?= esc($headTitle) ?></strong></h3>
      </div>
    </div>

    <div class="row">
      <!-- Roles -->
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="card-title mb-0">Grupos de Usuarios (Roles)</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>Rol (Key)</th>
                    <th>Título</th>
                    <th>Descripción</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($response['groups'] as $key => $group): ?>
                  <tr>
                    <td><span class="badge bg-primary"><?= esc($key) ?></span></td>
                    <td><strong><?= esc($group['title']) ?></strong></td>
                    <td><small><?= esc($group['description']) ?></small></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Permisos -->
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="card-title mb-0">Permisos del Sistema</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>Permiso</th>
                    <th>Descripción</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($response['permissions'] as $key => $desc): ?>
                  <tr>
                    <td><code><?= esc($key) ?></code></td>
                    <td><small><?= esc($desc) ?></small></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Matriz de permisos -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Matriz de Permisos por Defecto</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-sm">
                <thead class="table-light">
                  <tr>
                    <th style="width: 20%;">Rol</th>
                    <th>Permisos Asignados</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($response['matrix'] as $role => $rolePermissions): ?>
                  <tr>
                    <td class="text-nowrap"><span class="badge bg-primary"><?= esc($role) ?></span></td>
                    <td>
                      <?php foreach ($rolePermissions as $p): ?>
                        <span class="badge bg-secondary me-1 mb-1"><?= esc($p) ?></span>
                      <?php endforeach; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php $this->endSection() ?>
