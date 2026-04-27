<!-- ============================================================ -->
<!-- Offcanvas: Perfil del Trabajador                             -->
<!-- Partial incluido desde main_workers.php                      -->
<!-- ============================================================ -->
<div class="offcanvas offcanvas-end" style="width: min(580px, 95vw);" tabindex="-1" id="offcanvasWorker"
    aria-labelledby="offcanvasWorkerLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="offcanvasWorkerLabel">
            <i class="fas fa-id-badge me-2 text-primary"></i>Perfil del Trabajador
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body p-0" id="offcanvasWorkerBody">

        <!-- Spinner de carga -->
        <div id="oc-worker-loading" class="text-center py-5 px-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-2">Cargando perfil del trabajador…</p>
        </div>

        <!-- Contenido dinámico -->
        <div id="oc-worker-content" style="display:none;">

            <!-- ── Encabezado: Avatar + Nombre + Cargo ── -->
            <div class="px-4 py-3 border-bottom" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="d-flex align-items-center gap-3">
                    <img id="oc-w-avatar" src="" width="72" height="72"
                        class="rounded-circle border border-2 border-white shadow-sm flex-shrink-0"
                        style="object-fit: cover;" alt="Avatar">
                    <div class="flex-grow-1 min-width-0">
                        <h5 id="oc-w-fullname" class="mb-1 text-truncate"></h5>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            <span id="oc-w-job-badge" class="badge bg-primary small"></span>
                            <span id="oc-w-dept-badge" class="badge bg-secondary small"></span>
                            <span id="oc-w-status-badge" class="badge ms-1 small"></span>
                        </div>
                        <div class="text-muted small mt-1">
                            <i class="fas fa-hashtag fa-fw"></i>
                            <span id="oc-w-employee-number">—</span>
                            <span class="mx-1">·</span>
                            <i class="fas fa-building fa-fw"></i>
                            <span id="oc-w-location-badge">—</span>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción principales -->
                <div class="d-flex gap-2 mt-3 flex-wrap" id="oc-w-actions">
                    <!-- Rellenado dinámicamente por JS -->
                </div>
            </div>

            <!-- ── Cards de Indicadores ── -->
            <div class="px-4 py-3 border-bottom bg-light">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="card card-body py-2 px-1 h-100 border-0 shadow-sm">
                            <div class="fs-4 fw-bold text-success" id="oc-w-vacation-days">—</div>
                            <div class="small text-muted">Días Vacac.</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card card-body py-2 px-1 h-100 border-0 shadow-sm">
                            <div class="fs-4 fw-bold text-warning" id="oc-w-seniority">—</div>
                            <div class="small text-muted">Antigüedad</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card card-body py-2 px-1 h-100 border-0 shadow-sm">
                            <div class="fs-4 fw-bold text-info" id="oc-w-contracts-count">—</div>
                            <div class="small text-muted">Contratos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Tabs del Perfil ── -->
            <ul class="nav nav-tabs px-3 pt-3" id="workerOffcanvasTabs" role="tablist" style="border-bottom: 0;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="oc-tab-personal-tab" data-bs-toggle="tab"
                        data-bs-target="#oc-tab-personal" type="button" role="tab">
                        <i class="fas fa-user fa-fw me-1"></i>Personal
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="oc-tab-laboral-tab" data-bs-toggle="tab"
                        data-bs-target="#oc-tab-laboral" type="button" role="tab">
                        <i class="fas fa-briefcase fa-fw me-1"></i>Laboral
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="oc-tab-contratos-tab" data-bs-toggle="tab"
                        data-bs-target="#oc-tab-contratos" type="button" role="tab">
                        <i class="fas fa-file-contract fa-fw me-1"></i>Contratos
                    </button>
                </li>
            </ul>

            <div class="tab-content px-4 py-3" id="workerOffcanvasTabContent">

                <!-- ── Tab: Datos Personales ── -->
                <div class="tab-pane fade show active" id="oc-tab-personal" role="tabpanel">
                    <table class="table table-sm table-borderless">
                        <tbody>
                            <tr>
                                <td class="text-muted ps-0" style="width:38%"><i
                                        class="fas fa-id-card fa-fw me-1"></i>CURP</td>
                                <td id="oc-w-curp" class="font-monospace small fw-semibold"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-receipt fa-fw me-1"></i>RFC</td>
                                <td id="oc-w-rfc" class="font-monospace small fw-semibold"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-hospital fa-fw me-1"></i>NSS</td>
                                <td id="oc-w-nss"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-birthday-cake fa-fw me-1"></i>Fecha Nac.
                                </td>
                                <td id="oc-w-birth-date"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-venus-mars fa-fw me-1"></i>Género</td>
                                <td id="oc-w-gender"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-heart fa-fw me-1"></i>E. Civil</td>
                                <td id="oc-w-marital"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-phone fa-fw me-1"></i>Teléfono</td>
                                <td id="oc-w-phone"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-envelope fa-fw me-1"></i>Email Personal
                                </td>
                                <td id="oc-w-personal-email" class="small"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-first-aid fa-fw me-1"></i>Emergencias</td>
                                <td id="oc-w-emergency" class="small"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ── Tab: Datos Laborales ── -->
                <div class="tab-pane fade" id="oc-tab-laboral" role="tabpanel">
                    <table class="table table-sm table-borderless">
                        <tbody>
                            <tr>
                                <td class="text-muted ps-0" style="width:38%"><i
                                        class="fas fa-calendar-plus fa-fw me-1"></i>Fecha Ingreso</td>
                                <td id="oc-w-hiring-date" class="fw-semibold"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-user-tag fa-fw me-1"></i>Tipo</td>
                                <td id="oc-w-worker-type"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-user-tie fa-fw me-1"></i>Jefe Directo</td>
                                <td id="oc-w-manager"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-envelope-open fa-fw me-1"></i>Email Corp.
                                </td>
                                <td id="oc-w-corporate-email" class="small"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-dollar-sign fa-fw me-1"></i>Salario Mensual
                                </td>
                                <td id="oc-w-salary" class="fw-semibold text-success"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-calendar-day fa-fw me-1"></i>Salario Diario
                                </td>
                                <td id="oc-w-daily-salary"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0"><i class="fas fa-info-circle fa-fw me-1"></i>Estatus</td>
                                <td id="oc-w-status-text"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ── Tab: Contratos ── -->
                <div class="tab-pane fade" id="oc-tab-contratos" role="tabpanel">

                    <!-- Botones de gestión de contratos -->
                    <div class="d-flex gap-2 mb-3 flex-wrap">
                        <?php if (auth()->user()->can('hr.create')): ?>
                            <a href="#" class="btn btn-sm btn-outline-primary" id="oc-btn-new-contract-link">
                                <i class="fas fa-file-medical me-1"></i>Nuevo Contrato
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-secondary" id="oc-btn-finiquito" disabled>
                            <i class="fas fa-calculator me-1"></i>Calcular Finiquito
                        </button>
                    </div>

                    <!-- Buscador / Filtro por Fechas -->
                    <div class="row gx-2 mb-3 align-items-end" id="oc-contracts-filter" style="display: none;">
                        <div class="col-5">
                            <input type="date" id="oc-contract-start" class="form-control form-control-sm">
                        </div>
                        <div class="col-5">
                            <input type="date" id="oc-contract-end" class="form-control form-control-sm">
                        </div>
                        <div class="col-2 d-grid">
                            <button class="btn btn-sm btn-outline-primary" id="oc-btn-filter-contracts">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <div id="oc-contracts-footer" class="mt-3 text-center mb-4" style="display: none;">
                        <a href="#" id="oc-btn-full-history" class="btn btn-sm btn-light border w-100 py-2">
                            <i class="fas fa-history me-1"></i>Ver Historial Completo
                        </a>
                    </div>

                    <!-- Árbol cronológico de contratos -->
                    <div id="oc-contracts-timeline" class="position-relative ps-3"
                        style="border-left: 2px solid #e9ecef; margin-left: 10px;">
                        <div class="text-center py-4 text-muted" id="oc-contracts-empty" style="margin-left: -10px;">
                            <i class="fas fa-file-contract fa-2x mb-2 d-block"></i>
                            <span class="small">Cargando historial de contratos...</span>
                        </div>
                    </div>

                    </div>
                    
                    <!-- SECCIÓN: EXPEDIENTE DIGITAL -->
                    <div class="border-top pt-3 mt-3">
                        <p class="text-muted small fw-semibold text-uppercase mb-2">
                            <i class="fas fa-folder-open me-1"></i>Expediente Digital
                        </p>
                        <div id="oc-documents-list" class="list-group list-group-flush border rounded overflow-hidden">
                            <!-- Se puebla vía JS -->
                        </div>
                    </div>

                    <!-- Horario laboral (placeholder) -->
                    <div class="border-top pt-3 mt-3">
                        <p class="text-muted small fw-semibold text-uppercase mb-2">
                            <i class="fas fa-clock me-1"></i>Horario Laboral
                        </p>
                        <div class="text-muted small fst-italic" id="oc-w-schedule">
                            Sin horario asignado.
                        </div>
                    </div>

                    <!-- Incidencias (placeholder) -->
                    <div class="border-top pt-3 mt-3">
                        <p class="text-muted small fw-semibold text-uppercase mb-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>Últimas Incidencias
                        </p>
                        <div class="text-muted small fst-italic" id="oc-w-incidents">
                            Sin incidencias registradas.
                        </div>
                    </div>

                </div>

            </div><!-- /tab-content -->

        </div><!-- /oc-worker-content -->

    </div><!-- /offcanvas-body -->
