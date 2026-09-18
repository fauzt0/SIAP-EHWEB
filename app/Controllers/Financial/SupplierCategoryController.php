<?php

namespace App\Controllers\Financial;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Users\UserActivityLogsModel;

/**
 * SupplierCategoryController
 *
 * CRUD de categorías de proveedores (fin_supplier_categories).
 * Soft-delete con bloqueo si hay proveedores vinculados.
 */
class SupplierCategoryController extends BaseFinancialController
{
    public function index(): string
    {
        $this->setViewSuccess('Categorías de Proveedores');
        $this->setPageTittleAhead('Categorías', 'Categorías de Proveedores');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'      => route_to('dashboard.index'),
            'Proveedores' => route_to('suppliers.index'),
            'Categorías'  => '',
        ]);

        $this->viewData['response'] = [];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Financial/Suppliers/categories_index');
    }

    public function list_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();

        $postData = $this->request->getPost();
        $list     = $this->categoryModel->get_datatables($postData);
        $data     = [];
        $canManage = auth()->user()->can('catalog.manage-suppliers');

        foreach ($list as $category) {
            $isDeleted    = ! empty($category->deleted_at);
            $hasSuppliers = ((int) ($category->suppliers_count ?? 0)) > 0;
            $suppliersN   = (int) ($category->suppliers_count ?? 0);

            if ($isDeleted) {
                $statusBadge = '<span class="badge badge-subtle-dark"><i class="fas fa-trash me-1"></i>Eliminada</span>';
            } else {
                $statusBadge = $category->active
                    ? '<span class="badge badge-subtle-success"><i class="fas fa-check-circle me-1"></i>Activa</span>'
                    : '<span class="badge badge-subtle-danger"><i class="fas fa-times-circle me-1"></i>Inactiva</span>';
            }

            $linkedBadge = $suppliersN > 0
                ? '<span class="badge bg-primary rounded-pill">' . $suppliersN . '</span>'
                : '<span class="text-muted small">—</span>';

            $actions = '<div class="d-flex gap-1 align-items-center">';

            if ($canManage && $isDeleted) {
                $actions .= '<button type="button" class="btn btn-outline-success btn-sm btn-restore-category" '
                    . 'data-id="' . (int) $category->id . '" '
                    . 'data-name="' . esc($category->name, 'attr') . '" '
                    . 'title="Restaurar">'
                    . '<i class="fas fa-undo"></i></button>';
            } elseif ($canManage) {
                $actions .= '<button type="button" class="btn btn-outline-warning btn-sm btn-edit-category" '
                    . 'data-id="' . (int) $category->id . '" '
                    . 'data-name="' . esc($category->name, 'attr') . '" '
                    . 'data-description="' . esc((string) ($category->description ?? ''), 'attr') . '" '
                    . 'data-active="' . (int) $category->active . '" '
                    . 'title="Editar">'
                    . '<i class="fas fa-edit"></i></button>';

                $actions .= '<button type="button" class="btn btn-outline-danger btn-sm btn-delete-category" '
                    . 'data-id="' . (int) $category->id . '" '
                    . 'data-name="' . esc($category->name, 'attr') . '" '
                    . 'data-has-suppliers="' . ($hasSuppliers ? '1' : '0') . '" '
                    . 'data-suppliers-count="' . $suppliersN . '" '
                    . 'title="Eliminar">'
                    . '<i class="fas fa-trash-alt"></i></button>';
            } else {
                $actions .= '<span class="text-muted small">Sólo lectura</span>';
            }

            $actions .= '</div>';

            $data[] = [
                '<div class="fw-semibold"><i class="fas fa-fw fa-tags me-1 text-primary"></i>' . esc($category->name) . '</div>',
                esc($category->description ?? '—'),
                $linkedBadge,
                $statusBadge,
                $actions,
            ];
        }

        return $this->jsonWithCsrf([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->categoryModel->get_datatables_total($postData),
            'recordsFiltered' => $this->categoryModel->count_filtered($postData),
            'data'            => $data,
        ]);
    }

    public function store(): ResponseInterface
    {
        $this->assertAjaxPost();

        $data = $this->_extractCategoryData();

        $this->categoryModel->setValidationRule(
            'name',
            'required|max_length[100]|is_unique[fin_supplier_categories.name]'
        );

        $newId = $this->categoryModel->insert($data);

        if (! $newId) {
            $this->setOutputError(
                'Error al guardar la categoría. Verifique los datos e intente nuevamente.',
                $this->categoryModel->errors()
            );

            return $this->jsonWithCsrf($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'create_supplier_category',
            'Creó categoría de proveedor: ' . $data['name'] . ' (ID: ' . $newId . ')'
        );

        $this->setOutputSuccess('Categoría creada correctamente.');
        $this->outputData['response'] = ['id' => $newId];

        return $this->jsonWithCsrf($this->outputData);
    }

    public function update(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        $category = $this->categoryModel->find($id);
        if (! $category) {
            $this->setOutputError('Categoría no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }

        $data       = $this->_extractCategoryData();
        $data['id'] = $id;

        $this->categoryModel->setValidationRule(
            'name',
            'required|max_length[100]|is_unique[fin_supplier_categories.name,id,' . $id . ']'
        );

        if (! $this->categoryModel->update($id, $data)) {
            $this->setOutputError(
                'Error al actualizar la categoría.',
                $this->categoryModel->errors()
            );

            return $this->jsonWithCsrf($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'update_supplier_category',
            'Editó categoría de proveedor: ' . $data['name'] . ' (ID: ' . $id . ')'
        );

        $this->setOutputSuccess('Categoría actualizada correctamente.');
        $this->outputData['response'] = ['id' => $id];

        return $this->jsonWithCsrf($this->outputData);
    }

    public function delete(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        $category = $this->categoryModel->find($id);
        if (! $category) {
            $this->setOutputError('Categoría no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }

        if ($this->categoryModel->hasSuppliers($id)) {
            $this->setOutputError('Esta categoría tiene proveedores vinculados y no puede eliminarse.');

            return $this->jsonWithCsrf($this->outputData);
        }

        $this->categoryModel->delete($id);

        (new UserActivityLogsModel())->logActivity(
            'delete_supplier_category',
            'Eliminó (soft) categoría de proveedor: ' . $category->name . ' (ID: ' . $id . ')'
        );

        $this->setOutputSuccess('Categoría eliminada correctamente.');

        return $this->jsonWithCsrf($this->outputData);
    }

    public function restore(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        $category = $this->categoryModel->withDeleted()->find($id);
        if (! $category) {
            $this->setOutputError('Categoría no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->jsonWithCsrf($this->outputData);
        }

        if (! $this->categoryModel->restore($id)) {
            $this->setOutputError('No se pudo restaurar la categoría.');

            return $this->jsonWithCsrf($this->outputData);
        }

        (new UserActivityLogsModel())->logActivity(
            'restore_supplier_category',
            'Restauró categoría de proveedor: ' . $category->name . ' (ID: ' . $id . ')'
        );

        $this->setOutputSuccess('Categoría restaurada correctamente.');

        return $this->jsonWithCsrf($this->outputData);
    }

    private function _extractCategoryData(): array
    {
        return [
            'name'        => trim((string) $this->request->getPost('name')),
            'description' => trim((string) $this->request->getPost('description')) ?: null,
            'active'      => $this->request->getPost('active') !== null ? (int) $this->request->getPost('active') : 1,
        ];
    }
}
