<?php

namespace App\Services;

use App\Models\HR\HrProfileModel;
use App\Models\HR\HrEmploymentModel;
use App\Models\HR\ContractTemplateModel;
use App\Models\HR\WorkerContractModel;
use App\Libraries\PdfLibrary;

/**
 * ContractService
 * ─────────────────────────────────────────────────────────────────────────────
 * Servicio encargado de la orquestación de contratos: reemplazo de variables,
 * guardado en histórico y generación de archivos PDF.
 */
class ContractService
{
    protected $profileModel;
    protected $employmentModel;
    protected $templateModel;
    protected $workerContractModel;

    public function __construct()
    {
        $this->profileModel        = new HrProfileModel();
        $this->employmentModel     = new HrEmploymentModel();
        $this->templateModel       = new ContractTemplateModel();
        $this->workerContractModel = new WorkerContractModel();
    }

    /**
     * Genera un contrato para un trabajador basado en una plantilla.
     * 
     * @param int $profileId
     * @param int|null $templateId Si es null, usa la plantilla por defecto.
     * @param string $reason Motivo de la generación.
     * @return int|bool ID del contrato generado o false.
     */
    public function generateContract(int $profileId, int $templateId = null, string $reason = 'Generación automática')
    {
        // 1. Obtener datos del trabajador
        $worker = $this->profileModel->find($profileId);
        if (!$worker) return false;

        $employment = $this->employmentModel->where('profile_id', $profileId)->first();
        if (!$employment) return false;

        // 2. Obtener plantilla
        $template = $templateId ? $this->templateModel->find($templateId) : $this->templateModel->getDefaultTemplate();
        if (!$template) return false;

        // 3. Reemplazar variables
        $finalContent = $this->replaceVariables($template->content, $worker, $employment);

        // 4. Guardar en histórico
        $contractData = [
            'profile_id'       => $profileId,
            'template_id'      => $template->id,
            'contract_type_id' => $employment->contract_type_id ?? null,
            'content_snapshot' => $finalContent,
            'reason'           => $reason,
            'status'           => 'active'
        ];

        return $this->workerContractModel->insert($contractData);
    }

    public function previewTemplateRender(int $profileId, int $templateId): string
    {
        $worker = $this->profileModel->find($profileId);
        $employment = $this->employmentModel->where('profile_id', $profileId)->first();
        $template = $this->templateModel->find($templateId);

        if (!$worker || !$employment || !$template) {
            return '<p>Error al cargar los datos para previsualizar la plantilla.</p>';
        }

        $html = $this->replaceVariables($template->content, $worker, $employment);

        return $html;
    }

    public function getLftTemplateHtml(int $profileId): string
    {
        $worker = $this->profileModel->find($profileId);
        $employment = $this->employmentModel->where('profile_id', $profileId)->first();
        
        // Plantilla hardcodeada básica basada en la Ley Federal del Trabajo (Ejemplo simplificado)
        $lftContent = '
            <h2 style="text-align: center; text-transform: uppercase;">CONTRATO INDIVIDUAL DE TRABAJO (LFT)</h2>
            <p>CONTRATO INDIVIDUAL DE TRABAJO QUE CELEBRAN POR UNA PARTE <strong>{{nombre_empresa}}</strong> (EN LO SUCESIVO "EL PATRÓN") REPRESENTADA EN ESTE ACTO POR SU REPRESENTANTE LEGAL, Y POR LA OTRA PARTE EL C. <strong>{{nombre_trabajador}}</strong> (EN LO SUCESIVO "EL TRABAJADOR") AL TENOR DE LAS SIGUIENTES DECLARACIONES Y CLÁUSULAS:</p>
            <h3>DECLARACIONES</h3>
            <p><strong>I. Declara "EL PATRÓN":</strong></p>
            <p>Que es una persona moral legalmente constituida de conformidad con las leyes de la República Mexicana, con domicilio en sus instalaciones correspondientes y con capacidad legal para obligarse y contratar.</p>
            <p><strong>II. Declara "EL TRABAJADOR":</strong></p>
            <p>Que su nombre es <strong>{{nombre_trabajador}}</strong>, de nacionalidad <strong>{{nacionalidad}}</strong>, fecha de nacimiento <strong>{{fecha_nacimiento}}</strong>, con Registro Federal de Contribuyentes <strong>{{rfc}}</strong>, Clave Única de Registro de Población <strong>{{curp}}</strong> y domicilio en <strong>{{domicilio}}</strong>.</p>
            <h3>CLÁUSULAS</h3>
            <p><strong>PRIMERA.-</strong> "EL PATRÓN" contrata a "EL TRABAJADOR" para que preste sus servicios en el puesto de <strong>{{puesto}}</strong>, adscrito al departamento de <strong>{{departamento}}</strong>. Las actividades a desarrollar serán las inherentes al puesto mencionado y las que indique "EL PATRÓN".</p>
            <p><strong>SEGUNDA.-</strong> La jornada de trabajo será determinada de común acuerdo entre las partes, debiendo "EL TRABAJADOR" cumplir con las horas legales establecidas.</p>
            <p><strong>TERCERA.-</strong> "EL TRABAJADOR" percibirá por la prestación de sus servicios la cantidad de <strong>{{sueldo_mensual}}</strong> mensuales, pagaderos en la forma y términos convenidos.</p>
            <p><strong>CUARTA.-</strong> Este contrato rige a partir de la fecha <strong>{{fecha_ingreso}}</strong> conforme a lo dispuesto por la Ley Federal del Trabajo.</p>
            <br><br><br>
            <table style="width: 100%; text-align: center; margin-top: 50px;">
                <tr>
                    <td style="width: 50%;">
                        __________________________________<br>
                        <strong>Por la Empresa</strong><br>
                        Representante Legal
                    </td>
                    <td style="width: 50%;">
                        __________________________________<br>
                        <strong>Firma del Trabajador</strong><br>
                        {{nombre_trabajador}}
                    </td>
                </tr>
            </table>
        ';

        if (!$worker || !$employment) {
            return $lftContent;
        }

        return $this->replaceVariables($lftContent, $worker, $employment);
    }

