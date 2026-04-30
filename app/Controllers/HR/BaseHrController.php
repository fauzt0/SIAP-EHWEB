<?php

namespace App\Controllers\HR;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Breadcrumb;

// Modelos principales de HR compartidos
use App\Models\HR\HrProfileModel;
use App\Models\HR\HrEmploymentModel;

/**
 * BaseHrController
 * 
 * Controlador base para el módulo de Recursos Humanos.
 * Proporciona inicialización común, breadcrumbs y modelos base.
 */
class BaseHrController extends BaseController
{
    protected $helpers = ['form', 'url'];
    protected $breadcrumb;

    protected $profileModel;
    protected $employmentModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Instanciar modelos principales usados en todo el módulo
        $this->profileModel    = new HrProfileModel();
        $this->employmentModel = new HrEmploymentModel();

        // Configuración inicial del Breadcrumb para RRHH
        $this->breadcrumb = new Breadcrumb([
            'Inicio'           => base_url(),
            'Recursos Humanos' => route_to('hr.workers'),
        ]);

        // Título y cabecera por defecto del módulo
        $this->setPageTittleAhead('Recursos Humanos', 'Gestión de Recursos Humanos');
    }

    /**
     * Tabla de vacaciones LFT (Reforma 2023)
     */
    protected function getLftVacationDays(int $year): int
    {
        if ($year <= 0) return 12; // Mínimo
        if ($year == 1) return 12;
        if ($year == 2) return 14;
        if ($year == 3) return 16;
        if ($year == 4) return 18;
        if ($year == 5) return 20;
        if ($year >= 6 && $year <= 10) return 22;
        if ($year >= 11 && $year <= 15) return 24;
        if ($year >= 16 && $year <= 20) return 26;
        if ($year >= 21 && $year <= 25) return 28;
        if ($year >= 26 && $year <= 30) return 30;
        if ($year >= 31 && $year <= 35) return 32;
        return 34; // Max
    }

    /**
     * Calcula días hábiles entre dos fechas (Excluyendo Sábados y Domingos)
     */
    protected function calculateBusinessDays(string $startDate, string $endDate): int
    {
        $start = new \DateTime($startDate);
        $end   = new \DateTime($endDate);
        $end->modify('+1 day');

        $interval = \DateInterval::createFromDateString('1 day');
        $period   = new \DatePeriod($start, $interval, $end);

        $businessDays = 0;
        foreach ($period as $dt) {
            if ($dt->format('N') < 6) {
                $businessDays++;
            }
        }
        return $businessDays;
    }
}
