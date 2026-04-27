<?php

namespace App\Controllers\HR;

use App\Controllers\BaseController;
use App\Models\HR\HrDepartmentModel;
use App\Models\HR\HrJobModel;
use App\Libraries\Breadcrumb;

class CatalogController extends BaseController
{
    protected $departmentModel;
    protected $jobModel;
    protected $breadcrumb;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->departmentModel = new HrDepartmentModel();
        $this->jobModel        = new HrJobModel();

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
}
