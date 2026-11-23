<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

class FinLedgerEntryModel extends Model
{
    use DataTableTrait;

    protected $table            = 'fin_ledger_entries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'account_id',
        'reference_type',
        'reference_id',
        'type',
        'amount',
        'balance_after',
        'notes',
        'entry_date',
        'company_id',
        'branch_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $datatableConfig = [
        'column_order'  => [
            'fin_ledger_entries.entry_date',
            'fin_accounts.name',
            'fin_ledger_entries.reference_type',
            'fin_ledger_entries.type',
            'fin_ledger_entries.amount',
            'fin_ledger_entries.balance_after',
            'fin_ledger_entries.notes',
        ],
        'column_search' => [
            'fin_accounts.name',
            'fin_ledger_entries.reference_type',
            'fin_ledger_entries.notes',
        ],
        'order' => ['fin_ledger_entries.id' => 'desc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select([
                'fin_ledger_entries.*',
                'fin_accounts.name AS account_name',
            ])
            ->join('fin_accounts', 'fin_accounts.id = fin_ledger_entries.account_id', 'left');

        if (! empty($postData['account_id'])) {
            $this->builder()->where('fin_ledger_entries.account_id', (int) $postData['account_id']);
        }
        if (! empty($postData['reference_type'])) {
            $this->builder()->where('fin_ledger_entries.reference_type', (string) $postData['reference_type']);
        }

        $this->_apply_datatables_filters($postData);

        return $this;
    }

    public function alreadyPosted(string $referenceType, int $referenceId): bool
    {
        return $this->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->countAllResults() > 0;
    }
}
