<?php

namespace App\Controllers\Catalog;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Catalog\CatalogAttributeModel;
use App\Models\Catalog\CatalogProductImageModel;
use App\Models\Users\UserActivityLogsModel;

/**
 * CatalogProductController
 *
 * Controlador "delgado" (Slim Controller): solo orquesta HTTP.
 * Toda la lógica de datos reside en los Modelos (Sección 9 DOCUMENTACION_TECNICA.md).
 * Toda mutación se registra en la bitácora (Sección 8 DOCUMENTACION_TECNICA.md).
 */
class CatalogProductController extends BaseCatalogController
{
    // ────────────────────────────────────────────────────────────────────────
    // VISTA PRINCIPAL: Listado con DataTables + Cards de Stats
    // ────────────────────────────────────────────────────────────────────────

    public function index(): string
    {
        $this->setViewSuccess('Listado de Productos y Servicios');
        $this->setPageTittleAhead('Productos', 'Gestión de Productos y Servicios');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'    => base_url(),
            'Catálogo'  => route_to('catalog.products'),
            'Productos' => '',
        ]);

        // Estadísticas para cards (caché 5 min)
        $stats = cache()->get('catalog_product_stats');
        if (!$stats) {
            $stats = $this->productModel->getStats();
            cache()->save('catalog_product_stats', $stats, 300);
        }

        $this->viewData['response'] = [
            'categories' => $this->categoryModel->where('active', 1)->findAll(),
            'stats'      => $stats,
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Catalog/products_index');
    }

    // ────────────────────────────────────────────────────────────────────────
    // AJAX: DataTables Server-Side
    // ────────────────────────────────────────────────────────────────────────

    public function products_ajax()
    {
        if (!$this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $postData = $this->request->getPost();
        $list     = $this->productModel->get_datatables($postData);
        $data     = [];
        $no       = (int) $this->request->getPost('start');

        foreach ($list as $product) {
            $no++;
            $row = [];

            // Imagen miniatura
            $imgPath = $product->main_image ?? null;
            if (!empty($imgPath) && file_exists(FCPATH . 'uploads/catalog/' . $imgPath)) {
                $imgUrl = base_url('uploads/catalog/' . $imgPath);
            } else {
                $imgUrl = base_url('bootstrap/img/catalog/no-image.png');
            }
            $row[] = '<img src="' . $imgUrl . '" width="48" height="48" '
                   . 'class="rounded object-fit-cover shadow-sm border" '
                   . 'alt="' . esc($product->commercial_name) . '">';

            // Nombre comercial + SKU
            $row[] = '<div class="fw-semibold">' . esc($product->commercial_name) . '</div>'
                   . '<small class="text-muted font-monospace">' . esc($product->sku) . '</small>';

            // Categoría
            $row[] = !empty($product->category_name)
                ? '<span class="badge badge-subtle-secondary">' . esc($product->category_name) . '</span>'
                : '<span class="text-muted small fst-italic">Sin categoría</span>';

            // Tipo de producto con icono fas (no Lucide — Sección 12 DOCUMENTACION_TECNICA.md)
            $typeMap = [
                'service'  => ['label' => 'Servicio', 'color' => 'primary',   'icon' => 'fa-cloud'],
                'physical' => ['label' => 'Físico',   'color' => 'info',      'icon' => 'fa-box'],
                'digital'  => ['label' => 'Digital',  'color' => 'warning',   'icon' => 'fa-key'],
            ];
            $type     = $product->product_type ?? 'service';
            $typeInfo = $typeMap[$type] ?? ['label' => ucfirst($type), 'color' => 'secondary', 'icon' => 'fa-tag'];
            $row[]    = '<span class="badge badge-subtle-' . $typeInfo['color'] . '">'
                      . '<i class="fas fa-fw ' . $typeInfo['icon'] . ' me-1"></i>'
                      . $typeInfo['label'] . '</span>';

            // Planes configurados
            $plansCount = (int)($product->plans_count ?? 0);
            $row[]      = $plansCount > 0
                ? '<span class="badge bg-success rounded-pill">' . $plansCount . ' plan(es)</span>'
                : '<span class="text-muted small">Sin planes</span>';

            // Estatus (fas icons únicamente)
            $row[] = $product->active
                ? '<span class="badge badge-subtle-success"><i class="fas fa-check-circle me-1"></i>Activo</span>'
                : '<span class="badge badge-subtle-danger"><i class="fas fa-times-circle me-1"></i>Inactivo</span>';

            // Acciones (permisos según Sección 7 DOCUMENTACION_TECNICA.md)
            $pid     = $product->id;
            $actions = '<div class="d-flex gap-1 align-items-center">';
            $actions .= '<a href="' . route_to('catalog.products.edit', $pid) . '" '
                      . 'class="btn btn-sm btn-outline-warning" title="Editar">'
                      . '<i class="fas fa-edit"></i></a>';
            $actions .= '<button class="btn btn-sm btn-outline-primary btn-manage-plans" '
                      . 'data-id="' . $pid . '" data-name="' . esc($product->commercial_name) . '" '
                      . 'title="Gestionar Planes"><i class="fas fa-tags"></i></button>';
            $actions .= '<button class="btn btn-sm btn-outline-danger btn-delete-product" '
                      . 'data-id="' . $pid . '" title="Eliminar"><i class="fas fa-trash"></i></button>';
            $actions .= '</div>';
            $row[]   = $actions;

            $data[] = $row;
        }

        $output = [
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->productModel->count_all(),
            'recordsFiltered' => $this->productModel->count_filtered($postData),
            'data'            => $data,
        ];

        return $this->response->setJSON($output);
    }

    // ────────────────────────────────────────────────────────────────────────
    // VISTA FORMULARIO: Alta
    // ────────────────────────────────────────────────────────────────────────

    public function create(): string
    {
        $this->setViewSuccess('Alta de Producto');
        $this->setPageTittleAhead('Nuevo Producto', 'Alta de Producto o Servicio');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'         => base_url(),
            'Catálogo'       => route_to('catalog.products'),
            'Nuevo Producto' => '',
        ]);

        $this->viewData['response'] = [
            'categories' => $this->categoryModel->where('active', 1)->findAll(),
            'product'    => null,
            'attributes' => [],
            'images'     => [],
            'isEdit'     => false,
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Catalog/product_form');
    }

    // ────────────────────────────────────────────────────────────────────────
    // VISTA FORMULARIO: Edición
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function edit(int $id)
    {
        $product = $this->productModel->find($id);
        if (!$product) {
            return redirect()->to(route_to('catalog.products'))->with('error', 'Producto no encontrado.');
        }

        $this->setViewSuccess('Editar Producto');
        $this->setPageTittleAhead('Editar: ' . esc($product->commercial_name), 'Edición de Producto');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'   => base_url(),
            'Catálogo' => route_to('catalog.products'),
            'Editar'   => '',
        ]);

        $attrModel  = new CatalogAttributeModel();
        $imageModel = new CatalogProductImageModel();

        $this->viewData['response'] = [
            'categories' => $this->categoryModel->where('active', 1)->findAll(),
            'product'    => $product,
            'attributes' => $attrModel->getByProduct($id),
            'images'     => $imageModel->getByProduct($id),
            'isEdit'     => true,
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Catalog/product_form');
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST: Guardar Nuevo Producto
    // ────────────────────────────────────────────────────────────────────────

    public function store()
    {
        if (!$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $data = $this->_extractProductData();

        $newId = $this->productModel->createProductWithAttributes(
            $data,
            $this->request->getPost('attributes') ?? []
        );

        if (!$newId) {
            $this->setOutputError('Error al guardar el producto. Verifique los datos e intente nuevamente.', $this->productModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        // Procesar imágenes (lógica de archivo en controlador, lógica de DB en modelo)
        $this->_processImages($newId);

        // Invalidar caché de estadísticas
        cache()->delete('catalog_product_stats');

        // Bitácora de auditoría — obligatorio en mutaciones (Sección 8 DOCUMENTACION_TECNICA.md)
        (new UserActivityLogsModel())
            ->logActivity('create_product', 'Alta de producto ID: ' . $newId . ' | SKU: ' . $data['sku'] . ' | ' . $data['commercial_name']);

        $this->setOutputSuccess('Producto creado correctamente.');
        $this->outputData['response'] = [
            'id'       => $newId,
            'redirect' => route_to('catalog.products.edit', $newId),
        ];
        return $this->response->setJSON($this->outputData);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST: Actualizar Producto
    // ────────────────────────────────────────────────────────────────────────

    public function update(int $id)
    {
        if (!$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $product = $this->productModel->find($id);
        if (!$product) {
            $this->setOutputError('Producto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $data = $this->_extractProductData();

        $ok = $this->productModel->updateProductWithAttributes(
            $id,
            $data,
            $this->request->getPost('attributes') ?? []
        );

        if (!$ok) {
            $this->setOutputError('Error al actualizar el producto.', $this->productModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        $this->_processImages($id);
        cache()->delete('catalog_product_stats');

        (new UserActivityLogsModel())
            ->logActivity('update_product', 'Edición de producto ID: ' . $id . ' | SKU: ' . $data['sku'] . ' | ' . $data['commercial_name']);

        $this->setOutputSuccess('Producto actualizado correctamente.');
        $this->outputData['response'] = ['id' => $id];
        $this->outputData['csrf']     = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST AJAX: Eliminar Producto (Soft Delete)
    // ────────────────────────────────────────────────────────────────────────

    public function delete(int $id)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
        }

        $product = $this->productModel->find($id);
        if (!$product) {
            $this->setOutputError('Producto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->productModel->delete($id);
        cache()->delete('catalog_product_stats');

        (new UserActivityLogsModel())
            ->logActivity('delete_product', 'Eliminó (soft) producto ID: ' . $id . ' | ' . $product->commercial_name . ' | SKU: ' . $product->sku);

        $this->setOutputSuccess('Producto eliminado correctamente.');
        return $this->response->setJSON($this->outputData);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST AJAX: Eliminar imagen individual (lógica en modelo)
    // ────────────────────────────────────────────────────────────────────────

    public function delete_image(int $imageId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $imageModel = new CatalogProductImageModel();
        $ok         = $imageModel->deleteImage($imageId);

        if (!$ok) {
            $this->setOutputError('Imagen no encontrada o no se pudo eliminar.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())
            ->logActivity('delete_product_image', 'Eliminó imagen ID: ' . $imageId);

        $this->setOutputSuccess('Imagen eliminada correctamente.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST AJAX: Marcar imagen como principal (lógica en modelo)
    // ────────────────────────────────────────────────────────────────────────

    public function set_main_image(int $imageId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $imageModel = new CatalogProductImageModel();
        $ok         = $imageModel->setMainImage($imageId);

        if (!$ok) {
            $this->setOutputError('Imagen no encontrada.');
            return $this->response->setJSON($this->outputData);
        }

        (new UserActivityLogsModel())
            ->logActivity('set_main_image', 'Cambió imagen principal a ID: ' . $imageId);

        $this->setOutputSuccess('Imagen principal actualizada.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    // ────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS (solo orquestación de archivos físicos)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Extrae y sanitiza los datos del formulario de producto.
     * No hace consultas — solo transforma el input HTTP.
     */
    private function _extractProductData(): array
    {
        return [
            'category_id'       => $this->request->getPost('category_id') ?: null,
            'sku'               => strtoupper(trim((string)$this->request->getPost('sku'))),
            'barcode'           => trim((string)$this->request->getPost('barcode')) ?: null,
            'internal_name'     => trim((string)$this->request->getPost('internal_name')),
            'commercial_name'   => trim((string)$this->request->getPost('commercial_name')),
            'description_short' => trim((string)$this->request->getPost('description_short')) ?: null,
            'description_long'  => $this->request->getPost('description_long') ?: null,
            'product_type'      => $this->request->getPost('product_type') ?: 'service',
            'active'            => $this->request->getPost('active') ? 1 : 0,
        ];
    }

    /**
     * Mueve archivos al disco y delega el guardado en BD al modelo.
     * El controlador solo maneja el sistema de archivos; la lógica de DB va al modelo.
     */
    private function _processImages(int $productId): void
    {
        $files = $this->request->getFileMultiple('product_images');
        if (!$files) return;

        $imageModel = new CatalogProductImageModel();
        $targetDir  = FCPATH . 'uploads/catalog/';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        foreach ($files as $file) {
            if (!$file->isValid() || $file->hasMoved()) continue;

            $newName = $file->getRandomName();
            if ($file->move($targetDir, $newName)) {
                // La lógica de "primera imagen = principal" está encapsulada en el modelo
                $imageModel->saveProductImage($productId, $newName);
            }
        }
    }
}
