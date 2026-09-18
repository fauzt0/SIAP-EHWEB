<?php

namespace App\Services;

use App\Models\HR\HrProfileModel;
use App\Models\HR\HrEmploymentModel;
use App\Models\HR\ContractTemplateModel;
use App\Models\HR\WorkerContractModel;
use App\Models\HR\HrWorkerScheduleModel;
use App\Models\HR\HrShiftModel;
use App\Models\Organization\OrgBranchModel;
use App\Models\Organization\OrgCompanyModel;
use App\Libraries\PdfLibrary;

/**
 * Orquestación de contratos: variables dinámicas, versionado (fork) y PDF.
 */
class ContractService
{
    /** Campos del perfil que disparan un nuevo contrato al cambiar. */
    public const PROFILE_CONTRACT_FIELDS = [
        'first_name', 'last_name', 'nationality', 'curp', 'rfc', 'nss',
        'birth_date', 'address_full', 'phone_personal', 'personal_email',
        'bank_name', 'bank_account', 'bank_clabe',
    ];

    /** Campos laborales que disparan un nuevo contrato al cambiar. */
    public const EMPLOYMENT_CONTRACT_FIELDS = [
        'department_id', 'job_id', 'location_id', 'current_salary', 'daily_salary',
        'hiring_date', 'payroll_type', 'worker_type', 'corporate_email',
    ];

    protected HrProfileModel $profileModel;
    protected HrEmploymentModel $employmentModel;
    protected ContractTemplateModel $templateModel;
    protected WorkerContractModel $workerContractModel;

    public function __construct()
    {
        $this->profileModel        = new HrProfileModel();
        $this->employmentModel     = new HrEmploymentModel();
        $this->templateModel       = new ContractTemplateModel();
        $this->workerContractModel = new WorkerContractModel();
    }

    /**
     * Genera un contrato desde plantilla (alta o fork automático).
     */
    public function generateContract(
        int $profileId,
        ?int $templateId = null,
        string $reason = 'Generación automática',
        string $changeSource = 'auto',
        ?int $parentContractId = null
    ): int|false {
        $worker = $this->profileModel->find($profileId);
        if (! $worker) {
            return false;
        }

        $employment = $this->employmentModel->where('profile_id', $profileId)->first();
        if (! $employment) {
            return false;
        }

        $template = $templateId ? $this->templateModel->find($templateId) : $this->templateModel->getDefaultTemplate();
        if (! $template) {
            return false;
        }

        $finalContent = $this->replaceVariables($template->content, $worker, $employment);

        $contractData = [
            'profile_id'         => $profileId,
            'parent_contract_id' => $parentContractId,
            'template_id'        => $template->id,
            'contract_type_id'   => null,
            'content_snapshot'   => $finalContent,
            'reason'             => $reason,
            'change_source'      => $changeSource,
            'status'             => 'active',
        ];

        return $this->workerContractModel->createVersion($contractData);
    }

    /**
     * Fork automático si cambiaron datos relevantes del trabajador.
     */
    public function forkOnDataChange(
        int $profileId,
        object $oldProfile,
        object $oldEmployment,
        array $newProfileData,
        array $newEmploymentData,
        ?int $oldShiftId = null,
        ?int $newShiftId = null,
        bool $scheduleChanged = false
    ): int|false {
        if (! $this->hasContractRelevantChanges($oldProfile, $oldEmployment, $newProfileData, $newEmploymentData, $oldShiftId, $newShiftId, $scheduleChanged)) {
            return false;
        }

        $active = $this->workerContractModel->getActiveContract($profileId);
        if (! $active) {
            return $this->generateContract($profileId, null, 'Contrato generado por cambios en datos del trabajador', 'auto');
        }

        $templateId = $active->template_id;
        $reason     = 'Actualización automática por cambios en datos del trabajador';

        return $this->generateContract($profileId, $templateId, $reason, 'fork', $active->id);
    }

