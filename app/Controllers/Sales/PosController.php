<?php

declare(strict_types=1);

namespace App\Controllers\Sales;

use App\Models\CRM\CrmClientModel;
use App\Models\CRM\CrmFollowupModel;
use App\Models\Organization\OrgBranchModel;
use App\Models\Sales\SalesOrderModel;
use App\Models\Sales\SalesQuoteModel;
use App\Models\Users\UserActivityLogsModel;
use CodeIgniter\HTTP\ResponseInterface;

class PosController extends BaseSalesController
{
    public function index(): string
    {
        $this->setViewSuccess('Punto de venta');
        $this->setPageTittleAhead('POS', 'Venta directa');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio' => base_url(),
            'Ventas' => route_to('sales.orders'),
            'POS'    => '',
        ]);
        $this->viewData['response'] = [
            'clients'  => (new CrmClientModel())->getForSelect(),
            'branches' => (new OrgBranchModel())->getActive(),
            'canCreate'=> auth()->user()->can('sales.create'),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Sales/pos_index');
    }

    public function products_ajax(): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $q = trim((string) $this->request->getGet('q'));
        $productModel = new \App\Models\Catalog\CatalogProductModel();
        $productModel->select('id, commercial_name, sku, product_type')->where('active', 1);
        if ($q !== '') {
            $productModel->groupStart()
                ->like('commercial_name', $q)
                ->orLike('sku', $q)
                ->groupEnd();
        }
        $products = $productModel->orderBy('commercial_name', 'ASC')->findAll(30);
        $planModel = new \App\Models\Catalog\CatalogPlanModel();
        $rows      = [];
        foreach ($products as $product) {
            $plans = $planModel->where('product_id', $product->id)->where('is_active', 1)->findAll();
            if ($plans === []) {
                $rows[] = (object) [
                    'id'             => $product->id,
                    'commercial_name'=> $product->commercial_name,
                    'sku'            => $product->sku,
                    'product_type'   => $product->product_type,
                    'plan_id'        => null,
                    'plan_name'      => 'Precio único',
                    'billing_cycle'  => 'one_time',
                    'sale_price'     => 0,
                ];
                continue;
            }
            foreach ($plans as $plan) {
                $rows[] = (object) [
                    'id'             => $product->id,
                    'commercial_name'=> $product->commercial_name,
                    'sku'            => $product->sku,
                    'product_type'   => $product->product_type,
                    'plan_id'        => $plan->id,
                    'plan_name'      => $plan->plan_name,
                    'billing_cycle'  => $plan->billing_cycle,
                    'sale_price'     => $plan->sale_price,
                ];
            }
        }
        $this->setOutputSuccess('Productos', ['products' => $rows]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function checkout(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $mode  = $this->request->getPost('mode') === 'quote' ? 'quote' : 'sale';
        $items = json_decode((string) $this->request->getPost('items_json'), true);
        if (! is_array($items) || $items === []) {
            $this->setOutputError('Agregue productos al carrito.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $clientId = (int) $this->request->getPost('client_id');
        if ($clientId <= 0 && $this->request->getPost('walk_in')) {
            $clientId = $this->ensureWalkInClient();
        }
        if ($clientId <= 0) {
            $this->setOutputError('Seleccione un cliente o marque venta de mostrador.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $branchId = (int) $this->request->getPost('branch_id') ?: null;
        $notes    = $this->request->getPost('notes');
        $mapped   = [];
        foreach ($items as $item) {
            $mapped[] = [
                'product_id'    => ! empty($item['product_id']) ? $item['product_id'] : null,
                'plan_id'       => ! empty($item['plan_id']) ? $item['plan_id'] : null,
                'item_name'     => $item['item_name'] ?? 'Producto',
                'unit_price'    => $item['unit_price'] ?? 0,
                'quantity'      => $item['quantity'] ?? 1,
                'billing_cycle' => $item['billing_cycle'] ?? 'one_time',
                'period_start'  => date('Y-m-d'),
            ];
        }

        if ($mode === 'quote') {
            $status = $this->request->getPost('quote_status') ?: 'draft';
            if (! in_array($status, ['draft', 'sent'], true)) {
                $status = 'draft';
            }
            $id = (new SalesQuoteModel())->createWithItems([
                'client_id'   => $clientId,
                'branch_id'   => $branchId,
                'channel'     => 'pos',
                'status'      => $status,
                'valid_until' => $this->request->getPost('valid_until') ?: date('Y-m-d', strtotime('+15 days')),
                'notes'       => $notes,
                'created_by'  => auth()->id(),
            ], $mapped);
            if (! $id) {
                $this->setOutputError('No se guardó la cotización.');
                $this->outputData['csrf'] = csrf_hash();

                return $this->response->setJSON($this->outputData);
            }
            $quote = (new SalesQuoteModel())->find($id);
            $this->followup($clientId, 'quote', 'Cotización POS ' . $quote->quote_number, $id, null);
            (new UserActivityLogsModel())->logActivity('sales_pos_quote', 'POS cotización ID ' . $id);
            $this->setOutputSuccess('Cotización generada', [
                'mode'       => 'quote',
                'id'         => $id,
                'number'     => $quote->quote_number,
                'pdf'        => site_url('nat/sales/documents/quote/' . $id),
            ]);
        } else {
            $id = (new SalesOrderModel())->createWithItems([
                'client_id'  => $clientId,
                'branch_id'  => $branchId,
                'status'     => 'confirmed',
                'channel'    => 'pos',
                'order_type' => 'new',
                'notes'      => $notes,
                'created_by' => auth()->id(),
            ], $mapped);
            if (! $id) {
                $this->setOutputError('No se registró la venta.');
                $this->outputData['csrf'] = csrf_hash();

                return $this->response->setJSON($this->outputData);
            }
            $order = (new SalesOrderModel())->find($id);
            $this->followup($clientId, 'order', 'Venta POS ' . ($order->order_number ?? $id), null, $id);
            (new UserActivityLogsModel())->logActivity('sales_pos_sale', 'POS venta ID ' . $id);
            $receipt = (new \App\Models\Sales\SalesReceiptModel())->where('order_id', $id)->orderBy('id', 'DESC')->first();
            $this->setOutputSuccess('Venta registrada', [
                'mode'        => 'sale',
                'id'          => $id,
                'number'      => $order->order_number ?? ('#' . $id),
                'pdf'         => site_url('nat/sales/documents/order/' . $id),
                'receipt_pdf' => $receipt ? site_url('nat/sales/documents/receipt/' . $receipt->id) : null,
            ]);
        }

        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    private function ensureWalkInClient(): int
    {
        $model = new CrmClientModel();
        $row   = $model->where('source', 'pos_walkin')->where('company_name', 'Público general')->first();
        if ($row) {
            return (int) $row->id;
        }
        $id = $model->createWithRelated([
            'company_name' => 'Público general',
            'status'       => 'active',
            'source'       => 'pos_walkin',
            'notes'        => 'Cliente de mostrador (POS)',
            'active'       => 1,
        ]);

        return $id ? (int) $id : 0;
    }

    private function followup(int $clientId, string $type, string $title, ?int $quoteId, ?int $orderId): void
    {
        (new CrmFollowupModel())->createVersion([
            'client_id'       => $clientId,
            'followup_type'   => $type,
            'title'           => $title,
            'comment'         => 'Generado desde POS',
            'sales_quote_id'  => $quoteId,
            'sales_order_id'  => $orderId,
            'created_by'      => auth()->id(),
            'change_source'   => 'auto',
            'followup_status' => 'closed',
        ]);
    }
}
