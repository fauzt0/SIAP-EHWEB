<?php

declare(strict_types=1);

namespace App\Models\Sales;

use App\Traits\DataTableTrait;
use CodeIgniter\Model;

class CustomerServiceModel extends Model
{
    use DataTableTrait;

    protected $table            = 'customer_services';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'client_id', 'order_id', 'product_id', 'plan_id', 'reference_name',
        'status', 'billing_cycle', 'auto_renew', 'amount', 'next_due_date',
        'last_renewal_at', 'plugin_ref', 'activation_status',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $datatableConfig = [
        'column_order'  => [
            'customer_services.reference_name',
            'crm_clients.company_name',
            'customer_services.billing_cycle',
            'customer_services.status',
            'customer_services.next_due_date',
            null,
        ],
        'column_search' => [
            'customer_services.reference_name',
            'crm_clients.company_name',
        ],
        'order' => ['customer_services.next_due_date' => 'asc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select('customer_services.*, crm_clients.company_name, catalog_products.commercial_name AS product_name')
            ->join('crm_clients', 'crm_clients.id = customer_services.client_id', 'left')
            ->join('catalog_products', 'catalog_products.id = customer_services.product_id', 'left');

        if (! empty($postData['filter_status'])) {
            $this->builder()->where('customer_services.status', $postData['filter_status']);
        }

        $this->_apply_datatables_filters($postData);
    }
}
