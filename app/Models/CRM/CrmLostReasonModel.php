<?php

declare(strict_types=1);

namespace App\Models\CRM;

use CodeIgniter\Model;

class CrmLostReasonModel extends Model
{
    protected $table            = 'crm_lost_reasons';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['slug', 'name', 'is_active'];
    protected $useTimestamps    = true;
    protected $deletedField     = 'deleted_at';

    public function getActive(): array
    {
        return $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }
}
