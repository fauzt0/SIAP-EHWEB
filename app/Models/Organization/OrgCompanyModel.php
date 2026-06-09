<?php

namespace App\Models\Organization;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Model;

class OrgCompanyModel extends Model
{
    protected $table            = 'org_company_profile';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'commercial_name', 'legal_name', 'tax_id', 'logo_path', 'favicon_path',
        'primary_email', 'primary_phone', 'website_url',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'commercial_name' => [
            'label' => 'Nombre Comercial',
            'rules' => 'required|max_length[255]',
        ],
        'legal_name' => [
            'label' => 'Razón Social',
            'rules' => 'permit_empty|max_length[255]',
        ],
        'tax_id' => [
            'label' => 'RFC / Tax ID',
            'rules' => 'permit_empty|max_length[20]',
        ],
        'primary_email' => [
            'label' => 'Correo Principal',
            'rules' => 'required|valid_email|max_length[100]',
        ],
        'primary_phone' => [
            'label' => 'Teléfono Principal',
            'rules' => 'required|max_length[20]',
        ],
        'website_url' => [
            'label' => 'Sitio Web',
            'rules' => 'permit_empty|valid_url_strict|max_length[255]',
        ],
    ];

    protected $validationMessages = [
        'commercial_name' => [
            'required' => 'El nombre comercial es obligatorio.',
        ],
        'primary_email' => [
            'required'    => 'El correo principal es obligatorio.',
            'valid_email' => 'El correo principal no es válido.',
        ],
        'primary_phone' => [
            'required' => 'El teléfono principal es obligatorio.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Perfil único de la matriz (primer registro no eliminado).
     */
    public function getProfile(): ?object
    {
        return $this->first();
    }

    /**
     * Crea o actualiza el perfil corporativo, incluyendo logo opcional.
     *
     * @return int|false ID guardado o false si falla validación/persistencia
     */
    public function saveProfile(array $data, ?UploadedFile $logoFile = null): int|false
    {
        $id      = ! empty($data['id']) ? (int) $data['id'] : null;
        $existing = $id ? $this->find($id) : $this->getProfile();

        if ($id && ! $existing) {
            return false;
        }

        if (! $id && $existing) {
            $id = (int) $existing->id;
        }

        unset($data['id']);

        if ($logoFile !== null) {
            helper('upload');
            $stored = store_public_upload($logoFile, 'organization', $existing->logo_path ?? null);
            if ($stored !== null) {
                $data['logo_path'] = $stored;
            }
        }

        if ($id) {
            if (! $this->update($id, $data)) {
                return false;
            }

            return $id;
        }

        return $this->insert($data) ?: false;
    }
}
