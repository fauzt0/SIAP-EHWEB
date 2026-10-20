<?php
$listUrl  = $niListUrl ?? '';
$saveUrl  = $niSaveUrl ?? '';
$indexUrl = $niIndexUrl ?? '';
$homePath = $niHomePath ?? '/nat';
$token    = $niToken ?? '';
$header   = $niHeader ?? 'X-CSRF-TOKEN';
?>
<style>
    #ni-quick .ni-quick-menu { width: 22rem; max-width: 90vw; }
    #ni-quick .ni-quick-list { max-height: 22rem; overflow: auto; }
    #ni-quick .ni-quick-row { cursor: pointer; border: 0; border-bottom: 1px solid rgba(0,0,0,.06); }
    #ni-quick .ni-quick-row:hover { filter: brightness(.97); }
    #ni-dash .ni-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1rem;
    }
    #ni-dash .ni-card { border: 0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.12); min-height: 140px; cursor: pointer; }
    #ni-dash .ni-body, #ni-quick-body { white-space: pre-wrap; word-break: break-word; }
</style>
<li class="nav-item dropdown" id="ni-quick">
    <a class="nav-icon dropdown-toggle" href="#" id="niQuickToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Notas internas">
        <div class="position-relative">
            <i class="fas fa-sticky-note align-middle text-body"></i>
            <span class="indicator" id="ni-quick-count" style="display:none">0</span>
        </div>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0 ni-quick-menu" aria-labelledby="niQuickToggle">
        <div class="dropdown-menu-header">Notas internas</div>
        <div class="list-group list-group-flush ni-quick-list" id="ni-quick-list">
            <div class="text-center text-muted small py-3">Cargando…</div>
        </div>
        <div class="dropdown-menu-footer">
            <a href="<?= esc($indexUrl, 'attr') ?>" class="text-muted small">Abrir Notas internas</a>
        </div>
    </div>
