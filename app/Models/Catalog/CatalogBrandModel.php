<?php

namespace App\Models\Catalog;

use CodeIgniter\Model;

class CatalogBrandModel extends Model
{
    protected $table            = 'catalog_brands';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'logo_path', 'active'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Obtiene solo las marcas activas ordenadas por nombre.
     */
    public function getActive(): array
    {
        return $this->where('active', 1)->orderBy('name', 'ASC')->findAll();
    }
}
