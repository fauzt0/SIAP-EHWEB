<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

class FinAccountModel extends Model
{
    use DataTableTrait;

    protected $table            = 'fin_accounts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'type',
        'currency',
        'balance',
        'is_active',
        'company_id',
        'branch_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'     => 'required|max_length[100]',
        'type'     => 'required|in_list[cash,bank,digital]',
        'currency' => 'permit_empty|max_length[3]',
        'balance'  => 'permit_empty|decimal',
    ];

    protected $datatableConfig = [
        'column_order'  => ['fin_accounts.name', 'fin_accounts.type', 'fin_accounts.currency', 'fin_accounts.balance', 'fin_accounts.is_active', null],
        'column_search' => ['fin_accounts.name', 'fin_accounts.type', 'fin_accounts.currency'],
        'order'         => ['fin_accounts.name' => 'asc'],
    ];

    public function getActive(): array
    {
        return $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }

    public function defaultAccountId(): ?int
    {
        $row = $this->where('is_active', 1)->orderBy('id', 'ASC')->first();

        return $row ? (int) $row->id : null;
    }
}
