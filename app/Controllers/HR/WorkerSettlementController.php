<?php

namespace App\Controllers\HR;

use App\Controllers\BaseController;
use App\Models\HR\HrEmploymentModel;
use App\Models\HR\HrVacationModel;
use Config\HrConfig;

class WorkerSettlementController extends BaseHrController
{
    protected $employmentModel;
    protected $vacationModel;
    protected $hrConfig;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->employmentModel = new HrEmploymentModel();
        $this->vacationModel   = new HrVacationModel();
        $this->hrConfig        = config('HrConfig');
    }

    /**
     * calculateAjax
     * Realiza los cálculos de finiquito/liquidación en tiempo real.
     */
    public function calculateAjax(int $profileId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        }

        $type            = $this->request->getGet('type') ?? 'finiquito'; // 'finiquito' o 'liquidacion'
        $terminationDate = $this->request->getGet('date') ?? date('Y-m-d');
        $extraDays       = (int)($this->request->getGet('extra_days') ?? 0);

        $worker = $this->employmentModel->where('profile_id', $profileId)->first();
        if (!$worker) {
            $this->setOutputError('Datos laborales no encontrados.');
            return $this->response->setJSON($this->outputData);
        }

        $hiringDate = new \DateTime($worker->hiring_date);
        $termDate   = new \DateTime($terminationDate);
        
        if ($termDate < $hiringDate) {
            $this->setOutputError('La fecha de baja no puede ser anterior a la de ingreso.');
            return $this->response->setJSON($this->outputData);
        }

        // 1. Antigüedad
        $diff = $hiringDate->diff($termDate);
        $yearsOfService = $diff->y;
        $totalDaysWorked = $diff->days;

        // 2. Salarios
        $sd = (float)$worker->daily_salary;
        
        // Salario Diario Integrado (SDI) para Indemnizaciones
        // Usamos los días de vacaciones que le corresponden por su antigüedad real para la integración
        $vacationDaysForIntegration = $this->getLftVacationDays($yearsOfService > 0 ? $yearsOfService : 1);
        $factor = 1 + ($this->hrConfig->aguinaldoDaysDefault / 365) + (($vacationDaysForIntegration * $this->hrConfig->vacationPremiumPercent) / 365);
        $sdi = $sd * $factor;

        // 3. Aguinaldo Proporcional (Días trabajados en el año actual / 365)
        $yearStart = new \DateTime($termDate->format('Y-01-01'));
        $daysInYear = $termDate->diff($yearStart)->days + 1;
        $aguinaldoProp = ($this->hrConfig->aguinaldoDaysDefault / 365) * $daysInYear * $sd;

        // 4. Vacaciones Proporcionales (Días desde el último aniversario / 365)
        $lastAnniversary = clone $hiringDate;
        $lastAnniversary->setDate($termDate->format('Y'), $hiringDate->format('m'), $hiringDate->format('d'));
        if ($lastAnniversary > $termDate) {
            $lastAnniversary->modify('-1 year');
        }
        $daysSinceAnniversary = $termDate->diff($lastAnniversary)->days;
        
        // Obtenemos cuántos días le corresponden según su próximo aniversario
        $nextYear = $yearsOfService + 1;
        $vacDaysTotal = $this->getLftVacationDays($nextYear);
        $vacationsProp = ($vacDaysTotal / 365) * $daysSinceAnniversary * $sd;

        // 5. Prima Vacacional (25% de las vacaciones proporcionales)
        $primaVacProp = $vacationsProp * $this->hrConfig->vacationPremiumPercent;

        // 6. Sueldos pendientes
        $sueldosPendientes = $sd * $extraDays;

        $subtotalFiniquito = $aguinaldoProp + $vacationsProp + $primaVacProp + $sueldosPendientes;

        // 7. Indemnizaciones (Solo Liquidación)
        $indemnity90 = 0;
        $indemnity20 = 0;
        $seniorityPremium = 0;
        if ($type === 'liquidacion') {
            // Indemnización 90 días (Constitucional)
            $indemnity90 = $this->hrConfig->indemnityDays * $sdi;
            
            // Indemnización 20 días por año (Art. 50 LFT)
            $indemnity20 = 20 * ($yearsOfService + ($daysSinceAnniversary/365)) * $sdi;

            // Prima de antigüedad: 12 días por año laborado. 
            // Tope de 2 Salarios Mínimos
            $topeSueldoPrima = $this->hrConfig->minWageGeneral * 2;
            $sueldoParaPrima = min($sd, $topeSueldoPrima);
            $seniorityPremium = $this->hrConfig->seniorityPremiumDaysPerYear * ($yearsOfService + ($daysSinceAnniversary/365)) * $sueldoParaPrima;
        }

        $total = $subtotalFiniquito + $indemnity90 + $indemnity20 + $seniorityPremium;

        $data = [
            'seniority' => [
                'years' => $yearsOfService,
                'months' => $diff->m,
                'days' => $diff->d,
                'total_days' => $totalDaysWorked
            ],
            'salaries' => [
                'sd' => round($sd, 2),
                'sdi' => round($sdi, 2)
            ],
            'concepts' => [
                'aguinaldo' => [
                    'label' => 'Aguinaldo Proporcional',
                    'amount' => round($aguinaldoProp, 2),
                    'days' => round(($this->hrConfig->aguinaldoDaysDefault / 365) * $daysInYear, 2)
                ],
                'vacations' => [
                    'label' => 'Vacaciones Proporcionales',
                    'amount' => round($vacationsProp, 2),
                    'days' => round(($vacDaysTotal / 365) * $daysSinceAnniversary, 2)
                ],
                'vacation_premium' => [
                    'label' => 'Prima Vacacional (25%)',
                    'amount' => round($primaVacProp, 2)
                ],
                'pending_salary' => [
                    'label' => 'Salarios Devengados (' . $extraDays . ' días)',
                    'amount' => round($sueldosPendientes, 2)
                ]
            ],
            'indemnities' => ($type === 'liquidacion') ? [
                'constitutional' => [
                    'label' => 'Indemnización 90 días',
                    'amount' => round($indemnity90, 2)
                ],
                'indemnity20' => [
                    'label' => 'Indemnización 20 días p/año',
                    'amount' => round($indemnity20, 2)
                ],
                'seniority_premium' => [
                    'label' => 'Prima de Antigüedad',
                    'amount' => round($seniorityPremium, 2)
                ]
            ] : null,
            'subtotal_finiquito' => round($subtotalFiniquito, 2),
            'total' => round($total, 2)
        ];

        $this->setOutputSuccess('Cálculo realizado', $data);
        return $this->response->setJSON($this->outputData);
    }

    /**
     * processBajaAjax
     * Registra la baja oficial del trabajador.
     */
    public function processBajaAjax(int $profileId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        }

        $termDate = $this->request->getPost('termination_date');
        $summary  = $this->request->getPost('summary'); // Texto con los montos calculados

        $worker = $this->employmentModel->where('profile_id', $profileId)->first();
        if (!$worker) {
            $this->setOutputError('Trabajador no encontrado.');
            return $this->response->setJSON($this->outputData);
        }

        $updateData = [
            'status' => 'terminated',
            'termination_date' => $termDate,
            'notes' => trim(($worker->notes ?? '') . "\n\n[BAJA REGISTRADA " . date('Y-m-d H:i') . "]\n" . $summary)
        ];

        if ($this->employmentModel->update($profileId, $updateData)) {
            // Limpiar caché de estadísticas
            cache()->delete('hr_dashboard_stats');
            $this->setOutputSuccess('La baja ha sido registrada correctamente. El trabajador ahora figura como inactivo.');
        } else {
            $this->setOutputError('Error al registrar la baja.');
        }

        return $this->response->setJSON($this->outputData);
    }
}
