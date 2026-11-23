<?php

namespace App\Controllers\Accounting;

use App\Models\Financial\FinAccountModel;
use App\Models\Financial\FinExpenseCategoryModel;
use App\Models\Financial\FinExpenseModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\AccountingPostingService;
use CodeIgniter\HTTP\ResponseInterface;

class ExpenseController extends BaseAccountingController
{
    public function index(): string
    {
        $this->setViewSuccess('Gastos');
        $this->setPageTittleAhead('Gastos', 'Fijos y variables');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'       => base_url(),
            'Contabilidad' => route_to('accounting.dashboard'),
            'Gastos'       => '',
        ]);
        $this->viewData['response'] = [
            'categories' => (new FinExpenseCategoryModel())->grouped(),
            'accounts'   => (new FinAccountModel())->getActive(),
            'stats'      => (new FinExpenseModel())->getDashboardStats(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Accounting/expenses');
    }

    public function list_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();
        $model = new FinExpenseModel();
        $post  = $this->request->getPost();
        $list  = $model->get_datatables($post);
        $data  = [];
        $canTx = auth()->user()->can('finance.transactions');

        foreach ($list as $row) {
            $row    = (array) $row;
            $id     = (int) $row['id'];
            $status = $row['status'] ?? 'pending';
            $color  = ['pending' => 'warning', 'partial' => 'info', 'paid' => 'success', 'cancelled' => 'secondary'][$status] ?? 'secondary';
            $btn    = '';
            if ($canTx && in_array($status, ['pending', 'partial'], true)) {
                $btn = '<button class="btn btn-sm btn-primary btn-pay-exp" data-id="' . $id . '" data-amount="' . esc((string) $row['total_amount']) . '">Pagar</button>';
            }
            $data[] = [
                esc($row['due_date'] ?? ''),
                esc($row['supplier_name'] ?? ($row['notes'] ?? '—')),
                esc($row['period_label'] ?? ''),
                number_format((float) ($row['total_amount'] ?? 0), 2),
                number_format((float) ($row['paid_amount'] ?? 0), 2),
                '<span class="badge bg-' . $color . '">' . esc($status) . '</span>',
                $btn,
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
        $model = new FinExpenseModel();
        $ok    = $model->insert([
            'category_id'  => (int) $this->request->getPost('category_id'),
            'supplier_id'  => $this->request->getPost('supplier_id') ?: null,
            'total_amount' => (float) $this->request->getPost('total_amount'),
            'paid_amount'  => 0,
            'due_date'     => $this->request->getPost('due_date') ?: date('Y-m-d'),
            'status'       => 'pending',
            'currency'     => 'MXN',
            'notes'        => trim((string) $this->request->getPost('notes')),
            'source_type'  => 'manual',
            'org_branch_id'=> $this->request->getPost('branch_id') ?: null,
        ]);
        if (! $ok) {
            $this->setOutputError('No se pudo registrar el gasto.', $model->errors());

            return $this->jsonWithCsrf($this->outputData);
        }
        (new UserActivityLogsModel())->logActivity('accounting_expense_create', 'Gasto ID ' . $model->getInsertID());
        $this->setOutputSuccess('Gasto registrado.', ['id' => $model->getInsertID()]);

        return $this->jsonWithCsrf($this->outputData);
    }

    public function mark_paid(int $id): ResponseInterface
    {
        $this->assertAjaxPost();
        $model  = new FinExpenseModel();
        $exp    = $model->find($id);
        if (! $exp) {
            $this->setOutputError('Gasto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }
        $amount = (float) ($this->request->getPost('paid_amount') ?: $exp->total_amount);
        if (! $model->markPaid($id, $amount)) {
            $this->setOutputError('No se pudo marcar el pago.');

            return $this->jsonWithCsrf($this->outputData);
        }
        $accountId = $this->request->getPost('account_id') ? (int) $this->request->getPost('account_id') : null;
        (new AccountingPostingService())->fromExpensePayment($id, $accountId);
        (new UserActivityLogsModel())->logActivity('accounting_expense_pay', 'Pago gasto ID ' . $id);
        $this->setOutputSuccess('Pago contabilizado.');

        return $this->jsonWithCsrf($this->outputData);
    }
}
