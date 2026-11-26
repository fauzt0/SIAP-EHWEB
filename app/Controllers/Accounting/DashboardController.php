<?php

namespace App\Controllers\Accounting;

use App\Models\Financial\FinAccountModel;
use App\Models\Financial\FinExpenseModel;
use App\Models\Financial\FinLedgerEntryModel;
use App\Models\Financial\FinPaymentFolioModel;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseAccountingController
{
    public function index(): string
    {
        $this->setViewSuccess('Contabilidad');
        $this->setPageTittleAhead('Contabilidad', 'Resumen de tesorería');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'       => base_url(),
            'Contabilidad' => '',
        ]);

        $accounts  = new FinAccountModel();
        $folios    = new FinPaymentFolioModel();
        $expenses  = new FinExpenseModel();

        $openSupplier = 0.0;
        $db = db_connect();
        if ($db->tableExists('fin_supplier_invoices')) {
            $row = $db->table('fin_supplier_invoices')
                ->selectSum('total', 't')
                ->selectSum('paid_amount', 'p')
                ->whereIn('status', ['open', 'partial'])
                ->where('deleted_at', null)
                ->get()
                ->getRow();
            $openSupplier = max(0, (float) ($row->t ?? 0) - (float) ($row->p ?? 0));
        }

        $this->viewData['response'] = [
            'accounts'      => $accounts->getActive(),
            'folios'        => $folios->getDashboardStats(),
            'expenses'      => $expenses->getDashboardStats(),
            'open_supplier' => $openSupplier,
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Accounting/dashboard');
    }

    public function ledger_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();

        $model = new FinLedgerEntryModel();
        $post  = $this->request->getPost();
        $list  = $model->get_datatables($post);
        $data  = [];
        foreach ($list as $row) {
            $row = (array) $row;
            $type = ($row['type'] ?? '') === 'debit' ? 'Ingreso' : 'Egreso';
            $color = ($row['type'] ?? '') === 'debit' ? 'success' : 'danger';
            $data[] = [
                esc($row['entry_date'] ?? ''),
                esc($row['account_name'] ?? '—'),
                esc($row['reference_type'] ?? ''),
                '<span class="badge bg-' . $color . '">' . $type . '</span>',
                number_format((float) ($row['amount'] ?? 0), 2),
                number_format((float) ($row['balance_after'] ?? 0), 2),
                esc($row['notes'] ?? ''),
            ];
        }

        return $this->jsonWithCsrf([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $model->count_all(),
            'recordsFiltered' => $model->count_filtered($post),
            'data'            => $data,
        ]);
    }
}
