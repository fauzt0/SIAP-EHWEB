<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

class FinPaymentFolioModel extends Model
{
    use DataTableTrait;

    public const STATUSES = [
        'requested' => 'Solicitada',
        'approved'  => 'Aprobada',
        'executed'  => 'Ejecutada',
        'rejected'  => 'Rechazada',
    ];

    protected $table            = 'fin_payment_folios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'folio_number',
        'expense_id',
        'account_id',
        'amount_paid',
        'concept',
        'status',
        'requested_by',
        'approved_by',
        'company_id',
        'branch_id',
        'executed_by',
        'approved_at',
        'executed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'folio_number' => 'required|max_length[20]',
        'account_id'   => 'required|is_natural_no_zero',
        'amount_paid'  => 'required|decimal|greater_than[0]',
        'concept'      => 'required|min_length[3]',
        'status'       => 'required|in_list[requested,approved,executed,rejected]',
        'requested_by' => 'required|is_natural_no_zero',
    ];

    protected $datatableConfig = [
        'column_order'  => [
            'fin_payment_folios.folio_number',
            'fin_payment_folios.concept',
            'fin_accounts.name',
            'fin_payment_folios.amount_paid',
            'fin_payment_folios.status',
            'fin_payment_folios.created_at',
            null,
        ],
        'column_search' => [
            'fin_payment_folios.folio_number',
            'fin_payment_folios.concept',
            'fin_accounts.name',
        ],
        'order' => ['fin_payment_folios.id' => 'desc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select([
                'fin_payment_folios.*',
                'fin_accounts.name AS account_name',
            ])
            ->join('fin_accounts', 'fin_accounts.id = fin_payment_folios.account_id', 'left');

        if (! empty($postData['status'])) {
            $this->builder()->where('fin_payment_folios.status', (string) $postData['status']);
        }

        $this->_apply_datatables_filters($postData);

        return $this;
    }

    public function nextFolioNumber(): string
    {
        $year  = date('Y');
        $prefix = 'SDF-' . $year . '-';
        $last = $this->withDeleted()
            ->like('folio_number', $prefix, 'after')
            ->orderBy('id', 'DESC')
            ->first();

        $seq = 1;
        if ($last && preg_match('/SDF-\d{4}-(\d+)/', (string) $last->folio_number, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function getDashboardStats(): array
    {
        $tbl = static fn () => $this->db->table($this->table)->where('deleted_at', null);

        $requestedCount  = $tbl()->where('status', 'requested')->countAllResults();
        $approvedCount   = $tbl()->where('status', 'approved')->countAllResults();
        $requestedAmount = $tbl()->selectSum('amount_paid', 'amount')->where('status', 'requested')->get()->getRow();
        $approvedAmount  = $tbl()->selectSum('amount_paid', 'amount')->where('status', 'approved')->get()->getRow();
        $executedAmount  = $tbl()->selectSum('amount_paid', 'amount')
            ->where('status', 'executed')
            ->where('executed_at >=', date('Y-m-01 00:00:00'))
            ->get()->getRow();

        return [
            'requested_count'  => $requestedCount,
            'requested_amount' => (float) ($requestedAmount->amount ?? 0),
            'approved_count'   => $approvedCount,
            'approved_amount'  => (float) ($approvedAmount->amount ?? 0),
            'executed_month'   => (float) ($executedAmount->amount ?? 0),
        ];
    }
}
