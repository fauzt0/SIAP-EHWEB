<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrProfileModel extends Model
{
    use \App\Traits\DataTableTrait;

    protected $table            = 'hr_profiles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'first_name',
        'last_name',
        'nationality',
        'curp',
        'rfc',
        'tax_regime',
        'nss',
        'birth_date',
        'gender',
        'marital_status',
        'phone_personal',
        'personal_email',
        'address_full',
        'emergency_contact_name',
        'emergency_contact_phone',
        'beneficiaries',
        'bank_name',
        'bank_account',
        'bank_clabe'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // DataTable configuration
    protected $datatableConfig = [
        'table'        => 'hr_profiles',
        'column_order' => [
            'users.first_name',
            'hr_employment_data.employee_number',
            'j.name',
            'd.name',
            'ob.name',
            'hr_employment_data.hiring_date',
            'hr_employment_data.status',
        ],
        'column_search' => [
            'users.first_name',
            'users.last_name',
            'hr_profiles.first_name',
            'hr_profiles.last_name',
            'hr_employment_data.employee_number',
            'hr_profiles.curp',
            'hr_profiles.rfc',
        ],
        'order' => ['hr_profiles.id' => 'ASC'],
    ];

    // Validation
    protected $validationRules      = [
        'user_id'        => 'permit_empty|is_natural_no_zero|is_unique[hr_profiles.user_id,id,{id}]',
        'first_name'     => 'required_without[user_id]|max_length[100]',
        'last_name'      => 'required_without[user_id]|max_length[100]',
        'curp'           => 'required|exact_length[18]|alpha_numeric|is_unique[hr_profiles.curp,id,{id}]',
        'rfc'            => 'required|min_length[12]|max_length[13]|alpha_numeric|is_unique[hr_profiles.rfc,id,{id}]',
        'nss'            => 'permit_empty|exact_length[11]|numeric|is_unique[hr_profiles.nss,id,{id}]',
        'birth_date'     => 'required|valid_date',
        'gender'         => 'required|in_list[M,F,O]',
        'marital_status' => 'required|in_list[soltero,casado,divorciado,viudo,union_libre]',
        'bank_clabe'     => 'permit_empty|exact_length[18]|numeric'
    ];

    protected $validationMessages   = [
        'curp' => [
            'is_unique' => 'Esta CURP ya se encuentra registrada en otro perfil.'
        ],
        'rfc' => [
            'is_unique' => 'Este RFC ya se encuentra registrado en otro perfil.'
        ],
        'nss' => [
            'is_unique' => 'Este Número de Seguridad Social ya se encuentra registrado.'
        ]
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;


    /**
     * _get_datatables_query()
     * -------------------------------------------------------------------
     * Construye la consulta JOIN base para el listado de trabajadores.
     * Une hr_profiles con hr_employment_data, users y los catálogos
     * para obtener todos los datos necesarios en una sola query.
     */
    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select('
                hr_profiles.id,
                hr_profiles.user_id,
                hr_profiles.first_name AS manual_first_name,
                hr_profiles.last_name AS manual_last_name,
                hr_profiles.curp,
                hr_profiles.rfc,
                hr_employment_data.employee_number,
                hr_employment_data.hiring_date,
                hr_employment_data.status,
                users.first_name AS user_first_name,
                users.last_name AS user_last_name,
                users.avatar,
                d.name AS department_name,
                j.name AS job_name,
                ob.name AS location_name
            ')
            ->join('users', 'users.id = hr_profiles.user_id', 'left')
            ->join('hr_employment_data', 'hr_employment_data.profile_id = hr_profiles.id', 'left')
            ->join('hr_cat_departments d', 'd.id = hr_employment_data.department_id', 'left')
            ->join('hr_cat_jobs j', 'j.id = hr_employment_data.job_id', 'left')
            ->join('org_branches ob', 'ob.id = hr_employment_data.location_id', 'left')
            ->where('hr_profiles.deleted_at IS NULL');

        // Filtro por estatus laboral
        if (!empty($postData['status'])) {
            $this->builder()->where('hr_employment_data.status', $postData['status']);
        }

        // Filtro por departamento
        if (!empty($postData['department_id'])) {
            $this->builder()->where('hr_employment_data.department_id', (int) $postData['department_id']);
        }

        // Filtro por sucursal/ubicación
        if (!empty($postData['location_id'])) {
            $this->builder()->where('hr_employment_data.location_id', (int) $postData['location_id']);
        }

        $this->_apply_datatables_filters($postData);
    }


    /**
     * countActiveWorkers()
     * Retorna el total de trabajadores activos (sin soft-delete) para DataTables.
     */
    public function countActiveWorkers(): int
    {
        return $this->where('hr_profiles.deleted_at IS NULL')->countAllResults(false);
    }

    /**
     * createWorkerWithEmployment()
     * Ejecuta una transacción atómica para insertar el perfil y los datos laborales.
     */
    public function createWorkerWithEmployment(array $profileData, array $employmentData): bool
    {
        $this->db->transStart();
        
        $this->skipValidation(true)->insert($profileData);
        $profileId = $this->getInsertID();
        
        $employmentData['profile_id'] = $profileId;
        
        $employmentModel = new \App\Models\HR\HrEmploymentModel();
        $employmentModel->skipValidation(true)->insert($employmentData);
        
        $this->db->transComplete();
        
        return $this->db->transStatus();
    }

    /**
     * updateWorkerWithEmployment()
     * Ejecuta una transacción atómica para actualizar el perfil y los datos laborales.
     */
    public function updateWorkerWithEmployment(int $profileId, array $profileData, array $employmentData): bool
    {
        $this->db->transStart();
        
        $this->skipValidation(true)->where('id', $profileId)->set($profileData)->update();
        
        $employmentModel = new \App\Models\HR\HrEmploymentModel();
        $existingEmployment = $employmentModel->withDeleted()->where('profile_id', $profileId)->first();
        
        if ($existingEmployment) {
            $employmentModel->skipValidation(true)->where('profile_id', $profileId)->set($employmentData)->update();
        } else {
            $employmentData['profile_id'] = $profileId;
            $employmentModel->skipValidation(true)->insert($employmentData);
        }
        
        $this->db->transComplete();
        
        return $this->db->transStatus();
    }

    /**
     * getAvailableUsers()
     * Retorna los usuarios del sistema que NO tienen un perfil vinculado.
     */
    public function getAvailableUsers(): array
    {
        return $this->db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.username')
            ->join('hr_profiles hp', 'hp.user_id = u.id', 'left')
            ->where('hp.user_id IS NULL')
            ->where('u.deleted_at IS NULL')
            ->get()
            ->getResultObject();
    }

    /**
     * getAvailableManagers()
     * Retorna la lista de trabajadores activos para ser asignados como jefes directos.
     * Excluye el ID proporcionado para evitar que alguien sea su propio jefe.
     */
    public function getAvailableManagers(?int $excludeProfileId = null): array
    {
        $builder = $this->select('hr_profiles.id, COALESCE(users.first_name, hr_profiles.first_name) as first_name, COALESCE(users.last_name, hr_profiles.last_name) as last_name')
            ->join('users', 'users.id = hr_profiles.user_id', 'left')
            ->where('hr_profiles.deleted_at IS NULL');
            
        if ($excludeProfileId !== null) {
            $builder->where('hr_profiles.id !=', $excludeProfileId);
        }
        
        return $builder->findAll();
    }
    /**
     * getManagerName()
     * Retorna el nombre completo del jefe directo dado su profile_id.
     */
    public function getManagerName(int $managerId): ?string
    {
        $mgr = $this->select('COALESCE(users.first_name, hr_profiles.first_name) as first_name, COALESCE(users.last_name, hr_profiles.last_name) as last_name')
            ->join('users', 'users.id = hr_profiles.user_id', 'left')
            ->find($managerId);
        return $mgr ? trim(($mgr->first_name ?? '') . ' ' . ($mgr->last_name ?? '')) : null;
    }
}

