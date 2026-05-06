<?php

namespace App\Controllers\Catalog;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Catalog\CatalogPlanModel;
use App\Models\Catalog\CatalogProductRelationModel;
use App\Models\Users\UserActivityLogsModel;

/**
 * CatalogPlanController
 *
 * Gestiona el CRUD de Planes de Precios y Relaciones de Productos (Bundles/Gifts/Upsells).
 * Todos los endpoints son exclusivamente AJAX (Sección 1 DOCUMENTACION_TECNICA.md).
 * Toda mutación se registra en bitácora (Sección 8 DOCUMENTACION_TECNICA.md).
 * Sin consultas de DB en el controlador (Sección 9 DOCUMENTACION_TECNICA.md).
 */
class CatalogPlanController extends BaseCatalogController
{
    private CatalogPlanModel $planModel;
    private CatalogProductRelationModel $relationModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->planModel     = new CatalogPlanModel();
        $this->relationModel = new CatalogProductRelationModel();
    }

    // ══════════════════════════════════════════════════
    // ████  PLANES  ████
    // ══════════════════════════════════════════════════

    /**
     * GET AJAX: Obtiene todos los planes de un producto (activos e inactivos).
     */
    public function getPlans(int $productId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $product = $this->productModel->find($productId);
        if (!$product) {
            $this->setOutputError('Producto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $plans = $this->planModel->getAllByProduct($productId);

        $this->setOutputSuccess('Planes cargados.');
        $this->outputData['response'] = [
            'product' => [
                'id'             => $product->id,
                'commercial_name'=> $product->commercial_name,
                'sku'            => $product->sku,
                'product_type'   => $product->product_type,
            ],
            'plans' => $plans,
        ];

        return $this->response->setJSON($this->outputData);
    }

    /**
     * POST AJAX: Crea un nuevo plan para un producto.
     */
    public function storePlan(int $productId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $product = $this->productModel->find($productId);
        if (!$product) {
            $this->setOutputError('Producto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $data = $this->_extractPlanData($productId);

        $newId = $this->planModel->createPlan($data);

        if (!$newId) {
            $this->setOutputError('Error al guardar el plan.', $this->planModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        // Invalidar caché de stats (el contador de planes cambió)
        cache()->delete('catalog_product_stats');

        // Bitácora — Sección 8
        (new UserActivityLogsModel())
            ->logActivity(
                'create_plan',
                'Creó plan "' . $data['plan_name'] . '" para producto ID: ' . $productId . ' | ' . $product->commercial_name
            );

        $this->setOutputSuccess('Plan creado correctamente.');
        $this->outputData['response'] = ['plan_id' => $newId];
        $this->outputData['csrf']     = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    /**
     * POST AJAX: Actualiza un plan existente.
     */
    public function updatePlan(int $planId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $plan = $this->planModel->getPlanWithProduct($planId);
        if (!$plan) {
            $this->setOutputError('Plan no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $data = $this->_extractPlanData($plan->product_id);

        if (!$this->planModel->updatePlan($planId, $data)) {
            $this->setOutputError('Error al actualizar el plan.', $this->planModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        // Bitácora — Sección 8
        (new UserActivityLogsModel())
            ->logActivity(
                'update_plan',
                'Editó plan ID: ' . $planId . ' "' . $data['plan_name'] . '" | Producto: ' . $plan->product_name
            );

        $this->setOutputSuccess('Plan actualizado correctamente.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    /**
     * POST AJAX: Elimina (soft delete) un plan.
     */
    public function deletePlan(int $planId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $plan = $this->planModel->getPlanWithProduct($planId);
        if (!$plan) {
            $this->setOutputError('Plan no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->planModel->delete($planId);
        cache()->delete('catalog_product_stats');

        (new UserActivityLogsModel())
            ->logActivity(
                'delete_plan',
                'Eliminó (soft) plan ID: ' . $planId . ' "' . $plan->plan_name . '" | Producto: ' . $plan->product_name
            );

        $this->setOutputSuccess('Plan eliminado correctamente.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    /**
     * POST AJAX: Activa o desactiva un plan sin eliminarlo.
     */
    public function togglePlan(int $planId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $plan = $this->planModel->getPlanWithProduct($planId);
        if (!$plan) {
            $this->setOutputError('Plan no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $newState = $this->planModel->toggleActive($planId);

        if ($newState === false) {
            $this->setOutputError('Error al cambiar el estado del plan.');
            return $this->response->setJSON($this->outputData);
        }

        $stateLabel = $plan->is_active ? 'desactivado' : 'activado';

        (new UserActivityLogsModel())
            ->logActivity(
                'toggle_plan',
                'Plan ID: ' . $planId . ' "' . $plan->plan_name . '" ' . $stateLabel . ' | Producto: ' . $plan->product_name
            );

        $this->setOutputSuccess('Plan ' . $stateLabel . ' correctamente.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    // ══════════════════════════════════════════════════
    // ████  RELACIONES (Bundles / Gifts / Upsells)  ████
    // ══════════════════════════════════════════════════

    /**
     * GET AJAX: Obtiene las relaciones de un producto.
     */
    public function getRelations(int $productId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $relations = $this->relationModel->getRelationsForProduct($productId);

        $this->setOutputSuccess('Relaciones cargadas.');
        $this->outputData['response'] = $relations;
        return $this->response->setJSON($this->outputData);
    }

    /**
     * POST AJAX: Crea una relación entre productos.
     */
    public function storeRelation(int $productId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $relatedId = (int)$this->request->getPost('related_product_id');
        if ($relatedId === $productId) {
            $this->setOutputError('Un producto no puede relacionarse consigo mismo.');
            return $this->response->setJSON($this->outputData);
        }

        $data = [
            'main_product_id'    => $productId,
            'related_product_id' => $relatedId,
            'relation_type'      => $this->request->getPost('relation_type') ?: 'gift',
            'override_price'     => (float)($this->request->getPost('override_price') ?: 0),
            // duration_months: período gratuito en meses. 0 = permanente.
            // >0 = solo gratuito ese período, luego se cobra (regla de negocio documentada)
            'duration_months'    => (int)($this->request->getPost('duration_months') ?: 0),
        ];

        $newId = $this->relationModel->createRelation($data);

        if (!$newId) {
            $errors = $this->relationModel->errors() ?: ['Ya existe esta relación.'];
            $this->setOutputError('No se pudo crear la relación.', $errors);
            return $this->response->setJSON($this->outputData);
        }

        $product = $this->productModel->find($productId);
        (new UserActivityLogsModel())
            ->logActivity(
                'create_relation',
                'Creó relación ' . $data['relation_type'] . ' entre producto ID ' . $productId
                . ' y producto ID ' . $relatedId
                . ($data['duration_months'] > 0 ? ' | Período gratuito: ' . $data['duration_months'] . ' mes(es)' : ' | Permanente')
            );

        $this->setOutputSuccess('Relación creada correctamente.');
        $this->outputData['response'] = ['relation_id' => $newId];
        $this->outputData['csrf']     = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    /**
     * POST AJAX: Elimina una relación entre productos.
     */
    public function deleteRelation(int $relationId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $relation = $this->relationModel->find($relationId);
        if (!$relation) {
            $this->setOutputError('Relación no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->relationModel->delete($relationId);

        (new UserActivityLogsModel())
            ->logActivity(
                'delete_relation',
                'Eliminó relación ID: ' . $relationId
                . ' | Tipo: ' . $relation->relation_type
                . ' | Producto: ' . $relation->main_product_id . ' → ' . $relation->related_product_id
            );

        $this->setOutputSuccess('Relación eliminada correctamente.');
        $this->outputData['csrf'] = csrf_hash();
        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────
    // HELPERS PRIVADOS (solo transformación de input HTTP)
    // ─────────────────────────────────────────────────

    /**
     * Extrae y sanitiza los datos de plan desde el POST.
     * Sin lógica de DB — solo mapeo de input (Sección 9 DOCUMENTACION_TECNICA.md).
     */
    private function _extractPlanData(int $productId): array
    {
        return [
            'product_id'    => $productId,
            'plan_name'     => trim((string)$this->request->getPost('plan_name')),
            'billing_cycle' => $this->request->getPost('billing_cycle') ?: 'monthly',
            'sale_price'    => (float)($this->request->getPost('sale_price') ?: 0),
            'renewal_price' => (float)($this->request->getPost('renewal_price') ?: 0),
            'setup_fee'     => (float)($this->request->getPost('setup_fee') ?: 0),
            'is_active'     => $this->request->getPost('is_active') ? 1 : 0,
        ];
    }
}
