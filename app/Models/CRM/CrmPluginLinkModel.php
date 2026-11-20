<?php

declare(strict_types=1);

namespace App\Models\CRM;

use CodeIgniter\Model;

class CrmPluginLinkModel extends Model
{
    protected $table            = 'crm_plugin_links';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_id', 'sales_order_id', 'service_id', 'plugin_slug', 'external_ref', 'meta_json',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
