<?php
/**
 * Partial: modal de planeador de nóminas.
 *
 * Variables: $response['schedules'], $response['branches'], $response['period_types']
 */
$schedules   = $response['schedules'] ?? [];
$branches    = $response['branches'] ?? [];
$periodTypes = $response['period_types'] ?? \App\Models\HR\HrPayrollPeriodModel::PERIOD_TYPES;
$canManage   = auth()->user()->can('hr.manage-payroll');
?>
<div class="modal fade" id="payroll-schedule-modal" tabindex="-1" aria-labelledby="payroll-schedule-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payroll-schedule-modal-label">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i>Planeador de nóminas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?php if ($canManage): ?>
                <form id="form-payroll-schedule" class="border rounded p-3 mb-3 bg-light">
                    <input type="hidden" name="schedule_id" id="schedule-id" value="">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="schedule-name" class="form-control form-control-sm" required maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Tipo <span class="text-danger">*</span></label>
                            <select name="period_type" id="schedule-period-type" class="form-select form-select-sm" required>
                                <?php foreach ($periodTypes as $key => $label): ?>
                                <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Próxima fecha <span class="text-danger">*</span></label>
                            <input type="date" name="next_payment_date" id="schedule-next-date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Día (1-31)</label>
                            <input type="number" name="recurrence_day" id="schedule-recurrence-day" class="form-control form-control-sm" min="1" max="31">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">Sucursal</label>
                            <select name="branch_id" id="schedule-branch-id" class="form-select form-select-sm">
                                <option value="">— Todas —</option>
                                <?php foreach ($branches as $b): ?>
                                <option value="<?= (int) $b->id ?>"><?= esc($b->commercial_name ?? $b->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Activo</label>
                            <select name="active" id="schedule-active" class="form-select form-select-sm">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn-save-schedule">
                                <i class="fas fa-save me-1"></i>Guardar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-schedule">Limpiar</button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="schedule-modal-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Próxima fecha</th>
                                <th>Sucursal</th>
                                <th>Activo</th>
                                <?php if ($canManage): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="schedule-modal-tbody">
                            <?php if (empty($schedules)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Sin calendarios programados.</td></tr>
                            <?php else: ?>
                            <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td><?= esc($s['name'] ?? '') ?></td>
                                <td><?= esc($periodTypes[$s['period_type'] ?? ''] ?? ($s['period_type'] ?? '')) ?></td>
                                <td><?= ! empty($s['next_payment_date']) ? date('d/m/Y', strtotime($s['next_payment_date'])) : '—' ?></td>
                                <td><?= esc($s['branch_commercial_name'] ?? $s['branch_name'] ?? 'Todas') ?></td>
                                <td>
                                    <?php if (! empty($s['active'])): ?>
                                    <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canManage): ?>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-edit-schedule"
                                        data-id="<?= (int) $s['id'] ?>"
                                        data-name="<?= esc($s['name'] ?? '', 'attr') ?>"
                                        data-period-type="<?= esc($s['period_type'] ?? '', 'attr') ?>"
                                        data-next="<?= esc($s['next_payment_date'] ?? '', 'attr') ?>"
                                        data-recurrence="<?= esc((string) ($s['recurrence_day'] ?? ''), 'attr') ?>"
                                        data-branch="<?= esc((string) ($s['branch_id'] ?? ''), 'attr') ?>"
                                        data-active="<?= (int) ($s['active'] ?? 1) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-schedule" data-id="<?= (int) $s['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= route_to('hr.payroll.schedule') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i>Vista completa
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
