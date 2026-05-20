<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

class CatalogProductModel extends Model
{
    use DataTableTrait;

    protected $table            = 'catalog_products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'category_id', 'brand_id', 'business_unit_id', 'sku', 'barcode', 'internal_name', 'commercial_name',
        'description_short', 'description_long', 'product_type', 'active',
    ];

    protected $validationRules = [
        'business_unit_id' => 'required|is_natural_no_zero|is_not_unique[org_branches.id]',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $datatableConfig = [
        'column_order'  => [null, 'catalog_products.sku', 'catalog_products.commercial_name', 'catalog_categories.name', 'catalog_brands.name', 'catalog_products.product_type', 'catalog_products.active', null],
        'column_search' => ['catalog_products.sku', 'catalog_products.commercial_name', 'catalog_products.internal_name', 'catalog_products.description_short', 'catalog_brands.name'],
        'order'         => ['catalog_products.id' => 'desc']
    ];

    /**
     * Query base para DataTables con joins y filtros adicionales
     */
    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select('catalog_products.*, catalog_categories.name as category_name, org_branches.name as branch_name, org_branches.branch_code as branch_code, catalog_brands.name as brand_name, (SELECT COUNT(*) FROM catalog_product_plans WHERE catalog_product_plans.product_id = catalog_products.id AND catalog_product_plans.deleted_at IS NULL) as plans_count, (SELECT path FROM catalog_product_images WHERE catalog_product_images.product_id = catalog_products.id AND catalog_product_images.is_main = 1 LIMIT 1) as main_image')
            ->join('catalog_categories', 'catalog_categories.id = catalog_products.category_id', 'left')
            ->join('org_branches', 'org_branches.id = catalog_products.business_unit_id', 'left')
            ->join('catalog_brands', 'catalog_brands.id = catalog_products.brand_id', 'left');

        // Filtros personalizados desde el front
        if (!empty($postData['filter_branch'])) {
            $this->builder()->where('catalog_products.business_unit_id', (int) $postData['filter_branch']);
        }
        if (!empty($postData['filter_type'])) {
            $this->builder()->where('catalog_products.product_type', $postData['filter_type']);
        }
        if (!empty($postData['filter_category'])) {
            $catId = (int)$postData['filter_category'];
            
            // Buscar subcategorías para incluirlas en el filtro (1 nivel de profundidad)
            $catModel = new \App\Models\Catalog\CatalogCategoryModel();
            $children = $catModel->withDeleted()->where('parent_id', $catId)->findAll();
            $catIds = [$catId];
            foreach ($children as $child) {
                $catIds[] = $child->id;
            }
            
            $this->builder()->whereIn('catalog_products.category_id', $catIds);
        }
        if (isset($postData['filter_active']) && $postData['filter_active'] !== '') {
            if ($postData['filter_active'] === 'deleted') {
                $this->builder()->where('catalog_products.deleted_at IS NOT NULL');
            } else {
                $this->builder()->where('catalog_products.active', (int)$postData['filter_active']);
                $this->builder()->where('catalog_products.deleted_at IS NULL');
            }
        } else {
            $this->builder()->where('catalog_products.deleted_at IS NULL');
        }

        $this->_apply_datatables_filters($postData);
    }

    /**
     * Estadísticas para el dashboard del módulo
     */
    public function getStats(): array
    {
        return [
            'total'    => $this->countAll(),
            'services' => $this->where('product_type', 'service')->countAllResults(),
            'physical' => $this->where('product_type', 'physical')->countAllResults(),
            'digital'  => $this->where('product_type', 'digital')->countAllResults(),
            'inactive' => $this->db->table('catalog_products')->where('active', 0)->where('deleted_at IS NULL', null, false)->countAllResults(),
        ];
    }

    /**
     * Crea un producto junto con sus atributos dinámicos en una sola transacción.
     * Cumple estándar MVC: el Controlador solo llama este método.
     *
     * @param array $productData  Datos de catalog_products
     * @param array $attributes   Array de ['attr_key' => ..., 'attr_value' => ..., 'is_highlight' => ...]
     * @return int|false  ID del nuevo producto, o false si falla
     */
    public function createProductWithAttributes(array $productData, array $attributes = []): int|false
    {
        $this->db->transStart();

        $newId = $this->insert($productData);

        if ($newId && !empty($attributes)) {
            $attrModel = new \App\Models\Catalog\CatalogAttributeModel();
            foreach ($attributes as $attr) {
                if (empty($attr['attr_key'])) continue;
                $attrModel->insert([
                    'product_id'   => $newId,
                    'attr_key'     => trim($attr['attr_key']),
                    'attr_value'   => trim($attr['attr_value'] ?? ''),
                    'is_highlight' => isset($attr['is_highlight']) ? 1 : 0,
                ]);
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? $newId : false;
    }

    /**
     * Actualiza un producto y reemplaza sus atributos en una sola transacción.
     *
     * @param int   $id          ID del producto a actualizar
     * @param array $productData Datos nuevos de catalog_products
     * @param array $attributes  Atributos nuevos (reemplaza todos los existentes)
     * @return bool
     */
    public function updateProductWithAttributes(int $id, array $productData, array $attributes = []): bool
    {
        $this->db->transStart();

        $this->update($id, $productData);

        // Reemplazar atributos: soft-delete los viejos, insertar los nuevos
        $attrModel = new \App\Models\Catalog\CatalogAttributeModel();
        $attrModel->where('product_id', $id)->delete();

        if (!empty($attributes)) {
            foreach ($attributes as $attr) {
                if (empty($attr['attr_key'])) continue;
                $attrModel->insert([
                    'product_id'   => $id,
                    'attr_key'     => trim($attr['attr_key']),
                    'attr_value'   => trim($attr['attr_value'] ?? ''),
                    'is_highlight' => isset($attr['is_highlight']) ? 1 : 0,
                ]);
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }
}
