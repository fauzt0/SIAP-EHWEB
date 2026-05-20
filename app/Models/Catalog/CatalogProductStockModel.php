<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;
use App\Models\Users\UserActivityLogsModel;

/**
 * CatalogProductStockModel
 *
 * Gestiona las existencias de productos físicos por sucursal.
 * Incluye la lógica transaccional de movimientos (Sección 9 DOCUMENTACION_TECNICA.md).
 */
class CatalogProductStockModel extends Model
{
    protected $table            = 'catalog_product_stock';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id', 'org_branches_id', 'stock', 'min_alert'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // ── Consultas ──────────────────────────────────────────────────────────────

    /**
     * Obtiene el stock de un producto, opcionalmente filtrado por sucursal.
     */
    public function getStock(int $productId, int $branchId = null)
    {
        $query = $this->where('product_id', $productId);
        if ($branchId) {
            return $query->where('org_branches_id', $branchId)->first();
        }
        return $query->findAll();
    }

    /**
     * Obtiene el stock de un producto con nombre de sucursal incluido.
     */
    public function getStockWithBranches(int $productId): array
    {
        return $this->db->table($this->table . ' s')
            ->select('s.*, b.name AS branch_name')
            ->join('org_branches b', 'b.id = s.org_branches_id', 'left')
            ->where('s.product_id', $productId)
            ->where('s.deleted_at IS NULL')
            ->get()->getResultArray();
    }

    // ── Mutaciones Transaccionales ────────────────────────────────────────────

    /**
     * Registra un movimiento de inventario de forma atómica:
     *   1. Actualiza (o crea) el saldo en catalog_product_stock
     *   2. Inserta el movimiento en inventory_movements
     *   3. Escribe en la bitácora de auditoría (Sección 8 DOCUMENTACION_TECNICA.md)
     *
     * @param int    $productId
     * @param int    $branchId          Sucursal origen del movimiento
     * @param string $type             'entry' | 'exit' | 'transfer'
     * @param float  $quantity         Siempre positivo; la dirección la define $type
     * @param string $notes
     * @param int    $targetBranchId   Solo requerido para 'transfer'
     * @return bool|string  true si éxito, mensaje de error si falla
     */
    public function registerMovement(
        int    $productId,
        int    $branchId,
        string $type,
        float  $quantity,
        string $notes = '',
        int    $targetBranchId = 0
    ): bool|string {
        if ($quantity <= 0) {
            return 'La cantidad debe ser mayor que cero.';
        }

        if (!in_array($type, ['entry', 'exit', 'transfer'])) {
            return 'Tipo de movimiento inválido.';
        }

        $movementModel = new InventoryMovementModel();

        $this->db->transBegin();

        // ── 1. Actualizar saldo en la sucursal origen ──
        $stockRow = $this->getStock($productId, $branchId);

        if ($type === 'exit' || $type === 'transfer') {
            if (!$stockRow || $stockRow->stock < $quantity) {
                $this->db->transRollback();
                return 'Stock insuficiente en la sucursal seleccionada.';
            }
            $newStock = (float)$stockRow->stock - $quantity;
        } else {
            $newStock = $stockRow ? (float)$stockRow->stock + $quantity : $quantity;
        }

        if ($stockRow) {
            $this->update($stockRow->id, ['stock' => $newStock]);
        } else {
            $this->insert([
                'product_id'      => $productId,
                'org_branches_id' => $branchId,
                'stock'           => $newStock,
                'min_alert'       => 0,
            ]);
        }

        // ── 2. Para transferencias, sumar en sucursal destino ──
        if ($type === 'transfer' && $targetBranchId > 0) {
            $targetRow = $this->getStock($productId, $targetBranchId);
            if ($targetRow) {
                $this->update($targetRow->id, ['stock' => (float)$targetRow->stock + $quantity]);
            } else {
                $this->insert([
                    'product_id'      => $productId,
                    'org_branches_id' => $targetBranchId,
                    'stock'           => $quantity,
                    'min_alert'       => 0,
                ]);
            }
        }

        // ── 3. Registrar en inventory_movements ──
        $movementModel->insert([
            'catalog_product_id'   => $productId,
            'type'                 => $type,
            'reference_id'         => null,
            'org_branch_id'        => $branchId,
            'target_org_branch_id' => ($type === 'transfer' && $targetBranchId > 0) ? $targetBranchId : null,
            'quantity'             => $quantity,
            'notes'                => $notes,
        ]);

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            log_message('error', 'Error en transacción registerMovement: ' . json_encode($this->db->error()));
            return 'Error interno al registrar el movimiento.';
        }

        $this->db->transCommit();

        // ── 4. Bitácora de auditoría (fuera de la transacción para no bloquearla) ──
        $typeLabels = ['entry' => 'Entrada', 'exit' => 'Salida', 'transfer' => 'Transferencia'];
        (new UserActivityLogsModel())->logActivity(
            'inventory_movement',
            sprintf(
                '%s de %.2f uds. | Producto ID: %d | Sucursal ID: %d%s | Notas: %s',
                $typeLabels[$type],
                $quantity,
                $productId,
                $branchId,
                $targetBranchId > 0 ? ' → Sucursal ID: ' . $targetBranchId : '',
                $notes ?: 'N/A'
            )
        );

        return true;
    }
}
