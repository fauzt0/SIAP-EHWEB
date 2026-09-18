<?php

namespace App\Controllers\HR;

use App\Models\HR\HrIncidenceModel;
use App\Models\Users\UserActivityLogsModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class WorkerIncidenceController extends BaseHrController
{
    protected HrIncidenceModel $incidenceModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->incidenceModel = new HrIncidenceModel();
    }

    /**
     * Registra una nueva incidencia vía AJAX (Fase 2)
     */
    public function addIncidenceAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        }

        $data = [
            'profile_id'     => $this->request->getPost('profile_id'),
            'incidence_type' => $this->request->getPost('incidence_type'),
            'date'           => $this->request->getPost('date'),
            'justified'      => $this->request->getPost('justified') ? 1 : 0,
            'notes'          => $this->request->getPost('notes'),
            'status'         => 'pendiente'
        ];

        if ($this->recordIncidence($data)) {
            (new UserActivityLogsModel())->logActivity(
                'create_incidence',
                'Registró incidencia ' . $data['incidence_type'] . ' para profile_id: ' . $data['profile_id'] . ' (fecha: ' . $data['date'] . ')'
            );
            $this->setOutputSuccess('Incidencia registrada correctamente.');
        } else {
            $this->setOutputError('No se pudo registrar la incidencia.');
        }

        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    /**
     * Actualiza el estatus de una incidencia vía AJAX (Fase 2)
     */
    public function updateIncidenceStatusAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        }

        $incidenceId = $this->request->getPost('id');
        $status      = $this->request->getPost('status');
        $notes       = $this->request->getPost('notes');

        if ($this->updateIncidenceStatus((int) $incidenceId, $status, $notes)) {
            (new UserActivityLogsModel())->logActivity(
                'update_incidence',
                'Actualizó estatus de incidencia ID: ' . $incidenceId . ' a: ' . $status
            );
            $this->setOutputSuccess('Estatus de incidencia actualizado.');
        } else {
            $this->setOutputError('Error al actualizar el estatus.');
        }

        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    /**
     * Delega el registro de incidencia al modelo (transacción en capa de datos).
     */
    private function recordIncidence(array $data): bool
    {
        return $this->incidenceModel->recordIncidence($data);
    }

    /**
     * Delega la actualización de estatus al modelo (transacción en capa de datos).
     */
    private function updateIncidenceStatus(int $incidenceId, string $status, ?string $notes = null): bool
    {
        return $this->incidenceModel->updateIncidenceStatus($incidenceId, $status, $notes);
    }
}
