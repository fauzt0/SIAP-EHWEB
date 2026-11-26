<?php

namespace App\Controllers\Accounting;

use App\Models\Financial\FinAccountModel;
use App\Models\Users\UserActivityLogsModel;
use CodeIgniter\HTTP\ResponseInterface;

class AccountController extends BaseAccountingController
{
    public function index(): string
    {
        $this->setViewSuccess('Cuentas');
        $this->setPageTittleAhead('Cuentas', 'Caja y bancos');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'       => base_url(),
            'Contabilidad' => route_to('accounting.dashboard'),
            'Cuentas'      => '',
        ]);
        $this->viewData['response'] = [
            'accounts' => (new FinAccountModel())->findAll(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Accounting/accounts');
    }

    public function list_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();
        $model = new FinAccountModel();
        $post  = $this->request->getPost();
        $list  = $model->get_datatables($post);
        $data  = [];
        foreach ($list as $row) {
            $row = (array) $row;
            $data[] = [
                esc($row['name'] ?? ''),
                esc($row['type'] ?? ''),
                esc($row['currency'] ?? 'MXN'),
                number_format((float) ($row['balance'] ?? 0), 2),
                ((int) ($row['is_active'] ?? 0) === 1) ? 'Activa' : 'Inactiva',
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
        $model = new FinAccountModel();
        $ok    = $model->insert([
            'name'     => trim((string) $this->request->getPost('name')),
            'type'     => $this->request->getPost('type') ?: 'bank',
            'currency' => $this->request->getPost('currency') ?: 'MXN',
            'balance'  => (float) ($this->request->getPost('balance') ?: 0),
            'is_active'=> 1,
        ]);
        if (! $ok) {
            $this->setOutputError('No se pudo crear la cuenta.', $model->errors());

            return $this->jsonWithCsrf($this->outputData);
        }
        (new UserActivityLogsModel())->logActivity('accounting_account_create', 'Cuenta ' . $this->request->getPost('name'));
        $this->setOutputSuccess('Cuenta creada.');

        return $this->jsonWithCsrf($this->outputData);
    }
}
