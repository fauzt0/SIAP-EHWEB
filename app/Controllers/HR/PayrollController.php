<?php

namespace App\Controllers\HR;

use App\Libraries\Breadcrumb;
use App\Models\HR\HrPayrollEntryModel;
use App\Models\HR\HrPayrollPeriodModel;
use App\Models\HR\HrPayrollScheduleModel;
use App\Models\Organization\OrgBranchModel;
use App\Models\Organization\OrgCompanyModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Nóminas: periodos, entradas por trabajador, recibos y calendario.
 * Transacciones multi-tabla viven en el modelo (§9). Todo POST AJAX usa assertAjaxPost (§1).
 */
class PayrollController extends BaseHrController
{
    protected HrPayrollPeriodModel $periodModel;
    protected HrPayrollEntryModel $entryModel;
    protected HrPayrollScheduleModel $scheduleModel;
    protected OrgBranchModel $branchModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        $this->periodModel   = new HrPayrollPeriodModel();
        $this->entryModel    = new HrPayrollEntryModel();
        $this->scheduleModel = new HrPayrollScheduleModel();
        $this->branchModel   = new OrgBranchModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET — Dashboard / listado
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): string
    {
        $this->setViewSuccess('Nóminas');
        $this->setPageTittleAhead('Nóminas', 'Gestión de Nóminas');

        $crumb = new Breadcrumb([]);
        $this->viewData['breadcrumb'] = $crumb->getBreadCrumbHtml([
            'Inicio'  => route_to('dashboard.index'),
            'RR.HH'   => route_to('hr.workers'),
            'Nóminas' => '',
        ]);

        $this->viewData['response'] = [
            'stats'        => $this->periodModel->getDashboardStats(),
            'branches'     => $this->branchModel->getActive(),
            'period_types' => HrPayrollPeriodModel::PERIOD_TYPES,
            'statuses'     => HrPayrollPeriodModel::STATUSES,
            'schedules'    => $this->scheduleModel->getAllWithBranch(),
            'upcoming'     => $this->scheduleModel->getUpcoming(30),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/Payroll/payroll_index');
    }

    public function list_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();

        $postData = $this->request->getPost();
        $list     = $this->periodModel->get_datatables($postData);
        $data     = [];
        $canManage = auth()->user()->can('hr.manage-payroll');

        foreach ($list as $row) {
            $row     = (array) $row;
            $status  = $row['status'] ?? 'draft';
            $color   = HrPayrollPeriodModel::STATUS_COLORS[$status] ?? 'secondary';
            $label   = HrPayrollPeriodModel::STATUSES[$status] ?? $status;
            $typeLbl = HrPayrollPeriodModel::PERIOD_TYPES[$row['period_type'] ?? ''] ?? ($row['period_type'] ?? '—');

            $start = ! empty($row['start_date']) ? date('d/m/Y', strtotime($row['start_date'])) : '—';
            $end   = ! empty($row['end_date']) ? date('d/m/Y', strtotime($row['end_date'])) : '—';

            $statusBadge = '<span class="badge bg-' . $color . '">' . esc($label) . '</span>';

            $pid     = (int) $row['id'];
            $actions = '<div class="d-flex gap-1 align-items-center flex-wrap">';
            $actions .= '<a href="' . route_to('hr.payroll.show', $pid) . '" class="btn btn-outline-primary btn-sm" title="Ver detalle">'
                     . '<i class="fas fa-eye"></i></a>';

            if ($canManage && $status === 'draft') {
                $actions .= '<button type="button" class="btn btn-outline-success btn-sm btn-approve-period" data-id="' . $pid . '" title="Aprobar">'
                         . '<i class="fas fa-check"></i></button>';
            }
            if ($canManage && in_array($status, ['draft', 'approved'], true)) {
                $actions .= '<button type="button" class="btn btn-outline-danger btn-sm btn-cancel-period" data-id="' . $pid . '" title="Cancelar">'
                         . '<i class="fas fa-times"></i></button>';
            }
            $actions .= '</div>';

            $data[] = [
                '<span class="fw-semibold">' . esc($row['name'] ?? '') . '</span>'
                    . '<div class="small text-muted">' . esc($row['branch_commercial_name'] ?? $row['branch_name'] ?? 'Todas las sucursales') . '</div>',
                esc($typeLbl),
                $start . ' — ' . $end,
                (int) ($row['worker_count'] ?? 0),
                '$' . number_format((float) ($row['total_gross'] ?? 0), 2),
                '$' . number_format((float) ($row['total_net'] ?? 0), 2),
                $statusBadge,
                $actions,
            ];
        }

        return $this->jsonWithCsrf([
            'draw'            => (int) ($postData['draw'] ?? 0),
            'recordsTotal'    => $this->periodModel->count_all(),
            'recordsFiltered' => $this->periodModel->count_filtered($postData),
            'data'            => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET — Formulario de nuevo periodo
    // ─────────────────────────────────────────────────────────────────────────

    public function create(): string|ResponseInterface
    {
        return redirect()->to(route_to('hr.payroll.index') . '?open=create');
    }

    public function show(int $id): string
    {
        $period = $this->periodModel->getWithDetails($id);
        if (! $period) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Periodo de nómina no encontrado.');
        }

        $this->setViewSuccess('Periodo de nómina');
        $this->setPageTittleAhead($period['name'], 'Detalle de Nómina');

        $crumb = new Breadcrumb([]);
        $this->viewData['breadcrumb'] = $crumb->getBreadCrumbHtml([
            'Inicio'           => route_to('dashboard.index'),
            'RR.HH'            => route_to('hr.workers'),
            'Nóminas'          => route_to('hr.payroll.index'),
            'Período #' . $id  => '',
        ]);

        $this->viewData['response'] = [
            'period'          => $period,
            'payment_methods' => HrPayrollEntryModel::PAYMENT_METHODS,
            'payment_statuses'=> HrPayrollEntryModel::PAYMENT_STATUSES,
            'period_types'    => HrPayrollPeriodModel::PERIOD_TYPES,
            'statuses'        => HrPayrollPeriodModel::STATUSES,
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/Payroll/payroll_show');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Crear periodo + entradas (§9 en el modelo)
    // ─────────────────────────────────────────────────────────────────────────

    public function store(): ResponseInterface
    {
        $this->assertAjaxPost();

        $periodData = [
            'name'         => trim((string) $this->request->getPost('name')),
            'period_type'  => (string) $this->request->getPost('period_type'),
            'start_date'   => (string) $this->request->getPost('start_date'),
            'end_date'     => (string) $this->request->getPost('end_date'),
            'branch_id'    => $this->request->getPost('branch_id') ? (int) $this->request->getPost('branch_id') : null,
            'created_by'   => user_id(),
            'status'       => 'draft',
            'worked_days'  => $this->request->getPost('worked_days') ? (int) $this->request->getPost('worked_days') : null,
        ];

        if (! $this->periodModel->validate($periodData)) {
            $this->setOutputError('Verifique los datos del periodo.', $this->periodModel->errors());

            return $this->jsonWithCsrf($this->outputData);
        }

        $branchId = $periodData['branch_id'] ?: null;
        $workers  = $this->periodModel->getActiveWorkersForPayroll($branchId);

        $periodId = $this->periodModel->createPeriodWithEntries($periodData, $workers);
        if (! $periodId) {
            $this->setOutputError(
                'No se pudo crear el periodo de nómina.',
                $this->periodModel->errors()
            );

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'create_payroll_period',
            'Creó período de nómina: ' . $periodData['name'] . ' (ID: ' . $periodId . ')'
        );

        $this->setOutputSuccess('Periodo de nómina creado. Se generaron ' . count($workers) . ' entrada(s).');
        $this->outputData['response'] = [
            'id'          => $periodId,
            'redirect_to' => route_to('hr.payroll.show', $periodId),
        ];

        return $this->jsonWithCsrf($this->outputData);
    }

    public function approve(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        $period = $this->periodModel->find($id);
        if (! $period) {
            $this->setOutputError('Periodo no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }
        if (($period['status'] ?? '') !== 'draft') {
            $this->setOutputError('Solo se pueden aprobar periodos en borrador.');

            return $this->jsonWithCsrf($this->outputData);
        }

        if (! $this->periodModel->update($id, ['status' => 'approved'])) {
            $this->setOutputError('No se pudo aprobar el periodo.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'approve_payroll_period',
            'Aprobó período de nómina: ' . ($period['name'] ?? '') . ' (ID: ' . $id . ')'
        );

        $this->setOutputSuccess('Periodo aprobado.');

        return $this->jsonWithCsrf($this->outputData);
    }

    public function cancel(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        $period = $this->periodModel->find($id);
        if (! $period) {
            $this->setOutputError('Periodo no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }
        if (($period['status'] ?? '') === 'paid') {
            $this->setOutputError('No se puede cancelar un periodo ya pagado.');

            return $this->jsonWithCsrf($this->outputData);
        }
        if (($period['status'] ?? '') === 'cancelled') {
            $this->setOutputError('El periodo ya está cancelado.');

            return $this->jsonWithCsrf($this->outputData);
        }

        if (! $this->periodModel->update($id, ['status' => 'cancelled'])) {
            $this->setOutputError('No se pudo cancelar el periodo.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'cancel_payroll_period',
            'Canceló período de nómina: ' . ($period['name'] ?? '') . ' (ID: ' . $id . ')'
        );

        $this->setOutputSuccess('Periodo cancelado.');

        return $this->jsonWithCsrf($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Entradas individuales
    // ─────────────────────────────────────────────────────────────────────────

    public function updateEntry(int $entryId): ResponseInterface
    {
        $this->assertAjaxPost();

        $entry = $this->entryModel->find($entryId);
        if (! $entry) {
            $this->setOutputError('Entrada no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }

        $period = $this->periodModel->find((int) $entry['period_id']);
        if ($period && in_array($period['status'] ?? '', ['paid', 'cancelled'], true)) {
            $this->setOutputError('No se puede editar una entrada de un periodo pagado o cancelado.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $data = [
            'overtime_hours'    => (float) $this->request->getPost('overtime_hours'),
            'bonuses'           => (float) $this->request->getPost('bonuses'),
            'benefits'          => (float) $this->request->getPost('benefits'),
            'imss_employee'     => (float) $this->request->getPost('imss_employee'),
            'infonavit'         => (float) $this->request->getPost('infonavit'),
            'isr'               => (float) $this->request->getPost('isr'),
            'other_deductions'  => (float) $this->request->getPost('other_deductions'),
            'payment_method'    => (string) $this->request->getPost('payment_method'),
            'notes'             => $this->request->getPost('notes') !== null
                ? (string) $this->request->getPost('notes')
                : ($entry['notes'] ?? null),
            'worked_days'       => $this->request->getPost('worked_days') !== null
                ? (int) $this->request->getPost('worked_days')
                : ($entry['worked_days'] ?? null),
        ];

        if (! $this->entryModel->updateEntryFields($entryId, $data)) {
            $this->setOutputError('No se pudo actualizar la entrada.', $this->entryModel->errors());

            return $this->jsonWithCsrf($this->outputData);
        }

        $receipt    = $this->entryModel->getReceiptData($entryId);
        $workerName = $receipt['worker_name'] ?? ('ID ' . $entry['hr_profile_id']);

        $this->activityLogModel->logActivity(
            'update_payroll_entry',
            'Actualizó entrada ID: ' . $entryId . ' Trabajador: ' . $workerName
        );

        $this->setOutputSuccess('Entrada actualizada.');

        return $this->jsonWithCsrf($this->outputData);
    }

    public function markPaid(int $entryId): ResponseInterface
    {
        $this->assertAjaxPost();

        $entry = $this->entryModel->find($entryId);
        if (! $entry) {
            $this->setOutputError('Entrada no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }
        if (($entry['payment_status'] ?? '') === 'paid') {
            $this->setOutputError('Esta entrada ya está marcada como pagada.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $paymentDate = (string) $this->request->getPost('payment_date');
        if ($paymentDate === '') {
            $paymentDate = date('Y-m-d');
        }
        $reference = $this->request->getPost('payment_reference')
            ? (string) $this->request->getPost('payment_reference')
            : null;

        if (! $this->entryModel->markEntryPaid($entryId, $paymentDate, $reference)) {
            $this->setOutputError('No se pudo marcar la entrada como pagada.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'payroll_entry_paid',
            'Marcó como pagada entrada ID: ' . $entryId
        );

        $this->setOutputSuccess('Pago registrado.');

        return $this->jsonWithCsrf($this->outputData);
    }

    public function markPeriodPaid(int $periodId): ResponseInterface
    {
        $this->assertAjaxPost();

        $period = $this->periodModel->find($periodId);
        if (! $period) {
            $this->setOutputError('Periodo no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }
        if (($period['status'] ?? '') === 'cancelled') {
            $this->setOutputError('No se puede pagar un periodo cancelado.');

            return $this->jsonWithCsrf($this->outputData);
        }
        if (($period['status'] ?? '') === 'paid') {
            $this->setOutputError('El periodo ya está marcado como pagado.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $paymentDate = (string) $this->request->getPost('payment_date');
        if ($paymentDate === '') {
            $paymentDate = date('Y-m-d');
        }
        $reference = $this->request->getPost('payment_reference')
            ? (string) $this->request->getPost('payment_reference')
            : null;

        if (! $this->periodModel->markAllEntriesPaid($periodId, $paymentDate, $reference)) {
            $this->setOutputError('No se pudieron marcar las entradas como pagadas.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'payroll_period_paid',
            'Marcó todas las entradas del período ID: ' . $periodId
        );

        $this->setOutputSuccess('Todas las entradas del periodo se marcaron como pagadas.');

        return $this->jsonWithCsrf($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET — Recibo de nómina (impresión)
    // ─────────────────────────────────────────────────────────────────────────

    public function receipt(int $entryId): string
    {
        $entry = $this->entryModel->getReceiptData($entryId);
        if (! $entry) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Recibo no encontrado.');
        }

        $company = (new OrgCompanyModel())->getProfile();

        $this->setViewSuccess('Recibo de nómina');
        $this->setPageTittleAhead('Recibo de nómina', 'Recibo de Nómina');

        $crumb = new Breadcrumb([]);
        $this->viewData['breadcrumb'] = $crumb->getBreadCrumbHtml([
            'Inicio'  => route_to('dashboard.index'),
            'RR.HH'   => route_to('hr.workers'),
            'Nóminas' => route_to('hr.payroll.index'),
            'Recibo'  => '',
        ]);

        $this->viewData['response'] = [
            'entry'           => $entry,
            'company'         => $company,
            'payment_methods' => HrPayrollEntryModel::PAYMENT_METHODS,
            'payment_statuses'=> HrPayrollEntryModel::PAYMENT_STATUSES,
            'period_types'    => HrPayrollPeriodModel::PERIOD_TYPES,
        ];
        $this->viewData['layout'] = 'Layouts/print_layout';

        return $this->renderLayout('Layouts/print_layout', 'HR/Payroll/payroll_receipt');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Calendario / planeador
    // ─────────────────────────────────────────────────────────────────────────

    public function schedule(): string
    {
        $this->setViewSuccess('Calendario de nóminas');
        $this->setPageTittleAhead('Calendario de Nóminas', 'Planeador de Nóminas');

        $crumb = new Breadcrumb([]);
        $this->viewData['breadcrumb'] = $crumb->getBreadCrumbHtml([
            'Inicio'     => route_to('dashboard.index'),
            'RR.HH'      => route_to('hr.workers'),
            'Nóminas'    => route_to('hr.payroll.index'),
            'Calendario' => '',
        ]);

        $this->viewData['response'] = [
            'schedules'    => $this->scheduleModel->getAllWithBranch(),
            'upcoming'     => $this->scheduleModel->getUpcoming(30),
            'branches'     => $this->branchModel->getActive(),
            'period_types' => HrPayrollPeriodModel::PERIOD_TYPES,
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/Payroll/payroll_schedule');
    }

    public function schedule_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();

        $days      = (int) ($this->request->getPost('days') ?? 30);
        $upcoming  = $this->scheduleModel->getUpcoming($days > 0 ? $days : 30);
        $all       = $this->scheduleModel->getAllWithBranch();

        $this->setOutputSuccess('Calendarios cargados.');
        $this->outputData['response'] = [
            'upcoming'     => $upcoming,
            'schedules'    => $all,
            'period_types' => HrPayrollPeriodModel::PERIOD_TYPES,
        ];

        return $this->jsonWithCsrf($this->outputData);
    }

    public function storeSchedule(): ResponseInterface
    {
        $this->assertAjaxPost();

        $data = [
            'name'              => trim((string) $this->request->getPost('name')),
            'period_type'       => (string) $this->request->getPost('period_type'),
            'next_payment_date' => (string) $this->request->getPost('next_payment_date'),
            'recurrence_day'    => $this->request->getPost('recurrence_day') !== ''
                ? (int) $this->request->getPost('recurrence_day')
                : null,
            'branch_id'         => $this->request->getPost('branch_id')
                ? (int) $this->request->getPost('branch_id')
                : null,
            'active'            => $this->request->getPost('active') === '0' ? 0 : 1,
        ];

        $id = $this->scheduleModel->insert($data);
        if (! $id) {
            $this->setOutputError('No se pudo guardar el calendario.', $this->scheduleModel->errors());

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'create_payroll_schedule',
            'Creó calendario de nómina: ' . $data['name'] . ' (ID: ' . $id . ')'
        );

        $this->setOutputSuccess('Calendario guardado.');
        $this->outputData['response'] = ['id' => $id];

        return $this->jsonWithCsrf($this->outputData);
    }

    public function updateSchedule(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        $schedule = $this->scheduleModel->find($id);
        if (! $schedule) {
            $this->setOutputError('Calendario no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }

        $data = [
            'name'              => trim((string) $this->request->getPost('name')),
            'period_type'       => (string) $this->request->getPost('period_type'),
            'next_payment_date' => (string) $this->request->getPost('next_payment_date'),
            'recurrence_day'    => $this->request->getPost('recurrence_day') !== ''
                ? (int) $this->request->getPost('recurrence_day')
                : null,
            'branch_id'         => $this->request->getPost('branch_id')
                ? (int) $this->request->getPost('branch_id')
                : null,
            'active'            => $this->request->getPost('active') === '0' ? 0 : 1,
        ];

        if (! $this->scheduleModel->update($id, $data)) {
            $this->setOutputError('No se pudo actualizar el calendario.', $this->scheduleModel->errors());

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'update_payroll_schedule',
            'Actualizó calendario de nómina: ' . $data['name'] . ' (ID: ' . $id . ')'
        );

        $this->setOutputSuccess('Calendario actualizado.');

        return $this->jsonWithCsrf($this->outputData);
    }

    public function deleteSchedule(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        $schedule = $this->scheduleModel->find($id);
        if (! $schedule) {
            $this->setOutputError('Calendario no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }

        if (! $this->scheduleModel->delete($id)) {
            $this->setOutputError('No se pudo eliminar el calendario.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'delete_payroll_schedule',
            'Eliminó calendario de nómina: ' . ($schedule['name'] ?? '') . ' (ID: ' . $id . ')'
        );

        $this->setOutputSuccess('Calendario eliminado.');

        return $this->jsonWithCsrf($this->outputData);
    }
}