    /**
     * Guarda un contrato manual editado como nueva versión.
     */
    public function saveManualContractVersion(
        int $profileId,
        string $content,
        int $contractTypeId,
        ?int $templateId,
        string $reason,
        ?int $parentContractId = null,
        bool $saveAsTemplate = false
    ): int|false {
        $contractData = [
            'profile_id'         => $profileId,
            'parent_contract_id' => $parentContractId,
            'template_id'        => $templateId ?: null,
            'contract_type_id'   => $contractTypeId,
            'content_snapshot'   => $content,
            'reason'             => $reason,
            'change_source'      => $parentContractId ? 'fork' : 'manual',
            'status'             => 'active',
        ];

        $newId = $this->workerContractModel->createVersion($contractData);

        if ($newId && $saveAsTemplate && $templateId) {
            $origTemplate = $this->templateModel->find($templateId);
            if ($origTemplate && ! empty($origTemplate->content)) {
                $typeModel = new \App\Models\HR\HrContractTypeModel();
                $typeData  = $typeModel->find($contractTypeId);
                $typeName  = $typeData ? $typeData->name : 'Manual';

                $this->templateModel->insert([
                    'name'        => substr('Plantilla: ' . $typeName . ' (' . date('Y-m-d H:i') . ')', 0, 99),
                    'description' => 'Copia de plantilla original (variables {{}}), no del contrato renderizado.',
                    'content'     => $origTemplate->content,
                    'base_model'  => $origTemplate->base_model ?? 'corporate',
                ]);
            }
        }

        if ($newId) {
            $this->persistPdf($newId);
        }

        return $newId;
    }

    public function previewTemplateRender(int $profileId, int $templateId): string
    {
        $worker     = $this->profileModel->find($profileId);
        $employment = $this->employmentModel->where('profile_id', $profileId)->first();
        $template   = $this->templateModel->find($templateId);

        if (! $worker || ! $employment || ! $template) {
            return '<p>Error al cargar los datos para previsualizar la plantilla.</p>';
        }

        return $this->replaceVariables($template->content, $worker, $employment);
    }

    public function getLftTemplateHtml(int $profileId): string
    {
        $worker     = $this->profileModel->find($profileId);
        $employment = $this->employmentModel->where('profile_id', $profileId)->first();

        $lftContent = \App\Libraries\ContractBaseModels::getModel('lft');

        if (! $worker || ! $employment) {
            return $lftContent;
        }

        return $this->replaceVariables($lftContent, $worker, $employment);
    }

    /**
     * Detecta si hubo cambios en campos que afectan el contrato.
     */
    public function hasContractRelevantChanges(
        object $oldProfile,
        object $oldEmployment,
        array $newProfileData,
        array $newEmploymentData,
        ?int $oldShiftId = null,
        ?int $newShiftId = null,
        bool $scheduleChanged = false
    ): bool {
        if ($scheduleChanged) {
            return true;
        }

        foreach (self::PROFILE_CONTRACT_FIELDS as $field) {
            $old = $oldProfile->{$field} ?? null;
            $new = $newProfileData[$field] ?? $old;
            if (! self::profileFieldEquals($field, $old, $new)) {
                return true;
            }
        }

        foreach (self::EMPLOYMENT_CONTRACT_FIELDS as $field) {
            $old = $oldEmployment->{$field} ?? null;
            $new = $newEmploymentData[$field] ?? $oldEmployment->{$field} ?? null;
            if (! self::employmentFieldEquals($field, $old, $new)) {
                return true;
            }
        }

        if ($oldShiftId !== $newShiftId) {
            return true;
        }

        return false;
    }

    private static function profileFieldEquals(string $field, $oldVal, $newVal): bool
    {
        if ($field === 'birth_date') {
            return self::normalizeDate($oldVal) === self::normalizeDate($newVal);
        }

        return self::normalizeString($oldVal) === self::normalizeString($newVal);
    }

    private static function employmentFieldEquals(string $field, $oldVal, $newVal): bool
    {
        if (in_array($field, ['current_salary', 'daily_salary'], true)) {
            return round((float) $oldVal, 2) === round((float) $newVal, 2);
        }

        if (in_array($field, ['department_id', 'job_id', 'location_id'], true)) {
            return self::normalizeId($oldVal) === self::normalizeId($newVal);
        }

        if ($field === 'hiring_date') {
            return self::normalizeDate($oldVal) === self::normalizeDate($newVal);
        }

        return self::normalizeString($oldVal) === self::normalizeString($newVal);
    }

