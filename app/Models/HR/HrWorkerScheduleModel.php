<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrWorkerScheduleModel extends Model
{
    protected $table            = 'hr_worker_schedules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'profile_id',
        'shift_id',
        'start_date'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
