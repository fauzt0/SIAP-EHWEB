<?php

namespace App\Controllers\Organization;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\OrgCompanyModel;
use App\Models\OrgBranchModel;
use App\Models\Users\UserActivityLogsModel;

class OrganizationController extends BaseController
{
    protected $companyModel;
    protected $branchModel;

    public function __construct()
    {
        $this->companyModel = new OrgCompanyModel();
        $this->branchModel  = new OrgBranchModel();
    }

    // ────────────────────────────────────────────────────────────────────────
    // PERFIL DE LA COMPAÑÍA (MATRIZ)
    // ────────────────────────────────────────────────────────────────────────

    public function profile(): string
    {
        if (!auth()->user()->can('admin.manage-organization')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->setViewSuccess('Perfil de la Compañía');
        $this->setPageTittleAhead('Perfil Corporativo', 'Configuración de la Matriz');

        $this->viewData['response'] = [
            'company' => $this->companyModel->getProfile()
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Organization/profile');
    }

    public function updateProfile()
    {
        if (!$this->request->isAJAX() || !$this->request->is('post') || !auth()->user()->can('admin.manage-organization')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $id = $this->request->getPost('id');
        $data = [
            'commercial_name' => $this->request->getPost('commercial_name'),
            'legal_name'      => $this->request->getPost('legal_name'),
            'tax_id'          => $this->request->getPost('tax_id'),
            'primary_email'   => $this->request->getPost('primary_email'),
            'primary_phone'   => $this->request->getPost('primary_phone'),
            'website_url'     => $this->request->getPost('website_url'),
        ];

        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            $newName = $logo->getRandomName();
            if (!is_dir(FCPATH . 'uploads/organization/')) {
                mkdir(FCPATH . 'uploads/organization/', 0755, true);
            }
            $logo->move(FCPATH . 'uploads/organization/', $newName);
            $data['logo_path'] = 'uploads/organization/' . $newName;
        }

        if ($id) {
            $this->companyModel->update($id, $data);
            $action = 'Actualizó el perfil de la compañía';
        } else {
            $this->companyModel->insert($data);
            $action = 'Creó el perfil de la compañía';
        }

        (new UserActivityLogsModel())->logActivity('update_company_profile', $action);

        $this->setOutputSuccess('Perfil actualizado correctamente.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SUCURSALES (UNIDADES DE NEGOCIO)
    // ────────────────────────────────────────────────────────────────────────

    public function branches(): string
    {
        if (!auth()->user()->can('admin.manage-organization')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->setViewSuccess('Listado de Sucursales');
        $this->setPageTittleAhead('Sucursales', 'Unidades de Negocio');

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Organization/branches_index');
    }

    public function getBranches()
    {
        if (!$this->request->isAJAX() || !auth()->user()->can('admin.manage-organization')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $postData = $this->request->getPost();
        $list     = $this->branchModel->get_datatables($postData);
        $data     = [];

        foreach ($list as $branch) {
            $row   = [];
            $logo  = $branch->logo_path ? base_url($branch->logo_path) : base_url('bootstrap/img/avatars/avatar.jpg');
            $row[] = '<img src="' . $logo . '" class="img-thumbnail" width="40">';
            $row[] = esc($branch->name);
            $row[] = esc($branch->branch_code);
            $row[] = esc($branch->phone);
            $row[] = esc($branch->email);
            
            $statusBadge = $branch->active ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-danger">Inactiva</span>';
            $row[] = $statusBadge;

            $actions = '<div class="btn-group btn-group-sm">';
            $actions .= '<a href="' . base_url('nat/organization/branch/edit/' . $branch->id) . '" class="btn btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>';
            if ($branch->active) {
                $actions .= '<button class="btn btn-outline-warning" onclick="toggleBranchStatus(' . $branch->id . ', 0)" title="Desactivar"><i class="fas fa-ban"></i></button>';
            } else {
                $actions .= '<button class="btn btn-outline-success" onclick="toggleBranchStatus(' . $branch->id . ', 1)" title="Activar"><i class="fas fa-check"></i></button>';
            }
            $actions .= '<button class="btn btn-outline-danger" onclick="deleteBranch(' . $branch->id . ')" title="Eliminar"><i class="fas fa-trash"></i></button>';
            $actions .= '</div>';
            $row[] = $actions;

            $data[] = $row;
        }

        $output = [
            "draw"            => isset($postData['draw']) ? intval($postData['draw']) : 0,
            "recordsTotal"    => $this->branchModel->countAllResults(),
            "recordsFiltered" => $this->branchModel->count_filtered($postData),
            "data"            => $data,
            "csrf"            => csrf_hash()
        ];

        return $this->response->setJSON($output);
    }

    public function createBranch(): string
    {
        if (!auth()->user()->can('admin.manage-organization')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $company = $this->companyModel->getProfile();

        $this->setViewSuccess('Nueva Sucursal');
        $this->setPageTittleAhead('Nueva Sucursal', 'Alta de Unidad de Negocio');
        $this->viewData['response'] = [
            'branch'  => null,
            'isEdit'  => false,
            'company' => $company
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Organization/branch_form');
    }

    public function editBranch(int $id)
    {
        if (!auth()->user()->can('admin.manage-organization')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $branch = $this->branchModel->find($id);
        if (!$branch) {
            return redirect()->to('nat/organization/branches')->with('error', 'Sucursal no encontrada.');
        }

        $company = $this->companyModel->getProfile();

        $this->setViewSuccess('Editar Sucursal');
        $this->setPageTittleAhead('Editar Sucursal', 'Modificar Unidad de Negocio');
        $this->viewData['response'] = [
            'branch'  => $branch,
            'isEdit'  => true,
            'company' => $company
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Organization/branch_form');
    }

    public function saveBranch()
    {
        if (!$this->request->isAJAX() || !$this->request->is('post') || !auth()->user()->can('admin.manage-organization')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $id = $this->request->getPost('id');
        $data = [
            'company_id'       => $this->request->getPost('company_id'),
            'name'             => $this->request->getPost('name'),
            'branch_code'      => $this->request->getPost('branch_code'),
            'is_main'          => $this->request->getPost('is_main') ? 1 : 0,
            'address'          => $this->request->getPost('address'),
            'phone'            => $this->request->getPost('phone'),
            'email'            => $this->request->getPost('email'),
            'pos_printer_name' => $this->request->getPost('pos_printer_name'),
            'active'           => $this->request->getPost('active') ? 1 : 0,
        ];

        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            $newName = $logo->getRandomName();
            if (!is_dir(FCPATH . 'uploads/organization/')) {
                mkdir(FCPATH . 'uploads/organization/', 0755, true);
            }
            $logo->move(FCPATH . 'uploads/organization/', $newName);
            $data['logo_path'] = 'uploads/organization/' . $newName;
        }

        if ($id) {
            $this->branchModel->update($id, $data);
            $action = 'Actualizó la sucursal ID: ' . $id;
        } else {
            $this->branchModel->insert($data);
            $action = 'Creó una nueva sucursal: ' . $data['name'];
        }

        (new UserActivityLogsModel())->logActivity('save_branch', $action);

        $this->setOutputSuccess('Sucursal guardada correctamente.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    public function toggleBranchStatus(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post') || !auth()->user()->can('admin.manage-organization')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $status = $this->request->getPost('status') ? 1 : 0;
        $this->branchModel->update($id, ['active' => $status]);

        (new UserActivityLogsModel())->logActivity('toggle_branch_status', 'Cambió estado de sucursal ID ' . $id . ' a ' . $status);

        $this->setOutputSuccess('Estado actualizado correctamente.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    public function deleteBranch(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post') || !auth()->user()->can('admin.manage-organization')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $this->branchModel->delete($id);

        (new UserActivityLogsModel())->logActivity('delete_branch', 'Eliminó la sucursal ID: ' . $id);

        $this->setOutputSuccess('Sucursal eliminada correctamente.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }
}
