<?php

declare(strict_types=1);

namespace App\Models\Sales;

use App\Traits\DataTableTrait;
use CodeIgniter\Model;

class SalesQuoteModel extends Model
{
    use DataTableTrait;

    protected $table            = 'sales_quotes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'quote_number', 'client_id', 'parent_quote_id', 'version', 'status',
        'valid_until', 'subtotal', 'tax_amount', 'total_amount', 'currency',
        'notes', 'converted_order_id', 'plugin_ref', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'client_id' => 'required|is_natural_no_zero',
        'status'    => 'required|in_list[draft,sent,approved,rejected,expired,converted]',
    ];

    protected $datatableConfig = [
        'column_order'  => [
            'sales_quotes.quote_number',
            'crm_clients.company_name',
            'sales_quotes.status',
            'sales_quotes.total_amount',
            'sales_quotes.valid_until',
            null,
        ],
        'column_search' => [
            'sales_quotes.quote_number',
            'crm_clients.company_name',
            'sales_quotes.status',
        ],
        'order' => ['sales_quotes.id' => 'desc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select('sales_quotes.*, crm_clients.company_name, crm_clients.client_code')
            ->join('crm_clients', 'crm_clients.id = sales_quotes.client_id', 'left');

        if (! empty($postData['filter_status'])) {
            $this->builder()->where('sales_quotes.status', $postData['filter_status']);
        }
        if (! empty($postData['filter_client'])) {
            $this->builder()->where('sales_quotes.client_id', (int) $postData['filter_client']);
        }

        $this->_apply_datatables_filters($postData);
    }

    public function nextNumber(): string
    {
        $year = date('Y');
        $row  = $this->db->query(
            'SELECT quote_number FROM sales_quotes WHERE quote_number LIKE ? ORDER BY id DESC LIMIT 1',
            ['COT-' . $year . '-%']
        )->getRow();
        $seq = 1;
        if ($row && preg_match('/(\d+)$/', (string) $row->quote_number, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('COT-%s-%04d', $year, $seq);
    }

    /**
     * @param list<array<string,mixed>> $items
     */
    public function createWithItems(array $quote, array $items): int|false
    {
        $this->db->transStart();

        if (empty($quote['quote_number'])) {
            $quote['quote_number'] = $this->nextNumber();
        }

        $totals = $this->sumItems($items);
        $quote['subtotal']     = $totals['subtotal'];
        $quote['tax_amount']   = $totals['tax'];
        $quote['total_amount'] = $totals['total'];

        $id = $this->insert($quote);
        if (! $id) {
            $this->db->transRollback();

            return false;
        }

        $itemModel = new SalesQuoteItemModel();
        foreach ($items as $item) {
            $item['quote_id']   = $id;
            $item['line_total'] = round(((float) ($item['unit_price'] ?? 0)) * ((int) ($item['quantity'] ?? 1)), 2);
            if (! $itemModel->insert($item)) {
                $this->db->transRollback();

                return false;
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? (int) $id : false;
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return array{subtotal:float,tax:float,total:float}
     */
    public function sumItems(array $items, float $taxRate = 0.16): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) ($item['unit_price'] ?? 0)) * ((int) ($item['quantity'] ?? 1));
        }
        $tax = round($subtotal * $taxRate, 2);

        return ['subtotal' => round($subtotal, 2), 'tax' => $tax, 'total' => round($subtotal + $tax, 2)];
    }

    public function getWithItems(int $id): ?object
    {
        $quote = $this->find($id);
        if (! $quote) {
            return null;
        }
        $quote->items = (new SalesQuoteItemModel())->where('quote_id', $id)->findAll();

        return $quote;
    }
}
