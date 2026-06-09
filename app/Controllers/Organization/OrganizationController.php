<?php

namespace App\Controllers\Organization;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controlador del módulo Organización (perfil matriz y sucursales).
 * Orquesta HTTP únicamente; mutaciones delegadas a modelos (§9).
 */
class OrganizationController extends BaseOrganizationController
{
    // ────────────────────────────────────────────────────────────────────────
    // PERFIL DE LA COMPAÑÍA (MATRIZ)
    // ────────────────────────────────────────────────────────────────────────

    public function profile(): string
    {
        $this->setViewSuccess('Perfil de la Compañía');
        $this->setPageTittleAhead('Perfil Corporativo', 'Configuración de la Matriz');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Perfil Matriz' => '',
        ]);

        $this->viewData['response'] = [
            'company' => $this->companyModel->getProfile(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Organization/profile');
    }

    public function updateProfile(): ResponseInterface
    {
        $this->assertAjaxPost();

        $data = [
            'id'              => $this->request->getPost('id'),
            'commercial_name' => $this->request->getPost('commercial_name'),
            'legal_name'      => $this->request->getPost('legal_name'),
            'tax_id'          => $this->request->getPost('tax_id'),
            'primary_email'   => $this->request->getPost('primary_email'),
            'primary_phone'   => $this->request->getPost('primary_phone'),
            'website_url'     => $this->request->getPost('website_url'),
        ];

        $logoFile = $this->request->getFile('logo');
        $savedId  = $this->companyModel->saveProfile(
            $data,
            $logoFile && $logoFile->isValid() ? $logoFile : null
        );

        if ($savedId === false) {
            $this->setOutputError(
                'No se pudo guardar el perfil.',
                $this->companyModel->errors()
            );

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'update_company_profile',
            ($data['id'] ? 'Actualizó' : 'Creó') . ' el perfil de la compañía (ID: ' . $savedId . ')'
        );

        $this->setOutputSuccess('Perfil actualizado correctamente.');

        return $this->jsonWithCsrf($this->outputData);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SUCURSALES
    // ────────────────────────────────────────────────────────────────────────

    public function branches(): string
    {
        $this->setViewSuccess('Listado de Sucursales');
        $this->setPageTittleAhead('Sucursales', 'Unidades de Negocio');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Sucursales' => '',
        ]);

        $this->viewData['response'] = [
            'company' => $this->companyModel->getProfile(),
            'stats'   => $this->branchModel->getStats(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Organization/branches_index');
    }

    public function list_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();

        $postData = $this->request->getPost();
        $list     = $this->branchModel->get_datatables($postData);
        $data     = [];

        foreach ($list as $branch) {
            $sid = (int) $branch->id;
            $logo = $branch->logo_path
                ? base_url($branch->logo_path)
                : base_url('bootstrap/img/avatars/avatar.jpg');

            $mainBadge = $branch->is_main
                ? ' <span class="badge badge-subtle-primary ms-1"><i class="fas fa-star me-1"></i>Principal</span>'
                : '';

            $statusBadge = $branch->active
                ? '<span class="badge badge-subtle-success"><i class="fas fa-check-circle me-1"></i>Activa</span>'
                : '<span class="badge badge-subtle-danger"><i class="fas fa-times-circle me-1"></i>Inactiva</span>';

            $actions = '<div class="d-flex gap-1 align-items-center">';
            $actions .= '<button type="button" class="btn btn-outline-info btn-sm btn-show-branch" data-id="' . $sid . '" title="Ver detalle">'
                . '<i class="fas fa-eye"></i></button>';
            $actions .= '<button type="button" class="btn btn-outline-primary btn-sm btn-edit-branch" data-id="' . $sid . '" title="Editar">'
                . '<i class="fas fa-edit"></i></button>';

            if ($branch->active) {
                $actions .= '<button type="button" class="btn btn-outline-warning btn-sm btn-toggle-branch" data-id="' . $sid . '" data-status="0" title="Desactivar">'
                    . '<i class="fas fa-ban"></i></button>';
            } else {
                $actions .= '<button type="button" class="btn btn-outline-success btn-sm btn-toggle-branch" data-id="' . $sid . '" data-status="1" title="Activar">'
                    . '<i class="fas fa-check"></i></button>';
            }

            $actions .= '<button type="button" class="btn btn-outline-danger btn-sm btn-delete-branch" data-id="' . $sid . '" title="Eliminar">'
                . '<i class="fas fa-trash"></i></button>';
            $actions .= '</div>';

            $displayName = $branch->commercial_name ?: $branch->name;
            $rfcLine     = ! empty($branch->tax_id)
                ? '<div class="small text-muted font-monospace">' . esc($branch->tax_id) . '</div>'
                : '';

            $data[] = [
                '<img src="' . esc($logo) . '" class="rounded" width="40" height="40" style="object-fit:cover" alt="">',
                '<div class="fw-semibold">' . esc($displayName) . $mainBadge . '</div>'
                . '<div class="small text-muted">' . esc($branch->name) . '</div>' . $rfcLine,
                '<span class="font-monospace small">' . esc($branch->branch_code) . '</span>',
                esc($branch->phone ?? '—'),
                esc($branch->email ?? '—'),
                $statusBadge,
                $actions,
            ];
        }

        return $this->jsonWithCsrf([
            'draw'            => (int) ($postData['draw'] ?? 0),
            'recordsTotal'    => $this->branchModel->count_all(),
            'recordsFiltered' => $this->branchModel->count_filtered($postData),
            'data'            => $data,
        ]);
    }

    public function get_ajax(int $id): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $branch = $this->branchModel->find($id);
        if (! $branch) {
            $this->setOutputError('Sucursal no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }

        $payload = [
            'id'               => $branch->id,
            'company_id'       => $branch->company_id,
            'name'             => $branch->name,
            'commercial_name'  => $branch->commercial_name,
            'legal_name'       => $branch->legal_name,
            'tax_id'           => $branch->tax_id,
            'branch_code'      => $branch->branch_code,
            'is_main'          => (int) $branch->is_main,
            'address'          => $branch->address,
            'phone'            => $branch->phone,
            'email'            => $branch->email,
            'website_url'      => $branch->website_url,
            'pos_printer_name' => $branch->pos_printer_name,
            'active'           => (int) $branch->active,
            'logo_url'         => $branch->logo_path
                ? base_url($branch->logo_path)
                : base_url('bootstrap/img/avatars/avatar.jpg'),
            'created_at'       => $branch->created_at,
            'updated_at'       => $branch->updated_at,
        ];

        $this->setOutputSuccess('Datos cargados.', $payload);

        return $this->jsonWithCsrf($this->outputData);
    }

    public function createBranch(): ResponseInterface|string
    {
        $company = $this->companyModel->getProfile();
        if (! $company) {
            return redirect()->to(route_to('organization.profile'))
                ->with('error', 'Configure primero el perfil de la compañía matriz.');
        }

        return redirect()->to(route_to('organization.branches') . '?open=create');
    }

    public function editBranch(int $id): ResponseInterface|string
    {
        if (! $this->branchModel->find($id)) {
            return redirect()->to(route_to('organization.branches'))
                ->with('error', 'Sucursal no encontrada.');
        }

        return redirect()->to(route_to('organization.branches') . '?open=edit&id=' . $id);
    }

    public function saveBranch(): ResponseInterface
    {
        $this->assertAjaxPost();

        $data = [
            'id'               => $this->request->getPost('id'),
            'company_id'       => $this->request->getPost('company_id'),
            'name'             => $this->request->getPost('name'),
            'commercial_name'  => $this->request->getPost('commercial_name'),
            'legal_name'       => $this->request->getPost('legal_name'),
            'tax_id'           => $this->request->getPost('tax_id'),
            'branch_code'      => $this->request->getPost('branch_code'),
            'is_main'          => $this->request->getPost('is_main'),
            'address'          => $this->request->getPost('address'),
            'phone'            => $this->request->getPost('phone'),
            'email'            => $this->request->getPost('email'),
            'website_url'      => $this->request->getPost('website_url'),
            'pos_printer_name' => $this->request->getPost('pos_printer_name'),
            'active'           => $this->request->getPost('active'),
        ];

        $logoFile = $this->request->getFile('logo');
        $savedId  = $this->branchModel->saveBranch(
            $data,
            $logoFile && $logoFile->isValid() ? $logoFile : null
        );

        if ($savedId === false) {
            $this->setOutputError(
                'No se pudo guardar la sucursal.',
                $this->branchModel->errors()
            );

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'save_branch',
            ($data['id'] ? 'Actualizó' : 'Creó') . ' la sucursal ID: ' . $savedId
        );

        $this->setOutputSuccess('Sucursal guardada correctamente.');

        return $this->jsonWithCsrf($this->outputData);
    }

    public function toggleBranchStatus(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        $status = (int) $this->request->getPost('status');
        if (! $this->branchModel->find($id)) {
            $this->setOutputError('Sucursal no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }

        if (! $this->branchModel->setActiveStatus($id, $status)) {
            $this->setOutputError('No se pudo actualizar el estatus de la sucursal.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'toggle_branch_status',
            'Cambió estado de sucursal ID ' . $id . ' a ' . $status
        );

        $this->setOutputSuccess('Estado actualizado correctamente.');

        return $this->jsonWithCsrf($this->outputData);
    }

    public function deleteBranch(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        if (! $this->branchModel->find($id)) {
            $this->setOutputError('Sucursal no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }

        if (! $this->branchModel->removeBranch($id)) {
            $this->setOutputError('No se pudo eliminar la sucursal.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->activityLogModel->logActivity('delete_branch', 'Eliminó la sucursal ID: ' . $id);

        $this->setOutputSuccess('Sucursal eliminada correctamente.');

        return $this->jsonWithCsrf($this->outputData);
    }
}
