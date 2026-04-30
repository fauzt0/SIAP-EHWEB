<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrIncidenceModel extends Model
{
    protected $table            = 'hr_incidences';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'profile_id',
        'incidence_type',
        'date',
        'justified',
        'status',
        'notes'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Obtiene incidencias pendientes para el badge del dashboard.
     */
    public function getPendingCount(): int
    {
        return $this->where('status', 'pendiente')->countAllResults();
    }
}
