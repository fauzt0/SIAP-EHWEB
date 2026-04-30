<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrVacationRequestModel extends Model
{
    protected $table            = 'hr_vacation_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'profile_id',
        'start_date',
        'end_date',
        'days_requested',
        'status',
        'approver_id',
        'notes'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
