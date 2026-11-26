<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Traits\DataTableTrait;
use CodeIgniter\Model;

class CrmOpportunityModel extends Model
{
    use DataTableTrait;

    protected $table            = 'crm_opportunities';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'folio', 'title', 'client_id', 'pipeline_id', 'stage_id', 'assigned_user_id',
        'quote_id', 'order_id', 'project_id', 'expected_value', 'probability',
        'lost_reason_id', 'lost_notes', 'close_date', 'next_followup_at', 'notes', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'title'          => 'required|max_length[180]',
        'client_id'      => 'required|is_natural_no_zero',
        'pipeline_id'    => 'required|is_natural_no_zero',
        'stage_id'       => 'required|is_natural_no_zero',
        'expected_value' => 'permit_empty|decimal',
    ];

    protected $datatableConfig = [
        'column_order'  => [
            'crm_opportunities.folio',
            'crm_opportunities.title',
            'crm_clients.company_name',
            'crm_pipelines.name',
            'crm_pipeline_stages.name',
            'crm_opportunities.expected_value',
            'crm_opportunities.probability',
            'crm_opportunities.close_date',
            null,
        ],
        'column_search' => [
            'crm_opportunities.folio',
            'crm_opportunities.title',
            'crm_clients.company_name',
        ],
        'order' => ['crm_opportunities.id' => 'desc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select('crm_opportunities.*, crm_clients.company_name, crm_pipelines.name AS pipeline_name, crm_pipeline_stages.name AS stage_name, crm_pipeline_stages.is_won, crm_pipeline_stages.is_lost')
            ->join('crm_clients', 'crm_clients.id = crm_opportunities.client_id', 'left')
            ->join('crm_pipelines', 'crm_pipelines.id = crm_opportunities.pipeline_id', 'left')
            ->join('crm_pipeline_stages', 'crm_pipeline_stages.id = crm_opportunities.stage_id', 'left');

        if (! empty($postData['filter_pipeline'])) {
            $this->builder()->where('crm_opportunities.pipeline_id', (int) $postData['filter_pipeline']);
        }
        if (! empty($postData['filter_assigned'])) {
            $this->builder()->where('crm_opportunities.assigned_user_id', (int) $postData['filter_assigned']);
        }

        $this->_apply_datatables_filters($postData);
    }

    public function nextFolio(): string
    {
        $year = date('Y');
        $row  = $this->withDeleted()->like('folio', 'OPP-' . $year . '-', 'after')->orderBy('id', 'DESC')->first();
        $seq  = 1;
        if ($row && preg_match('/(\d+)$/', (string) $row->folio, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('OPP-%s-%04d', $year, $seq);
    }

    /**
     * @return array<int, list<object>>
     */
    public function board(int $pipelineId, ?int $onlyUserId = null): array
    {
        $builder = $this->select('crm_opportunities.*, crm_clients.company_name')
            ->join('crm_clients', 'crm_clients.id = crm_opportunities.client_id', 'left')
            ->where('crm_opportunities.pipeline_id', $pipelineId);
        if ($onlyUserId) {
            $builder->where('crm_opportunities.assigned_user_id', $onlyUserId);
        }
        $rows = $builder->orderBy('crm_opportunities.id', 'DESC')->findAll(400);
        $out  = [];
        foreach ($rows as $row) {
            $sid = (int) $row->stage_id;
            $out[$sid]   = $out[$sid] ?? [];
            $out[$sid][] = $row;
        }

        return $out;
    }

    public function forecast(?int $onlyUserId = null): array
    {
        $builder = $this->builder()
            ->select('SUM(expected_value) AS raw_value', false)
            ->select('SUM(expected_value * probability / 100) AS weighted', false)
            ->join('crm_pipeline_stages', 'crm_pipeline_stages.id = crm_opportunities.stage_id', 'left')
            ->where('crm_opportunities.deleted_at', null)
            ->where('crm_pipeline_stages.is_lost', 0)
            ->where('crm_pipeline_stages.is_won', 0);
        if ($onlyUserId) {
            $builder->where('crm_opportunities.assigned_user_id', $onlyUserId);
        }
        $row = $builder->get()->getRowArray();

        return [
            'pipeline_value' => (float) ($row['raw_value'] ?? 0),
            'weighted'       => (float) ($row['weighted'] ?? 0),
        ];
    }
}
