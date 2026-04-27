<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class ContractTemplateModel extends Model
{
    use \App\Traits\DataTableTrait;

    protected $table            = 'hr_cat_contract_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'description',
        'content',
        'base_model',
        'is_default',
        'header_logo',
        'footer_text'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // DataTable Configuration
    protected $datatableConfig = [
        'searchable' => ['name', 'description'],
        'orderable'  => ['id', 'name', 'updated_at'],
        'defaultOrder' => ['updated_at' => 'desc']
    ];

    // Validation
    protected $validationRules      = [
        'name'       => 'required|max_length[100]',
        'content'    => 'required',
        'base_model' => 'required|in_list[lft,modern,classic,corporate]',
    ];
    protected $validationMessages   = [
        'name' => [
            'required' => 'El nombre de la plantilla es obligatorio.',
            'max_length' => 'El nombre no puede exceder los 100 caracteres.',
        ],
        'content' => [
            'required' => 'El contenido de la plantilla es obligatorio.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['checkDefault'];
    protected $beforeUpdate   = ['checkDefault'];

    /**
     * Si se marca como default, desmarcar las demás
     */
    protected function checkDefault(array $data)
    {
        if (isset($data['data']['is_default']) && $data['data']['is_default'] == 1) {
            $this->builder()->update(['is_default' => 0]);
        }
        return $data;
    }

    /**
     * Obtiene la plantilla por defecto
     */
    public function getDefaultTemplate()
    {
        return $this->where('is_default', 1)->first() ?? $this->first();
    }
}