    /**
     * Lógica de reemplazo de variables {{...}}
     */
    protected function replaceVariables(string $content, $worker, $employment): string
    {
        $vars = [
            '{{nombre_trabajador}}'   => $worker->first_name . ' ' . $worker->last_name,
            '{{rfc}}'                 => $worker->rfc,
            '{{curp}}'                => $worker->curp,
            '{{nacionalidad}}'        => $worker->nationality,
            '{{fecha_nacimiento}}'    => $worker->birth_date,
            '{{domicilio}}'           => $worker->address_full,
            '{{puesto}}'              => $this->getJobName($employment->job_id),
            '{{departamento}}'        => $this->getDeptName($employment->department_id),
            '{{sueldo_mensual}}'      => $employment->current_salary ? '$' . number_format($employment->current_salary, 2) : '',
            '{{sueldo_diario}}'       => $employment->daily_salary ? '$' . number_format($employment->daily_salary, 2) : '',
            '{{fecha_ingreso}}'       => $employment->hiring_date,
            '{{fecha_actual}}'        => date('d/m/Y'),
            '{{nombre_empresa}}'      => 'ESPECIALISTAS HOSTING S.A. DE C.V.',
            '{{nombre_representante}}' => 'REPRESENTANTE LEGAL', // Podría venir de settings en el futuro
            '{{domicilio_patron}}'    => 'Domicilio Fiscal de la Empresa',
            '{{rfc_patron}}'          => 'RFC_EMPRESA_123',
        ];

        // Procesar cada variable: si está vacía, poner "N/A"
        foreach ($vars as $key => $val) {
            $value = trim((string)$val);
            $vars[$key] = empty($value) ? '<span style="color: red;">N/A</span>' : esc($value);
        }

        return str_replace(array_keys($vars), array_values($vars), $content);
    }

    protected function getJobName($jobId)
    {
        if (!$jobId) return 'Sin puesto';
        $jobModel = new \App\Models\HR\HrJobModel();
        $job = $jobModel->find($jobId);
        return $job ? esc($job->name) : 'Sin puesto';
    }

    protected function getDeptName($deptId)
    {
        if (!$deptId) return 'Sin departamento';
        $deptModel = new \App\Models\HR\HrDepartmentModel();
        $dept = $deptModel->find($deptId);
        return $dept ? esc($dept->name) : 'Sin departamento';
    }

    /**
     * Genera el PDF de un contrato específico.
     */
    public function exportToPdf(int $contractId, $dest = 'D')
    {
        $contract = $this->workerContractModel->find($contractId);
        if (!$contract) return false;

        // Obtener la plantilla: primero la del contrato, si no hay, usar la plantilla por defecto
        $template = $contract->template_id
            ? $this->templateModel->find($contract->template_id)
            : $this->templateModel->getDefaultTemplate();

        $logoHtml = '';
        if ($template && !empty($template->header_logo)) {
            $logoPath = FCPATH . $template->header_logo;
            if (file_exists($logoPath)) {
                // mPDF requires file:// prefix for absolute local filesystem paths
                $logoHtml = '<div style="text-align: right; margin-bottom: 20px;">
                                <img src="file://' . $logoPath . '" style="max-height: 80px;" alt="Logo">
                             </div>';
            }
        }

        $pdf = new PdfLibrary();
        
        // Estilo Premium corporativo para el PDF
        $html = '
        <style>
            body { 
                font-family: "Helvetica", "Arial", sans-serif; 
                font-size: 11pt; 
                color: #2c3e50; 
                line-height: 1.6; 
                text-align: justify;
            }
            h2, h3 { 
                color: #1a5276; /* Azul oscuro corporativo */
                margin-top: 15px;
                margin-bottom: 10px;
            }
            h2 { 
                text-align: center; 
                font-size: 16pt; 
                text-transform: uppercase;
                border-bottom: 2px solid #3498db;
                padding-bottom: 5px;
            }
            h3 { 
                font-size: 13pt; 
            }
            p { 
                margin-bottom: 12px; 
            }
            strong { 
                color: #111; 
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-top: 20px;
            }
            .signature-box {
                text-align: center;
                padding: 20px;
            }
            .signature-line {
                width: 80%;
                border-bottom: 1px solid #000;
                margin: 40px auto 10px auto;
            }
        </style>
        ' . $logoHtml . '<div style="padding: 0 15px;">' . $contract->content_snapshot . '</div>';

        $pdf->loadHtml($html);
        
        $filename = 'Contrato_' . str_replace(' ', '_', $contract->id) . '.pdf';
        
        // Devolvemos el binario y el nombre para que el controlador lo maneje
        return [
            'content'  => $pdf->getAsString(),
            'filename' => $filename,
            'dest'     => $dest
        ];
    }
}
