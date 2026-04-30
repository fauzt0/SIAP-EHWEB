<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * HrConfig
 * ─────────────────────────────────────────────────────────────────────────────
 * Variables de configuración y constantes legales para el módulo de RRHH.
 */
class HrConfig extends BaseConfig
{
    /**
     * @var float Salario Mínimo General (SMG) 2024 - Resto del país
     * Se usa como tope para el cálculo de la Prima de Antigüedad (tope de 2 SMG).
     */
    public $minWageGeneral = 248.93;

    /**
     * @var float Salario Mínimo Zona Libre Frontera Norte 2024
     */
    public $minWageBorder = 374.89;

    /**
     * @var int Días mínimos de Aguinaldo por Ley (LFT Art. 87)
     */
    public $aguinaldoDaysDefault = 15;

    /**
     * @var float Porcentaje mínimo de Prima Vacacional (LFT Art. 80)
     */
    public $vacationPremiumPercent = 0.25;

    /**
     * @var int Días de indemnización constitucional por despido injustificado (LFT Art. 48)
     */
    public $indemnityDays = 90;

    /**
     * @var int Días de prima de antigüedad por año laborado (LFT Art. 162)
     */
    public $seniorityPremiumDaysPerYear = 12;
}
