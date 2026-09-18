<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class WorkerContractModel extends Model
{
    protected $table            = 'hr_contracts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'profile_id',
        'parent_contract_id',
        'version',
        'template_id',
        'contract_type_id',
        'content_snapshot',
        'reason',
        'change_source',
        'file_path',
        'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'profile_id'       => 'required|is_natural_no_zero',
        'content_snapshot' => 'required',
        'status'           => 'required|in_list[active,archived,draft]',
        'change_source'    => 'required|in_list[auto,manual,fork]',
    ];

    protected $validationMessages = [
        'profile_id' => [
            'required' => 'El ID del trabajador es obligatorio.',
        ],
        'content_snapshot' => [
            'required' => 'El contenido del contrato es obligatorio.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Contrato activo vigente del trabajador (con lock opcional dentro de transacción).
     */
    public function getActiveContract(int $profileId, bool $forUpdate = false): ?object
    {
        if ($forUpdate) {
            return $this->db->query(
                "SELECT * FROM hr_contracts
                 WHERE profile_id = ? AND status = 'active' AND deleted_at IS NULL
                 ORDER BY version DESC, created_at DESC, id DESC
                 LIMIT 1 FOR UPDATE",
                [$profileId]
            )->getRow();
        }

        return $this->where('profile_id', $profileId)
            ->where('status', 'active')
            ->orderBy('version', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    /**
     * Archiva todos los contratos activos del trabajador.
     */
    public function archiveActiveContracts(int $profileId): void
    {
        $this->where('profile_id', $profileId)
            ->where('status', 'active')
            ->set(['status' => 'archived'])
            ->update();
    }

    /**
     * Siguiente número de versión para un trabajador (usar dentro de transacción con lock).
     */
    public function getNextVersion(int $profileId): int
    {
        $row = $this->db->query(
            "SELECT MAX(version) AS max_v FROM hr_contracts WHERE profile_id = ? AND deleted_at IS NULL",
            [$profileId]
        )->getRow();

        return ((int) ($row->max_v ?? 0)) + 1;
    }

    /**
     * Valida datos mínimos antes de tocar contratos activos.
     */
    public function validateVersionData(array $contractData): bool
    {
        $content = trim((string) ($contractData['content_snapshot'] ?? ''));
        if ($content === '') {
            return false;
        }

        if (empty($contractData['profile_id']) || (int) $contractData['profile_id'] <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Crea una nueva versión de contrato (fork) y archiva la anterior.
     * Si insert falla, el contrato active previo permanece intacto (rollback).
     *
     * @return int|false ID del nuevo contrato
     */
    public function createVersion(array $contractData): int|false
    {
        if (! $this->validateVersionData($contractData)) {
            return false;
        }

        $profileId = (int) $contractData['profile_id'];

        $contractData['change_source'] = $contractData['change_source'] ?? 'auto';
        $contractData['status']        = $contractData['status'] ?? 'active';

        $this->db->transBegin();

        try {
            $previousActive = $this->getActiveContract($profileId, true);

            if (empty($contractData['parent_contract_id']) && $previousActive) {
                $contractData['parent_contract_id'] = $previousActive->id;
            }

            if (empty($contractData['version'])) {
                $contractData['version'] = $this->getNextVersion($profileId);
            }

            $this->archiveActiveContracts($profileId);

            $newId = $this->insert($contractData);

            if ($newId === false) {
                $this->db->transRollback();

                return false;
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();

                return false;
            }

            $this->db->transCommit();

            return (int) $newId;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'WorkerContractModel::createVersion: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Historial plano ordenado por versión descendente.
     */
    public function getHistoryByProfile(int $profileId): array
    {
        return $this->select('hr_contracts.*, hr_cat_contract_types.name as contract_type_name, hr_cat_contract_templates.name as template_name')
            ->join('hr_cat_contract_types', 'hr_cat_contract_types.id = hr_contracts.contract_type_id', 'left')
            ->join('hr_cat_contract_templates', 'hr_cat_contract_templates.id = hr_contracts.template_id', 'left')
            ->where('hr_contracts.profile_id', $profileId)
            ->orderBy('hr_contracts.version', 'DESC')
            ->orderBy('hr_contracts.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Construye el árbol de versiones (raíz = versión 1, hijos = forks).
     *
     * @return array<int, array{contract: object, children: array}>
     */
    public function getContractTree(int $profileId): array
    {
        $contracts = $this->getHistoryByProfile($profileId);
        if (empty($contracts)) {
            return [];
        }

        $byId = [];
        foreach ($contracts as $c) {
            $byId[$c->id] = ['contract' => $c, 'children' => []];
        }

        $roots = [];
        foreach ($contracts as $c) {
            $parentId = $c->parent_contract_id ?? null;
            if ($parentId && isset($byId[$parentId])) {
                $byId[$parentId]['children'][] = &$byId[$c->id];
            } else {
                $roots[] = &$byId[$c->id];
            }
        }

        usort($roots, static fn ($a, $b) => $b['contract']->version <=> $a['contract']->version);

        $sortChildren = static function (array &$nodes) use (&$sortChildren): void {
            usort($nodes, static fn ($a, $b) => $b['contract']->version <=> $a['contract']->version);
            foreach ($nodes as &$node) {
                if (! empty($node['children'])) {
                    $sortChildren($node['children']);
                }
            }
        };
        $sortChildren($roots);

        return $roots;
    }
}
