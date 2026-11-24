<?php

declare(strict_types=1);

namespace App\Models\Projects;

use App\Traits\DataTableTrait;
use CodeIgniter\Model;

class PrmProjectModel extends Model
{
    use DataTableTrait;

    public const STATUSES = [
        'quoting'     => 'En cotización',
        'in_progress' => 'En desarrollo',
        'delivery'    => 'Entrega',
        'completed'   => 'Entregado',
        'on_hold'     => 'En pausa',
        'cancelled'   => 'Cancelado',
    ];

    protected $table            = 'prm_projects';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'code', 'name', 'client_id', 'quote_id', 'order_id', 'opportunity_id',
        'manager_id', 'status', 'progress', 'start_date', 'due_date', 'delivered_at',
        'budget', 'notes', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'      => 'required|max_length[180]',
        'client_id' => 'required|is_natural_no_zero',
        'status'    => 'required|in_list[quoting,in_progress,delivery,completed,on_hold,cancelled]',
    ];

    protected $datatableConfig = [
        'column_order'  => [
            'prm_projects.code',
            'prm_projects.name',
            'crm_clients.company_name',
            'prm_projects.status',
            'prm_projects.progress',
            'prm_projects.budget',
            'prm_projects.due_date',
            null,
        ],
        'column_search' => [
            'prm_projects.code',
            'prm_projects.name',
            'crm_clients.company_name',
        ],
        'order' => ['prm_projects.id' => 'desc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select('prm_projects.*, crm_clients.company_name')
            ->join('crm_clients', 'crm_clients.id = prm_projects.client_id', 'left');
        if (! empty($postData['filter_status'])) {
            $this->builder()->where('prm_projects.status', (string) $postData['filter_status']);
        }
        if (! empty($postData['filter_client'])) {
            $this->builder()->where('prm_projects.client_id', (int) $postData['filter_client']);
        }
        $this->_apply_datatables_filters($postData);
    }

    public function nextCode(): string
    {
        $year = date('Y');
        $row  = $this->withDeleted()->like('code', 'PRJ-' . $year . '-', 'after')->orderBy('id', 'DESC')->first();
        $seq  = 1;
        if ($row && preg_match('/(\d+)$/', (string) $row->code, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('PRJ-%s-%04d', $year, $seq);
    }
}
