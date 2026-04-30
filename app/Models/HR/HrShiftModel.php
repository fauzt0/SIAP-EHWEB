<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrShiftModel extends Model
{
    protected $table            = 'hr_cat_shifts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'name',
        'start_time',
        'end_time',
        'grace_period',
        'work_days'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
