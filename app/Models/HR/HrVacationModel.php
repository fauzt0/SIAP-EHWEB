<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrVacationModel extends Model
{
    protected $table            = 'hr_vacations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'profile_id',
        'start_date',
        'end_date',
        'total_days',
        'status',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Obtener total de días aprobados o gozados por un empleado
    public function getTotalDaysTaken(int $profileId): int
    {
        $result = $this->selectSum('total_days')
                       ->where('profile_id', $profileId)
                       ->whereIn('status', ['aprobado']) // o 'gozado' si existiera
                       ->first();
                       
        return (int) ($result->total_days ?? 0);
    }
}
