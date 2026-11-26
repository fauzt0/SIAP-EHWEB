<?php

declare(strict_types=1);

namespace App\Models\Sales;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Model;

class SalesPaymentProofModel extends Model
{
    protected $table            = 'sales_payment_proofs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_id', 'order_id', 'payment_id', 'receipt_id', 'file_path',
        'original_name', 'mime_type', 'notes', 'uploaded_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function storeProof(int $clientId, UploadedFile $file, array $meta): int|false
    {
        if (! $file->isValid() || $file->hasMoved()) {
            return false;
        }

        $mime = (string) $file->getMimeType();
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($mime, $allowed, true) || $file->getSize() > 8 * 1024 * 1024) {
            return false;
        }

        $dir = WRITEPATH . 'uploads/sales/proofs/' . $clientId . '/';
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return false;
        }

        $stored = $file->getRandomName();
        $file->move($dir, $stored);

        $id = $this->insert([
            'client_id'     => $clientId,
            'order_id'      => $meta['order_id'] ?? null,
            'payment_id'    => $meta['payment_id'] ?? null,
            'receipt_id'    => $meta['receipt_id'] ?? null,
            'file_path'     => 'sales/proofs/' . $clientId . '/' . $stored,
            'original_name' => $file->getClientName(),
            'mime_type'     => $mime,
            'notes'         => $meta['notes'] ?? null,
            'uploaded_by'   => $meta['uploaded_by'] ?? null,
        ]);

        return $id ? (int) $id : false;
    }

    public function absolutePath(object $row): string
    {
        return WRITEPATH . 'uploads/' . $row->file_path;
    }
}