    private static function normalizeId($val): ?int
    {
        if ($val === null || $val === '' || $val === '0' || $val === 0) {
            return null;
        }

        return (int) $val;
    }

    private static function normalizeDate($val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }

        return substr((string) $val, 0, 10);
    }

    private static function normalizeString($val): string
    {
        if ($val === null) {
            return '';
        }

        return trim((string) $val);
    }

    protected function replaceVariables(string $content, $worker, $employment): string
    {
        $branchProfile = $this->resolveBranchProfile($employment);
        $scheduleInfo  = $this->resolveScheduleInfo((int) $worker->id);

        $payrollLabels = [
            'semanal'    => 'Semanal',
            'quincenal'  => 'Quincenal',
            'mensual'    => 'Mensual',
            'catorcenal' => 'Catorcenal',
        ];

        $vars = [
            '{{nombre_trabajador}}'    => trim(($worker->first_name ?? '') . ' ' . ($worker->last_name ?? '')),
            '{{rfc}}'                  => $worker->rfc ?? '',
            '{{curp}}'                 => $worker->curp ?? '',
            '{{nss}}'                  => $worker->nss ?? '',
            '{{nacionalidad}}'         => $worker->nationality ?? '',
            '{{fecha_nacimiento}}'     => ! empty($worker->birth_date) ? date('d/m/Y', strtotime($worker->birth_date)) : '',
            '{{domicilio}}'            => $worker->address_full ?? '',
            '{{telefono}}'             => $worker->phone_personal ?? '',
            '{{email}}'                => $worker->personal_email ?? '',
            '{{puesto}}'               => $this->getJobName($employment->job_id),
            '{{departamento}}'         => $this->getDeptName($employment->department_id),
            '{{sucursal}}'             => $branchProfile['branch_name'] ?? '',
            '{{sueldo_mensual}}'       => $employment->current_salary ? '$' . number_format((float) $employment->current_salary, 2) : '',
            '{{sueldo_diario}}'        => $employment->daily_salary ? '$' . number_format((float) $employment->daily_salary, 2) : '',
            '{{fecha_ingreso}}'        => ! empty($employment->hiring_date) ? date('d/m/Y', strtotime($employment->hiring_date)) : '',
            '{{fecha_actual}}'         => date('d/m/Y'),
            '{{dias_pago}}'            => $payrollLabels[$employment->payroll_type ?? ''] ?? ($employment->payroll_type ?? ''),
            '{{horario}}'              => $scheduleInfo['label'],
            '{{horas_semanales}}'      => $scheduleInfo['weekly_hours'],
            '{{nombre_empresa}}'       => $branchProfile['legal_name'] ?: $branchProfile['commercial_name'],
            '{{nombre_comercial}}'     => $branchProfile['commercial_name'],
            '{{nombre_representante}}' => $branchProfile['representative'] ?? 'REPRESENTANTE LEGAL',
            '{{domicilio_patron}}'     => $branchProfile['address'] ?? '',
            '{{rfc_patron}}'           => $branchProfile['tax_id'] ?? '',
            '{{telefono_patron}}'      => $branchProfile['phone'] ?? '',
            '{{email_patron}}'         => $branchProfile['email'] ?? '',
        ];

        foreach ($vars as $key => $val) {
            $value      = trim((string) $val);
            $vars[$key] = $value === '' ? '<span style="color: red;">N/A</span>' : esc($value);
        }

        return str_replace(array_keys($vars), array_values($vars), $content);
    }

    /**
     * Datos fiscales y de marca: sucursal del trabajador → empresa.
     */
    protected function resolveBranchProfile($employment): array
    {
        $branchModel  = new OrgBranchModel();
        $companyModel = new OrgCompanyModel();
        $company      = $companyModel->first();

        $defaults = [
            'branch_name'     => '',
            'commercial_name' => $company->commercial_name ?? '',
            'legal_name'      => $company->legal_name ?? $company->commercial_name ?? '',
            'tax_id'          => $company->tax_id ?? '',
            'address'         => '',
            'phone'           => $company->primary_phone ?? '',
            'email'           => $company->primary_email ?? '',
            'logo_path'       => $company->logo_path ?? null,
            'representative'  => 'REPRESENTANTE LEGAL',
        ];

        if (empty($employment->location_id)) {
            return $defaults;
        }

        $fiscal = $branchModel->getFiscalProfile((int) $employment->location_id);
        if (! $fiscal) {
            return $defaults;
        }

        return [
            'branch_name'     => $fiscal->commercial_name,
            'commercial_name' => $fiscal->commercial_name,
            'legal_name'      => $fiscal->legal_name ?: $fiscal->commercial_name,
            'tax_id'          => $fiscal->tax_id ?? $defaults['tax_id'],
            'address'         => $fiscal->address ?? $defaults['address'],
            'phone'           => $fiscal->phone ?? $defaults['phone'],
            'email'           => $fiscal->email ?? $defaults['email'],
            'logo_path'       => $fiscal->logo_path ?: $defaults['logo_path'],
            'representative'  => 'REPRESENTANTE LEGAL',
        ];
    }

    protected function resolveScheduleInfo(int $profileId): array
    {
        $scheduleModel = new HrWorkerScheduleModel();
        $shiftModel    = new HrShiftModel();

        $schedule = $scheduleModel
            ->where('profile_id', $profileId)
            ->orderBy('start_date', 'DESC')
            ->first();

        if (! $schedule) {
            return ['label' => 'Por definir', 'weekly_hours' => 'Por definir'];
        }

        $shift = $shiftModel->find($schedule->shift_id);
        if (! $shift) {
            return ['label' => 'Por definir', 'weekly_hours' => 'Por definir'];
        }

        $daysMap = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
        $days    = array_filter(array_map('trim', explode(',', (string) $shift->work_days)));
        $dayLabels = array_map(static fn ($d) => $daysMap[(int) $d] ?? $d, $days);

        $start = substr((string) $shift->start_time, 0, 5);
        $end   = substr((string) $shift->end_time, 0, 5);

        $startTs = strtotime($shift->start_time);
        $endTs   = strtotime($shift->end_time);
        if ($endTs <= $startTs) {
            $endTs += 86400;
        }
        $hoursPerDay = max(0, ($endTs - $startTs) / 3600);
        $weeklyHours = round($hoursPerDay * count($days), 1);

        return [
            'label'        => $shift->name . ' (' . $start . ' - ' . $end . ', ' . implode(', ', $dayLabels) . ')',
            'weekly_hours' => (string) $weeklyHours . ' horas',
        ];
    }

    protected function getJobName($jobId): string
    {
        if (! $jobId) {
            return '';
        }
        $job = (new \App\Models\HR\HrJobModel())->find($jobId);

        return $job ? $job->name : '';
    }

    protected function getDeptName($deptId): string
    {
        if (! $deptId) {
            return '';
        }
        $dept = (new \App\Models\HR\HrDepartmentModel())->find($deptId);

        return $dept ? $dept->name : '';
    }

    /**
     * Logo para PDF: sucursal → plantilla → empresa (por profile o contrato).
     */
    public function resolveLogoPathForContext(?int $profileId = null, ?int $templateId = null, ?int $contractId = null): ?string
    {
        if ($contractId) {
            $contract = $this->workerContractModel->find($contractId);
            if ($contract) {
                $profileId  = (int) $contract->profile_id;
                $templateId = $contract->template_id ? (int) $contract->template_id : $templateId;
            }
        }

        if ($profileId) {
            $employment = $this->employmentModel->where('profile_id', $profileId)->first();
            if ($employment && ! empty($employment->location_id)) {
                $fiscal = (new OrgBranchModel())->getFiscalProfile((int) $employment->location_id);
                if ($fiscal && ! empty($fiscal->logo_path) && file_exists(FCPATH . $fiscal->logo_path)) {
                    return FCPATH . $fiscal->logo_path;
                }
            }
        }

        $template = $templateId
            ? $this->templateModel->find($templateId)
            : $this->templateModel->getDefaultTemplate();

        if ($template && ! empty($template->header_logo) && file_exists(FCPATH . $template->header_logo)) {
            return FCPATH . $template->header_logo;
        }

        $company = (new OrgCompanyModel())->first();
        if ($company && ! empty($company->logo_path) && file_exists(FCPATH . $company->logo_path)) {
            return FCPATH . $company->logo_path;
        }

        return null;
    }

    /**
     * Pie de página del PDF desde plantilla.
     */
    public function resolveFooterHtml(?int $templateId = null): string
    {
        $template = $templateId
            ? $this->templateModel->find($templateId)
            : $this->templateModel->getDefaultTemplate();

        if ($template && ! empty($template->footer_text)) {
            return '<div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #ccc; font-size: 9pt; color: #666; text-align: center;">'
                . $template->footer_text . '</div>';
        }

        return '';
    }

    /**
     * HTML completo para PDF (logo + cuerpo + footer). Usado por export y preview.
     */
    public function buildPdfHtml(string $bodyContent, ?int $profileId = null, ?int $templateId = null, ?int $contractId = null): string
    {
        $logoHtml = '';
        $logoPath = $this->resolveLogoPathForContext($profileId, $templateId, $contractId);
        if ($logoPath) {
            $logoHtml = '<div style="text-align: right; margin-bottom: 20px;">
                            <img src="file://' . $logoPath . '" style="max-height: 80px;" alt="Logo">
                         </div>';
        }

        $footerHtml = $this->resolveFooterHtml($templateId);

        return '
        <style>
            body { font-family: "Helvetica", "Arial", sans-serif; font-size: 11pt; color: #2c3e50; line-height: 1.6; text-align: justify; }
            h2, h3 { color: #1a5276; margin-top: 15px; margin-bottom: 10px; }
            h2 { text-align: center; font-size: 16pt; text-transform: uppercase; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
            h3 { font-size: 13pt; }
            p { margin-bottom: 12px; }
            strong { color: #111; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        </style>
        ' . $logoHtml . '<div style="padding: 0 15px;">' . $bodyContent . '</div>' . $footerHtml;
    }

    protected function resolveLogoPath(int $contractId): ?string
    {
        return $this->resolveLogoPathForContext(null, null, $contractId);
    }

    public function exportToPdf(int $contractId, string $dest = 'D'): array|false
    {
        $contract = $this->workerContractModel->find($contractId);
        if (! $contract) {
            return false;
        }

        $template = $contract->template_id
            ? $this->templateModel->find($contract->template_id)
            : $this->templateModel->getDefaultTemplate();

        $html = $this->buildPdfHtml(
            $contract->content_snapshot,
            (int) $contract->profile_id,
            $template ? (int) $template->id : null,
            $contractId
        );

        $pdf = new PdfLibrary();
        $pdf->loadHtml($html);

        $profile  = $this->profileModel->find($contract->profile_id);
        $namePart = $profile ? preg_replace('/[^A-Za-z0-9_]/', '_', $profile->last_name ?? 'Contrato') : 'Contrato';
        $filename = 'Contrato_v' . ($contract->version ?? 1) . '_' . $namePart . '.pdf';

        return [
            'content'  => $pdf->getAsString(),
            'filename' => $filename,
            'dest'     => $dest,
        ];
    }

    /**
     * Persiste el PDF en disco y actualiza file_path.
     */
    public function persistPdf(int $contractId): ?string
    {
        $pdfData = $this->exportToPdf($contractId);
        if (! $pdfData) {
            return null;
        }

        $contract = $this->workerContractModel->find($contractId);
        $dir      = FCPATH . 'uploads/hr/contracts/' . $contract->profile_id;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $relative = 'uploads/hr/contracts/' . $contract->profile_id . '/' . $pdfData['filename'];
        file_put_contents(FCPATH . $relative, $pdfData['content']);

        $this->workerContractModel->update($contractId, ['file_path' => $relative]);

        return $relative;
    }
}