</li>
<div class="modal fade" id="ni-quick-modal" tabindex="-1" aria-labelledby="ni-quick-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="ni-quick-sheet">
            <div class="modal-header">
                <h2 class="modal-title h5" id="ni-quick-title">Nota</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="ni-quick-body"></div>
                <small class="text-muted" id="ni-quick-when"></small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="ni-quick-pin">Fijar en el inicio</button>
                <a class="btn btn-primary" href="<?= esc($indexUrl, 'attr') ?>">Editar en Notas internas</a>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const listUrl = <?= json_encode($listUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const saveUrl = <?= json_encode($saveUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const homePath = <?= json_encode($homePath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const tokenName = <?= json_encode($token) ?>;
    const headerName = <?= json_encode($header) ?>;
    const quick = document.getElementById('ni-quick');
    const modalEl = document.getElementById('ni-quick-modal');
    const slot = document.querySelector('.navbar-nav.navbar-align');
    if (slot && quick) {
        slot.insertBefore(quick, slot.firstChild);
    }
    if (modalEl) {
        document.body.appendChild(modalEl);
    }

    const listEl = document.getElementById('ni-quick-list');
    const countEl = document.getElementById('ni-quick-count');
    const titleEl = document.getElementById('ni-quick-title');
    const bodyEl = document.getElementById('ni-quick-body');
    const whenEl = document.getElementById('ni-quick-when');
    const sheetEl = document.getElementById('ni-quick-sheet');
    const pinBtn = document.getElementById('ni-quick-pin');
    let notes = [];
    let openId = 0;

    function tokenEl() {
        return document.getElementById('csrf_token');
    }

    function post(url, fields) {
        window.niPostQueue = window.niPostQueue || Promise.resolve();
        const run = function () {
            return postNow(url, fields);
        };
        const queued = window.niPostQueue.then(run, run);
        window.niPostQueue = queued.then(function () {}, function () {});
        return queued;
    }

    function postNow(url, fields) {
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
        return fetch(url, { method: 'POST', credentials: 'same-origin', headers: headers, body: body }).then(async function (response) {
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

    function findNote(id) {
        return notes.find(function (note) { return Number(note.id) === Number(id); }) || null;
    }

    function paintPin(button, pinned) {
        button.title = pinned ? 'Quitar del inicio' : 'Fijar en el inicio';
        button.innerHTML = pinned
            ? '<i class="fas fa-thumbtack"></i>'
            : '<i class="fas fa-thumbtack text-muted"></i>';
    }

    function renderMenu() {
        if (!listEl) {
            return;
        }
        listEl.innerHTML = '';
        if (countEl) {
            countEl.textContent = String(notes.length);
            countEl.style.display = notes.length > 0 ? '' : 'none';
        }
        if (notes.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'text-center text-muted small py-3';
            empty.textContent = 'Todavía no hay notas.';
            listEl.appendChild(empty);
            return;
        }
        notes.forEach(function (note) {
            const row = document.createElement('div');
            row.className = 'list-group-item ni-quick-row d-flex align-items-start gap-2';
            row.style.background = note.color || '#fff9c4';

            const open = document.createElement('button');
            open.type = 'button';
            open.className = 'btn btn-link text-start text-body flex-grow-1 p-0 text-decoration-none';
            const heading = document.createElement('div');
            heading.className = 'fw-semibold';
            heading.textContent = note.title || 'Sin título';
            const when = document.createElement('div');
            when.className = 'small text-muted';
            when.textContent = note.updated_at || note.created_at || '';
            open.appendChild(heading);
            open.appendChild(when);
            open.addEventListener('click', function () { showNote(note.id); });

            const pin = document.createElement('button');
            pin.type = 'button';
            pin.className = 'btn btn-sm btn-link p-0';
            paintPin(pin, !!note.is_pinned);
            pin.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                togglePin(note.id);
            });

            row.appendChild(open);
            row.appendChild(pin);
            listEl.appendChild(row);
        });
    }

    function onHome() {
        const here = String(location.pathname || '').replace(/\/+$/, '') || '/';
        const home = String(homePath || '').replace(/\/+$/, '') || '/';
        return here === home;
    }

    function renderDash() {
        if (!onHome()) {
            return;
        }
        const host = document.querySelector('main.content .container-fluid');
        if (!host) {
            return;
        }
        let board = document.getElementById('ni-dash');
        const pinned = notes.filter(function (note) { return !!note.is_pinned; });
        if (pinned.length === 0) {
            if (board) {
                board.remove();
            }
            return;
        }
        if (!board) {
            board = document.createElement('section');
            board.id = 'ni-dash';
            board.className = 'mb-4';
            host.prepend(board);
        }
        board.innerHTML = '';
        const head = document.createElement('div');
        head.className = 'd-flex justify-content-between align-items-center mb-3';
        const title = document.createElement('h2');
        title.className = 'h4 mb-0';
        title.textContent = 'Notas fijadas';
        const hint = document.createElement('span');
        hint.className = 'text-muted small';
        hint.textContent = 'Quedan en el inicio mientras estén fijadas.';
        head.appendChild(title);
        head.appendChild(hint);

        const grid = document.createElement('div');
        grid.className = 'ni-grid';
        pinned.forEach(function (note) {
            const card = document.createElement('article');
            card.className = 'card ni-card';
            card.style.background = note.color || '#fff9c4';
            const body = document.createElement('div');
            body.className = 'card-body';
            const row = document.createElement('div');
            row.className = 'd-flex justify-content-between align-items-start gap-2';
            const h = document.createElement('h3');
            h.className = 'h6 mb-2';
            h.textContent = note.title || 'Sin título';
            const pin = document.createElement('button');
            pin.type = 'button';
            pin.className = 'btn btn-sm btn-link p-0';
            paintPin(pin, true);
            pin.addEventListener('click', function (event) {
                event.stopPropagation();
                togglePin(note.id);
            });
            row.appendChild(h);
            row.appendChild(pin);
            const text = document.createElement('div');
            text.className = 'ni-body small mb-2';
            text.innerHTML = note.body || '';
            const when = document.createElement('small');
            when.className = 'text-muted';
            when.textContent = note.updated_at || note.created_at || '';
            body.appendChild(row);
            body.appendChild(text);
            body.appendChild(when);
            card.appendChild(body);
            card.addEventListener('click', function () { showNote(note.id); });
            grid.appendChild(card);
        });
        board.appendChild(head);
        board.appendChild(grid);
    }

    function showNote(id) {
        const note = findNote(id);
        if (!note || !modalEl || !window.bootstrap) {
            return;
        }
        openId = Number(note.id);
        if (titleEl) {
            titleEl.textContent = note.title || 'Sin título';
        }
        if (bodyEl) {
            bodyEl.innerHTML = note.body || '';
        }
        if (whenEl) {
            whenEl.textContent = note.updated_at || note.created_at || '';
        }
        if (sheetEl) {
            sheetEl.style.background = note.color || '#fff9c4';
        }
        if (pinBtn) {
            pinBtn.textContent = note.is_pinned ? 'Quitar del inicio' : 'Fijar en el inicio';
        }
        const toggle = document.getElementById('niQuickToggle');
        if (toggle && window.bootstrap.Dropdown) {
            const menu = window.bootstrap.Dropdown.getInstance(toggle);
            if (menu) {
                menu.hide();
            }
        }
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function togglePin(id) {
        const note = findNote(id);
        if (!note) {
            return;
        }
        post(saveUrl, {
            id: String(note.id),
            title: note.title || '',
            body: note.body || '',
            color: note.color || '#fff9c4',
            is_pinned: note.is_pinned ? '0' : '1'
        }).then(function (data) {
            if (!data.success) {
                return;
            }
            document.dispatchEvent(new CustomEvent('ni-notes-changed'));
        });
    }

    function load() {
        post(listUrl, {}).then(function (data) {
            notes = data.success && Array.isArray(data.response) ? data.response : [];
            renderMenu();
            renderDash();
            if (openId && pinBtn) {
                const current = findNote(openId);
                if (current) {
                    pinBtn.textContent = current.is_pinned ? 'Quitar del inicio' : 'Fijar en el inicio';
                    if (sheetEl) {
                        sheetEl.style.background = current.color || '#fff9c4';
                    }
                }
            }
        }).catch(function () {
            if (listEl) {
                listEl.innerHTML = '';
                const fail = document.createElement('div');
                fail.className = 'text-center text-danger small py-3';
                fail.textContent = 'No se pudieron cargar las notas.';
                listEl.appendChild(fail);
            }
        });
    }

    if (pinBtn) {
        pinBtn.addEventListener('click', function () {
            if (openId) {
                togglePin(openId);
            }
        });
    }

    document.addEventListener('ni-notes-changed', load);
    load();
})();
</script>
