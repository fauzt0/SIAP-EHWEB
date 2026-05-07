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
        
        // Configurar el modelo temporalmente para que devuelva también las que sufrieron soft delete, 
        // de esta forma podemos ver las inactivas que antes fueron borradas con la papelera
        $list = $this->categoryModel->withDeleted()->get_datatables($postData);
        
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
            if ($category->active) {
                $actions .= '<button class="btn btn-sm btn-outline-warning btn-toggle-status" data-id="' . $category->id . '" data-status="0" title="Desactivar/Suspender"><i class="fas fa-ban"></i></button>';
            } else {
                $actions .= '<button class="btn btn-sm btn-outline-success btn-toggle-status" data-id="' . $category->id . '" data-status="1" title="Restaurar/Activar"><i class="fas fa-check-circle"></i></button>';
            }
            $actions .= '<button class="btn btn-sm btn-outline-primary btn-edit-category" data-id="' . $category->id . '" title="Editar"><i class="fas fa-edit"></i></button>';
            $actions .= '</div>';
            $row[] = $actions;

            $data[] = $row;
        }

        $output = [
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->categoryModel->withDeleted()->count_all(),
            'recordsFiltered' => $this->categoryModel->withDeleted()->count_filtered($postData),
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

        $category = $this->categoryModel->withDeleted()->find($id);
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

    /**
     * Cambia el estatus (Activo/Inactivo) de una categoría
     */
    public function toggle_status_ajax(int $id)
    {
        if (!$this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(403);
        }

        $status = $this->request->getPost('status') ? 1 : 0;
        
        // Si se va a desactivar, verificar si tiene hijos activos
        if ($status === 0) {
            $activeChildren = $this->categoryModel->withDeleted()->where('parent_id', $id)->where('active', 1)->countAllResults();
            if ($activeChildren > 0) {
                $this->setOutputError('No se puede desactivar porque tiene subcategorías activas.');
                return $this->response->setJSON($this->outputData);
            }
        }

        // Al actualizar, usamos withDeleted() por si la categoría había sido eliminada con el botón de basura anterior
        // y le quitamos la marca de deleted_at para restaurarla por completo
        $updateData = ['active' => $status];
        if ($status === 1) {
            $updateData['deleted_at'] = null; // Restaurar soft delete
        }
        
        $this->categoryModel->withDeleted()->update($id, $updateData);
        
        $actionName = $status ? 'activó/restauró' : 'desactivó/suspendió';
        (new UserActivityLogsModel())
            ->logActivity('update_category_status', ucfirst($actionName) . ' categoría ID: ' . $id);
            
        $this->setOutputSuccess('Categoría ' . ($status ? 'activada' : 'desactivada') . ' correctamente');
        return $this->response->setJSON($this->outputData);
    }
}
