<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($pageTitle ?? 'Notas internas') ?>
<?php $this->endSection(); ?>

<?php $this->section('pageStyles'); ?>
<style>
    .ni-wrap { max-width: 1100px; }
    .ni-composer, .ni-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
    }
    .ni-composer { background: #fff9c4; }
    .ni-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1rem;
    }
    .ni-card { min-height: 160px; cursor: pointer; }
    .ni-card .ni-body { white-space: pre-wrap; word-break: break-word; }
    .ni-editor {
        min-height: 4.5rem;
        outline: none;
    }
    .ni-editor:empty:before {
        content: attr(data-placeholder);
        color: #6c757d;
    }
    .ni-color {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 50%;
        border: 2px solid transparent;
        padding: 0;
    }
    .ni-color.active { border-color: #343a40; }
    .ni-toolbar .btn { padding: .15rem .45rem; }
</style>
<?php $this->endSection(); ?>

<?php $this->section('main'); ?>
<?php
$noteRoutes = $noteRoutes ?? [];
$noteColors = $noteColors ?? ['#fff9c4'];
?>
<div class="container-fluid p-0 ni-wrap">
    <div class="row mb-3">
        <div class="col-12">
            <?= $breadcrumb ?? '' ?>
            <h1 class="h3 mb-1"><i class="fas fa-sticky-note me-2"></i><?= esc($pageTitle ?? 'Notas internas') ?></h1>
            <p class="text-muted mb-0">Notas personales de tu usuario. Negrita, cursiva y subrayado.</p>
        </div>
    </div>

    <div class="card ni-composer mb-4" id="ni-composer">
        <div class="card-body">
            <input type="hidden" id="ni-id" value="">
            <input type="text" id="ni-title" class="form-control border-0 bg-transparent fw-semibold px-0 mb-2" maxlength="150" placeholder="Título">
            <div class="ni-toolbar btn-group btn-group-sm mb-2" role="group" aria-label="Formato">
                <button type="button" class="btn btn-light" data-cmd="bold" title="Negrita"><i class="fas fa-bold"></i></button>
                <button type="button" class="btn btn-light" data-cmd="italic" title="Cursiva"><i class="fas fa-italic"></i></button>
                <button type="button" class="btn btn-light" data-cmd="underline" title="Subrayado"><i class="fas fa-underline"></i></button>
            </div>
            <div id="ni-body" class="ni-editor" contenteditable="true" data-placeholder="Escribe una nota…"></div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <div class="d-flex align-items-center gap-2">
                    <?php foreach ($noteColors as $color): ?>
                        <button type="button"
                                class="ni-color<?= $color === '#fff9c4' ? ' active' : '' ?>"
                                data-color="<?= esc($color, 'attr') ?>"
                                style="background: <?= esc($color, 'attr') ?>"
                                aria-label="Color <?= esc($color, 'attr') ?>"></button>
                    <?php endforeach; ?>
                    <div class="form-check mb-0 ms-2">
                        <input class="form-check-input" type="checkbox" id="ni-pin">
                        <label class="form-check-label small" for="ni-pin">Fijar</label>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light" id="ni-clear">Nueva</button>
                    <button type="button" class="btn btn-sm btn-primary" id="ni-save">Guardar</button>
                </div>
            </div>
            <p class="small text-danger mb-0 mt-2 d-none" id="ni-error"></p>
        </div>
    </div>

    <div id="ni-grid" class="ni-grid" aria-live="polite"></div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function () {
    const routes = <?= json_encode($noteRoutes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const tokenName = <?= json_encode(csrf_token()) ?>;
    const headerName = <?= json_encode(csrf_header()) ?>;
    const composer = document.getElementById('ni-composer');
    const titleEl = document.getElementById('ni-title');
    const bodyEl = document.getElementById('ni-body');
    const idEl = document.getElementById('ni-id');
    const pinEl = document.getElementById('ni-pin');
    const errorEl = document.getElementById('ni-error');
    const grid = document.getElementById('ni-grid');
    let color = '#fff9c4';

    function tokenEl() {
        return document.getElementById('csrf_token');
    }

    function showError(message) {
        errorEl.textContent = message || '';
        errorEl.classList.toggle('d-none', !message);
    }

    function applyColor(next) {
        color = next || '#fff9c4';
        composer.style.background = color;
        document.querySelectorAll('.ni-color').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-color') === color);
        });
    }

    function clearForm() {
        idEl.value = '';
        titleEl.value = '';
        bodyEl.innerHTML = '';
        pinEl.checked = false;
        applyColor('#fff9c4');
        showError('');
        titleEl.focus();
    }

    function fillForm(note) {
        idEl.value = String(note.id || '');
        titleEl.value = note.title || '';
        bodyEl.innerHTML = note.body || '';
        pinEl.checked = !!note.is_pinned;
        applyColor(note.color || '#fff9c4');
        showError('');
        titleEl.focus();
    }

    function post(url, fields) {
        const el = tokenEl();
        const body = new URLSearchParams(fields);
        if (el) {
            body.set(tokenName, el.value);
        }
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        };
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
            return data;
        });
    }

    function render(notes) {
        grid.innerHTML = '';
        if (!notes || notes.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'text-muted';
            empty.textContent = 'Todavía no hay notas. Escribe la primera arriba.';
            grid.appendChild(empty);
            return;
        }
        notes.forEach(function (note) {
            const card = document.createElement('article');
            card.className = 'card ni-card';
            card.style.background = note.color || '#fff9c4';

            const body = document.createElement('div');
            body.className = 'card-body';

            const heading = document.createElement('div');
            heading.className = 'd-flex justify-content-between align-items-start gap-2';
            const h = document.createElement('h2');
            h.className = 'h6 mb-2';
            h.textContent = note.title || 'Sin título';
            const pin = document.createElement('button');
            pin.type = 'button';
            pin.className = 'btn btn-sm btn-link p-0';
            pin.title = note.is_pinned ? 'Quitar fijada' : 'Fijar';
            pin.innerHTML = note.is_pinned ? '<i class="fas fa-thumbtack"></i>' : '<i class="fas fa-thumbtack text-muted"></i>';
            pin.addEventListener('click', function (event) {
                event.stopPropagation();
                saveNote(Object.assign({}, note, { is_pinned: !note.is_pinned }));
            });
            heading.appendChild(h);
            heading.appendChild(pin);

            const text = document.createElement('div');
            text.className = 'ni-body small mb-2';
            text.innerHTML = note.body || '';

            const foot = document.createElement('div');
            foot.className = 'd-flex justify-content-between align-items-center';
            const when = document.createElement('small');
            when.className = 'text-muted';
            when.textContent = note.updated_at || note.created_at || '';
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'btn btn-sm btn-link text-danger p-0';
            del.textContent = 'Eliminar';
            del.addEventListener('click', function (event) {
                event.stopPropagation();
                if (!window.confirm('¿Eliminar esta nota?')) {
                    return;
                }
                const url = String(routes.delete || '').replace(/\/0$/, '/' + note.id);
                post(url, {}).then(function (data) {
                    if (!data.success) {
                        showError(data.message || 'No se pudo eliminar.');
                        return;
                    }
                    if (String(idEl.value) === String(note.id)) {
                        clearForm();
                    }
                    load();
                }).catch(function () {
                    showError('No se pudo eliminar.');
                });
            });
            foot.appendChild(when);
            foot.appendChild(del);

            body.appendChild(heading);
            body.appendChild(text);
            body.appendChild(foot);
            card.appendChild(body);
            card.addEventListener('click', function () { fillForm(note); });
            grid.appendChild(card);
        });
    }

    function load() {
        post(routes.list, {}).then(function (data) {
            if (!data.success) {
                showError(data.message || 'No se pudieron cargar las notas.');
                return;
            }
            showError('');
            render(Array.isArray(data.response) ? data.response : []);
        }).catch(function () {
            showError('No se pudieron cargar las notas.');
        });
    }

    function saveNote(source) {
        const fields = {
            id: source.id || idEl.value || '',
            title: source.title != null ? source.title : titleEl.value,
            body: source.body != null ? source.body : bodyEl.innerHTML,
            color: source.color || color,
            is_pinned: source.is_pinned ? '1' : '0'
        };
        post(routes.save, fields).then(function (data) {
            if (!data.success) {
                showError(data.message || 'No se pudo guardar.');
                return;
            }
            showError('');
            if (!source.id && data.response) {
                fillForm(data.response);
            }
            load();
        }).catch(function () {
            showError('No se pudo guardar.');
        });
    }

    document.querySelectorAll('[data-cmd]').forEach(function (btn) {
        btn.addEventListener('mousedown', function (event) {
            event.preventDefault();
            document.execCommand(btn.getAttribute('data-cmd'), false, null);
            bodyEl.focus();
        });
    });
    document.querySelectorAll('.ni-color').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyColor(btn.getAttribute('data-color'));
        });
    });
    document.getElementById('ni-clear').addEventListener('click', clearForm);
    document.getElementById('ni-save').addEventListener('click', function () {
        saveNote({
            id: idEl.value,
            title: titleEl.value,
            body: bodyEl.innerHTML,
            color: color,
            is_pinned: pinEl.checked
        });
    });

    applyColor(color);
    load();
})();
</script>
<?php $this->endSection(); ?>
