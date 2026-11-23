<?php

declare(strict_types=1);

namespace App\Controllers\Sales;

use App\Models\CRM\CrmClientModel;
use App\Models\CRM\CrmFollowupModel;
use App\Models\Sales\CustomerServiceModel;
use App\Models\Sales\SalesOrderModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\ClientCommerceBridge;
use CodeIgniter\HTTP\ResponseInterface;

class OrderController extends BaseSalesController
{
    public function index(): string
    {
        $this->setViewSuccess('Órdenes de venta');
        $this->setPageTittleAhead('Órdenes', 'Ventas');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio' => base_url(),
            'Ventas' => '',
        ]);
        $this->viewData['response'] = [
            'clients'  => (new CrmClientModel())->getForSelect(),
            'products' => (new \App\Models\Catalog\CatalogProductModel())
                ->select('id, commercial_name, sku')
                ->where('active', 1)
                ->orderBy('commercial_name', 'ASC')
                ->findAll(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Sales/orders_index');
    }

    public function list_ajax(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $model = new SalesOrderModel();
        $post  = $this->request->getPost();
        $list  = $model->get_datatables($post);
        $data  = [];
        foreach ($list as $row) {
            $data[] = [
                '<span class="font-monospace">' . esc($row->order_number ?? ('#' . $row->id)) . '</span>',
                esc($row->company_name ?? '—'),
                '<span class="badge badge-subtle-info">' . esc($row->status) . '</span>',
                esc($row->channel ?? '—'),
                '$' . number_format((float) $row->total_amount, 2),
                esc($row->created_at ?? ''),
                '<button class="btn btn-outline-info btn-sm btn-show-order" data-id="' . (int) $row->id . '"><i class="fas fa-eye"></i></button>',
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

        $order = (new SalesOrderModel())->getWithItems($id);
        if (! $order) {
            $this->setOutputError('Orden no encontrada.');

            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('Orden', [
            'order'       => $order,
            'plugin_tabs' => (new ClientCommerceBridge())->collectOrderTabs($id),
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
            $this->setOutputError('Agregue al menos un producto o servicio.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $model = new SalesOrderModel();
        $id    = $model->createWithItems([
            'client_id'  => (int) $this->request->getPost('client_id'),
            'status'     => 'confirmed',
            'channel'    => 'backend',
            'order_type' => 'new',
            'notes'      => $this->request->getPost('notes'),
            'created_by' => auth()->id(),
        ], $items);

        if (! $id) {
            $this->setOutputError('No se pudo crear la orden.', $model->errors());
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $order = $model->find($id);
        (new CrmFollowupModel())->createVersion([
            'client_id'      => (int) $order->client_id,
            'followup_type'  => 'order',
            'title'          => 'Orden ' . ($order->order_number ?? $id),
            'comment'        => 'Alta de orden de venta',
            'sales_order_id' => $id,
            'created_by'       => auth()->id(),
            'change_source'    => 'auto',
            'followup_status'  => 'closed',
        ]);
        (new UserActivityLogsModel())->logActivity('sales_order_create', 'Orden de venta ID ' . $id);
        $this->setOutputSuccess('Orden creada', ['id' => $id, 'order_number' => $order->order_number ?? null]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function upload_document(int $id): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $order = (new SalesOrderModel())->find($id);
        if (! $order) {
            $this->setOutputError('Orden no encontrada.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $file  = $this->request->getFile('document');
        $model = new \App\Models\Sales\SalesOrderDocumentModel();
        $docId = $model->storeDocument($id, $file, [
            'order_item_id' => $this->request->getPost('order_item_id') ?: null,
            'doc_type'      => $this->request->getPost('doc_type') ?: 'other',
            'notes'         => $this->request->getPost('notes'),
            'plugin_ref'    => $this->request->getPost('plugin_ref'),
            'uploaded_by'   => auth()->id(),
        ]);
        if (! $docId) {
            $this->setOutputError('Documento no válido (PDF, XML o imagen, máx. 10 MB).');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        \App\Libraries\PluginSystem\PluginHookRegistry::doAction('sales.order.documents', $id, $docId);
        (new UserActivityLogsModel())->logActivity('sales_order_document', 'Documento ID ' . $docId . ' orden ' . $id);
        $this->setOutputSuccess('Documento adjunto', ['id' => $docId]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function download_document(int $id)
    {
        $model = new \App\Models\Sales\SalesOrderDocumentModel();
        $row   = $model->find($id);
        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Documento no encontrado.');
        }
        $path = $model->absolutePath($row);
        if (! is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Archivo no encontrado.');
        }

        return $this->response->download($path, null)->setFileName($row->original_name);
    }

    public function generate_customer_po(int $id): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $itemIds = json_decode((string) $this->request->getPost('item_ids_json'), true);
        if (! is_array($itemIds)) {
            $itemIds = [];
        }
        $poId = (new \App\Models\Sales\SalesCustomerPoModel())->generateFromOrder(
            $id,
            $itemIds === [] ? null : $itemIds,
            auth()->id(),
            $this->request->getPost('notes')
        );
        if (! $poId) {
            $this->setOutputError('No se pudo generar la OC del cliente.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity('sales_customer_po', 'OC cliente ID ' . $poId . ' orden ' . $id);
        $this->setOutputSuccess('OC del cliente generada', ['id' => $poId]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function customer_po(int $id): string
    {
        $po = (new \App\Models\Sales\SalesCustomerPoModel())->getWithItems($id);
        if (! $po) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('OC del cliente no encontrada.');
        }
        $this->viewData['response'] = ['po' => $po];

        return view('Sales/customer_po_print', $this->viewData);
    }

    public function fulfill_item(int $itemId): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $model = new \App\Models\Sales\SalesOrderItemModel();
        $item  = $model->find($itemId);
        if (! $item) {
            $this->setOutputError('Partida no encontrada.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $status = (string) $this->request->getPost('fulfillment_status');
        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'activated', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            $this->setOutputError('Estatus de gestión no válido.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $payload = ['fulfillment_status' => $status];
        if ($status === 'activated') {
            $payload['activation_status'] = 'activated';
        }
        $model->update($itemId, $payload);
        (new UserActivityLogsModel())->logActivity('sales_item_fulfill', 'Partida ' . $itemId . ' → ' . $status);
        $this->setOutputSuccess('Partida actualizada', ['id' => $itemId, 'fulfillment_status' => $status]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function services(): string
    {
        $this->setViewSuccess('Servicios contratados');
        $this->setPageTittleAhead('Servicios', 'Renovaciones');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'    => base_url(),
            'Ventas'    => route_to('sales.orders'),
            'Servicios' => '',
        ]);
        $this->viewData['response'] = [];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Sales/services_index');
    }

    public function services_ajax(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $model = new CustomerServiceModel();
        $post  = $this->request->getPost();
        $list  = $model->get_datatables($post);
        $data  = [];
        foreach ($list as $row) {
            $data[] = [
                esc($row->reference_name ?: ($row->product_name ?? '—')),
                esc($row->company_name ?? '—'),
                esc($row->billing_cycle),
                '<span class="badge badge-subtle-success">' . esc($row->status) . '</span>',
                esc($row->next_due_date ?? '—'),
                '$' . number_format((float) ($row->amount ?? 0), 2),
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $model->count_all(),
            'recordsFiltered' => $model->count_filtered($post),
            'data'            => $data,
        ]);
    }
}
