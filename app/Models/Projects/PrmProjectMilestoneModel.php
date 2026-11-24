<?php

declare(strict_types=1);

namespace App\Models\Projects;

use CodeIgniter\Model;

class PrmProjectMilestoneModel extends Model
{
    protected $table            = 'prm_project_milestones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['project_id', 'name', 'due_date', 'status', 'sort_order', 'completed_at'];
    protected $useTimestamps    = true;
    protected $deletedField     = 'deleted_at';

    protected $validationRules = [
        'project_id' => 'required|is_natural_no_zero',
        'name'       => 'required|max_length[180]',
    ];
}
