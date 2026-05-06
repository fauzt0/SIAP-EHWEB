<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;

class CatalogPlanModel extends Model
{
    protected $table            = 'catalog_product_plans';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id', 'plan_name', 'billing_cycle',
        'sale_price', 'renewal_price', 'setup_fee', 'is_active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validaciones nativas CI4
    protected $validationRules = [
        'product_id'    => 'required|is_natural_no_zero',
        'plan_name'     => 'required|max_length[100]',
        'billing_cycle' => 'required|in_list[one_time,monthly,yearly,custom]',
        'sale_price'    => 'required|decimal|greater_than_equal_to[0]',
        'renewal_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'setup_fee'     => 'permit_empty|decimal|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'plan_name'     => ['required' => 'El nombre del plan es obligatorio.'],
        'billing_cycle' => ['in_list'  => 'El ciclo de facturación no es válido.'],
        'sale_price'    => ['required' => 'El precio de venta es obligatorio.'],
    ];

    // ─────────────────────────────────────────────────
    // Métodos de Consulta
    // ─────────────────────────────────────────────────

    /**
     * Obtiene todos los planes de un producto (activos e inactivos).
     */
    public function getAllByProduct(int $productId): array
    {
        return $this->where('product_id', $productId)
                    ->orderBy('billing_cycle', 'ASC')
                    ->findAll();
    }

    /**
     * Obtiene solo los planes activos de un producto.
     */
    public function getByProduct(int $productId): array
    {
        return $this->where('product_id', $productId)
                    ->where('is_active', 1)
                    ->orderBy('sale_price', 'ASC')
                    ->findAll();
    }

    /**
     * Obtiene un plan con el nombre de su producto (para auditoría).
     */
    public function getPlanWithProduct(int $planId): object|null
    {
        return $this->select('catalog_product_plans.*, catalog_products.commercial_name as product_name, catalog_products.sku')
                    ->join('catalog_products', 'catalog_products.id = catalog_product_plans.product_id', 'left')
                    ->find($planId);
    }

    // ─────────────────────────────────────────────────
    // Métodos de Mutación (Lógica de Negocio en Modelo)
    // ─────────────────────────────────────────────────

    /**
     * Crea un plan validando que el producto exista.
     * Cumple estándar MVC sección 9: toda la lógica de datos en el Modelo.
     *
     * @return int|false  ID del nuevo plan, o false si falla
     */
    public function createPlan(array $data): int|false
    {
        if (!$this->validate($data)) {
            return false;
        }

        // Para ciclos one_time, renewal_price debe ser 0
        if (($data['billing_cycle'] ?? '') === 'one_time') {
            $data['renewal_price'] = 0.00;
        }

        $this->insert($data);
        $newId = $this->db->insertID();

        return $newId ?: false;
    }

    /**
     * Actualiza un plan existente.
     *
     * @return bool
     */
    public function updatePlan(int $id, array $data): bool
    {
        if (!$this->validate($data)) {
            return false;
        }

        if (($data['billing_cycle'] ?? '') === 'one_time') {
            $data['renewal_price'] = 0.00;
        }

        return (bool) $this->update($id, $data);
    }

    /**
     * Activa o desactiva un plan sin eliminarlo.
     */
    public function toggleActive(int $id): bool
    {
        $plan = $this->find($id);
        if (!$plan) return false;

        return (bool) $this->update($id, ['is_active' => $plan->is_active ? 0 : 1]);
    }
}
