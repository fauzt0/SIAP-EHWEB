<?php

namespace App\Controllers\HR;

use App\Controllers\BaseController;
use App\Models\HR\HrDepartmentModel;
use App\Models\HR\HrJobModel;
use App\Models\HR\HrShiftModel;
use App\Libraries\Breadcrumb;

class CatalogController extends BaseController
{
    protected $departmentModel;
    protected $jobModel;
    protected $shiftModel;
    protected $breadcrumb;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->departmentModel = new HrDepartmentModel();
        $this->jobModel        = new HrJobModel();
        $this->shiftModel      = new HrShiftModel();

        $this->breadcrumb = new Breadcrumb([
            'Inicio'           => base_url(),
            'Recursos Humanos' => route_to('hr.workers'),
            'Catálogos'        => route_to('hr.catalogs.index'),
        ]);

        $this->setPageTittleAhead('Recursos Humanos', 'Catálogos de RRHH');
    }

    public function index()
    {
        $this->viewData['departments'] = $this->departmentModel->findAll();
        $this->viewData['jobs']        = $this->jobModel->findAll();
        $this->viewData['shifts']      = $this->shiftModel->orderBy('name', 'ASC')->findAll();
        $this->viewData['breadcrumb']  = $this->breadcrumb->getBreadCrumbHtml();

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/catalogs/index');
    }

    public function saveDepartment()
    {
        $id   = $this->request->getPost('id');
        $data = [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($id) {
            $this->departmentModel->update($id, $data);
            $msg = 'Departamento actualizado correctamente.';
        } else {
            $this->departmentModel->insert($data);
            $msg = 'Departamento creado correctamente.';
        }

        if ($this->departmentModel->errors()) {
            $this->setOutputError('Error al guardar el departamento.', $this->departmentModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        session()->setFlashdata('success', $msg);

        $this->setOutputSuccess($msg);
        return $this->response->setJSON($this->outputData);
    }

    public function deleteDepartment($id)
    {
        if ($this->departmentModel->delete($id)) {
            session()->setFlashdata('success', 'Departamento eliminado correctamente.');
            $this->setOutputSuccess('Departamento eliminado correctamente.');
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputError('No se pudo eliminar el departamento.');
        return $this->response->setJSON($this->outputData);
    }

    public function saveJob()
    {
        $id   = $this->request->getPost('id');
        $data = [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
        ];

        if ($id) {
            $this->jobModel->update($id, $data);
            $msg = 'Puesto actualizado correctamente.';
        } else {
            $this->jobModel->insert($data);
            $msg = 'Puesto creado correctamente.';
        }

        if ($this->jobModel->errors()) {
            $this->setOutputError('Error al guardar el puesto.', $this->jobModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        session()->setFlashdata('success', $msg);

        $this->setOutputSuccess($msg);
        return $this->response->setJSON($this->outputData);
    }

    public function deleteJob($id)
    {
        if ($this->jobModel->delete($id)) {
            session()->setFlashdata('success', 'Puesto eliminado correctamente.');
            $this->setOutputSuccess('Puesto eliminado correctamente.');
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputError('No se pudo eliminar el puesto.');
        return $this->response->setJSON($this->outputData);
    }

    public function saveShift()
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $id   = $this->request->getPost('id');
        $data = [
            'name'         => $this->request->getPost('name'),
            'start_time'   => $this->request->getPost('start_time'),
            'end_time'     => $this->request->getPost('end_time'),
            'grace_period' => (int) ($this->request->getPost('grace_period') ?: 0),
            'work_days'    => implode(',', (array) $this->request->getPost('work_days')),
        ];

        if ($id) {
            $this->shiftModel->update($id, $data);
            $msg = 'Turno actualizado correctamente.';
            $logAction = 'update_hr_shift';
        } else {
            $this->shiftModel->insert($data);
            $msg = 'Turno creado correctamente.';
            $logAction = 'create_hr_shift';
        }

        if ($this->shiftModel->errors()) {
            $this->setOutputError('Error al guardar el turno.', $this->shiftModel->errors());
            $this->outputData['csrf'] = csrf_hash();
            return $this->response->setJSON($this->outputData);
        }

        $shiftId = $id ?: $this->shiftModel->getInsertID();
        (new \App\Models\Users\UserActivityLogsModel())->logActivity(
            $logAction,
            $msg . ' (ID: ' . $shiftId . ', nombre: ' . $data['name'] . ')'
        );

        session()->setFlashdata('success', $msg);
        $this->setOutputSuccess($msg);
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    public function deleteShift($id)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $shiftId = (int) $id;
        $scheduleModel = new \App\Models\HR\HrWorkerScheduleModel();
        if ($scheduleModel->countByShiftId($shiftId) > 0) {
            $this->setOutputError('No se puede eliminar: el turno está asignado a uno o más trabajadores.');
            $this->outputData['csrf'] = csrf_hash();
            return $this->response->setJSON($this->outputData);
        }

        $shift = $this->shiftModel->find($shiftId);
        if ($this->shiftModel->delete($shiftId)) {
            (new \App\Models\Users\UserActivityLogsModel())->logActivity(
                'delete_hr_shift',
                'Eliminó turno ID: ' . $shiftId . ($shift ? ' (' . $shift->name . ')' : '')
            );
            session()->setFlashdata('success', 'Turno eliminado correctamente.');
            $this->setOutputSuccess('Turno eliminado correctamente.');
        } else {
            $this->setOutputError('No se pudo eliminar el turno.');
        }

        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }
}
