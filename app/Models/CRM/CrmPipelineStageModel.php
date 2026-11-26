<?php

declare(strict_types=1);

namespace App\Models\CRM;

use CodeIgniter\Model;

class CrmPipelineStageModel extends Model
{
    protected $table            = 'crm_pipeline_stages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['pipeline_id', 'slug', 'name', 'win_probability', 'is_won', 'is_lost', 'sort_order'];
    protected $useTimestamps    = true;
    protected $deletedField     = 'deleted_at';

    public function forPipeline(int $pipelineId): array
    {
        return $this->where('pipeline_id', $pipelineId)->orderBy('sort_order', 'ASC')->findAll();
    }

    public function firstOpenStage(int $pipelineId): ?object
    {
        return $this->where('pipeline_id', $pipelineId)
            ->where('is_won', 0)
            ->where('is_lost', 0)
            ->orderBy('sort_order', 'ASC')
            ->first();
    }
}
