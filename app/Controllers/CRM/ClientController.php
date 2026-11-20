<?php

declare(strict_types=1);

namespace App\Controllers\CRM;

use App\Models\CRM\CrmBillingProfileModel;
use App\Models\CRM\CrmClientContactModel;
use App\Models\CRM\CrmFollowupModel;
use App\Models\Sales\CustomerServiceModel;
use App\Models\Sales\SalesOrderModel;
use App\Models\Sales\SalesQuoteModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\ClientCommerceBridge;
use CodeIgniter\HTTP\ResponseInterface;

class ClientController extends BaseCrmController
{
    public function index(): string
    {
        $this->setViewSuccess('Listado de clientes');
        $this->setPageTittleAhead('Clientes', 'CRM empresarial');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'   => base_url(),
            'Clientes' => '',
        ]);

        $products = (new \App\Models\Catalog\CatalogProductModel())
            ->select('id, commercial_name, sku, product_type')
            ->where('active', 1)
            ->orderBy('commercial_name', 'ASC')
            ->findAll();

        $this->viewData['response'] = [
            'stats'    => $this->clientModel->getStats(),
            'products' => $products,
            'canManage'=> auth()->user()->can('crm.manage') || auth()->user()->can('sales.customers'),
            'canDelete'=> auth()->user()->can('crm.delete'),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'CRM/clients_index');
    }

    public function list_ajax(): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $post = $this->request->getPost();
        $list = $this->clientModel->get_datatables($post);
        $data = [];
        $canManage = auth()->user()->can('crm.manage') || auth()->user()->can('sales.customers');
        $canDelete = auth()->user()->can('crm.delete');

        $statusMap = [
            'lead'     => ['Lead', 'info'],
            'prospect' => ['Prospecto', 'primary'],
            'active'   => ['Activo', 'success'],
            'inactive' => ['Inactivo', 'secondary'],
            'churned'  => ['Baja', 'danger'],
        ];

        foreach ($list as $row) {
            [$label, $color] = $statusMap[$row->status] ?? [$row->status, 'secondary'];
            $actions = '<div class="d-flex gap-1">';
            $actions .= '<button class="btn btn-outline-info btn-sm btn-show-client" data-id="' . (int) $row->id . '" title="Ver"><i class="fas fa-eye"></i></button>';
            if ($canManage) {
                $actions .= '<button class="btn btn-outline-warning btn-sm btn-edit-client" data-id="' . (int) $row->id . '" title="Editar"><i class="fas fa-edit"></i></button>';
            }
            if ($canDelete) {
                $actions .= '<button class="btn btn-outline-danger btn-sm btn-delete-client" data-id="' . (int) $row->id . '" title="Eliminar"><i class="fas fa-trash"></i></button>';
            }
            $actions .= '</div>';

            $data[] = [
                '<span class="font-monospace small">' . esc($row->client_code) . '</span>',
                '<div class="fw-semibold">' . esc($row->company_name) . '</div><small class="text-muted">' . esc($row->trade_name ?? '') . '</small>',
                esc($row->rfc_tax_id ?? '—'),
                '<span class="badge badge-subtle-' . $color . '">' . esc($label) . '</span>',
                esc($row->email ?? '—'),
                '<span class="badge bg-secondary">' . (int) ($row->contacts_count ?? 0) . '</span>',
                ((int) $row->active === 1)
                    ? '<span class="badge badge-subtle-success">Sí</span>'
                    : '<span class="badge badge-subtle-dark">No</span>',
                $actions,
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->clientModel->count_all(),
            'recordsFiltered' => $this->clientModel->count_filtered($post),
            'data'            => $data,
        ]);
    }

    public function show_ajax(int $id): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $client = $this->clientModel->find($id);
        if (! $client) {
            $this->setOutputError('Cliente no encontrado.');

            return $this->response->setJSON($this->outputData);
        }

        $bridge = new ClientCommerceBridge();
        $this->setOutputSuccess('Cliente cargado', [
            'client'   => $client,
            'contacts' => (new CrmClientContactModel())->where('client_id', $id)->findAll(),
            'billing'  => (new CrmBillingProfileModel())->where('client_id', $id)->findAll(),
            'followups'=> (new CrmFollowupModel())->getTree($id),
            'orders'   => (new SalesOrderModel())->where('client_id', $id)->orderBy('id', 'DESC')->findAll(20),
            'quotes'   => (new SalesQuoteModel())->where('client_id', $id)->orderBy('id', 'DESC')->findAll(20),
            'services' => (new CustomerServiceModel())->where('client_id', $id)->findAll(),
            'plugin_tabs'  => $bridge->collectClientTabs($id),
            'plugin_links' => $bridge->linksForClient($id),
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

        $client = [
            'company_name'     => trim((string) $this->request->getPost('company_name')),
            'trade_name'       => $this->request->getPost('trade_name') ?: null,
            'rfc_tax_id'       => $this->request->getPost('rfc_tax_id') ?: null,
            'website'          => $this->request->getPost('website') ?: null,
            'email'            => $this->request->getPost('email') ?: null,
            'phone'            => $this->request->getPost('phone') ?: null,
            'status'           => $this->request->getPost('status') ?: 'lead',
            'source'           => $this->request->getPost('source') ?: null,
            'notes'            => $this->request->getPost('notes') ?: null,
            'active'           => $this->request->getPost('active') ? 1 : 0,
            'assigned_user_id' => auth()->id(),
        ];

        $contacts = $this->decodeRows((string) $this->request->getPost('contacts_json'));
        $billing  = $this->decodeRows((string) $this->request->getPost('billing_json'));

        if ($id === null) {
            $newId = $this->clientModel->createWithRelated($client, $contacts, $billing);
            if (! $newId) {
                $this->setOutputError('No se pudo crear el cliente.', $this->clientModel->errors());
                $this->outputData['csrf'] = csrf_hash();

                return $this->response->setJSON($this->outputData);
            }
            (new UserActivityLogsModel())->logActivity('crm_client_create', 'Alta cliente ID ' . $newId . ' ' . $client['company_name']);
            $this->setOutputSuccess('Cliente creado', ['id' => $newId]);
        } else {
            $ok = $this->clientModel->updateWithRelated($id, $client, $contacts, $billing);
            if (! $ok) {
                $this->setOutputError('No se pudo actualizar el cliente.', $this->clientModel->errors());
                $this->outputData['csrf'] = csrf_hash();

                return $this->response->setJSON($this->outputData);
            }
            (new UserActivityLogsModel())->logActivity('crm_client_update', 'Edición cliente ID ' . $id . ' ' . $client['company_name']);
            $this->setOutputSuccess('Cliente actualizado', ['id' => $id]);
        }

        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function delete(int $id): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $client = $this->clientModel->find($id);
        if (! $client) {
            $this->setOutputError('Cliente no encontrado.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $this->clientModel->delete($id);
        (new UserActivityLogsModel())->logActivity('crm_client_delete', 'Eliminó cliente ID ' . $id . ' ' . $client->company_name);
        $this->setOutputSuccess('Cliente eliminado');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function decodeRows(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }
}
