<?php

declare(strict_types=1);

namespace App\Controllers\CRM;

use App\Models\CRM\CrmFollowupModel;
use App\Models\Users\UserActivityLogsModel;
use CodeIgniter\HTTP\ResponseInterface;

class FollowupController extends BaseCrmController
{
    public function history_ajax(int $clientId): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $this->setOutputSuccess('Historial de seguimiento', [
            'followups' => (new CrmFollowupModel())->getTree($clientId),
        ]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function store(int $clientId): ResponseInterface
    {
        if (! $this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $client = $this->clientModel->find($clientId);
        if (! $client) {
            $this->setOutputError('Cliente no encontrado.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $newStatus = $this->request->getPost('client_status') ?: $client->status;
        $services  = json_decode((string) $this->request->getPost('services_json'), true);
        if (! is_array($services)) {
            $services = [];
        }

        $model = new CrmFollowupModel();
        $newId = $model->createVersion([
            'client_id'              => $clientId,
            'parent_followup_id'     => $this->request->getPost('parent_followup_id') ?: null,
            'followup_type'          => $this->request->getPost('followup_type') ?: 'comment',
            'title'                  => trim((string) $this->request->getPost('title')),
            'comment'                => $this->request->getPost('comment'),
            'client_status_snapshot' => $newStatus,
            'created_by'             => auth()->id(),
            'change_source'          => $this->request->getPost('parent_followup_id') ? 'fork' : 'manual',
        ], $services);

        if (! $newId) {
            $this->setOutputError('No se pudo registrar el seguimiento.', $model->errors());
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        if ($newStatus !== $client->status) {
            $this->clientModel->update($clientId, ['status' => $newStatus]);
        }

        (new UserActivityLogsModel())->logActivity(
            'crm_followup_create',
            'Seguimiento #' . $newId . ' cliente ID ' . $clientId . ' ' . $client->company_name
        );

        $this->setOutputSuccess('Seguimiento registrado', [
            'id'        => $newId,
            'followups' => $model->getTree($clientId),
        ]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }
}
