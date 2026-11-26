<?php

declare(strict_types=1);

namespace App\Models\Sales;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Model;

class SalesOrderDocumentModel extends Model
{
    protected $table            = 'sales_order_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_id', 'order_item_id', 'doc_type', 'original_name', 'file_path',
        'mime_type', 'notes', 'plugin_ref', 'uploaded_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function storeDocument(int $orderId, UploadedFile $file, array $meta): int|false
    {
        if (! $file->isValid() || $file->hasMoved()) {
            return false;
        }

        $mime    = (string) $file->getMimeType();
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'application/xml', 'text/xml'];
        if (! in_array($mime, $allowed, true) || $file->getSize() > 10 * 1024 * 1024) {
            return false;
        }

        $dir = WRITEPATH . 'uploads/sales/orders/' . $orderId . '/';
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return false;
        }

        $stored = $file->getRandomName();
        $file->move($dir, $stored);

        $id = $this->insert([
            'order_id'      => $orderId,
            'order_item_id' => $meta['order_item_id'] ?? null,
            'doc_type'      => $meta['doc_type'] ?? 'other',
            'original_name' => $file->getClientName(),
            'file_path'     => 'sales/orders/' . $orderId . '/' . $stored,
            'mime_type'     => $mime,
            'notes'         => $meta['notes'] ?? null,
            'plugin_ref'    => $meta['plugin_ref'] ?? null,
            'uploaded_by'   => $meta['uploaded_by'] ?? null,
        ]);

        return $id ? (int) $id : false;
    }

    public function absolutePath(object $row): string
    {
        return WRITEPATH . 'uploads/' . $row->file_path;
    }
}
