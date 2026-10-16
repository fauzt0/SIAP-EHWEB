<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($pageTitle ?? 'Plugins') ?>
<?php $this->endSection(); ?>

<?php $this->section('main'); ?>
<?php
$installed = $installed ?? [];
$available = $available ?? [];
$backups   = $backups ?? [];
$settings  = $settings ?? null;
?>
<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <?= $breadcrumb ?? '' ?>
            <h1 class="h3 mb-1"><i class="fas fa-plug me-2"></i><?= esc($pageTitle ?? 'Plugins') ?></h1>
            <p class="text-muted mb-0">Instala, activa y retira plugins sin modificar el ERP. Desinstalar archiva los datos; purgar los elimina.</p>
        </div>
    </div>

    <?php if (is_array($settings)): ?>
        <?php
        $manifest = $settings['manifest'] ?? [];
        $values   = $settings['values'] ?? [];
        $secrets  = $settings['secrets'] ?? [];
        $defs     = is_array($manifest['settings'] ?? null) ? $manifest['settings'] : [];
        ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Ajustes de <?= esc($manifest['name'] ?? '') ?></h5>
            </div>
            <div class="card-body">
                <?php if ($defs === []): ?>
                    <p class="mb-0 text-muted">Este plugin no declara ajustes.</p>
                <?php else: ?>
                    <form id="plugin-settings-form">
                        <?php foreach ($defs as $key => $definition): ?>
                            <?php
                            if (! is_array($definition)) {
                                continue;
                            }
                            $type  = (string) ($definition['type'] ?? 'text');
                            $label = (string) ($definition['label'] ?? $key);
                            $value = $values[$key] ?? ($definition['default'] ?? '');
                            ?>
                            <div class="mb-3">
                                <label class="form-label" for="setting-<?= esc($key, 'attr') ?>"><?= esc($label) ?></label>
                                <?php if ($type === 'password'): ?>
                                    <input class="form-control" type="password" autocomplete="new-password"
                                           id="setting-<?= esc($key, 'attr') ?>" name="settings[<?= esc($key, 'attr') ?>]"
                                           placeholder="<?= ! empty($secrets[$key]) ? 'Configurado. Déjalo vacío para conservarlo.' : '' ?>">
                                <?php elseif ($type === 'number'): ?>
                                    <input class="form-control" type="number" id="setting-<?= esc($key, 'attr') ?>"
                                           name="settings[<?= esc($key, 'attr') ?>]" value="<?= esc((string) $value, 'attr') ?>">
                                <?php elseif ($type === 'select'): ?>
                                    <?php $options = is_array($definition['options'] ?? null) ? $definition['options'] : []; ?>
                                    <select class="form-select" id="setting-<?= esc($key, 'attr') ?>"
                                            name="settings[<?= esc($key, 'attr') ?>]">
                                        <?php foreach ($options as $optKey => $optLabel): ?>
                                            <?php
                                            $optValue = array_is_list($options) ? (string) $optLabel : (string) $optKey;
                                            $optText  = array_is_list($options) ? (string) $optLabel : (string) $optLabel;
                                            ?>
                                            <option value="<?= esc($optValue, 'attr') ?>" <?= (string) $value === $optValue ? 'selected' : '' ?>>
                                                <?= esc($optText) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input class="form-control" type="text" id="setting-<?= esc($key, 'attr') ?>"
                                           name="settings[<?= esc($key, 'attr') ?>]" value="<?= esc(is_scalar($value) || $value === null ? (string) $value : '', 'attr') ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary" data-url="<?= esc(route_to('admin.plugins.settings.save', $manifest['slug'] ?? ''), 'attr') ?>">
                            <i class="fas fa-save me-1"></i> Guardar
                        </button>
                        <a class="btn btn-link" href="<?= esc(route_to('admin.plugins.index'), 'attr') ?>">Volver al market</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <h2 class="h5">Instalados</h2>
            <?php if ($installed === []): ?>
                <div class="card mb-3"><div class="card-body text-muted">No hay plugins instalados.</div></div>
            <?php endif; ?>
            <?php foreach ($installed as $plugin): ?>
                <?php
                $icon   = (string) ($plugin['icon'] ?? 'fa-plug');
                $icon   = preg_match('/^fa-[a-z0-9-]+$/', $icon) === 1 ? $icon : 'fa-plug';
                $active = ($plugin['status'] ?? '') === 'active';
                ?>
                <div class="card mb-3">
                    <div class="card-body d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <h3 class="h5 mb-1"><i class="fas <?= esc($icon, 'attr') ?> me-2"></i><?= esc($plugin['name'] ?? '') ?>
                                <small class="text-muted">v<?= esc($plugin['version'] ?? '') ?></small>
                            </h3>
                            <p class="mb-1 text-muted"><?= esc($plugin['description'] ?? '') ?></p>
                            <span class="badge <?= $active ? 'bg-success' : 'bg-secondary' ?>"><?= $active ? 'Activo' : esc((string) ($plugin['status'] ?? 'inactive')) ?></span>
                        </div>
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <a class="btn btn-outline-secondary btn-sm" href="<?= esc(route_to('admin.plugins.settings', $plugin['slug']), 'attr') ?>">
                                <i class="fas fa-cog"></i>
                            </a>
                            <button type="button" class="btn btn-outline-primary btn-sm js-plugin-post"
                                    data-url="<?= esc(route_to('admin.plugins.update'), 'attr') ?>"
                                    data-slug="<?= esc($plugin['slug'], 'attr') ?>">Actualizar</button>
                            <?php if ($active): ?>
                                <button type="button" class="btn btn-outline-warning btn-sm js-plugin-post"
                                        data-url="<?= esc(route_to('admin.plugins.deactivate'), 'attr') ?>"
                                        data-slug="<?= esc($plugin['slug'], 'attr') ?>">Desactivar</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-success btn-sm js-plugin-post"
                                        data-url="<?= esc(route_to('admin.plugins.activate'), 'attr') ?>"
                                        data-slug="<?= esc($plugin['slug'], 'attr') ?>">Activar</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalUninstall"
                                        data-slug="<?= esc($plugin['slug'], 'attr') ?>" data-name="<?= esc($plugin['name'] ?? '', 'attr') ?>">Desinstalar</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <h2 class="h5 mt-4">Disponibles en disco</h2>
            <?php if ($available === []): ?>
                <div class="card mb-3"><div class="card-body text-muted">No hay plugins pendientes de instalar.</div></div>
            <?php endif; ?>
            <?php foreach ($available as $plugin): ?>
                <?php
                $icon = (string) ($plugin['icon'] ?? 'fa-plug');
                $icon = preg_match('/^fa-[a-z0-9-]+$/', $icon) === 1 ? $icon : 'fa-plug';
                ?>
                <div class="card mb-3">
                    <div class="card-body d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <h3 class="h5 mb-1"><i class="fas <?= esc($icon, 'attr') ?> me-2"></i><?= esc($plugin['name'] ?? '') ?>
                                <small class="text-muted">v<?= esc((string) ($plugin['version'] ?? '')) ?></small>
                            </h3>
                            <p class="mb-1 text-muted"><?= esc($plugin['description'] ?? '') ?></p>
                            <?php if (! empty($plugin['error'])): ?>
                                <span class="badge bg-warning text-dark"><?= esc($plugin['error']) ?></span>
                            <?php elseif (($plugin['source'] ?? '') === 'remote'): ?>
                                <span class="badge bg-info">Catálogo remoto. Copia los archivos antes de instalar.</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark">No instalado</span>
                            <?php endif; ?>
                        </div>
                        <?php if (! empty($plugin['installable'])): ?>
                            <button type="button" class="btn btn-primary btn-sm js-plugin-post"
                                    data-url="<?= esc(route_to('admin.plugins.install'), 'attr') ?>"
                                    data-slug="<?= esc($plugin['slug'] ?? '', 'attr') ?>">
                                <i class="fas fa-plus me-1"></i> Instalar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-4">
            <h2 class="h5">Respaldos</h2>
            <?php if ($backups === []): ?>
                <div class="card"><div class="card-body text-muted">No hay respaldos recuperables.</div></div>
            <?php endif; ?>
            <?php foreach ($backups as $backup): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h3 class="h6 mb-1"><?= esc($backup['plugin_name'] ?? '') ?></h3>
                        <p class="small text-muted mb-2">
                            <?= esc($backup['uninstalled_at'] ?? '') ?>
                            <?php if (! empty($backup['restored_at'])): ?>
                                · restaurado
                            <?php endif; ?>
                        </p>
                        <?php if (empty($backup['restored_at'])): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm js-plugin-post"
                                    data-url="<?= esc(route_to('admin.plugins.restore'), 'attr') ?>"
                                    data-backup="<?= (int) $backup['id'] ?>">Restaurar</button>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalPurge"
                                    data-backup="<?= (int) $backup['id'] ?>" data-name="<?= esc($backup['plugin_name'] ?? '', 'attr') ?>">Purgar</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUninstall" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Desinstalar plugin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Se archivarán las tablas de <strong id="uninstall-name"></strong>. Podrás restaurarlas después.</p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="uninstall-confirm">
                    <label class="form-check-label" for="uninstall-confirm">Entiendo que el plugin dejará de estar instalado.</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="uninstall-go" disabled
                        data-url="<?= esc(route_to('admin.plugins.uninstall'), 'attr') ?>">Desinstalar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPurge" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Purgar datos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Esta acción eliminará permanentemente los datos archivados de <strong id="purge-name"></strong>.</p>
                <label class="form-label" for="purge-text">Escribe ELIMINAR para confirmar.</label>
                <input class="form-control" id="purge-text" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="purge-go" disabled
                        data-url="<?= esc(route_to('admin.plugins.purge'), 'attr') ?>">Purgar</button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function () {
    const tokenName = <?= json_encode(csrf_token()) ?>;
    const headerName = <?= json_encode(csrf_header()) ?>;

    function tokenEl() {
        return document.getElementById('csrf_token');
    }

    function pluginPost(url, fields) {
        const el = tokenEl();
        const body = new URLSearchParams(fields);
        if (el) {
            body.set(tokenName, el.value);
        }
        const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' };
        if (el) {
            headers[headerName] = el.value;
        }
        return fetch(url, { method: 'POST', headers: headers, body: body }).then(async function (response) {
            const next = response.headers.get(headerName);
            if (next && el) {
                el.value = next;
            }
            const data = await response.json().catch(function () { return {}; });
            if (data.csrf && el) {
                el.value = data.csrf;
            }
            if (!response.ok || data.success === false) {
                if (typeof notifyShow === 'function') {
                    notifyShow(data.message || 'No se pudo completar la operación.', 'danger');
                }
                return;
            }
            if (typeof notifyShow === 'function') {
                notifyShow(data.message || 'Listo.', 'success');
            }
            window.location.reload();
        }).catch(function () {
            if (typeof notifyShow === 'function') {
                notifyShow('Error de conexión.', 'danger');
            }
        });
    }

    document.querySelectorAll('.js-plugin-post').forEach(function (button) {
        button.addEventListener('click', function () {
            const fields = {};
            if (button.dataset.slug) {
                fields.slug = button.dataset.slug;
            }
            if (button.dataset.backup) {
                fields.backup_id = button.dataset.backup;
            }
            pluginPost(button.dataset.url, fields);
        });
    });

    const uninstallModal = document.getElementById('modalUninstall');
    if (uninstallModal) {
        uninstallModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            uninstallModal.dataset.slug = trigger ? trigger.dataset.slug : '';
            const name = document.getElementById('uninstall-name');
            if (name) {
                name.textContent = trigger ? trigger.dataset.name : '';
            }
            const check = document.getElementById('uninstall-confirm');
            const go = document.getElementById('uninstall-go');
            if (check) {
                check.checked = false;
            }
            if (go) {
                go.disabled = true;
            }
        });
    }

    const uninstallCheck = document.getElementById('uninstall-confirm');
    const uninstallGo = document.getElementById('uninstall-go');
    if (uninstallCheck && uninstallGo) {
        uninstallCheck.addEventListener('change', function () {
            uninstallGo.disabled = !uninstallCheck.checked;
        });
        uninstallGo.addEventListener('click', function () {
            pluginPost(uninstallGo.dataset.url, {
                slug: uninstallModal.dataset.slug || '',
                confirm: '1'
            });
        });
    }

    const purgeModal = document.getElementById('modalPurge');
    const purgeText = document.getElementById('purge-text');
    const purgeGo = document.getElementById('purge-go');
    if (purgeModal) {
        purgeModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            purgeModal.dataset.backup = trigger ? trigger.dataset.backup : '';
            const name = document.getElementById('purge-name');
            if (name) {
                name.textContent = trigger ? trigger.dataset.name : '';
            }
            if (purgeText) {
                purgeText.value = '';
            }
            if (purgeGo) {
                purgeGo.disabled = true;
            }
        });
    }
    if (purgeText && purgeGo) {
        purgeText.addEventListener('input', function () {
            purgeGo.disabled = purgeText.value !== 'ELIMINAR';
        });
        purgeGo.addEventListener('click', function () {
            pluginPost(purgeGo.dataset.url, {
                backup_id: purgeModal.dataset.backup || '',
                confirmation: purgeText.value
            });
        });
    }

    const settingsForm = document.getElementById('plugin-settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const button = settingsForm.querySelector('button[type="submit"]');
            const data = new FormData(settingsForm);
            const fields = {};
            data.forEach(function (value, key) {
                fields[key] = value;
            });
            pluginPost(button.dataset.url, fields);
        });
    }
})();
</script>
<?php $this->endSection(); ?>
