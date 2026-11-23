<?php

declare(strict_types=1);

namespace App\Controllers\Sales;

use App\Models\CRM\CrmClientModel;
use App\Models\CRM\CrmFollowupModel;
use App\Models\Organization\OrgBranchModel;
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
            'branches' => (new OrgBranchModel())->getActive(),
            'canEdit'  => auth()->user()->can('sales.edit') || auth()->user()->can('sales.create'),
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
        $canConvert = auth()->user()->can('sales.create');
        $canEdit    = auth()->user()->can('sales.edit') || auth()->user()->can('sales.create');
        foreach ($list as $row) {
            $actions = '<div class="d-flex gap-1 flex-wrap">'
                . '<button class="btn btn-outline-info btn-sm btn-show-quote" data-id="' . (int) $row->id . '"><i class="fas fa-eye"></i></button>';
            if ($canEdit && $row->status !== 'converted') {
                $actions .= '<button class="btn btn-outline-warning btn-sm btn-edit-quote" data-id="' . (int) $row->id . '"><i class="fas fa-edit"></i></button>';
            }
            if ($canConvert && ! in_array($row->status, ['converted', 'rejected'], true)) {
                $actions .= '<button class="btn btn-outline-success btn-sm btn-convert-quote" data-id="' . (int) $row->id . '" title="Convertir a venta"><i class="fas fa-file-invoice-dollar"></i></button>';
            }
            $actions .= $this->pdfButtons('quote', (int) $row->id) . '</div>';
            $data[] = [
                '<span class="font-monospace">' . esc($row->quote_number) . '</span>',
                esc($row->company_name ?? '—'),
                '<span class="badge badge-subtle-primary">' . esc($this->statusLabel($row->status)) . '</span>',
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
            'pdf'         => site_url('nat/sales/documents/quote/' . $id),
            'pdf_html'    => site_url('nat/sales/documents/quote/' . $id . '/html'),
        ]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function store(): ResponseInterface
    {
        return $this->persist(null);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->persist($id);
    }

    private function persist(?int $id): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $rawItems = $this->request->getPost('items_json');
        $items    = $rawItems === null || $rawItems === '' ? null : json_decode((string) $rawItems, true);
        if ($id === null && (! is_array($items) || $items === [])) {
            $this->setOutputError('Agregue al menos un renglón.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }
        if (is_array($items) && $items === []) {
            $this->setOutputError('Agregue al menos un renglón.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $status = $this->request->getPost('status') ?: 'draft';
        if (! in_array($status, ['draft', 'sent', 'approved', 'rejected', 'expired'], true)) {
            $status = 'draft';
        }

        $payload = [
            'client_id'   => (int) $this->request->getPost('client_id'),
            'branch_id'   => $this->request->getPost('branch_id') ?: null,
            'channel'     => $this->request->getPost('channel') ?: 'backend',
            'status'      => $status,
            'valid_until' => $this->request->getPost('valid_until') ?: null,
            'notes'       => $this->request->getPost('notes'),
        ];

        $model = new SalesQuoteModel();
        if ($id === null) {
            $payload['created_by'] = auth()->id();
            $newId = $model->createWithItems($payload, $items ?? []);
            if (! $newId) {
                $this->setOutputError('No se pudo guardar la cotización.', $model->errors());
                $this->outputData['csrf'] = csrf_hash();

                return $this->response->setJSON($this->outputData);
            }
            $quote = $model->find($newId);
            (new CrmFollowupModel())->createVersion([
                'client_id'       => (int) $quote->client_id,
                'followup_type'   => 'quote',
                'title'           => 'Cotización ' . $quote->quote_number,
                'comment'         => 'Alta de cotización',
                'sales_quote_id'  => $newId,
                'created_by'      => auth()->id(),
                'change_source'   => 'auto',
                'followup_status' => 'closed',
            ]);
            (new UserActivityLogsModel())->logActivity('sales_quote_create', 'Cotización ID ' . $newId . ' ' . $quote->quote_number);
            $this->setOutputSuccess('Cotización creada', [
                'id'  => $newId,
                'pdf' => site_url('nat/sales/documents/quote/' . $newId),
            ]);
        } else {
            $ok = $model->updateWithItems($id, $payload, is_array($items) ? $items : null);
            if (! $ok) {
                $this->setOutputError('No se pudo actualizar (¿ya convertida?).', $model->errors());
                $this->outputData['csrf'] = csrf_hash();

                return $this->response->setJSON($this->outputData);
            }
            (new UserActivityLogsModel())->logActivity('sales_quote_update', 'Cotización ID ' . $id . ' estatus ' . $status);
            $this->setOutputSuccess('Cotización actualizada', [
                'id'  => $id,
                'pdf' => site_url('nat/sales/documents/quote/' . $id),
            ]);
        }

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
        if (! $quote || in_array($quote->status, ['converted', 'rejected'], true)) {
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
                'period_start'  => date('Y-m-d'),
            ];
        }

        $orderId = (new SalesOrderModel())->createWithItems([
            'client_id'  => $quote->client_id,
            'quote_id'   => $id,
            'branch_id'  => $quote->branch_id ?? null,
            'status'     => 'confirmed',
            'channel'    => $quote->channel ?: 'backend',
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
        (new CrmFollowupModel())->createVersion([
            'client_id'       => (int) $quote->client_id,
            'followup_type'   => 'order',
            'title'           => 'Cotización ' . $quote->quote_number . ' convertida a venta',
            'comment'         => 'Orden #' . $orderId,
            'sales_quote_id'  => $id,
            'sales_order_id'  => $orderId,
            'created_by'      => auth()->id(),
            'change_source'   => 'auto',
            'followup_status' => 'closed',
        ]);
        (new UserActivityLogsModel())->logActivity('sales_quote_convert', 'Cotización ID ' . $id . ' → orden ' . $orderId);
        $this->setOutputSuccess('Convertida a venta', [
            'order_id' => $orderId,
            'pdf'      => site_url('nat/sales/documents/order/' . $orderId),
        ]);
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

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'draft'     => 'Borrador',
            'sent'      => 'Enviada',
            'approved'  => 'Aprobada',
            'rejected'  => 'Rechazada',
            'expired'   => 'Vencida',
            'converted' => 'Convertida',
            default     => (string) $status,
        };
    }
}
