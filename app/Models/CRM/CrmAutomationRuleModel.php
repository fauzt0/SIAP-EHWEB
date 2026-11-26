<?php

declare(strict_types=1);

namespace App\Models\CRM;

use CodeIgniter\Model;

class CrmAutomationRuleModel extends Model
{
    protected $table            = 'crm_automation_rules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'event', 'action', 'is_active', 'payload_json'];
    protected $useTimestamps    = true;
    protected $deletedField     = 'deleted_at';

    public function activeFor(string $event): array
    {
        return $this->where('event', $event)->where('is_active', 1)->findAll();
    }
}
