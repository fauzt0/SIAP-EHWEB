<?php
/**
 * Partial: formulario modal de OC (crear / editar).
 *
 * Variables esperadas en $response:
 * - suppliers, branches, default_branch_id
 * - po_mode: 'create'|'edit' (default create)
 * - supplier_readonly: bool — true en offcanvas de proveedor
 */
$poMode           = $po_mode ?? 'create';
$supplierReadonly = $supplier_readonly ?? false;
$branches         = $response['branches'] ?? [];
$suppliers        = $response['suppliers'] ?? [];
$defaultBranchId  = (int) ($response['default_branch_id'] ?? 0);
?>
<div class="modal fade" id="modalGeneratePO" tabindex="-1"
     aria-labelledby="modalGeneratePOLabel" aria-hidden="true"
     data-bs-backdrop="static" style="z-index:1065;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-top:4px solid #198754;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGeneratePOLabel">
                    <i class="fas fa-file-invoice-dollar me-2 text-success"></i>
                    <span id="po-modal-title-text">Generar Orden de Compra</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="form-generate-po" novalidate>
                    <input type="hidden" name="po_id" id="po-id" value="">
                    <input type="hidden" name="fin_supplier_id" id="po-supplier-id">
                    <div class="row g-3 mb-4">
                        <?php if ($supplierReadonly): ?>
                        <div class="col-md-4">
                            <label class="form-label">Proveedor</label>
                            <input type="text" id="po-supplier-name" class="form-control bg-light" readonly>
                        </div>
                        <?php else: ?>
                        <div class="col-md-4">
                            <label class="form-label">Proveedor <span class="text-danger">*</span></label>
                            <select name="fin_supplier_id_select" id="po-supplier-select" class="form-select" required>
                                <option value="">— Seleccione —</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= (int) $s->id ?>"><?= esc($s->commercial_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <label class="form-label">Sucursal / Unidad receptora <span class="text-danger">*</span></label>
                            <select name="org_branch_id" id="po-branch-id" class="form-select" required
                                    data-default="<?= $defaultBranchId ?>">
                                <option value="">— Seleccione —</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?= (int) $branch->id ?>"
                                    <?= $defaultBranchId === (int) $branch->id ? 'selected' : '' ?>>
                                    <?= esc($branch->commercial_name ?? $branch->name) ?>
                                    (<?= esc($branch->branch_code) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Número de OC <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="po_number" id="po-number"
                                       class="form-control" placeholder="Auto-generado" required>
                                <button type="button" class="btn btn-outline-secondary"
                                        id="btn-gen-po-number" title="Generar número" type="button">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estatus</label>
                            <select name="status" id="po-status" class="form-select">
                                <option value="draft">Borrador</option>
                                <option value="sent">Enviada al Proveedor</option>
                                <option value="approved">Aprobada</option>
                                <option value="completed">Completada</option>
                                <option value="cancelled">Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Moneda <span class="text-danger">*</span></label>
                            <select name="currency" id="po-currency" class="form-select">
                                <option value="MXN">MXN — Peso Mexicano</option>
                                <option value="USD">USD — Dólar Estadounidense</option>
                                <option value="EUR">EUR — Euro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Emisión</label>
                            <input type="date" name="issue_date" id="po-issue-date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Estimada de Entrega</label>
                            <input type="date" name="delivery_date" id="po-delivery-date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas / Condiciones de la OC</label>
                            <textarea name="notes" id="po-notes" class="form-control" rows="2"
                                      placeholder="Términos de pago, condiciones de entrega, observaciones…"></textarea>
                        </div>
                    </div>

                    <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">
                        <i class="fas fa-list-ol me-1"></i>Artículos / Líneas de la Orden
                        <button type="button" class="btn btn-outline-success btn-sm ms-2" id="btn-add-po-item">
                            <i class="fas fa-plus"></i> Agregar Línea
                        </button>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="po-items-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:38%">Descripción <span class="text-danger">*</span></th>
                                    <th style="width:12%">Cantidad</th>
                                    <th style="width:17%">Precio Unitario</th>
                                    <th style="width:17%">Total Línea</th>
                                    <th style="width:6%"></th>
                                </tr>
                            </thead>
                            <tbody id="po-items-tbody"></tbody>
                            <tfoot>
                                <tr class="table-secondary fw-bold">
                                    <td colspan="3" class="text-end pe-3">Total General:</td>
                                    <td id="po-grand-total" class="font-monospace">$0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <template id="tpl-po-item-row">
                        <tr class="po-item-row">
                            <td>
                                <input type="text" name="items[__idx__][description]"
                                       class="form-control form-control-sm" placeholder="Descripción" required>
                            </td>
                            <td>
                                <input type="number" name="items[__idx__][quantity]"
                                       class="form-control form-control-sm po-qty" min="1" value="1">
                            </td>
                            <td>
                                <input type="number" name="items[__idx__][unit_price]"
                                       class="form-control form-control-sm po-price" min="0" step="0.01" value="0.00">
                            </td>
                            <td class="po-line-total align-middle text-end font-monospace">$0.00</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-po-item" title="Eliminar línea">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-submit-generate-po">
                    <i class="fas fa-save me-1"></i><span id="po-submit-text">Crear Orden de Compra</span>
                </button>
            </div>
        </div>
    </div>
</div>
