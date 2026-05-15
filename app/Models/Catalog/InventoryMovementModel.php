<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;

/**
 * InventoryMovementModel
 *
 * Registra el histórico de cada movimiento de inventario.
 * Es una tabla de tipo Kardex: nunca se modifica, solo se inserta.
 */
class InventoryMovementModel extends Model
{
    protected $table            = 'inventory_movements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'catalog_product_id', 'type', 'reference_id', 'org_branch_id',
        'target_org_branch_id', 'quantity', 'notes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Historial simplificado del producto (solo los 200 más recientes).
     * Compat con el método antiguo para no romper código existente.
     */
    public function getHistory(int $productId): array
    {
        return $this->where('catalog_product_id', $productId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll(200);
    }

    /**
     * Kardex completo con nombres de sucursal legibles.
     * Usado por InventoryController::getKardex().
     */
    public function getKardexForProduct(int $productId): array
    {
        return $this->db->table($this->table . ' m')
            ->select([
                'm.id', 'm.type', 'm.quantity', 'm.notes',
                'm.created_at',
                'b.name  AS branch_name',
                'tb.name AS target_branch_name',
            ])
            ->join('org_branches b',  'b.id = m.org_branch_id',         'left')
            ->join('org_branches tb', 'tb.id = m.target_org_branch_id',  'left')
            ->where('m.catalog_product_id', $productId)
            ->where('m.deleted_at IS NULL')
            ->orderBy('m.created_at', 'DESC')
            ->limit(200)
            ->get()->getResultArray();
    }
}
