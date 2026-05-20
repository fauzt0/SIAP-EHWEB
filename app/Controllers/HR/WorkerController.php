<?php
/*
 * Controlador Principal del módulo de Recursos Humanos
 * Gestiona el CRUD completo de trabajadores (hr_profiles + hr_employment_data)
 *
 * Hereda de BaseController para acceder a la arquitectura viewData/outputData
 * y al helper renderLayout().
 */
namespace App\Controllers\HR;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Breadcrumb;

// Modelos de Recursos Humanos
use App\Models\HR\HrProfileModel;
use App\Models\HR\HrEmploymentModel;
use App\Models\HR\HrDepartmentModel;
use App\Models\HR\HrJobModel;
use App\Models\Organization\OrgBranchModel;
use App\Models\HR\HrContractTypeModel;

class WorkerController extends BaseHrController
{
    // Las propiedades compartidas ($profileModel, $employmentModel, $breadcrumb, etc.)
    // y el método initController() han sido movidos a BaseHrController para respetar el DRY.

    public function index(): string
    {
        $this->setViewSuccess('Módulo de Recursos Humanos');
        $this->setPageTittleAhead('Trabajadores', 'Listado de Trabajadores');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'           => route_to('dashboard.index'),
            'Recursos Humanos' => route_to('hr.workers'),
        ]);

        // --- Estadísticas del Dashboard (con Caché 10 min) ---
        $stats = cache()->get('hr_dashboard_stats');
        if (!$stats) {
            $incidenceModel = new \App\Models\HR\HrIncidenceModel();
            $vacationModel = new \App\Models\HR\HrVacationModel();
            
            $totalActive   = $this->employmentModel->where('status', 'active')->countAllResults();
            $totalInactive = $this->employmentModel->whereIn('status', ['inactive', 'suspended'])->countAllResults();
            $totalEmployees = $totalActive + $totalInactive;
            
            $newHires    = $this->employmentModel->where('hiring_date >=', date('Y-m-01'))->countAllResults();
            $totalSalary = $this->employmentModel->where('status', 'active')->selectSum('current_salary')->first()->current_salary ?? 0;
            $pendingInc  = $incidenceModel->where('status', 'pendiente')->countAllResults();
            $pendingVac  = $vacationModel->where('status', 'pendiente')->countAllResults();

            $stats = [
                'total_active'    => (int)$totalActive,
                'total_inactive'  => (int)$totalInactive,
                'total_employees' => (int)$totalEmployees,
                'new_hires'       => (int)$newHires,
                'total_payroll'   => (float)$totalSalary,
                'pending_incid'   => (int)$pendingInc,
                'pending_vac'     => (int)$pendingVac
            ];
            cache()->save('hr_dashboard_stats', $stats, 600);
        }

        $this->viewData['response'] = [
            'responseMessage'    => 'Listado de Trabajadores',
            'departments'        => (new HrDepartmentModel())->findAll(),
            'locations'          => (new OrgBranchModel())->getActive(),
            'contract_templates' => (new \App\Models\HR\ContractTemplateModel())->findAll(),
            'stats'              => $stats
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/main_workers');
    }

    public function workers_ajax()
    {
        if (!$this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $postData = $this->request->getPost();
        $list = $this->profileModel->get_datatables($postData);
        $data = [];
        $no   = (int) $this->request->getPost('start');

        foreach ($list as $worker) {
            $no++;
            $row = [];

            // Avatar del usuario (heredado de la tabla users, si tiene)
            $avatarFileName = $worker->avatar ?? null;
            if (!empty($avatarFileName) && file_exists(FCPATH . 'uploads/avatars/' . $avatarFileName)) {
                $avatarUrl = base_url('uploads/avatars/' . $avatarFileName);
            } else {
                $avatarUrl = base_url('bootstrap/img/avatars/avatar.jpg');
            }
            $row[] = '<img src="' . $avatarUrl . '" width="32" height="32" class="rounded-circle my-n1" style="object-fit: cover;" alt="Avatar">';

            // Nombre completo (prioriza nombre de usuario, luego nombre manual) + número de empleado
            $firstName = !empty($worker->user_first_name) ? $worker->user_first_name : $worker->manual_first_name;
            $lastName  = !empty($worker->user_last_name) ? $worker->user_last_name : $worker->manual_last_name;
            $fullName  = esc($firstName . ' ' . $lastName);
            $empNumber = esc($worker->employee_number ?? 'Sin asignar');
            $row[]     = $fullName . '<br><small class="text-muted">#' . $empNumber . '</small>';

            // Puesto y Departamento
            $row[] = esc($worker->job_name ?? 'Sin puesto');
            $row[] = esc($worker->department_name ?? 'Sin departamento');
            $row[] = esc($worker->location_name ?? 'Sin sucursal');

            // Fecha de contratación
            $hiringDate = !empty($worker->hiring_date) ? date('d/m/Y', strtotime($worker->hiring_date)) : 'N/A';
            $row[]      = $hiringDate;

            // Badge de estatus laboral
            $statusColors = [
                'active'     => 'success',
                'on_leave'   => 'warning',
                'terminated' => 'danger',
                'suspended'  => 'secondary',
            ];
            $status      = $worker->status ?? 'active';
            $statusLabel = [
                'active'     => 'Activo',
                'on_leave'   => 'Permiso',
                'terminated' => 'Baja',
                'suspended'  => 'Suspendido',
            ][$status] ?? ucfirst($status);
            $badgeColor = $statusColors[$status] ?? 'secondary';
            $row[] = '<span class="badge badge-subtle-' . $badgeColor . '">' . $statusLabel . '</span>';

            // Botones de acción (usando profile_id)
            $profileId   = $worker->id;
            $actionsHtml = '<div class="d-flex gap-1">';
            if (auth()->user()->can('hr.view')) {
                $actionsHtml .= '<button class="btn btn-sm btn-outline-primary btn-view-worker" data-user-id="' . $profileId . '" title="Ver perfil"><i class="fas fa-fw fa-eye"></i></button>';
            }
            if (auth()->user()->can('hr.edit')) {
                $actionsHtml .= '<a href="' . route_to('hr.worker.edit', $profileId) . '" class="btn btn-sm btn-outline-warning" title="Editar"><i class="fas fa-fw fa-edit"></i></a>';
            }
            if (auth()->user()->can('hr.delete')) {
                $actionsHtml .= '<button class="btn btn-sm btn-outline-danger btn-delete-worker" data-user-id="' . $profileId . '" title="Eliminar"><i class="fas fa-fw fa-trash"></i></button>';
            }
            $actionsHtml .= '</div>';
            $row[] = $actionsHtml;

            $data[] = $row;
        }

        $output = [
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->profileModel->countActiveWorkers(),
            'recordsFiltered' => $this->profileModel->count_filtered($postData),
            'data'            => $data,
        ];

        return $this->response->setJSON($output);
    }

    public function show_ajax(int $profileId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Acceso no permitido');
        }

        $profile    = $this->profileModel->withDeleted()->find($profileId);
        $employment = $this->employmentModel->withDeleted()->where('profile_id', $profileId)->first();

        if (!$profile) {
            $this->setOutputError('Trabajador no encontrado', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        // Datos del usuario (si está vinculado)
        $user = null;
        if (!empty($profile->user_id)) {
            $usersProvider = auth()->getProvider();
            $user          = $usersProvider->withDeleted()->find($profile->user_id);
        }

        $avatarFileName = $user->avatar ?? null;
        $avatarUrl = (!empty($avatarFileName) && file_exists(FCPATH . 'uploads/avatars/' . $avatarFileName))
            ? base_url('uploads/avatars/' . $avatarFileName)
            : base_url('bootstrap/img/avatars/avatar.jpg');

        // Resolver nombres de catálogos para evitar JOINs en el front
        $deptName     = null;
        $jobName      = null;
        $locationName = null;
        $managerName  = null;

        if (!empty($employment)) {
            if (!empty($employment->department_id)) {
                $dept = (new HrDepartmentModel())->find($employment->department_id);
                $deptName = $dept->name ?? null;
            }
            if (!empty($employment->job_id)) {
                $job = (new HrJobModel())->find($employment->job_id);
                $jobName = $job->name ?? null;
            }
            if (!empty($employment->location_id)) {
                $branch = (new \App\Models\Organization\OrgBranchModel())->find($employment->location_id);
                $locationName = $branch->name ?? null;
            }
            if (!empty($employment->direct_manager_id)) {
                $managerName = $this->profileModel->getManagerName($employment->direct_manager_id);
            }
        }

        $this->setOutputSuccess('Datos del trabajador cargados');
        $this->outputData['response'] = [
            'profile_id' => $profileId,
            'user_id'    => $profile->user_id,
            'username'   => $user->username ?? '',
            'first_name' => $user->first_name ?? $profile->first_name ?? '',
            'last_name'  => $user->last_name ?? $profile->last_name ?? '',
            'avatar'     => $avatarUrl,
            // Catálogos resueltos
            'department_name' => $deptName,
            'job_name'        => $jobName,
            'location_name'   => $locationName,
            'manager_name'    => $managerName,
            // Datos personales HR
            'nationality'             => $profile->nationality,
            'curp'                    => $profile->curp,
            'rfc'                     => $profile->rfc,
            'tax_regime'              => $profile->tax_regime,
            'nss'                     => $profile->nss,
            'birth_date'              => $profile->birth_date,
            'gender'                  => $profile->gender,
            'marital_status'          => $profile->marital_status,
            'phone_personal'          => $profile->phone_personal,
            'personal_email'          => $profile->personal_email,
            'address_full'            => $profile->address_full,
            'emergency_contact_name'  => $profile->emergency_contact_name,
            'emergency_contact_phone' => $profile->emergency_contact_phone,
            'beneficiaries'           => $profile->beneficiaries,
            'bank_name'               => $profile->bank_name,
            'bank_account'            => $profile->bank_account,
            'bank_clabe'              => $profile->bank_clabe,
            // Datos laborales
            'employee_number'    => $employment->employee_number ?? null,
            'worker_type'        => $employment->worker_type ?? null,
            'corporate_email'    => $employment->corporate_email ?? null,
            'department_id'      => $employment->department_id ?? null,
            'job_id'             => $employment->job_id ?? null,
            'location_id'        => $employment->location_id ?? null,
            'direct_manager_id'  => $employment->direct_manager_id ?? null,
            'current_salary'     => $employment->current_salary ?? null,
            'daily_salary'       => $employment->daily_salary ?? null,
            'hiring_date'        => $employment->hiring_date ?? null,
            'status'             => $employment->status ?? 'active',
            // Expediente digital
            'documents'          => (new \App\Models\HR\HrDocumentModel())->select('hr_documents.*, hr_cat_document_types.name as type_name')
                                    ->join('hr_cat_document_types', 'hr_cat_document_types.id = hr_documents.document_type_id', 'left')
                                    ->where('profile_id', $profileId)
                                    ->findAll(),
            // Historial de contratos
            'contracts'          => (new \App\Models\HR\WorkerContractModel())->getHistoryByProfile($profileId),
            'contracts_count'    => (new \App\Models\HR\WorkerContractModel())->where('profile_id', $profileId)->countAllResults(),
            // Horario Laboral (Fase 2)
            'schedule'           => (new \App\Models\HR\HrWorkerScheduleModel())
                                    ->select('hr_worker_schedules.*, hr_cat_shifts.name, hr_cat_shifts.start_time, hr_cat_shifts.end_time, hr_cat_shifts.work_days')
                                    ->join('hr_cat_shifts', 'hr_cat_shifts.id = hr_worker_schedules.shift_id')
                                    ->where('hr_worker_schedules.profile_id', $profileId)
                                    ->orderBy('hr_worker_schedules.start_date', 'DESC')
                                    ->first(),
            // Incidencias (Fase 2)
            'incidences'         => (new \App\Models\HR\HrIncidenceModel())
                                    ->where('profile_id', $profileId)
                                    ->orderBy('date', 'DESC')
                                    ->limit(5)
                                    ->findAll(),
            'incidences_count'   => (new \App\Models\HR\HrIncidenceModel())
                                    ->where('profile_id', $profileId)
                                    ->where('status !=', 'rechazada')
                                    ->countAllResults(),
            // Vacaciones (Fase 2 - Placeholder hasta Sprint 3)
            'vacation_balance'   => 0,
            'vacation_earned'    => 0,
            'vacation_taken'     => (new \App\Models\HR\HrVacationRequestModel())
                                    ->where('profile_id', $profileId)
                                    ->where('status', 'aprobado')
                                    ->first()->days_requested ?? 0,
        ];

        return $this->response->setJSON($this->outputData);
    }

    public function new_worker()
    {
        // Validación: Bloquear alta si no hay plantillas de contratos
        $templateModel = new \App\Models\HR\ContractTemplateModel();
        if ($templateModel->countAllResults() === 0) {
            return redirect()->to(route_to('hr.workers'))->with('error', 'No existen plantillas de contratos registradas. Debes crear al menos una plantilla antes de poder dar de alta a nuevos trabajadores.');
        }

        $this->setViewSuccess('Alta de nuevo trabajador');
        $this->setPageTittleAhead('Alta de Empleado', 'Nuevo Trabajador');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'           => route_to('dashboard.index'),
            'Recursos Humanos' => route_to('hr.workers'),
            'Nuevo Trabajador' => '',
        ]);

        // Generar número de empleado sugerido (EJ: EMP-0001)
        $lastEmp = $this->employmentModel->orderBy('profile_id', 'DESC')->first();
        $nextNum = 1;
        if ($lastEmp && !empty($lastEmp->employee_number)) {
            preg_match('/\d+/', $lastEmp->employee_number, $matches);
            if (!empty($matches)) {
                $nextNum = (int)$matches[0] + 1;
            }
        }
        $autoEmployeeNumber = 'EMP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        // Lista de posibles jefes directos
        $managers = $this->profileModel->getAvailableManagers();

        $this->viewData['response'] = [
            'departments'        => (new HrDepartmentModel())->findAll(),
            'jobs'               => (new HrJobModel())->findAll(),
            'locations'          => (new OrgBranchModel())->getActive(),
            'contractTypes'      => (new HrContractTypeModel())->findAll(),
            'users'              => $this->profileModel->getAvailableUsers(),
            'managers'           => $managers,
            'autoEmployeeNumber' => $autoEmployeeNumber,
            'document_types'     => (new \App\Models\HR\HrDocumentTypeModel())->findAll(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/worker_form');
    }

    public function create()
    {
        if (!$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido');
        }

        $userId = $this->request->getPost('user_id') ? (int) $this->request->getPost('user_id') : null;

        if ($userId) {
            $existingProfile = $this->profileModel->where('user_id', $userId)->first();
            if ($existingProfile) {
                $this->setOutputError('Este usuario ya tiene un perfil de trabajador registrado.');
                return $this->response->setJSON($this->outputData);
            }
        }

        $profileData = [
            'user_id'                 => $userId,
            'first_name'              => trim((string)$this->request->getPost('first_name')) ?: null,
            'last_name'               => trim((string)$this->request->getPost('last_name')) ?: null,
            'nationality'             => trim((string)$this->request->getPost('nationality')),
            'curp'                    => strtoupper(trim((string)$this->request->getPost('curp'))),
            'rfc'                     => strtoupper(trim((string)$this->request->getPost('rfc'))),
            'tax_regime'              => trim((string)$this->request->getPost('tax_regime')),
            'nss'                     => $this->request->getPost('nss') ?: null,
            'birth_date'              => $this->request->getPost('birth_date'),
            'gender'                  => $this->request->getPost('gender'),
            'marital_status'          => $this->request->getPost('marital_status'),
            'phone_personal'          => $this->request->getPost('phone_personal'),
            'personal_email'          => $this->request->getPost('personal_email'),
            'address_full'            => $this->request->getPost('address_full'),
            'emergency_contact_name'  => $this->request->getPost('emergency_contact_name'),
            'emergency_contact_phone' => $this->request->getPost('emergency_contact_phone'),
            'beneficiaries'           => $this->request->getPost('beneficiaries'),
            'bank_name'               => $this->request->getPost('bank_name'),
            'bank_account'            => $this->request->getPost('bank_account'),
            'bank_clabe'              => $this->request->getPost('bank_clabe'),
        ];

        if (!$this->profileModel->validate($profileData)) {
            $this->setOutputError('Error de validación en datos personales', $this->profileModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        $employmentData = [
            'employee_number'         => $this->request->getPost('employee_number'),
            'worker_type'             => $this->request->getPost('worker_type') ?: 'planta',
            'direct_manager_id'       => $this->request->getPost('direct_manager_id') ? (int)$this->request->getPost('direct_manager_id') : null,
            'corporate_email'         => $this->request->getPost('corporate_email'),
            'department_id'           => $this->request->getPost('department_id') ?: null,
            'job_id'                  => $this->request->getPost('job_id') ?: null,
            'location_id'             => $this->request->getPost('location_id') ?: null,
            'current_salary'          => (float) $this->request->getPost('current_salary'),
            'daily_salary'            => (float) $this->request->getPost('daily_salary'),
            'payroll_type'            => $this->request->getPost('payroll_type') ?: 'quincenal',
            'payment_method'          => $this->request->getPost('payment_method') ?: 'transferencia',
            'alimony_percent'         => (float) $this->request->getPost('alimony_percent'),
            'alimony_fixed_amount'    => (float) $this->request->getPost('alimony_fixed_amount'),
            'isr_retention'           => (float) $this->request->getPost('isr_retention'),
            'imss_fee'                => (float) $this->request->getPost('imss_fee'),
            'infonavit_contribution'  => (float) $this->request->getPost('infonavit_contribution'),
            'afore_contribution'      => (float) $this->request->getPost('afore_contribution'),
            'benefits_infonavit'      => $this->request->getPost('benefits_infonavit') ? 1 : 0,
            'benefits_fonacot'        => $this->request->getPost('benefits_fonacot') ? 1 : 0,
            'benefits_afore'          => $this->request->getPost('benefits_afore') ? 1 : 0,
            'benefits_vacations'      => $this->request->getPost('benefits_vacations') ? 1 : 0,
            'hiring_date'             => $this->request->getPost('hiring_date'),
            'status'                  => 'active',
            'notes'                   => $this->request->getPost('notes'),
        ];

        if (!$this->employmentModel->validate($employmentData)) {
            $this->setOutputError('Error de validación en datos laborales', $this->employmentModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        if (!$this->profileModel->createWorkerWithEmployment($profileData, $employmentData)) {
            $this->setOutputError('Error al guardar el trabajador. Por favor, intente nuevamente.');
            return $this->response->setJSON($this->outputData);
        }
        $newProfileId = $this->profileModel->getInsertID();

        // PROCESAR DOCUMENTOS DEL EXPEDIENTE
        $this->processDocuments($newProfileId);

        // GENERACIÓN AUTOMÁTICA DE CONTRATO
        try {
            $contractService = new \App\Services\ContractService();
            $contractService->generateContract($newProfileId, null, 'Contrato generado automáticamente al alta');
        } catch (\Exception $e) {
            log_message('error', 'Error al generar contrato automático en alta: ' . $e->getMessage());
        }

        $logModel = new \App\Models\Users\UserActivityLogsModel();
        $logModel->logActivity('create_worker', 'Alta del trabajador con profile_id: ' . $newProfileId);

        $this->setOutputSuccess('Trabajador registrado correctamente.', ResponseInterface::HTTP_CREATED);
        $this->outputData['response'] = ['profile_id' => $newProfileId, 'redirect' => route_to('hr.workers')];

        return $this->response->setJSON($this->outputData);
    }

    public function edit(int $profileId)
    {
        // Validación: Bloquear edición si no hay plantillas de contratos
        $templateModel = new \App\Models\HR\ContractTemplateModel();
        if ($templateModel->countAllResults() === 0) {
            return redirect()->to(route_to('hr.workers'))->with('error', 'No existen plantillas de contratos registradas. Debes crear al menos una plantilla antes de poder editar trabajadores.');
        }

        $profile    = $this->profileModel->find($profileId);
        $employment = $this->employmentModel->where('profile_id', $profileId)->first();

        if (!$profile) {
            return redirect()->to(route_to('hr.workers'))->with('error', 'Trabajador no encontrado.');
        }

        $user = null;
        if (!empty($profile->user_id)) {
            $usersProvider = auth()->getProvider();
            $user = $usersProvider->find($profile->user_id);
        }

        $this->setViewSuccess('Editando trabajador');
        $this->setPageTittleAhead('Editar Trabajador', 'Edición de Datos del Trabajador');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'           => route_to('dashboard.index'),
            'Recursos Humanos' => route_to('hr.workers'),
            'Editar'           => '',
        ]);

        $managers = $this->profileModel->getAvailableManagers($profileId);

        $this->viewData['response'] = [
            'user'          => $user,
            'profile'       => $profile,
            'employment'    => $employment,
            'departments'   => (new HrDepartmentModel())->findAll(),
            'jobs'          => (new HrJobModel())->findAll(),
            'locations'     => (new OrgBranchModel())->getActive(),
            'contractTypes' => (new HrContractTypeModel())->findAll(),
            'managers'      => $managers,
            'users'         => $this->profileModel->getAvailableUsers(), // En caso de que quiera vincularse ahora
            'isEdit'        => true,
            'autoEmployeeNumber' => $employment->employee_number ?? '',
            'document_types'     => (new \App\Models\HR\HrDocumentTypeModel())->findAll(),
            'documents'          => (new \App\Models\HR\HrDocumentModel())->select('hr_documents.*, hr_cat_document_types.name as type_name')
                                    ->join('hr_cat_document_types', 'hr_cat_document_types.id = hr_documents.document_type_id', 'left')
                                    ->where('profile_id', $profileId)
                                    ->withDeleted() // CARGAR TODOS PARA EL FILTRO
                                    ->findAll(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/worker_form');
    }

    public function update(int $profileId)
    {
        if (!$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido');
        }

        $profile    = $this->profileModel->find($profileId);
        $employment = $this->employmentModel->where('profile_id', $profileId)->first();

        if (!$profile) {
            $this->setOutputError('Trabajador no encontrado', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $userId = $this->request->getPost('user_id') ? (int) $this->request->getPost('user_id') : null;

        $profileData = [
            'user_id'                 => $userId,
            'first_name'              => trim((string)$this->request->getPost('first_name')) ?: null,
            'last_name'               => trim((string)$this->request->getPost('last_name')) ?: null,
            'nationality'             => trim((string)$this->request->getPost('nationality')),
            'curp'                    => strtoupper(trim((string)$this->request->getPost('curp'))),
            'rfc'                     => strtoupper(trim((string)$this->request->getPost('rfc'))),
            'tax_regime'              => trim((string)$this->request->getPost('tax_regime')),
            'nss'                     => $this->request->getPost('nss') ?: null,
            'birth_date'              => $this->request->getPost('birth_date'),
            'gender'                  => $this->request->getPost('gender'),
            'marital_status'          => $this->request->getPost('marital_status'),
            'phone_personal'          => $this->request->getPost('phone_personal'),
            'personal_email'          => $this->request->getPost('personal_email'),
            'address_full'            => $this->request->getPost('address_full'),
            'emergency_contact_name'  => $this->request->getPost('emergency_contact_name'),
            'emergency_contact_phone' => $this->request->getPost('emergency_contact_phone'),
            'beneficiaries'           => $this->request->getPost('beneficiaries'),
            'bank_name'               => $this->request->getPost('bank_name'),
            'bank_account'            => $this->request->getPost('bank_account'),
            'bank_clabe'              => $this->request->getPost('bank_clabe'),
        ];

        $employmentData = [
            'employee_number'         => $this->request->getPost('employee_number'),
            'worker_type'             => $this->request->getPost('worker_type') ?: 'planta',
            'direct_manager_id'       => $this->request->getPost('direct_manager_id') ? (int)$this->request->getPost('direct_manager_id') : null,
            'corporate_email'         => $this->request->getPost('corporate_email'),
            'department_id'           => $this->request->getPost('department_id') ?: null,
            'job_id'                  => $this->request->getPost('job_id') ?: null,
            'location_id'             => $this->request->getPost('location_id') ?: null,
            'current_salary'          => (float) $this->request->getPost('current_salary'),
            'daily_salary'            => (float) $this->request->getPost('daily_salary'),
            'payroll_type'            => $this->request->getPost('payroll_type') ?: 'quincenal',
            'payment_method'          => $this->request->getPost('payment_method') ?: 'transferencia',
            'alimony_percent'         => (float) $this->request->getPost('alimony_percent'),
            'alimony_fixed_amount'    => (float) $this->request->getPost('alimony_fixed_amount'),
            'isr_retention'           => (float) $this->request->getPost('isr_retention'),
            'imss_fee'                => (float) $this->request->getPost('imss_fee'),
            'infonavit_contribution'  => (float) $this->request->getPost('infonavit_contribution'),
            'afore_contribution'      => (float) $this->request->getPost('afore_contribution'),
            'benefits_infonavit'      => $this->request->getPost('benefits_infonavit') ? 1 : 0,
            'benefits_fonacot'        => $this->request->getPost('benefits_fonacot') ? 1 : 0,
            'benefits_afore'          => $this->request->getPost('benefits_afore') ? 1 : 0,
            'benefits_vacations'      => $this->request->getPost('benefits_vacations') ? 1 : 0,
            'hiring_date'             => $this->request->getPost('hiring_date'),
            'termination_date'        => $this->request->getPost('termination_date') ?: null,
            'status'                  => $this->request->getPost('status') ?? ($employment->status ?? 'active'),
            'notes'                   => $this->request->getPost('notes'),
        ];

        if (!$this->profileModel->updateWorkerWithEmployment($profileId, $profileData, $employmentData)) {
            $this->setOutputError('Error al actualizar el trabajador.', $this->profileModel->errors() ?: $this->employmentModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        // PROCESAR NUEVOS DOCUMENTOS DEL EXPEDIENTE
        $this->processDocuments($profileId);

        // GENERACIÓN AUTOMÁTICA DE CONTRATO POR CAMBIOS
        try {
            $contractService = new \App\Services\ContractService();
            $contractService->generateContract($profileId, null, 'Actualización automática por cambios en datos del trabajador');
        } catch (\Exception $e) {
            log_message('error', 'Error al generar contrato automático en actualización: ' . $e->getMessage());
        }

        $logModel = new \App\Models\Users\UserActivityLogsModel();
        $logModel->logActivity('update_worker', 'Edición del trabajador con profile_id: ' . $profileId);

        $this->setOutputSuccess('Trabajador actualizado correctamente.');
        $this->outputData['response'] = ['profile_id' => $profileId, 'redirect' => route_to('hr.workers')];
        $this->outputData['csrf']     = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    /**
     * Procesa la carga de archivos del expediente digital.
     */
    private function processDocuments(int $profileId)
    {
        $typeIds = $this->request->getPost('document_type_ids');
        $notes   = $this->request->getPost('document_notes');
        $files   = $this->request->getFileMultiple('document_files');

        if (!$files) return;

        $docModel = new \App\Models\HR\HrDocumentModel();
        
        foreach ($files as $index => $file) {
            if ($file->isValid() && !$file->hasMoved()) {
                // Directorio seguro: writable/uploads/hr/documents/{profileId}/
                $newName = $file->getRandomName();
                $targetDir = WRITEPATH . 'uploads/hr/documents/' . $profileId;
                
                if ($file->move($targetDir, $newName)) {
                    $docModel->insert([
                        'profile_id'       => $profileId,
                        'document_type_id' => !empty($typeIds[$index]) ? (int)$typeIds[$index] : null,
                        'file_path'        => 'hr/documents/' . $profileId . '/' . $newName,
                        'notes'            => !empty($notes[$index]) ? $notes[$index] : null,
                    ]);
                }
            }
        }
    }

    public function delete(int $profileId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $profile = $this->profileModel->withDeleted()->find($profileId);
        if (!$profile) {
            $this->setOutputError('Trabajador no encontrado', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->profileModel->delete($profileId);
        $this->employmentModel->where('profile_id', $profileId)->delete();

        $logModel = new \App\Models\Users\UserActivityLogsModel();
        $logModel->logActivity('delete_worker', 'Eliminó (soft) al trabajador con profile_id: ' . $profileId);

        $this->setOutputSuccess('Trabajador eliminado correctamente.');
        return $this->response->setJSON($this->outputData);
    }

    public function restore(int $profileId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $profile = $this->profileModel->withDeleted()->find($profileId);
        if (!$profile || empty($profile->deleted_at)) {
            $this->setOutputError('Trabajador no encontrado o no está eliminado.');
            return $this->response->setJSON($this->outputData);
        }

        $this->profileModel->withDeleted()->update($profileId, ['deleted_at' => null]);
        $this->employmentModel->withDeleted()->where('profile_id', $profileId)->set(['deleted_at' => null])->update();

        $logModel = new \App\Models\Users\UserActivityLogsModel();
        $logModel->logActivity('restore_worker', 'Restauró al trabajador con profile_id: ' . $profileId);

        $this->setOutputSuccess('Trabajador restaurado correctamente.');
        return $this->response->setJSON($this->outputData);
    }
}
