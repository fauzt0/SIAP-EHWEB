<?php

namespace App\Controllers\Catalog;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Users\UserActivityLogsModel;

/**
 * CatalogCategoryController
 * 
 * Gestiona el catálogo de categorías de productos y servicios.
 * Implementa una estructura jerárquica (Padre/Hijo).
 */
class CatalogCategoryController extends BaseCatalogController
{
    /**
     * Vista principal del catálogo de categorías
     */
    public function index(): string
    {
        $this->setViewSuccess('Módulo de Categorías');
        $this->setPageTittleAhead('Categorías', 'Gestión de Categorías de Catálogo');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'      => base_url(),
            'Catálogo'    => route_to('catalog.products'),
            'Categorías'  => '',
        ]);

        $this->viewData['response'] = [
            'parent_categories' => $this->categoryModel->where('parent_id', null)->where('active', 1)->findAll(),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Catalog/categories_index');
    }

    /**
     * Respuesta JSON para el DataTable de categorías
     */
    public function categories_ajax()
    {
        if (!$this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $postData = $this->request->getPost();
        $list = $this->categoryModel->get_datatables($postData);
        $data = [];
        $no   = (int) $this->request->getPost('start');

        foreach ($list as $category) {
            $no++;
            $row = [];

            // Icono y Nombre
            $row[] = '<i class="' . esc($category->icon ?: 'fas fa-folder') . ' me-2"></i>' . esc($category->name);
            
            // Slug
            $row[] = '<code class="text-primary">' . esc($category->slug) . '</code>';

            // Padre
            $row[] = $category->parent_name ? esc($category->parent_name) : '<span class="text-muted italic">Principal</span>';

            // Estatus
            $statusBadge = $category->active 
                ? '<span class="badge badge-subtle-success">Activo</span>' 
                : '<span class="badge badge-subtle-danger">Inactivo</span>';
            $row[] = $statusBadge;

            // Acciones
            $actions = '<div class="d-flex gap-1">';
            $actions .= '<button class="btn btn-sm btn-outline-warning btn-edit-category" data-id="' . $category->id . '" title="Editar"><i class="fas fa-edit"></i></button>';
            $actions .= '<button class="btn btn-sm btn-outline-danger btn-delete-category" data-id="' . $category->id . '" title="Eliminar"><i class="fas fa-trash"></i></button>';
            $actions .= '</div>';
            $row[] = $actions;

            $data[] = $row;
        }

        $output = [
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->categoryModel->count_all(),
            'recordsFiltered' => $this->categoryModel->count_filtered($postData),
            'data'            => $data,
        ];

        return $this->response->setJSON($output);
    }

    /**
     * Obtiene los datos de una categoría específica (AJAX)
     */
    public function get_ajax(int $id)
    {
        if (!$this->request->isAJAX()) {
             throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $category = $this->categoryModel->find($id);
        if (!$category) {
            $this->setOutputError('Categoría no encontrada');
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('Datos cargados', $category);
        return $this->response->setJSON($this->outputData);
    }

    /**
     * Guarda o actualiza una categoría
     */
    public function save_ajax()
    {
        if (!$this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(403);
        }

        $id = $this->request->getPost('id');
        $data = [
            'parent_id'   => $this->request->getPost('parent_id') ?: null,
            'name'        => trim((string)$this->request->getPost('name')),
            'slug'        => $this->request->getPost('slug') ?: url_title((string)$this->request->getPost('name'), '-', true),
            'description' => $this->request->getPost('description'),
            'icon'        => $this->request->getPost('icon') ?: 'fas fa-folder',
            'active'      => $this->request->getPost('active') ? 1 : 0,
        ];

        // Validación básica
        if (empty($data['name'])) {
            $this->setOutputError('El nombre es obligatorio');
            return $this->response->setJSON($this->outputData);
        }

        if ($id) {
            $this->categoryModel->update($id, $data);
            $message = 'Categoría actualizada correctamente';
            (new UserActivityLogsModel())
                ->logActivity('update_category', 'Editó categoría ID: ' . $id . ' | ' . $data['name']);
        } else {
            $this->categoryModel->insert($data);
            $message = 'Categoría creada correctamente';
            (new UserActivityLogsModel())
                ->logActivity('create_category', 'Creó categoría: ' . $data['name'] . ' | Slug: ' . $data['slug']);
        }

        $this->setOutputSuccess($message);
        return $this->response->setJSON($this->outputData);
    }

    /**
     * Elimina una categoría (Soft Delete)
     */
    public function delete_ajax(int $id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        // Verificar si tiene hijos
        $hasChildren = $this->categoryModel->where('parent_id', $id)->countAllResults();
        if ($hasChildren > 0) {
            $this->setOutputError('No se puede eliminar una categoría que contiene subcategorías.');
            return $this->response->setJSON($this->outputData);
        }

        // Verificar si tiene productos asociados
        $hasProducts = $this->productModel->where('category_id', $id)->countAllResults();
        if ($hasProducts > 0) {
            $this->setOutputError('No se puede eliminar una categoría que tiene productos asociados.');
            return $this->response->setJSON($this->outputData);
        }

        $this->categoryModel->delete($id);
        (new UserActivityLogsModel())
            ->logActivity('delete_category', 'Eliminó (soft) categoría ID: ' . $id);
        $this->setOutputSuccess('Categoría eliminada correctamente');
        return $this->response->setJSON($this->outputData);
    }
}