</div>

<script>
    /**
     * loadWorkerOffcanvas(profileId)
     * Carga datos del trabajador vía AJAX y puebla el offcanvas.
     */
    function loadWorkerOffcanvas(profileId) {
        // Mostrar offcanvas y el spinner
        const offcanvasEl = document.getElementById('offcanvasWorker');
        const oc = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
        oc.show();

        document.getElementById('oc-worker-loading').style.display = 'block';
        document.getElementById('oc-worker-content').style.display = 'none';

        // Resetear a la primera pestaña
        const firstTab = document.getElementById('oc-tab-personal-tab');
        if (firstTab) bootstrap.Tab.getOrCreateInstance(firstTab).show();

        // Guardar el profileId en el offcanvas para uso de los botones internos
        offcanvasEl.dataset.profileId = profileId;

        fetch('<?= route_to('hr.worker.show_ajax', 0) ?>'.replace('/0', '/' + profileId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(resp => {
                if (!resp.success) {
                    document.getElementById('oc-worker-loading').innerHTML =
                        '<div class="alert alert-danger m-3">No se pudo cargar el perfil del trabajador.</div>';
                    return;
                }

                const w = resp.response;
                const st = w.status || 'active';

                // ── Cabecera ────────────────────────────────────────────────────────
                document.getElementById('oc-w-avatar').src = w.avatar || '<?= base_url('bootstrap/img/avatars/avatar.jpg') ?>';
                document.getElementById('oc-w-fullname').textContent = (w.first_name || '') + ' ' + (w.last_name || '');
                document.getElementById('oc-w-employee-number').textContent = w.employee_number || '—';
                document.getElementById('oc-w-location-badge').textContent = w.location_name || '—';

                // Badges puesto / depto / estatus
                document.getElementById('oc-w-job-badge').textContent = w.job_name || '';
                document.getElementById('oc-w-dept-badge').textContent = w.department_name || '';
                const statusColors = { active: 'success', on_leave: 'warning', terminated: 'danger', suspended: 'secondary' };
                const statusLabels = { active: 'Activo', on_leave: 'En Permiso', terminated: 'Baja', suspended: 'Suspendido' };
                document.getElementById('oc-w-status-badge').className = 'badge ms-1 small badge-subtle-' + (statusColors[st] || 'secondary');
                document.getElementById('oc-w-status-badge').textContent = statusLabels[st] || st;

                // ── Cards de indicadores ─────────────────────────────────────────────
                // Antigüedad en años desde fecha de ingreso
                let seniority = '—';
                if (w.hiring_date) {
                    const years = Math.floor((new Date() - new Date(w.hiring_date)) / (365.25 * 24 * 3600 * 1000));
                    seniority = years < 1 ? '< 1 año' : years + (years === 1 ? ' año' : ' años');
                }
                document.getElementById('oc-w-seniority').textContent = seniority;
                document.getElementById('oc-w-vacation-days').textContent = '—';   // Se completará con el módulo de vacaciones
                document.getElementById('oc-w-contracts-count').textContent = '—';   // Se completará con el módulo de contratos

                // ── Tab Personal ─────────────────────────────────────────────────────
                const genderMap = { M: 'Masculino', F: 'Femenino', O: 'Otro' };
                const maritalMap = { soltero: 'Soltero/a', casado: 'Casado/a', divorciado: 'Divorciado/a', viudo: 'Viudo/a', union_libre: 'Unión Libre' };

                document.getElementById('oc-w-curp').textContent = w.curp || '—';
                document.getElementById('oc-w-rfc').textContent = w.rfc || '—';
                document.getElementById('oc-w-nss').textContent = w.nss || '—';
                document.getElementById('oc-w-birth-date').textContent = w.birth_date || '—';
                document.getElementById('oc-w-gender').textContent = genderMap[w.gender] || w.gender || '—';
                document.getElementById('oc-w-marital').textContent = maritalMap[w.marital_status] || w.marital_status || '—';
                document.getElementById('oc-w-phone').textContent = w.phone_personal || '—';
                document.getElementById('oc-w-personal-email').textContent = w.personal_email || '—';
                document.getElementById('oc-w-emergency').textContent =
                    (w.emergency_contact_name || '—') +
                    (w.emergency_contact_phone ? ' · ' + w.emergency_contact_phone : '');

                // ── Tab Laboral ───────────────────────────────────────────────────────
                const workerTypeMap = { planta: 'De Planta', temporal: 'Temporal', proyecto: 'Por Proyecto', honorarios: 'Honorarios' };
                const fmt = v => v ? '$' + parseFloat(v).toLocaleString('es-MX', { minimumFractionDigits: 2 }) : '—';

                document.getElementById('oc-w-hiring-date').textContent = w.hiring_date || '—';
                document.getElementById('oc-w-worker-type').textContent = workerTypeMap[w.worker_type] || w.worker_type || '—';
                document.getElementById('oc-w-manager').textContent = w.manager_name || '—';
                document.getElementById('oc-w-corporate-email').textContent = w.corporate_email || '—';
                document.getElementById('oc-w-salary').textContent = fmt(w.current_salary);
                document.getElementById('oc-w-daily-salary').textContent = fmt(w.daily_salary);
                document.getElementById('oc-w-status-text').innerHTML =
                    '<span class="badge badge-subtle-' + (statusColors[st] || 'secondary') + '">' +
                    (statusLabels[st] || st) + '</span>';

                // ── Tab Contratos (Historial) ────────────────────────────────────────
                document.getElementById('oc-w-contracts-count').textContent = w.contracts_count || '0';

                const timelineEl = document.getElementById('oc-contracts-timeline');
                if (w.contracts && w.contracts.length > 0) {
                    let timelineHtml = '<div class="timeline timeline-sm mt-2">';
                    w.contracts.forEach(c => {
                        const date = new Date(c.created_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
                        timelineHtml += `
                    <div class="timeline-item">
                        <div class="timeline-point bg-primary"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-0 fw-bold">${c.contract_type_name || 'Contrato'}</h6>
                                    <small class="text-muted">${date} · ${c.reason || 'S/M'}</small>
                                </div>
                                <a href="<?= base_url('nat/hr/contracts/download') ?>/${c.id}" target="_blank" class="btn btn-sm btn-link text-primary p-0">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </a>
                            </div>
                            <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                Plantilla: ${c.template_name || 'N/A'}
                            </div>
                        </div>
                    </div>`;
                    });
                    timelineHtml += '</div>';
                    timelineEl.innerHTML = timelineHtml;
                } else {
                    timelineEl.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-file-contract fa-2x mb-2 d-block"></i>
                    <span class="small">No hay contratos registrados aún.</span>
                </div>`;
                }

                // ── Botones de acción contextuales ───────────────────────────────────
                const actionsEl = document.getElementById('oc-w-actions');
                let actionsHtml = '';

                if (st === 'terminated') {
                    <?php if (auth()->user()->can('hr.edit')): ?>
                        actionsHtml += `
                <button class="btn btn-sm btn-success" onclick="restoreWorker(${w.profile_id})">
                    <i class="fas fa-fw fa-undo me-1"></i>Restaurar
                </button>`;
                    <?php endif; ?>
                } else {
                    <?php if (auth()->user()->can('hr.edit')): ?>
                        actionsHtml += `
                <a href="<?= route_to('hr.worker.edit', 0) ?>".replace('/0', '/' + w.profile_id)
                   class="btn btn-sm btn-outline-warning" id="oc-w-edit-link">
                    <i class="fas fa-fw fa-edit me-1"></i>Editar
                </a>`;
                    <?php endif; ?>
                    <?php if (auth()->user()->can('hr.delete')): ?>
                        actionsHtml += `
                <button class="btn btn-sm btn-outline-danger" onclick="deleteWorkerFromOffcanvas(${w.profile_id})">
                    <i class="fas fa-fw fa-user-times me-1"></i>Dar de Baja
                </button>`;
                    <?php endif; ?>
                }
                actionsEl.innerHTML = actionsHtml;

                // Fijar href del enlace de edición y nuevo contrato (se insertan vía innerHTML, href se evalúa después)
                const editLink = actionsEl.querySelector('#oc-w-edit-link');
                if (editLink) {
                    editLink.href = '<?= route_to('hr.worker.edit', 0) ?>'.replace('/0', '/' + w.profile_id);
                }
                const newContractLink = document.getElementById('oc-btn-new-contract-link');
                if (newContractLink) {
                    newContractLink.href = '<?= route_to('hr.contracts.generate', 0) ?>'.replace('/0', '/' + w.profile_id);
                }
                const fullHistoryLink = document.getElementById('oc-btn-full-history');
                if (fullHistoryLink) {
                    fullHistoryLink.href = '<?= route_to('hr.contracts.history', 0) ?>'.replace('/0', '/' + w.profile_id);
                }

                // Mostrar contenido
                document.getElementById('oc-worker-loading').style.display = 'none';
                document.getElementById('oc-worker-content').style.display = 'block';

                if (typeof lucide !== 'undefined') lucide.createIcons();

                // Cargar historial de contratos
                loadContractsHistory(profileId);

                // Cargar expediente digital
                renderDocuments(resp.response.documents || []);
            })
            .catch(error => {
                console.error('Error en worker show_ajax:', error);
                document.getElementById('oc-worker-loading').innerHTML =
                    '<div class="alert alert-danger m-3">Error de conexión con el servidor.</div>';
            });
    }

    /**
     * renderDocuments(docs)
     * Renderiza la lista de documentos del expediente digital en el offcanvas.
     */
    function renderDocuments(docs) {
        const container = document.getElementById('oc-documents-list');
        if (!container) return;

        if (!docs || docs.length === 0) {
            container.innerHTML = `
                <div class="p-3 text-center text-muted small bg-light">
                    <i class="fas fa-info-circle me-1"></i> No hay documentos cargados.
                </div>`;
            return;
        }

        let html = '';
        docs.forEach(doc => {
            const dateObj = new Date(doc.created_at);
            const dateStr = dateObj.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
            // Generar URL usando base_url() y el ID
            const downloadUrl = '<?= base_url('nat/hr/documents/download/') ?>' + doc.id;

            html += `
                <div class="list-group-item px-3 py-2 border-0 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div style="min-width: 0;">
                            <div class="fw-bold small text-truncate" title="${doc.type_name || 'Documento'}">${doc.type_name || 'Documento'}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">
                                <i class="far fa-calendar-alt me-1"></i>${dateStr}
                                ${doc.notes ? `<br><i class="far fa-comment-dots me-1"></i>${doc.notes}` : ''}
                            </div>
                        </div>
                        <div class="btn-group ms-2">
                            <a href="${downloadUrl}?action=view" target="_blank" class="btn btn-xs btn-outline-primary" title="Ver">
                                <i class="fas fa-eye fa-xs"></i>
                            </a>
                            <a href="${downloadUrl}?action=download" class="btn btn-xs btn-outline-secondary" title="Descargar">
                                <i class="fas fa-download fa-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>`;
        });
        container.innerHTML = html;
    }

    /**
     * loadContractsHistory(profileId)
     */
    function loadContractsHistory(profileId) {
        const start = document.getElementById('oc-contract-start').value;
        const end = document.getElementById('oc-contract-end').value;
        const timeline = document.getElementById('oc-contracts-timeline');
        const filterBox = document.getElementById('oc-contracts-filter');

        // Solo mostrar filtro si hay petición inicial o ya estaba visible
        filterBox.style.display = 'flex';
        timeline.innerHTML = '<div class="text-center py-4 text-muted" style="margin-left: -10px;"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><br><span class="small">Cargando contratos...</span></div>';

        let url = '<?= base_url('nat/hr/contracts/get_contracts/') ?>' + profileId;
        if (start || end) {
            url += `?start_date=${start}&end_date=${end}`;
        }

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(resp => {
                if (!resp.success || !resp.data || resp.data.length === 0) {
                    timeline.innerHTML = `
                <div class="text-center py-4 text-muted" style="margin-left: -10px;">
                    <i class="fas fa-file-contract fa-2x mb-2 d-block text-secondary"></i>
                    <span class="small">No hay contratos registrados aún.</span>
                </div>`;
                    document.getElementById('oc-contracts-footer').style.display = 'none';
                    return;
                }

                let html = '';
                // Mostramos solo los últimos 5 para el resumen
                const last5 = resp.data.slice(0, 5);

                last5.forEach(contract => {
                    const dateObj = new Date(contract.created_at);
                    const dateStr = dateObj.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
                    const timeStr = dateObj.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });

                    let typeLabel = "Vigente";
                    let typeClass = "success";
                    let realReason = contract.reason;

                    if (contract.contract_type_name) {
                        typeLabel = contract.contract_type_name;
                        typeClass = "primary";
                        if (contract.reason.includes(' - ')) {
                            const parts = contract.reason.split(' - ');
                            realReason = parts[1];
                        }
                    } else if (contract.template_name) {
                        typeLabel = contract.template_name;
                        typeClass = "primary";
                    } else if (contract.reason.includes(' - ')) {
                        const parts = contract.reason.split(' - ');
                        typeLabel = parts[0];
                        realReason = parts.slice(1).join(' - ');
                        typeClass = "primary";
                    } else if (contract.reason.toLowerCase().includes('actualización')) {
                        typeLabel = "Actualización";
                        typeClass = "secondary";
                    }

                    html += `
            <div class="position-relative mb-4" style="margin-left: 20px;">
                <span class="position-absolute" style="left: -37px; top: 2px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #3b7ddd; background: #fff;"></span>
                <div class="d-flex justify-content-between mb-1">
                    <div>
                        <span class="badge bg-${typeClass}">${typeLabel}</span>
                        <small class="text-muted ms-2"><i class="fas fa-clock me-1"></i>${timeStr}</small>
                    </div>
                    <span class="small text-muted">${dateStr}</span>
                </div>
                <h6 class="mb-1 text-dark fw-bold" style="font-size: 0.9rem;">${typeLabel}</h6>
                <p class="small text-muted mb-2">${realReason}</p>
                <div class="mt-2 d-flex gap-2">
                    <a href="<?= base_url('nat/hr/contracts/download/') ?>${contract.id}?action=view" target="_blank" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.7rem;">
                        <i class="fas fa-eye me-1"></i>Ver
                    </a>
                    <a href="<?= base_url('nat/hr/contracts/download/') ?>${contract.id}" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.7rem;" target="_blank">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                </div>
            </div>`;
                });

                timeline.innerHTML = html;
                document.getElementById('oc-contracts-footer').style.display = 'block';
            })
            .catch(err => {
                timeline.innerHTML = '<div class="alert alert-danger m-3" style="margin-left:-10px;">Error al cargar historial.</div>';
            });
    }

    /**
     * deleteWorkerFromOffcanvas(profileId)
     */
    function deleteWorkerFromOffcanvas(profileId) {
        if (!confirm('¿Está seguro de dar de baja a este trabajador? Esta acción puede ser revertida.')) return;
        const csrfInput = document.getElementById('csrf_token');

        fetch('<?= base_url('nat/hr/worker/delete/') ?>' + profileId, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '<?= csrf_token() ?>=' + (csrfInput ? csrfInput.value : '')
        })
            .then(r => {
                const c = r.headers.get('<?= csrf_header() ?>');
                if (c && csrfInput) csrfInput.value = c;
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasWorker'))?.hide();
                    if (typeof notifyShow === 'function') notifyShow(data.message, 'success');
                    if (window.workersDataTable) window.workersDataTable.ajax.reload(null, false);
                } else {
                    if (typeof notifyShow === 'function') notifyShow(data.message || 'Error al eliminar', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof notifyShow === 'function') notifyShow('Error de conexión', 'danger');
            });
    }

    /**
     * restoreWorker(profileId)
     */
    function restoreWorker(profileId) {
        if (!confirm('¿Restaurar a este trabajador?')) return;
        const csrfInput = document.getElementById('csrf_token');

        fetch('<?= base_url('nat/hr/worker/restore/') ?>' + profileId, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '<?= csrf_token() ?>=' + (csrfInput ? csrfInput.value : '')
        })
            .then(r => {
                const c = r.headers.get('<?= csrf_header() ?>');
                if (c && csrfInput) csrfInput.value = c;
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasWorker'))?.hide();
                    if (typeof notifyShow === 'function') notifyShow(data.message, 'success');
                    if (window.workersDataTable) window.workersDataTable.draw();
                } else {
                    if (typeof notifyShow === 'function') notifyShow(data.message || 'Error al restaurar', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof notifyShow === 'function') notifyShow('Error de conexión', 'danger');
            });
    }

    // Event Listeners para Contratos
    document.addEventListener('DOMContentLoaded', function () {
        const btnFilter = document.getElementById('oc-btn-filter-contracts');
        if (btnFilter) {
            btnFilter.addEventListener('click', function () {
                const profileId = document.getElementById('offcanvasWorker').dataset.profileId;
                if (profileId) loadContractsHistory(profileId);
            });
        }
    });

    // Función para previsualizar el contrato
    function previewContract(contractId) {
        if (typeof notifyShow === 'function') notifyShow('Abriendo previsualización...', 'primary');
        window.open('<?= base_url('nat/hr/contracts/download/') ?>' + contractId + '?action=view', '_blank');
    }
</script>