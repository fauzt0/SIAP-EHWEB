<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Traits\DataTableTrait;
use CodeIgniter\Model;

class CrmClientModel extends Model
{
    use DataTableTrait;

    protected $table            = 'crm_clients';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_code', 'company_name', 'person_type', 'trade_name', 'rfc_tax_id', 'website',
        'email', 'phone', 'status', 'source', 'tags', 'assigned_user_id', 'branch_id',
        'expected_value', 'next_followup_at', 'notes', 'custom_fields', 'lost_reason_id',
        'plugin_ref', 'active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'company_name' => 'required|max_length[255]',
        'email'        => 'permit_empty|valid_email|max_length[255]',
        'status'       => 'required|in_list[lead,prospect,active,inactive,churned]',
        'rfc_tax_id'   => 'permit_empty|max_length[20]',
    ];

    protected $datatableConfig = [
        'column_order'  => [
            'crm_clients.client_code',
            'crm_clients.company_name',
            'crm_clients.rfc_tax_id',
            'crm_clients.status',
            'crm_clients.email',
            'crm_clients.expected_value',
            'crm_clients.next_followup_at',
            null,
            'crm_clients.active',
            null,
        ],
        'column_search' => [
            'crm_clients.client_code',
            'crm_clients.company_name',
            'crm_clients.trade_name',
            'crm_clients.rfc_tax_id',
            'crm_clients.email',
            'crm_clients.phone',
        ],
        'order' => ['crm_clients.id' => 'desc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select('crm_clients.*, (SELECT COUNT(*) FROM crm_client_contacts c WHERE c.client_id = crm_clients.id AND c.deleted_at IS NULL) AS contacts_count')
            ->select('(SELECT COUNT(*) FROM sales_orders so WHERE so.client_id = crm_clients.id AND so.deleted_at IS NULL) AS orders_count', false);

        if (! empty($postData['filter_status'])) {
            $this->builder()->where('crm_clients.status', $postData['filter_status']);
        }
        if (! empty($postData['filter_assigned'])) {
            $this->builder()->where('crm_clients.assigned_user_id', (int) $postData['filter_assigned']);
        }
        if (! empty($postData['filter_branch'])) {
            $this->builder()->where('crm_clients.branch_id', (int) $postData['filter_branch']);
        }
        if (isset($postData['filter_active']) && $postData['filter_active'] !== '') {
            $this->builder()->where('crm_clients.active', (int) $postData['filter_active']);
        }

        $this->_apply_datatables_filters($postData);
    }

    public function nextClientCode(): string
    {
        $year = date('Y');
        $row  = $this->db->query(
            "SELECT client_code FROM crm_clients WHERE client_code LIKE ? ORDER BY id DESC LIMIT 1",
            ['CLI-' . $year . '-%']
        )->getRow();

        $seq = 1;
        if ($row && preg_match('/(\d+)$/', (string) $row->client_code, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('CLI-%s-%04d', $year, $seq);
    }

    public function createWithRelated(array $client, array $contacts = [], array $billing = []): int|false
    {
        $this->db->transStart();

        if (empty($client['client_code'])) {
            $client['client_code'] = $this->nextClientCode();
        }

        $id = $this->insert($client);
        if (! $id) {
            $this->db->transRollback();

            return false;
        }

        $contactModel = new CrmClientContactModel();
        foreach ($contacts as $row) {
            if (trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }
            $row['client_id'] = $id;
            if (! $contactModel->insert($row)) {
                $this->db->transRollback();

                return false;
            }
        }

        $billingModel = new CrmBillingProfileModel();
        foreach ($billing as $row) {
            if (trim((string) ($row['legal_name'] ?? '')) === '') {
                continue;
            }
            $row['client_id'] = $id;
            if (! $billingModel->insert($row)) {
                $this->db->transRollback();

                return false;
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? (int) $id : false;
    }

    public function updateWithRelated(int $id, array $client, array $contacts = [], array $billing = []): bool
    {
        $this->db->transStart();

        if (! $this->update($id, $client)) {
            $this->db->transRollback();

            return false;
        }

        $contactModel = new CrmClientContactModel();
        $contactModel->where('client_id', $id)->delete();
        foreach ($contacts as $row) {
            if (trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }
            $row['client_id'] = $id;
            if (! $contactModel->insert($row)) {
                $this->db->transRollback();

                return false;
            }
        }

        $billingModel = new CrmBillingProfileModel();
        $billingModel->where('client_id', $id)->delete();
        foreach ($billing as $row) {
            if (trim((string) ($row['legal_name'] ?? '')) === '') {
                continue;
            }
            $row['client_id'] = $id;
            if (! $billingModel->insert($row)) {
                $this->db->transRollback();

                return false;
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    public function getStats(): array
    {
        $row = $this->builder()
            ->select('COUNT(*) AS total')
            ->select('SUM(CASE WHEN status = "lead" THEN 1 ELSE 0 END) AS leads', false)
            ->select('SUM(CASE WHEN status = "prospect" THEN 1 ELSE 0 END) AS prospects', false)
            ->select('SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) AS active_clients', false)
            ->select('SUM(CASE WHEN status IN ("lead","prospect") THEN expected_value ELSE 0 END) AS pipeline_value', false)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return [
            'total'          => (int) ($row['total'] ?? 0),
            'leads'          => (int) ($row['leads'] ?? 0),
            'prospects'      => (int) ($row['prospects'] ?? 0),
            'active'         => (int) ($row['active_clients'] ?? 0),
            'pipeline_value' => (float) ($row['pipeline_value'] ?? 0),
        ];
    }

    /**
     * @return array<string,list<object>>
     */
    public function getPipeline(): array
    {
        $rows = $this->select('crm_clients.*, users.username, users.first_name, users.last_name, org_branches.name AS branch_name')
            ->join('users', 'users.id = crm_clients.assigned_user_id', 'left')
            ->join('org_branches', 'org_branches.id = crm_clients.branch_id', 'left')
            ->orderBy('crm_clients.next_followup_at', 'ASC')
            ->findAll(500);

        $cols = [
            'lead'     => [],
            'prospect' => [],
            'active'   => [],
            'inactive' => [],
            'churned'  => [],
        ];
        foreach ($rows as $row) {
            $key = $row->status ?? 'lead';
            if (! isset($cols[$key])) {
                $cols[$key] = [];
            }
            $cols[$key][] = $row;
        }

        return $cols;
    }

    public function findDuplicateRfc(?string $rfc, ?int $exceptId = null): ?object
    {
        return $this->findDuplicateContact(null, null, $rfc, $exceptId);
    }

    public function findDuplicateContact(?string $email, ?string $phone, ?string $rfc, ?int $exceptId = null): ?object
    {
        $email = strtolower(trim((string) $email));
        $phone = preg_replace('/\D+/', '', (string) $phone) ?? '';
        $rfc   = strtoupper(preg_replace('/\s+/', '', (string) $rfc) ?? '');
        if ($email === '' && $phone === '' && $rfc === '') {
            return null;
        }
        $builder = $this->groupStart();
        $started = false;
        if ($email !== '') {
            $builder->where('email', $email);
            $started = true;
        }
        if ($phone !== '') {
            $started ? $builder->orWhere('phone', $phone) : $builder->where('phone', $phone);
            $started = true;
        }
        if ($rfc !== '') {
            $started ? $builder->orWhere('rfc_tax_id', $rfc) : $builder->where('rfc_tax_id', $rfc);
        }
        $builder->groupEnd();
        if ($exceptId) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->first();
    }

    public function getForSelect(): array
    {
        return $this->select('id, client_code, company_name, status')
            ->where('active', 1)
            ->orderBy('company_name', 'ASC')
            ->findAll();
    }
}
