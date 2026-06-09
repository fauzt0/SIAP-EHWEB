<?php

namespace App\Models\Financial;

use CodeIgniter\Model;

/**
 * FinSupplierContactModel
 * 
 * Modelo para la gestión de contactos adicionales de proveedores (fin_supplier_contacts).
 * Permite registrar múltiples personas de contacto por cada proveedor.
 */
class FinSupplierContactModel extends Model
{
    protected $table            = 'fin_supplier_contacts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fin_supplier_id',
        'contact_name',
        'job_title',
        'email',
        'phone',
        'is_primary',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'fin_supplier_id' => [
            'label' => 'Proveedor',
            'rules' => 'required|is_natural_no_zero|is_not_unique[fin_suppliers.id]',
        ],
        'contact_name' => [
            'label' => 'Nombre del Contacto',
            'rules' => 'required|max_length[255]',
        ],
        'job_title' => [
            'label' => 'Puesto/Cargo',
            'rules' => 'permit_empty|max_length[150]',
        ],
        'email' => [
            'label' => 'Correo Electrónico',
            'rules' => 'permit_empty|valid_email|max_length[255]',
        ],
        'phone' => [
            'label' => 'Teléfono',
            'rules' => 'permit_empty|max_length[50]',
        ],
        'is_primary' => [
            'label' => 'Contacto Principal',
            'rules' => 'permit_empty|in_list[0,1]',
        ],
    ];

    protected $validationMessages = [
        'fin_supplier_id' => [
            'required'        => 'El proveedor es obligatorio.',
            'is_natural_no_zero' => 'El proveedor no es válido.',
            'is_not_unique'   => 'El proveedor especificado no existe.',
        ],
        'contact_name' => [
            'required'   => 'El nombre del contacto es obligatorio.',
            'max_length' => 'El nombre del contacto no puede exceder los 255 caracteres.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * getContactsBySupplier
     * 
     * Obtiene todos los contactos activos de un proveedor específico.
     *
     * @param int $supplierId ID del proveedor
     * @return array
     */
    public function getContactsBySupplier(int $supplierId): array
    {
        return $this->where('fin_supplier_id', $supplierId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('contact_name', 'ASC')
            ->findAll();
    }

    /**
     * getPrimaryContact
     *
     * Obtiene el contacto principal de un proveedor.
     *
     * @param int $supplierId ID del proveedor
     * @return object|null
     */
    public function getPrimaryContact(int $supplierId): ?object
    {
        return $this->where('fin_supplier_id', $supplierId)
            ->where('is_primary', 1)
            ->first();
    }

    /**
     * demotePrimary
     *
     * Sets is_primary = 0 for all contacts of a supplier, optionally excluding
     * one specific contact ID (useful when promoting a new primary).
     *
     * @param int      $supplierId   Supplier whose contacts will be demoted
     * @param int|null $excludeId    Contact ID to exclude from the update
     * @return void
     */
    public function demotePrimary(int $supplierId, ?int $excludeId = null): void
    {
        $builder = $this->builder()
            ->where('fin_supplier_id', $supplierId)
            ->where('deleted_at IS NULL');

        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        $builder->update(['is_primary' => 0]);
    }

    /**
     * setPrimary
     *
     * Atomically promotes one contact as primary and demotes all others
     * for the same supplier. Encapsulates the two-step logic (§9).
     *
     * @param int $contactId  Contact to promote
     * @param int $supplierId Supplier to scope the demotion
     * @return bool
     */
    public function setPrimary(int $contactId, int $supplierId): bool
    {
        $this->db->transStart();

        // Demote all others for this supplier
        $this->demotePrimary($supplierId, $contactId);

        // Promote the target
        $this->update($contactId, ['is_primary' => 1]);

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * createContact
     *
     * Inserta un contacto y gestiona la promoción de contacto principal en transacción.
     *
     * @param array $data Datos del contacto
     * @return int|false ID del contacto creado o false si falla
     */
    public function createContact(array $data): int|false
    {
        $this->db->transStart();

        if (!empty($data['is_primary'])) {
            $this->demotePrimary((int) $data['fin_supplier_id']);
        }

        $newId = $this->insert($data);

        $this->db->transComplete();

        return $this->db->transStatus() ? ($newId ?: false) : false;
    }

    /**
     * updateContact
     *
     * Actualiza un contacto y gestiona la promoción de contacto principal en transacción.
     *
     * @param int   $contactId  ID del contacto
     * @param int   $supplierId ID del proveedor (ownership guard)
     * @param array $data       Datos actualizados
     * @return bool
     */
    public function updateContact(int $contactId, int $supplierId, array $data): bool
    {
        $this->db->transStart();

        if (!empty($data['is_primary'])) {
            $this->demotePrimary($supplierId, $contactId);
        }

        $ok = $this->update($contactId, $data);

        $this->db->transComplete();

        return $this->db->transStatus() && $ok;
    }

    /**
     * softDelete
     *
     * Elimina (soft) un contacto de proveedor.
     *
     * @param int $contactId ID del contacto
     * @return bool
     */
    public function softDelete(int $contactId): bool
    {
        if (!$this->find($contactId)) {
            return false;
        }

        return (bool) $this->delete($contactId);
    }
}
