<?php

namespace App\Models\HR;

use App\Libraries\AlertService;
use CodeIgniter\Model;

class HrIncidenceModel extends Model
{
    protected $table            = 'hr_incidences';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'profile_id',
        'incidence_type',
        'date',
        'justified',
        'status',
        'notes'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Obtiene incidencias pendientes para el badge del dashboard.
     */
    public function getPendingCount(): int
    {
        return $this->where('status', 'pendiente')->countAllResults();
    }

    /**
     * Registra una incidencia y notifica al usuario vinculado (transacción atómica).
     */
    public function recordIncidence(array $data): bool
    {
        $this->db->transStart();

        $this->insert($data);
        $incidenceId = (int) $this->getInsertID();

        $profile = (new HrProfileModel())->find($data['profile_id'] ?? 0);
        if ($profile && !empty($profile->user_id) && $incidenceId > 0) {
            $typeLabels = ['falta' => 'Falta', 'retardo' => 'Retardo', 'permiso' => 'Permiso', 'incapacidad' => 'Incapacidad'];
            $label      = $typeLabels[$data['incidence_type'] ?? ''] ?? ($data['incidence_type'] ?? 'incidencia');

            $this->notifyLinkedUser(
                (int) $profile->user_id,
                'Nueva Incidencia Registrada',
                "Se ha registrado un(a) {$label} para la fecha {$data['date']}.",
                'warning',
                'fa-exclamation-circle',
                $incidenceId
            );
        }

        cache()->delete('hr_dashboard_stats');
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Actualiza el estatus de una incidencia y notifica al usuario vinculado.
     */
    public function updateIncidenceStatus(int $incidenceId, string $status, ?string $notes): bool
    {
        $incidence = $this->find($incidenceId);
        if (!$incidence) {
            return false;
        }

        $this->db->transStart();

        $this->update($incidenceId, ['status' => $status, 'notes' => $notes]);

        $profile = (new HrProfileModel())->find($incidence->profile_id);
        if ($profile && !empty($profile->user_id)) {
            $statusLabels = ['justificada' => 'Justificada', 'descontada' => 'Descontada', 'rechazada' => 'Rechazada'];
            $statusText   = $statusLabels[$status] ?? $status;

            $this->notifyLinkedUser(
                (int) $profile->user_id,
                'Actualización de Incidencia',
                "Tu incidencia del {$incidence->date} ha sido marcada como: {$statusText}.",
                'info',
                'fa-info-circle',
                $incidenceId
            );
        }

        cache()->delete('hr_dashboard_stats');
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Despacha una alerta persistente (§21) y la asigna al usuario del trabajador.
     */
    private function notifyLinkedUser(
        int $userId,
        string $title,
        string $message,
        string $type,
        string $icon,
        int $incidenceId
    ): void {
        $alertService = new AlertService();
        $alertId      = $alertService->dispatch(
            title: $title,
            message: $message,
            type: $type,
            icon: $icon,
            targetUrl: null,
            requiredPermission: null,
            module: 'hr',
            referenceType: 'incidence',
            referenceId: $incidenceId
        );

        if ($alertId > 0) {
            $alertService->assignToUsers($alertId, [$userId]);
        }
    }
}
