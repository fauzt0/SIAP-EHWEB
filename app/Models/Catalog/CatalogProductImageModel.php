<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;

class CatalogProductImageModel extends Model
{
    protected $table            = 'catalog_product_images';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false; // No incluí deleted_at en mi migración manual por error, pero la tabla tiene timestamps
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id', 'path', 'is_main'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByProduct(int $productId)
    {
        return $this->where('product_id', $productId)->findAll();
    }

    public function getMainImage(int $productId)
    {
        return $this->where('product_id', $productId)
                    ->where('is_main', 1)
                    ->first();
    }

    /**
     * Guarda una imagen subida asociada a un producto.
     * La primera imagen de un producto sin portada se convierte automáticamente en principal.
     */
    public function saveProductImage(int $productId, string $newName): bool
    {
        $hasMain = $this->where('product_id', $productId)->where('is_main', 1)->countAllResults() > 0;

        return (bool) $this->insert([
            'product_id' => $productId,
            'path'       => $newName,
            'is_main'    => $hasMain ? 0 : 1,
        ]);
    }

    /**
     * Marca una imagen como principal y quita el flag de las demás del mismo producto.
     * Usa transacción para garantizar consistencia.
     */
    public function setMainImage(int $imageId): bool
    {
        $image = $this->find($imageId);
        if (!$image) return false;

        $this->db->transStart();
        $this->db->table($this->table)->where('product_id', $image->product_id)->update(['is_main' => 0]);
        $this->db->table($this->table)->where('id', $imageId)->update(['is_main' => 1]);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Elimina el registro de BD y el archivo físico de disco.
     */
    public function deleteImage(int $imageId): bool
    {
        $image = $this->find($imageId);
        if (!$image) return false;

        $fullPath = FCPATH . 'uploads/catalog/' . $image->path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        return (bool) $this->delete($imageId);
    }
}
