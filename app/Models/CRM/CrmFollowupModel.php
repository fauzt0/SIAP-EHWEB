<?php

declare(strict_types=1);

namespace App\Models\CRM;

use CodeIgniter\Model;

class CrmFollowupModel extends Model
{
    protected $table            = 'crm_followups';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_id', 'contact_id', 'parent_followup_id', 'version', 'change_source', 'status',
        'followup_status', 'followup_type', 'title', 'incident', 'comment', 'client_status_snapshot',
        'sales_order_id', 'sales_quote_id', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'client_id'     => 'required|is_natural_no_zero',
        'title'         => 'required|max_length[180]',
        'followup_type'   => 'required|in_list[comment,status_change,meeting,call,email,quote,order,service,whatsapp,ticket,support]',
        'followup_status' => 'permit_empty|in_list[open,in_progress,waiting,closed]',
        'change_source'   => 'required|in_list[auto,manual,fork]',
        'status'          => 'required|in_list[active,archived,draft]',
    ];

    public function getActive(int $clientId, bool $forUpdate = false): ?object
    {
        if ($forUpdate) {
            return $this->db->query(
                "SELECT * FROM crm_followups
                 WHERE client_id = ? AND status = 'active' AND deleted_at IS NULL
                 ORDER BY version DESC, id DESC LIMIT 1 FOR UPDATE",
                [$clientId]
            )->getRow();
        }

        return $this->where('client_id', $clientId)
            ->where('status', 'active')
            ->orderBy('version', 'DESC')
            ->first();
    }

    public function getNextVersion(int $clientId): int
    {
        $row = $this->db->query(
            'SELECT MAX(version) AS max_v FROM crm_followups WHERE client_id = ? AND deleted_at IS NULL',
            [$clientId]
        )->getRow();

        return ((int) ($row->max_v ?? 0)) + 1;
    }

    /**
     * Nueva revisión (fork) del seguimiento: archiva la vigente y conserva parent_id.
     *
     * @param list<array<string,mixed>> $services
     */
    public function createVersion(array $data, array $services = []): int|false
    {
        $clientId = (int) ($data['client_id'] ?? 0);
        if ($clientId <= 0 || trim((string) ($data['title'] ?? '')) === '') {
            return false;
        }

        $data['change_source']   = $data['change_source'] ?? 'manual';
        $data['status']          = $data['status'] ?? 'active';
        $data['followup_type']   = $data['followup_type'] ?? 'comment';
        $data['followup_status'] = $data['followup_status'] ?? 'open';
        if (empty($data['incident'])) {
            $data['incident'] = $data['title'] ?? null;
        }

        $this->db->transBegin();

        try {
            $previous = $this->getActive($clientId, true);
            if (empty($data['parent_followup_id']) && $previous) {
                $data['parent_followup_id'] = $previous->id;
            }
            $data['version'] = $data['version'] ?? $this->getNextVersion($clientId);

            $this->where('client_id', $clientId)
                ->where('status', 'active')
                ->set(['status' => 'archived'])
                ->update();

            $newId = $this->insert($data);
            if ($newId === false) {
                $this->db->transRollback();

                return false;
            }

            $svcModel = new CrmFollowupServiceModel();
            foreach ($services as $svc) {
                $productId = (int) ($svc['product_id'] ?? 0);
                if ($productId <= 0) {
                    continue;
                }
                $svcModel->insert([
                    'followup_id' => $newId,
                    'product_id'  => $productId,
                    'plan_id'     => ! empty($svc['plan_id']) ? (int) $svc['plan_id'] : null,
                    'service_id'  => ! empty($svc['service_id']) ? (int) $svc['service_id'] : null,
                    'notes'       => $svc['notes'] ?? null,
                ]);
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();

                return false;
            }

            $this->db->transCommit();

            \App\Libraries\PluginSystem\PluginHookRegistry::doAction('crm.followup.created', (int) $newId, $clientId);

            return (int) $newId;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'CrmFollowupModel::createVersion: ' . $e->getMessage());

            return false;
        }
    }

    public function getHistoryByClient(int $clientId): array
    {
        return $this->select('crm_followups.*, crm_client_contacts.name AS contact_name, crm_client_contacts.contact_type')
            ->join('crm_client_contacts', 'crm_client_contacts.id = crm_followups.contact_id', 'left')
            ->where('crm_followups.client_id', $clientId)
            ->orderBy('crm_followups.version', 'DESC')
            ->orderBy('crm_followups.id', 'DESC')
            ->findAll();
    }

    /**
     * @return list<object>
     */
    public function getTree(int $clientId): array
    {
        $rows = $this->getHistoryByClient($clientId);
        $svcModel = new CrmFollowupServiceModel();
        foreach ($rows as $row) {
            $row->services = $svcModel->getByFollowup((int) $row->id);
        }

        return $rows;
    }
}
