<?php

declare(strict_types=1);

namespace App\Controllers\Sales;

use App\Models\CRM\CrmClientModel;
use App\Models\CRM\CrmFollowupModel;
use App\Models\Sales\SalesOrderModel;
use App\Models\Sales\SalesQuoteModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\ClientCommerceBridge;
use CodeIgniter\HTTP\ResponseInterface;

class QuoteController extends BaseSalesController
{
    public function index(): string
    {
        $this->setViewSuccess('Cotizaciones');
        $this->setPageTittleAhead('Cotizaciones', 'Ventas');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'       => base_url(),
            'Ventas'       => route_to('sales.orders'),
            'Cotizaciones' => '',
        ]);
        $this->viewData['response'] = [
            'clients'  => (new CrmClientModel())->getForSelect(),
            'products' => $this->catalogProducts(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Sales/quotes_index');
    }

    public function list_ajax(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $model = new SalesQuoteModel();
        $post  = $this->request->getPost();
        $list  = $model->get_datatables($post);
        $data  = [];
        foreach ($list as $row) {
            $actions = '<div class="d-flex gap-1">'
                . '<button class="btn btn-outline-info btn-sm btn-show-quote" data-id="' . (int) $row->id . '"><i class="fas fa-eye"></i></button>';
            if (auth()->user()->can('sales.create') && $row->status !== 'converted') {
                $actions .= '<button class="btn btn-outline-success btn-sm btn-convert-quote" data-id="' . (int) $row->id . '"><i class="fas fa-file-invoice-dollar"></i></button>';
            }
            $actions .= '</div>';
            $data[] = [
                '<span class="font-monospace">' . esc($row->quote_number) . '</span>',
                esc($row->company_name ?? '—'),
                '<span class="badge badge-subtle-primary">' . esc($row->status) . '</span>',
                '$' . number_format((float) $row->total_amount, 2),
                esc($row->valid_until ?? '—'),
                $actions,
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $model->count_all(),
            'recordsFiltered' => $model->count_filtered($post),
            'data'            => $data,
        ]);
    }

    public function show_ajax(int $id): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $quote = (new SalesQuoteModel())->getWithItems($id);
        if (! $quote) {
            $this->setOutputError('Cotización no encontrada.');

            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('Cotización', [
            'quote'       => $quote,
            'plugin_tabs' => (new ClientCommerceBridge())->collectQuoteTabs($id),
        ]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function store(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $items = json_decode((string) $this->request->getPost('items_json'), true);
        if (! is_array($items) || $items === []) {
            $this->setOutputError('Agregue al menos un renglón.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $model = new SalesQuoteModel();
        $id    = $model->createWithItems([
            'client_id'   => (int) $this->request->getPost('client_id'),
            'status'      => $this->request->getPost('status') ?: 'draft',
            'valid_until' => $this->request->getPost('valid_until') ?: null,
            'notes'       => $this->request->getPost('notes'),
            'created_by'  => auth()->id(),
        ], $items);

        if (! $id) {
            $this->setOutputError('No se pudo guardar la cotización.', $model->errors());
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $quote = $model->find($id);
        (new CrmFollowupModel())->createVersion([
            'client_id'         => (int) $quote->client_id,
            'followup_type'     => 'quote',
            'title'             => 'Cotización ' . $quote->quote_number,
            'comment'           => 'Alta de cotización',
            'sales_quote_id'    => $id,
            'created_by'        => auth()->id(),
            'change_source'     => 'auto',
            'followup_status'   => 'closed',
        ]);
        (new UserActivityLogsModel())->logActivity('sales_quote_create', 'Cotización ID ' . $id . ' ' . $quote->quote_number);
        $this->setOutputSuccess('Cotización creada', ['id' => $id]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function convert(int $id): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $quoteModel = new SalesQuoteModel();
        $quote      = $quoteModel->getWithItems($id);
        if (! $quote || $quote->status === 'converted') {
            $this->setOutputError('No se puede convertir esta cotización.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $items = [];
        foreach ($quote->items as $item) {
            $items[] = [
                'product_id'    => $item->product_id,
                'plan_id'       => $item->plan_id,
                'item_name'     => $item->item_name,
                'unit_price'    => $item->unit_price,
                'quantity'      => $item->quantity,
                'billing_cycle' => $item->billing_cycle,
            ];
        }

        $orderId = (new SalesOrderModel())->createWithItems([
            'client_id'  => $quote->client_id,
            'quote_id'   => $id,
            'status'     => 'confirmed',
            'channel'    => 'backend',
            'order_type' => 'new',
            'created_by' => auth()->id(),
            'notes'      => 'Convertida de ' . $quote->quote_number,
        ], $items);

        if (! $orderId) {
            $this->setOutputError('Falló la conversión a orden.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $quoteModel->update($id, ['status' => 'converted', 'converted_order_id' => $orderId]);
        (new UserActivityLogsModel())->logActivity('sales_quote_convert', 'Cotización ID ' . $id . ' → orden ' . $orderId);
        $this->setOutputSuccess('Convertida a orden', ['order_id' => $orderId]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    /**
     * @return list<object>
     */
    private function catalogProducts(): array
    {
        return (new \App\Models\Catalog\CatalogProductModel())
            ->select('id, commercial_name, sku')
            ->where('active', 1)
            ->orderBy('commercial_name', 'ASC')
            ->findAll();
    }
}
