<?php

namespace App\Controllers\Accounting;

use App\Models\Financial\FinAccountModel;
use App\Models\Financial\FinPurchaseOrderModel;
use App\Models\Financial\FinSupplierInvoiceModel;
use App\Models\Financial\FinSupplierModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\AccountingPostingService;
use CodeIgniter\HTTP\ResponseInterface;

class InvoiceController extends BaseAccountingController
{
    public function index(): string
    {
        $this->setViewSuccess('Facturas');
        $this->setPageTittleAhead('Facturas', 'Proveedores y ventas');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'       => base_url(),
            'Contabilidad' => route_to('accounting.dashboard'),
            'Facturas'     => '',
        ]);
        $this->viewData['response'] = [
            'suppliers' => (new FinSupplierModel())->getActiveSuppliers(),
            'accounts'  => (new FinAccountModel())->getActive(),
            'orders'    => (new FinPurchaseOrderModel())
                ->select('id, po_number, fin_supplier_id, total_amount, status')
                ->orderBy('id', 'DESC')
                ->findAll(100),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Accounting/invoices');
    }

    public function supplier_list_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();
        $model = new FinSupplierInvoiceModel();
        $post  = $this->request->getPost();
        $list  = $model->get_datatables($post);
        $data  = [];
        $canTx = auth()->user()->can('finance.transactions');
        foreach ($list as $row) {
            $row    = (array) $row;
            $id     = (int) $row['id'];
            $status = $row['status'] ?? 'open';
            $color  = ['draft' => 'secondary', 'open' => 'warning', 'partial' => 'info', 'paid' => 'success', 'cancelled' => 'dark'][$status] ?? 'secondary';
            $btn    = '';
            if ($canTx && in_array($status, ['open', 'partial'], true)) {
                $due = (float) $row['total'] - (float) $row['paid_amount'];
                $btn = '<button class="btn btn-sm btn-primary btn-pay-inv" data-id="' . $id . '" data-due="' . $due . '">Pagar</button>';
            }
            $data[] = [
                esc($row['folio'] ?? ''),
                esc($row['supplier_name'] ?? ''),
                esc($row['invoice_number'] ?? ($row['po_number'] ?? '')),
                esc($row['invoice_date'] ?? ''),
                number_format((float) ($row['total'] ?? 0), 2),
                number_format((float) ($row['paid_amount'] ?? 0), 2),
                '<span class="badge bg-' . $color . '">' . esc(FinSupplierInvoiceModel::STATUSES[$status] ?? $status) . '</span>',
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

    public function sales_list_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();
        $db   = db_connect();
        $draw = (int) $this->request->getPost('draw');
        if (! $db->tableExists('billing_invoices')) {
            return $this->jsonWithCsrf(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
        }

        $builder = $db->table('billing_invoices bi')
            ->select('bi.id, bi.invoice_number, bi.status, bi.created_at, bi.order_id, so.order_number, so.total_amount, c.company_name')
            ->join('sales_orders so', 'so.id = bi.order_id', 'left')
            ->join('crm_clients c', 'c.id = so.client_id', 'left');
        if ($db->fieldExists('deleted_at', 'billing_invoices')) {
            $builder->where('bi.deleted_at', null);
        }
        $total = $builder->countAllResults(false);
        $search = $this->request->getPost('search')['value'] ?? '';
        if ($search !== '') {
            $builder->groupStart()
                ->like('bi.invoice_number', $search)
                ->orLike('so.order_number', $search)
                ->orLike('c.company_name', $search)
                ->groupEnd();
        }
        $filtered = $builder->countAllResults(false);
        $start  = (int) $this->request->getPost('start');
        $length = (int) $this->request->getPost('length');
        if ($length > 0) {
            $builder->limit($length, $start);
        }
        $rows = $builder->orderBy('bi.id', 'DESC')->get()->getResultArray();
        $data = [];
        $canTx = auth()->user()->can('finance.transactions');
        foreach ($rows as $row) {
            $status = $row['status'] ?? 'draft';
            $btn    = '';
            if ($canTx && $status === 'draft') {
                $btn = '<button class="btn btn-sm btn-outline-primary btn-issue-sale" data-id="' . (int) $row['id'] . '">Emitir</button>';
            }
            $data[] = [
                esc($row['invoice_number'] ?? ''),
                esc($row['company_name'] ?? '—'),
                esc($row['order_number'] ?? ''),
                number_format((float) ($row['total_amount'] ?? 0), 2),
                esc($status),
                esc($row['created_at'] ?? ''),
                $btn,
            ];
        }

        return $this->jsonWithCsrf([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    public function store_supplier(): ResponseInterface
    {
        $this->assertAjaxPost();
        $model = new FinSupplierInvoiceModel();
        $total = (float) $this->request->getPost('total');
        $ok    = $model->insert([
            'folio'             => $model->nextFolio(),
            'supplier_id'       => (int) $this->request->getPost('supplier_id'),
            'purchase_order_id' => $this->request->getPost('purchase_order_id') ?: null,
            'invoice_number'    => $this->request->getPost('invoice_number') ?: null,
            'invoice_date'      => $this->request->getPost('invoice_date') ?: date('Y-m-d'),
            'due_date'          => $this->request->getPost('due_date') ?: null,
            'subtotal'          => $total,
            'tax_amount'        => 0,
            'total'             => $total,
            'paid_amount'       => 0,
            'status'            => 'open',
            'account_id'        => $this->request->getPost('account_id') ?: null,
            'notes'             => $this->request->getPost('notes'),
            'created_by'        => auth()->id(),
        ]);
        if (! $ok) {
            $this->setOutputError('No se pudo registrar la factura.', $model->errors());

            return $this->jsonWithCsrf($this->outputData);
        }
        (new UserActivityLogsModel())->logActivity('supplier_invoice_create', 'Factura proveedor ID ' . $model->getInsertID());
        $this->setOutputSuccess('Factura de proveedor registrada.', ['id' => $model->getInsertID()]);

        return $this->jsonWithCsrf($this->outputData);
    }

    public function pay_supplier(int $id): ResponseInterface
    {
        $this->assertAjaxPost();
        $model = new FinSupplierInvoiceModel();
        $inv   = $model->find($id);
        if (! $inv) {
            $this->setOutputError('Factura no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }
        $pay   = (float) ($this->request->getPost('paid_amount') ?: ((float) $inv->total - (float) $inv->paid_amount));
        $paid  = min((float) $inv->total, (float) $inv->paid_amount + $pay);
        $status = $paid + 0.009 >= (float) $inv->total ? 'paid' : 'partial';
        $model->update($id, ['paid_amount' => $paid, 'status' => $status]);

        $accountId = $this->request->getPost('account_id') ? (int) $this->request->getPost('account_id') : (int) ($inv->account_id ?: 0);
        (new AccountingPostingService())->post([
            'account_id'     => $accountId ?: null,
            'reference_type' => 'supplier_invoice_pay',
            'reference_id'   => $id,
            'type'           => 'credit',
            'amount'         => $pay,
            'notes'          => 'Pago factura proveedor ' . $inv->folio,
        ]);
        if ($inv->purchase_order_id && $status === 'paid') {
            $po = new FinPurchaseOrderModel();
            $order = $po->find((int) $inv->purchase_order_id);
            if ($order && $order->status === 'approved') {
                $po->updateStatus((int) $order->id, 'completed');
            }
        }
        (new UserActivityLogsModel())->logActivity('supplier_invoice_pay', 'Pago factura ' . $inv->folio);
        $this->setOutputSuccess('Pago de factura registrado.');

        return $this->jsonWithCsrf($this->outputData);
    }

    public function issue_sales(int $id): ResponseInterface
    {
        $this->assertAjaxPost();
        $db = db_connect();
        if (! $db->tableExists('billing_invoices')) {
            $this->setOutputError('No existe el catálogo de facturas de venta.');

            return $this->jsonWithCsrf($this->outputData);
        }
        $row = $db->table('billing_invoices')->where('id', $id)->get()->getRow();
        if (! $row) {
            $this->setOutputError('Factura no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }
        $db->table('billing_invoices')->where('id', $id)->update(['status' => 'issued', 'updated_at' => date('Y-m-d H:i:s')]);
        (new UserActivityLogsModel())->logActivity('sales_invoice_issue', 'Emitió factura de venta ID ' . $id);
        $this->setOutputSuccess('Factura de venta emitida.');

        return $this->jsonWithCsrf($this->outputData);
    }
}
