<?php

namespace App\Controllers\Catalog;

use App\Models\Catalog\CatalogProductStockModel;
use App\Models\Catalog\InventoryMovementModel;
use App\Models\Users\UserActivityLogsModel;

/**
 * InventoryController
 *
 * Gestiona el inventario de stock de productos físicos por sucursal.
 * Sigue el patrón "Thin Controller" (Sección 9 DOCUMENTACION_TECNICA.md):
 * toda la lógica transaccional reside en CatalogProductStockModel.
 */
class InventoryController extends BaseCatalogController
{
    protected CatalogProductStockModel $stockModel;
    protected InventoryMovementModel   $movementModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface   $request,
        \CodeIgniter\HTTP\ResponseInterface  $response,
        \Psr\Log\LoggerInterface             $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->stockModel    = new CatalogProductStockModel();
        $this->movementModel = new InventoryMovementModel();
    }

    // ── Endpoints de consulta ──────────────────────────────────────────────────

    /**
     * Devuelve el stock actual de un producto por sucursal (AJAX GET).
     */
    public function getStock(int $productId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $product = $this->productModel->find($productId);
        if (!$product) {
            $this->setOutputError('Producto no encontrado.');
            return $this->response->setJSON($this->outputData);
        }

        $stock = $this->stockModel->getStockWithBranches($productId);

        $this->setOutputSuccess('Stock cargado.', $stock);
        return $this->response->setJSON($this->outputData);
    }

    /**
     * Devuelve el historial de movimientos (Kardex) de un producto (AJAX GET).
     */
    public function getKardex(int $productId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $product = $this->productModel->find($productId);
        if (!$product) {
            $this->setOutputError('Producto no encontrado.');
            return $this->response->setJSON($this->outputData);
        }

        // Obtener historial con nombres de sucursal legibles
        $history = $this->movementModel->getKardexForProduct($productId);

        $this->setOutputSuccess('Kardex cargado.', $history);
        return $this->response->setJSON($this->outputData);
    }

    // ── Endpoints de mutación ──────────────────────────────────────────────────

    /**
     * Registra un movimiento de inventario (Entrada / Salida / Transferencia).
     * Delega la lógica transaccional al modelo (Sección 9 DOCUMENTACION_TECNICA.md).
     */
    public function registerMovement(int $productId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $product = $this->productModel->find($productId);
        if (!$product || $product->product_type !== 'physical') {
            $this->setOutputError('Producto no encontrado o no es un producto físico.');
            return $this->response->setJSON($this->outputData);
        }

        $type           = $this->request->getPost('type');
        $branchId       = (int) $this->request->getPost('org_branch_id');
        $targetBranchId = (int) ($this->request->getPost('target_org_branch_id') ?? 0);
        $quantity       = (float) $this->request->getPost('quantity');
        $notes          = trim((string) $this->request->getPost('notes'));

        if ($branchId <= 0) {
            $this->setOutputError('Debe seleccionar una sucursal.');
            return $this->response->setJSON($this->outputData);
        }

        if ($type === 'transfer' && $targetBranchId <= 0) {
            $this->setOutputError('Debe seleccionar una sucursal destino para las transferencias.');
            return $this->response->setJSON($this->outputData);
        }

        // La lógica real reside en el modelo (transacción + bitácora)
        $result = $this->stockModel->registerMovement(
            $productId,
            $branchId,
            $type,
            $quantity,
            $notes,
            $targetBranchId
        );

        if ($result !== true) {
            $this->setOutputError($result);
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('Movimiento registrado correctamente.');
        return $this->response->setJSON($this->outputData);
    }

    /**
     * Actualiza el stock mínimo de alerta para un producto en una sucursal (AJAX POST).
     */
    public function updateMinAlert(int $productId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $branchId  = (int) $this->request->getPost('org_branch_id');
        $minAlert  = (float) $this->request->getPost('min_alert');

        $stockRow = $this->stockModel->getStock($productId, $branchId);

        if (!$stockRow) {
            $this->setOutputError('No existe registro de stock para esta sucursal.');
            return $this->response->setJSON($this->outputData);
        }

        $this->stockModel->update($stockRow->id, ['min_alert' => $minAlert]);

        (new UserActivityLogsModel())->logActivity(
            'update_stock_alert',
            "Actualizó alerta mínima a {$minAlert} uds. | Producto ID: {$productId} | Sucursal ID: {$branchId}"
        );

        $this->setOutputSuccess('Alerta mínima actualizada.');
        return $this->response->setJSON($this->outputData);
    }
}
