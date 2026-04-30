<?php

namespace App\Controllers\HR;

class WorkerContractController extends BaseHrController
{
    public function contractHistory(int $profileId)
    {
        $profile = $this->profileModel->find($profileId);
        if (!$profile) {
            return redirect()->to(route_to('hr.workers'))->with('error', 'Trabajador no encontrado.');
        }

        $this->setViewSuccess('Historial de Contratos');
        $this->setPageTittleAhead('Historial de Contratos', 'Empleado: ' . esc($profile->first_name . ' ' . $profile->last_name));

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'           => route_to('dashboard.index'),
            'Recursos Humanos' => route_to('hr.workers'),
            'Historial'        => '',
        ]);

        $this->viewData['response'] = [
            'profile_id'  => $profileId,
            'worker_name' => $profile->first_name . ' ' . $profile->last_name,
            'contracts'   => (new \App\Models\HR\WorkerContractModel())->getHistoryByProfile($profileId),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/contracts/worker_contracts_history');
    }

    public function getContracts(int $profileId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $startDate = $this->request->getGet('start_date');
        $endDate   = $this->request->getGet('end_date');

        $workerContractModel = new \App\Models\HR\WorkerContractModel();
        
        $query = $workerContractModel->select('hr_contracts.*, hr_cat_contract_types.name as contract_type_name, hr_cat_contract_templates.name as template_name')
                                     ->join('hr_cat_contract_types', 'hr_cat_contract_types.id = hr_contracts.contract_type_id', 'left')
                                     ->join('hr_cat_contract_templates', 'hr_cat_contract_templates.id = hr_contracts.template_id', 'left')
                                     ->where('hr_contracts.profile_id', $profileId)
                                     ->orderBy('hr_contracts.created_at', 'DESC');
                                     
        if (!empty($startDate)) {
            $query->where('DATE(hr_contracts.created_at) >=', $startDate);
        }
        if (!empty($endDate)) {
            $query->where('DATE(hr_contracts.created_at) <=', $endDate);
        }

        $contracts = $query->findAll();
        $documents = (new \App\Models\HR\HrDocumentModel())->where('profile_id', $profileId)->findAll();

        return $this->response->setJSON(['success' => true, 'data' => $contracts, 'documents' => $documents]);
    }

    public function downloadContract(int $contractId)
    {
        $action  = $this->request->getGet('action') === 'view' ? 'I' : 'D';
        $service = new \App\Services\ContractService(); // TODO: Mover lógica de este servicio aquí en el futuro
        $pdfData = $service->exportToPdf($contractId);
        
        $response = $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', ($action === 'I' ? 'inline' : 'attachment') . '; filename="' . $pdfData['filename'] . '"')
            ->setBody($pdfData['content']);

        return $response;
    }

    public function generateContractView(int $profileId, int $contractId = 0)
    {
        $profile = $this->profileModel->find($profileId);
        if (!$profile) {
            return redirect()->to(route_to('hr.workers'))->with('error', 'Trabajador no encontrado.');
        }

        $editContent = '';
        $editType = '';
        if ($contractId > 0) {
            $contractModel = new \App\Models\HR\WorkerContractModel();
            $contract = $contractModel->find($contractId);
            if ($contract && $contract->profile_id == $profileId) {
                $editContent = $contract->content_snapshot;
                // Intentar extraer el tipo de contrato del reason
                if (strpos($contract->reason, ' - ') !== false) {
                    $parts = explode(' - ', $contract->reason, 2);
                    $editType = $parts[0];
                }
            }
        }

        $this->setViewSuccess('Generar Contrato Manual');
        $this->setPageTittleAhead('Generar Nuevo Contrato', 'Empleado: ' . esc($profile->first_name . ' ' . $profile->last_name));

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'           => route_to('dashboard.index'),
            'Recursos Humanos' => route_to('hr.workers'),
            'Generar Contrato' => '',
        ]);

        $this->viewData['response'] = [
            'profile_id'         => $profileId,
            'worker_name'        => $profile->first_name . ' ' . $profile->last_name,
            'contract_templates' => (new \App\Models\HR\ContractTemplateModel())->findAll(),
            'contract_types'     => (new \App\Models\HR\HrContractTypeModel())->findAll(),
            'edit_content'       => $editContent,
            'edit_type'          => $editType,
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/contracts/worker_contract_generate');
    }

    public function getRenderedTemplateAjax()
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $profileId  = (int) $this->request->getPost('profile_id');
        $templateId = $this->request->getPost('template_id');

        if (empty($profileId) || empty($templateId)) {
            $this->setOutputError('Datos incompletos.');
            return $this->response->setJSON($this->outputData);
        }

        try {
            $contractService = new \App\Services\ContractService();
            // LFT is a special case. Let's assume if templateId is 'lft', we load a default text.
            if ($templateId === 'lft') {
                $html = $contractService->getLftTemplateHtml($profileId); // We will implement this or just return static text
            } else {
                $html = $contractService->previewTemplateRender($profileId, (int)$templateId);
            }
            
            $this->setOutputSuccess('Plantilla cargada.');
            $this->outputData['html'] = $html;
        } catch (\Exception $e) {
            log_message('error', 'Error al renderizar plantilla: ' . $e->getMessage());
            $this->setOutputError('Error interno al renderizar la plantilla.');
        }

        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    public function previewPdfRaw()
    {
        $html = $this->request->getPost('content');
        if (empty($html)) {
            return "No hay contenido para previsualizar.";
        }

        // Generar PDF al vuelo
        try {
            $pdf = new \App\Libraries\PdfLibrary([
                'format' => 'Letter'
            ]);

            $pdf->loadHtml($html);
            $binary = $pdf->getAsString();

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="Previsualizacion_Contrato.pdf"')
                ->setBody($binary);

        } catch (\Exception $e) {
            log_message('error', 'Error mPDF preview: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody("Error al generar la previsualización del PDF.");
        }
    }

    public function saveManualContract(int $profileId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $typeId       = $this->request->getPost('contract_type_id');
        $templateId   = $this->request->getPost('template_id');
        $reason       = $this->request->getPost('reason') ?: 'Generación manual';
        $content      = $this->request->getPost('content');
        $saveTemplate = $this->request->getPost('save_as_template');

        if (empty($typeId) || empty($content)) {
            $this->setOutputError('El tipo de contrato y el contenido son obligatorios.');
            return $this->response->setJSON($this->outputData);
        }

        try {
            // Guardar el snapshot en worker_contracts
            $workerContractModel = new \App\Models\HR\WorkerContractModel();
            
            $contractData = [
                'profile_id'       => $profileId,
                'template_id'      => $templateId ?: null,
                'contract_type_id' => $typeId,
                'content_snapshot' => $content,
                'reason'           => $reason,
                'status'           => 'active'
            ];

            if (!$workerContractModel->insert($contractData)) {
                $this->setOutputError('Error de validación al guardar contrato.', $workerContractModel->errors());
                $this->outputData['csrf'] = csrf_hash();
                return $this->response->setJSON($this->outputData);
            }

            // Guardar como nueva plantilla global si se solicitó
            if ($saveTemplate === 'true' || $saveTemplate === '1' || $saveTemplate === 'on') {
                $templateModel = new \App\Models\HR\ContractTemplateModel();
                $typeModel = new \App\Models\HR\HrContractTypeModel();
                $typeData = $typeModel->find($typeId);
                $typeName = $typeData ? $typeData->name : 'Manual';
                $templateData = [
                    'name'        => substr('Plantilla: ' . $typeName . ' (' . date('Y-m-d H:i') . ')', 0, 99),
                    'description' => 'Plantilla generada a partir de un contrato manual.',
                    'content'     => $content,
                    'base_model'  => 'corporate' // Valor por defecto para pasar validación
                ];

                if (!$templateModel->insert($templateData)) {
                    log_message('error', 'Error al guardar plantilla: ' . json_encode($templateModel->errors()));
                    // No detenemos el proceso, solo logueamos, el contrato ya se guardó
                }
            }

            $this->setOutputSuccess('Contrato generado y guardado correctamente.');
        } catch (\Exception $e) {
            log_message('error', 'Error en saveManualContract: ' . $e->getMessage());
            $this->setOutputError('Error interno al guardar el contrato.');
        }
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }
}
