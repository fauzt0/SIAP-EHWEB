<?php

declare(strict_types=1);

namespace App\Models\CRM;

use CodeIgniter\Model;

class CrmFollowupServiceModel extends Model
{
    protected $table            = 'crm_followup_services';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['followup_id', 'product_id', 'plan_id', 'service_id', 'notes'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    public function getByFollowup(int $followupId): array
    {
        return $this->select('crm_followup_services.*, catalog_products.commercial_name AS product_name, catalog_product_plans.plan_name')
            ->join('catalog_products', 'catalog_products.id = crm_followup_services.product_id', 'left')
            ->join('catalog_product_plans', 'catalog_product_plans.id = crm_followup_services.plan_id', 'left')
            ->where('crm_followup_services.followup_id', $followupId)
            ->findAll();
    }
}
