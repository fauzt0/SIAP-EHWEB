<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

class FinSupplierInvoiceModel extends Model
{
    use DataTableTrait;

    public const STATUSES = [
        'draft'     => 'Borrador',
        'open'      => 'Abierta',
        'partial'   => 'Parcial',
        'paid'      => 'Pagada',
        'cancelled' => 'Cancelada',
    ];

    protected $table            = 'fin_supplier_invoices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'folio',
        'supplier_id',
        'purchase_order_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total',
        'paid_amount',
        'status',
        'company_id',
        'branch_id',
        'account_id',
        'notes',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'folio'       => 'required|max_length[40]',
        'supplier_id' => 'required|is_natural_no_zero',
        'invoice_date'=> 'required|valid_date',
        'total'       => 'required|decimal|greater_than_equal_to[0]',
        'status'      => 'required|in_list[draft,open,partial,paid,cancelled]',
    ];

    protected $datatableConfig = [
        'column_order'  => [
            'fin_supplier_invoices.folio',
            'fin_suppliers.commercial_name',
            'fin_supplier_invoices.invoice_number',
            'fin_supplier_invoices.invoice_date',
            'fin_supplier_invoices.total',
            'fin_supplier_invoices.paid_amount',
            'fin_supplier_invoices.status',
            null,
        ],
        'column_search' => [
            'fin_supplier_invoices.folio',
            'fin_suppliers.commercial_name',
            'fin_supplier_invoices.invoice_number',
            'fin_purchase_orders.po_number',
        ],
        'order' => ['fin_supplier_invoices.id' => 'desc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select([
                'fin_supplier_invoices.*',
                'fin_suppliers.commercial_name AS supplier_name',
                'fin_purchase_orders.po_number',
            ])
            ->join('fin_suppliers', 'fin_suppliers.id = fin_supplier_invoices.supplier_id', 'left')
            ->join('fin_purchase_orders', 'fin_purchase_orders.id = fin_supplier_invoices.purchase_order_id', 'left');

        if (! empty($postData['status'])) {
            $this->builder()->where('fin_supplier_invoices.status', (string) $postData['status']);
        }
        if (! empty($postData['supplier_id'])) {
            $this->builder()->where('fin_supplier_invoices.supplier_id', (int) $postData['supplier_id']);
        }

        $this->_apply_datatables_filters($postData);

        return $this;
    }

    public function nextFolio(): string
    {
        $year   = date('Y');
        $prefix = 'FPROV-' . $year . '-';
        $last   = $this->withDeleted()->like('folio', $prefix, 'after')->orderBy('id', 'DESC')->first();
        $seq    = 1;
        if ($last && preg_match('/FPROV-\d{4}-(\d+)/', (string) $last->folio, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function findByPurchaseOrder(int $poId): ?object
    {
        return $this->where('purchase_order_id', $poId)->first();
    }
}
