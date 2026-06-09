<?php

namespace App\Models\Financial;

use CodeIgniter\Model;

/**
 * FinPurchaseOrderItemModel
 * 
 * Modelo para la gestión de partidas de órdenes de compra (fin_purchase_order_items).
 * Cada partida pertenece a una orden de compra y puede opcionalmente
 * estar asociada a un producto del catálogo.
 * Nota: Esta tabla NO utiliza soft deletes ya que las partidas se gestionan
 * por reemplazo completo dentro de la orden de compra.
 */
class FinPurchaseOrderItemModel extends Model
{
    protected $table            = 'fin_purchase_order_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fin_purchase_order_id',
        'catalog_product_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'fin_purchase_order_id' => [
            'label' => 'Orden de Compra',
            'rules' => 'required|is_natural_no_zero|is_not_unique[fin_purchase_orders.id]',
        ],
        'catalog_product_id' => [
            'label' => 'Producto del Catálogo',
            'rules' => 'permit_empty|is_natural_no_zero|is_not_unique[catalog_products.id]',
        ],
        'description' => [
            'label' => 'Descripción',
            'rules' => 'required|max_length[255]',
        ],
        'quantity' => [
            'label' => 'Cantidad',
            'rules' => 'required|is_natural_no_zero',
        ],
        'unit_price' => [
            'label' => 'Precio Unitario',
            'rules' => 'required|decimal|greater_than_equal_to[0]',
        ],
        'total_price' => [
            'label' => 'Precio Total',
            'rules' => 'required|decimal|greater_than_equal_to[0]',
        ],
    ];

    protected $validationMessages = [
        'fin_purchase_order_id' => [
            'required'      => 'La orden de compra es obligatoria.',
            'is_natural_no_zero' => 'La orden de compra no es válida.',
            'is_not_unique' => 'La orden de compra especificada no existe.',
        ],
        'description' => [
            'required'   => 'La descripción de la partida es obligatoria.',
            'max_length' => 'La descripción no puede exceder los 255 caracteres.',
        ],
        'quantity' => [
            'required' => 'La cantidad es obligatoria.',
            'is_natural_no_zero' => 'La cantidad debe ser un número entero positivo.',
        ],
        'unit_price' => [
            'required' => 'El precio unitario es obligatorio.',
        ],
        'total_price' => [
            'required' => 'El precio total es obligatorio.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * getItemsByOrder
     * 
     * Obtiene todas las partidas de una orden de compra específica,
     * incluyendo información del producto del catálogo si existe.
     *
     * @param int $orderId ID de la orden de compra
     * @return array
     */
    public function getItemsByOrder(int $orderId): array
    {
        return $this->select(
            'fin_purchase_order_items.*, catalog_products.sku AS product_sku, catalog_products.commercial_name AS product_name'
        )
            ->join('catalog_products', 'catalog_products.id = fin_purchase_order_items.catalog_product_id', 'left')
            ->where('fin_purchase_order_items.fin_purchase_order_id', $orderId)
            ->orderBy('fin_purchase_order_items.id', 'ASC')
            ->findAll();
    }

    /**
     * getItemsByProduct
     * 
     * Obtiene todas las partidas asociadas a un producto específico.
     *
     * @param int $productId ID del producto del catálogo
     * @return array
     */
    public function getItemsByProduct(int $productId): array
    {
        return $this->where('catalog_product_id', $productId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * calculateItemTotal
     * 
     * Calcula el precio total de una partida basado en cantidad y precio unitario.
     *
     * @param int   $quantity
     * @param float $unitPrice
     * @return float
     */
    public function calculateItemTotal(int $quantity, float $unitPrice): float
    {
        return round($quantity * $unitPrice, 2);
    }

    /**
     * batchInsertItems
     * 
     * Inserta múltiples partidas para una orden en una sola transacción.
     * Este método es llamado desde FinPurchaseOrderModel.
     *
     * @param int   $orderId ID de la orden de compra
     * @param array $items   Array de datos de partidas
     * @return bool
     */
    public function batchInsertItems(int $orderId, array $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'fin_purchase_order_id' => $orderId,
                'catalog_product_id'    => $item['catalog_product_id'] ?? null,
                'description'           => $item['description'] ?? '',
                'quantity'              => $item['quantity'] ?? 1,
                'unit_price'            => $item['unit_price'] ?? 0,
                'total_price'           => $this->calculateItemTotal(
                    $item['quantity'] ?? 1,
                    $item['unit_price'] ?? 0
                ),
            ];
        }

        return $this->insertBatch($data);
    }
}
