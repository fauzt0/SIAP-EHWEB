<?php

namespace App\Controllers\Accounting;

use App\Models\Financial\FinAccountModel;
use App\Models\Financial\FinPaymentFolioModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\AccountingPostingService;
use CodeIgniter\HTTP\ResponseInterface;

class FundRequestController extends BaseAccountingController
{
    public function index(): string
    {
        $this->setViewSuccess('Solicitud de fondos');
        $this->setPageTittleAhead('Solicitud de fondos', 'Folios de gasto');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'             => base_url(),
            'Contabilidad'       => route_to('accounting.dashboard'),
            'Solicitud de fondos'=> '',
        ]);
        $this->viewData['response'] = [
            'accounts' => (new FinAccountModel())->getActive(),
            'statuses' => FinPaymentFolioModel::STATUSES,
            'stats'    => (new FinPaymentFolioModel())->getDashboardStats(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Accounting/fund_requests');
    }

    public function list_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();
        $model = new FinPaymentFolioModel();
        $post  = $this->request->getPost();
        $list  = $model->get_datatables($post);
        $data  = [];
        $canTx = auth()->user()->can('finance.transactions');

        foreach ($list as $row) {
            $row    = (array) $row;
            $status = $row['status'] ?? 'requested';
            $label  = FinPaymentFolioModel::STATUSES[$status] ?? $status;
            $color  = [
                'requested' => 'warning',
                'approved'  => 'info',
                'executed'  => 'success',
                'rejected'  => 'danger',
            ][$status] ?? 'secondary';
            $id = (int) $row['id'];
            $btns = '';
            if ($canTx && $status === 'requested') {
                $btns .= '<button class="btn btn-sm btn-outline-success btn-approve" data-id="' . $id . '">Aprobar</button> ';
                $btns .= '<button class="btn btn-sm btn-outline-danger btn-reject" data-id="' . $id . '">Rechazar</button>';
            }
            if ($canTx && $status === 'approved') {
                $btns .= '<button class="btn btn-sm btn-primary btn-execute" data-id="' . $id . '">Ejecutar</button>';
            }
            $data[] = [
                esc($row['folio_number'] ?? ''),
                esc($row['concept'] ?? ''),
                esc($row['account_name'] ?? '—'),
                number_format((float) ($row['amount_paid'] ?? 0), 2),
                '<span class="badge bg-' . $color . '">' . esc($label) . '</span>',
                esc($row['created_at'] ?? ''),
                $btns,
            ];
        }

        return $this->jsonWithCsrf([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $model->count_all(),
            'recordsFiltered' => $model->count_filtered($post),
            'data'            => $data,
        ]);
    }

    public function store(): ResponseInterface
    {
        $this->assertAjaxPost();
        $model  = new FinPaymentFolioModel();
        $folio  = $model->nextFolioNumber();
        $userId = (int) auth()->id();
        $ok     = $model->insert([
            'folio_number' => $folio,
            'account_id'   => (int) $this->request->getPost('account_id'),
            'amount_paid'  => (float) $this->request->getPost('amount_paid'),
            'concept'      => trim((string) $this->request->getPost('concept')),
            'status'       => 'requested',
            'requested_by' => $userId,
            'branch_id'    => $this->request->getPost('branch_id') ?: null,
        ]);

        if (! $ok) {
            $this->setOutputError('No se pudo generar el folio.', $model->errors());

            return $this->jsonWithCsrf($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'fund_request_create',
            'Solicitud de fondos ' . $folio . ' por ' . $this->request->getPost('amount_paid')
        );
        $this->setOutputSuccess('Folio generado: ' . $folio, ['folio' => $folio, 'id' => $model->getInsertID()]);

        return $this->jsonWithCsrf($this->outputData);
    }

    public function approve(int $id): ResponseInterface
    {
        return $this->transition($id, 'requested', 'approved', 'fund_request_approve', 'Solicitud aprobada.');
    }

    public function reject(int $id): ResponseInterface
    {
        return $this->transition($id, 'requested', 'rejected', 'fund_request_reject', 'Solicitud rechazada.');
    }

    public function execute(int $id): ResponseInterface
    {
        $this->assertAjaxPost();
        $model = new FinPaymentFolioModel();
        $folio = $model->find($id);
        if (! $folio) {
            $this->setOutputError('Folio no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }
        if ($folio->status !== 'approved') {
            $this->setOutputError('Solo se ejecutan folios aprobados.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $posted = (new AccountingPostingService())->fromFundRequest($id);
        if (! ($posted['ok'] ?? false)) {
            $this->setOutputError($posted['message'] ?? 'No se pudo contabilizar el folio.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $model->update($id, [
            'status'      => 'executed',
            'executed_by' => auth()->id(),
            'executed_at' => date('Y-m-d H:i:s'),
        ]);

        (new UserActivityLogsModel())->logActivity(
            'fund_request_execute',
            'Ejecutó folio ' . $folio->folio_number
        );
        $this->setOutputSuccess('Folio ejecutado y contabilizado: ' . $folio->folio_number);

        return $this->jsonWithCsrf($this->outputData);
    }

    private function transition(int $id, string $from, string $to, string $logType, string $okMsg): ResponseInterface
    {
        $this->assertAjaxPost();
        $model = new FinPaymentFolioModel();
        $folio = $model->find($id);
        if (! $folio) {
            $this->setOutputError('Folio no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }
        if ($folio->status !== $from) {
            $this->setOutputError('El folio no está en el estado esperado.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $data = ['status' => $to];
        if ($to === 'approved') {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = date('Y-m-d H:i:s');
        }
        $model->update($id, $data);
        (new UserActivityLogsModel())->logActivity($logType, $folio->folio_number . ' → ' . $to);
        $this->setOutputSuccess($okMsg);

        return $this->jsonWithCsrf($this->outputData);
    }
}
