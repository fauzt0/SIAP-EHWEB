<?php

namespace App\Controllers\HR;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\HR\HrVacationModel;

class WorkerVacationController extends BaseHrController
{
    protected $vacationModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->vacationModel = new HrVacationModel();
    }

    /**
     * Calcula los días de vacaciones: Totales ganados, Disponibles (sin caducar) y Caducados.
     * Aplica la regla LFT: Caducan 18 meses después del aniversario (6 meses para darlas + 1 año para reclamar).
     */
    private function calculateVacationBalance(string $hiringDate, int $totalTaken): array
    {
        $hiring = new \DateTime($hiringDate);
        $now    = new \DateTime();
        
        if ($hiring > $now) return ['entitled' => 0, 'available' => 0, 'expired' => 0];

        $seniorityYears = $hiring->diff($now)->y;
        $expiredDays    = 0;
        $totalEarned    = 0;
        $remainingTaken = $totalTaken;

        // Recorremos cada año desde el primero hasta el actual
        for ($year = 1; $year <= $seniorityYears; $year++) {
            $daysInThisYear = $this->getLftVacationDays($year);
            $totalEarned += $daysInThisYear;

            // Fecha de aniversario de este año específico
            $anniversaryDate = (clone $hiring)->modify("+$year year");
            // Fecha de caducidad: 18 meses después del aniversario
            $expirationDate = (clone $anniversaryDate)->modify("+18 months");

            if ($now > $expirationDate) {
                // El periodo ya caducó legalmente
                // Primero usamos los días ya tomados para "cubrir" este periodo viejo
                if ($remainingTaken >= $daysInThisYear) {
                    $remainingTaken -= $daysInThisYear;
                } else {
                    // Si no se tomaron todos los días de este año, la diferencia se pierde
                    $expiredInYear = $daysInThisYear - $remainingTaken;
                    $expiredDays += $expiredInYear;
                    $remainingTaken = 0;
                }
            }
        }

        // El balance disponible es: Total ganado - Días tomados - Días caducados
        $available = max(0, $totalEarned - $totalTaken - $expiredDays);

        return [
            'entitled'  => $totalEarned,
            'available' => (int)$available,
            'expired'   => (int)$expiredDays
        ];
    }


    /**
     * Devuelve los datos de vacaciones de un perfil
     */
    public function getVacations(int $profileId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $employment = $this->employmentModel->where('profile_id', $profileId)->first();
        if (!$employment || empty($employment->hiring_date)) {
            $this->setOutputError('Fecha de contratación no encontrada.');
            return $this->response->setJSON($this->outputData);
        }

        $totalTaken    = $this->vacationModel->getTotalDaysTaken($profileId);
        $balance       = $this->calculateVacationBalance($employment->hiring_date, $totalTaken);

        $history = $this->vacationModel->where('profile_id', $profileId)->orderBy('start_date', 'DESC')->findAll();

        $data = [
            'total_entitled' => $balance['entitled'],
            'total_taken'    => $totalTaken,
            'available'      => $balance['available'],
            'expired'        => $balance['expired'],
            'history'        => $history,
            'hiring_date'    => $employment->hiring_date
        ];

        $this->setOutputSuccess('Datos obtenidos.', $data);

        return $this->response->setJSON($this->outputData);
    }

    /**
     * Registra una nueva solicitud de vacaciones
     */
    public function addVacationAjax()
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $profileId = (int)$this->request->getPost('profile_id');
        $startDate = $this->request->getPost('start_date');
        $endDate   = $this->request->getPost('end_date');
        $notes     = $this->request->getPost('notes');

        if (!$profileId || !$startDate || !$endDate) {
            $this->setOutputError('Datos incompletos.');
            return $this->response->setJSON($this->outputData);
        }

        // Calcular días hábiles entre fechas (L-V)
        $totalDays = $this->calculateBusinessDays($startDate, $endDate);

        if ($totalDays <= 0) {
            $this->setOutputError('El rango seleccionado no contiene días laborables válidos.');
            return $this->response->setJSON($this->outputData);
        }

        $data = [
            'profile_id' => $profileId,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'total_days' => $totalDays,
            'status'     => 'pendiente', // RH debe aprobarla desde el panel global
            'notes'      => $notes,
            'created_by' => user_id(),
        ];

        if ($this->vacationModel->insert($data)) {
            cache()->delete('hr_dashboard_stats');
            $this->setOutputSuccess('Vacaciones registradas correctamente.');
        } else {
            $this->setOutputError('Error al registrar las vacaciones.', $this->vacationModel->errors());
        }

        return $this->response->setJSON($this->outputData);
    }

    /**
     * Devuelve todas las solicitudes de vacaciones para el modal global
     */
    public function getGlobalVacations()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        // Obtener todas las vacaciones haciendo JOIN con el perfil para sacar el nombre
        $builder = $this->vacationModel->builder();
        $builder->select('
            hr_vacations.*, 
            COALESCE(users.first_name, hr_profiles.first_name) AS first_name, 
            COALESCE(users.last_name, hr_profiles.last_name) AS last_name, 
            hr_employment_data.employee_number
        ');
        $builder->join('hr_profiles', 'hr_profiles.id = hr_vacations.profile_id', 'left');
        $builder->join('users', 'users.id = hr_profiles.user_id', 'left');
        $builder->join('hr_employment_data', 'hr_employment_data.profile_id = hr_vacations.profile_id', 'left');
        $builder->orderBy('hr_vacations.created_at', 'DESC');
        
        $vacations = $builder->get()->getResult();

        $this->setOutputSuccess('Datos globales obtenidos.', $vacations);
        return $this->response->setJSON($this->outputData);
    }

    /**
     * Cambia el estado de una solicitud (Aprobar/Rechazar)
     */
    public function updateStatusAjax(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $status = $this->request->getPost('status');
        if (!in_array($status, ['pendiente', 'aprobado', 'rechazado'])) {
            $this->setOutputError('Estado inválido.');
            return $this->response->setJSON($this->outputData);
        }

        if ($this->vacationModel->update($id, ['status' => $status])) {
            cache()->delete('hr_dashboard_stats');
            $this->setOutputSuccess('Estado actualizado correctamente.');
        } else {
            $this->setOutputError('Error al actualizar el estado.');
        }

        return $this->response->setJSON($this->outputData);
    }

    /**
     * Elimina un registro de vacaciones (Solo Administrador y si no ha comenzado)
     */
    public function deleteAjax(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $vacation = $this->vacationModel->find($id);
        if (!$vacation) {
            $this->setOutputError('Registro no encontrado.');
            return $this->response->setJSON($this->outputData);
        }

        $now = new \DateTime();
        $startDate = new \DateTime($vacation->start_date);
        
        // No permitir eliminar si ya comenzó o es hoy
        if ($now >= $startDate->setTime(0,0,0)) {
            $this->setOutputError('No se puede eliminar una vacación que ya ha comenzado o está en curso.');
            return $this->response->setJSON($this->outputData);
        }

        if ($this->vacationModel->delete($id)) {
            cache()->delete('hr_dashboard_stats');
            $this->setOutputSuccess('Registro eliminado correctamente. Los días han sido devueltos al balance del trabajador.');
        } else {
            $this->setOutputError('Error al eliminar el registro.');
        }
        return $this->response->setJSON($this->outputData);
    }
}
