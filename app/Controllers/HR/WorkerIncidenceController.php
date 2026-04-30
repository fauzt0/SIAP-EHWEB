<?php

namespace App\Controllers\HR;

class WorkerIncidenceController extends BaseHrController
{
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
            $this->setOutputSuccess('Incidencia registrada correctamente.');
        } else {
            $this->setOutputError('No se pudo registrar la incidencia.');
        }

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

        if ($this->updateIncidenceStatus((int)$incidenceId, $status, $notes)) {
            $this->setOutputSuccess('Estatus de incidencia actualizado.');
        } else {
            $this->setOutputError('Error al actualizar el estatus.');
        }

        return $this->response->setJSON($this->outputData);
    }

    /**
     * Lógica interna para registrar incidencia
     */
    private function recordIncidence(array $data): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $incidenceModel = new \App\Models\HR\HrIncidenceModel();
        $incidenceModel->insert($data);

        // Notificar al usuario vinculado si existe
        $profile = $this->profileModel->find($data['profile_id']);
        if ($profile && !empty($profile->user_id)) {
            $typeLabels = ['falta' => 'Falta', 'retardo' => 'Retardo', 'permiso' => 'Permiso', 'incapacidad' => 'Incapacidad'];
            $label = $typeLabels[$data['incidence_type']] ?? $data['incidence_type'];

            (new \App\Models\System\SysNotificationModel())->insert([
                'user_id'    => $profile->user_id,
                'title'      => 'Nueva Incidencia Registrada',
                'message'    => "Se ha registrado un(a) {$label} para la fecha {$data['date']}.",
                'type'       => 'incidence',
                'is_read'    => 0,
                'target_url' => null
            ]);
        }

        cache()->delete('hr_dashboard_stats');
        $db->transComplete();
        return $db->transStatus();
    }

    /**
     * Lógica interna para actualizar estatus
     */
    private function updateIncidenceStatus(int $incidenceId, string $status, string $notes = null): bool
    {
        $incidenceModel = new \App\Models\HR\HrIncidenceModel();
        $incidence = $incidenceModel->find($incidenceId);
        if (!$incidence) return false;

        $db = \Config\Database::connect();
        $db->transStart();

        $incidenceModel->update($incidenceId, ['status' => $status, 'notes' => $notes]);

        $profile = $this->profileModel->find($incidence->profile_id);
        if ($profile && !empty($profile->user_id)) {
            $statusLabels = ['justificada' => 'Justificada', 'descontada' => 'Descontada', 'rechazada' => 'Rechazada'];
            $statusText = $statusLabels[$status] ?? $status;

            (new \App\Models\System\SysNotificationModel())->insert([
                'user_id'    => $profile->user_id,
                'title'      => 'Actualización de Incidencia',
                'message'    => "Tu incidencia del {$incidence->date} ha sido marcada como: {$statusText}.",
                'type'       => 'incidence',
                'is_read'    => 0,
                'target_url' => null
            ]);
        }

        cache()->delete('hr_dashboard_stats');
        $db->transComplete();
        return $db->transStatus();
    }
}
