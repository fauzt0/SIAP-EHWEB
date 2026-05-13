<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;

/**
 * CatalogProductRelationModel
 *
 * Gestiona las relaciones entre productos: Bundles, Gifts y Upsells.
 *
 * Regla de negocio clave (comentada por el usuario):
 * - Un `gift` con `duration_months > 0` es gratuito solo durante ese período.
 *   Después del período, el producto relacionado se cobra como adicional
 *   usando su propio precio de plan o el `override_price` si está definido.
 * - `duration_months = 0` significa que el regalo/bundle es permanente.
 */
class CatalogProductRelationModel extends Model
{
    protected $table            = 'catalog_product_relations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false; // La tabla no tiene deleted_at en la migración original
    protected $protectFields    = true;
    protected $allowedFields    = [
        'main_product_id', 'related_product_id',
        'relation_type', 'override_price', 'duration_months'
    ];

    protected $validationRules = [
        'main_product_id'    => 'required|is_natural_no_zero',
        'related_product_id' => 'required|is_natural_no_zero',
        'relation_type'      => 'required|in_list[bundle,gift,upsell]',
        'override_price'     => 'permit_empty|decimal|greater_than_equal_to[0]',
        'duration_months'    => 'permit_empty|is_natural',
    ];

    // ─────────────────────────────────────────────────
    // Métodos de Consulta
    // ─────────────────────────────────────────────────

    /**
     * Obtiene todas las relaciones de un producto principal,
     * resolviendo el nombre y SKU del producto relacionado.
     */
    public function getRelationsForProduct(int $mainProductId): array
    {
        return $this->select(
                'catalog_product_relations.*,
                 rp.commercial_name as related_name,
                 rp.sku             as related_sku,
                 rp.product_type    as related_type'
            )
            ->join('catalog_products rp', 'rp.id = catalog_product_relations.related_product_id', 'left')
            ->where('main_product_id', $mainProductId)
            ->findAll();
    }

    /**
     * Verifica si una relación entre dos productos ya existe para evitar duplicados.
     */
    public function relationExists(int $mainId, int $relatedId, string $type): bool
    {
        return $this->where('main_product_id', $mainId)
                    ->where('related_product_id', $relatedId)
                    ->where('relation_type', $type)
                    ->countAllResults() > 0;
    }

    // ─────────────────────────────────────────────────
    // Mutación — Lógica de Negocio en Modelo (Sección 9)
    // ─────────────────────────────────────────────────

    /**
     * Crea una relación entre productos con validación de duplicados.
     *
     * Para gifts/bundles con período gratuito:
     *   - duration_months > 0 = gratuito solo ese tiempo
     *   - duration_months = 0  = gratuito/incluido de forma permanente
     *
     * @return int|false  ID de la relación, o false si falla/duplicada
     */
    public function createRelation(array $data): int|false
    {
        if (!$this->validate($data)) {
            return false;
        }

        // Prevenir duplicados
        if ($this->relationExists(
            (int)$data['main_product_id'],
            (int)$data['related_product_id'],
            $data['relation_type']
        )) {
            return false;
        }

        // Un upsell no tiene período gratuito por definición
        if ($data['relation_type'] === 'upsell') {
            $data['duration_months'] = 0;
            $data['override_price']  = 0.00;
        }

        $newId = $this->insert($data);

        if ($newId === false) {
            log_message('error', 'Error insertando relación: ' . json_encode($this->errors()) . ' | DB Error: ' . json_encode($this->db->error()));
            return false;
        }

        return (int)$newId;
    }

    /**
     * Obtiene un resumen legible del período gratuito.
     * Útil para renderizar en vistas.
     */
    public function getFreePeriodLabel(object $relation): string
    {
        if ($relation->relation_type === 'upsell') return 'N/A';
        if ((int)$relation->duration_months === 0)  return 'Permanente';
        return $relation->duration_months . ' mes(es) gratuito(s)';
    }
}
