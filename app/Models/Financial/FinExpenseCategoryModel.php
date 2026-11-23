<?php

namespace App\Models\Financial;

use CodeIgniter\Model;

class FinExpenseCategoryModel extends Model
{
    protected $table            = 'fin_expense_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'expense_type', 'parent_id'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function findByName(string $name): ?object
    {
        return $this->where('name', $name)->first();
    }

    public function idByName(string $name): ?int
    {
        $row = $this->findByName($name);

        return $row ? (int) $row->id : null;
    }

    public function grouped(): array
    {
        $rows = $this->orderBy('expense_type', 'ASC')->orderBy('name', 'ASC')->findAll();
        $out  = ['fixed' => [], 'variable' => []];
        foreach ($rows as $row) {
            $type = $row->expense_type === 'fixed' ? 'fixed' : 'variable';
            $out[$type][] = $row;
        }

        return $out;
    }
}
