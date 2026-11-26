<?php

declare(strict_types=1);

namespace App\Models\Sales;

use CodeIgniter\Model;

class SalesDocumentTemplateModel extends Model
{
    protected $table            = 'sales_document_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'doc_kind', 'title', 'legal_note', 'header_extra_html', 'footer_extra_html',
        'watermark_text', 'paper', 'show_logo', 'default_branch_id', 'active',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'doc_kind' => 'required|in_list[quote,order,receipt]',
        'title'    => 'required|max_length[120]',
        'paper'    => 'permit_empty|in_list[Letter,A4]',
    ];

    public function forKind(string $kind): ?object
    {
        return $this->where('doc_kind', $kind)->where('active', 1)->first()
            ?: $this->where('doc_kind', $kind)->first();
    }
}
