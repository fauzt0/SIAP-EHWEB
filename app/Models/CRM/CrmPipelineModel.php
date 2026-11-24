<?php

declare(strict_types=1);

namespace App\Models\CRM;

use CodeIgniter\Model;

class CrmPipelineModel extends Model
{
    protected $table            = 'crm_pipelines';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['slug', 'name', 'kind', 'is_default', 'sort_order'];
    protected $useTimestamps    = true;
    protected $deletedField     = 'deleted_at';

    public function getActive(): array
    {
        return $this->orderBy('sort_order', 'ASC')->findAll();
    }

    public function defaultId(): ?int
    {
        $row = $this->where('is_default', 1)->first() ?: $this->first();

        return $row ? (int) $row->id : null;
    }
}
