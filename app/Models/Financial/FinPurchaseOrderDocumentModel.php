<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Documentos de expediente de OC (inbound_archive / outbound).
 */
class FinPurchaseOrderDocumentModel extends Model
{
    public const CHANNEL_INBOUND  = 'inbound_archive';
    public const CHANNEL_OUTBOUND = 'outbound';

    public const INBOUND_TYPES = [
        'supplier_invoice_pdf',
        'supplier_invoice_xml',
        'supplier_invoice_image',
    ];

    public const OUTBOUND_TYPES = [
        'payment_proof',
        'clarification',
        'other',
    ];

    public const INBOUND_MIMES = [
        'application/pdf',
        'application/xml',
        'text/xml',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    public const OUTBOUND_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    protected $table            = 'fin_purchase_order_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fin_purchase_order_id',
        'doc_channel',
        'doc_type',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'fin_purchase_order_id' => 'required|is_natural_no_zero',
        'doc_channel'             => 'required|in_list[inbound_archive,outbound]',
        'doc_type'                => 'required',
        'original_name'           => 'required|max_length[255]',
        'stored_name'             => 'required|max_length[255]',
        'file_path'               => 'required|max_length[255]',
    ];

    /**
     * Documentos de una OC, opcionalmente filtrados por canal.
     */
    public function getByOrder(int $poId, ?string $channel = null): array
    {
        $builder = $this->where('fin_purchase_order_id', $poId)
            ->orderBy('created_at', 'DESC');

        if ($channel !== null) {
            $builder->where('doc_channel', $channel);
        }

        return $builder->findAll();
    }

    /**
     * Guarda archivo en disco e inserta registro en transacción.
     */
    public function storeDocument(int $poId, UploadedFile $file, array $meta): int|false
    {
        if (!$file->isValid() || $file->hasMoved()) {
            return false;
        }

        $channel = $meta['doc_channel'] ?? self::CHANNEL_INBOUND;
        $docType = $meta['doc_type'] ?? 'other';

        if (!$this->isValidTypeForChannel($channel, $docType)) {
            return false;
        }

        $mime       = $file->getMimeType();
        $size       = $file->getSize();
        $clientName = $file->getClientName();

        if (!$this->isAllowedMime($channel, $mime)) {
            return false;
        }

        if ($size > 10 * 1024 * 1024) {
            return false;
        }

        $uploadDir = WRITEPATH . 'uploads/financial/po/' . $poId . '/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return false;
        }

        $storedName   = $file->getRandomName();
        $relativePath = 'financial/po/' . $poId . '/' . $storedName;
        $absolutePath = $uploadDir . $storedName;

        $file->move($uploadDir, $storedName);

        $this->db->transStart();

        $data = [
            'fin_purchase_order_id' => $poId,
            'doc_channel'           => $channel,
            'doc_type'              => $docType,
            'original_name'         => $clientName,
            'stored_name'           => $storedName,
            'file_path'             => $relativePath,
            'mime_type'             => $mime,
            'file_size'             => $size,
            'notes'                 => $meta['notes'] ?? null,
            'uploaded_by'           => $meta['uploaded_by'] ?? null,
        ];

        $newId = $this->insert($data);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            return false;
        }

        return (int) $newId;
    }

    public function softDeleteDocument(int $id): bool
    {
        return (bool) $this->delete($id);
    }

    public function isValidTypeForChannel(string $channel, string $docType): bool
    {
        if ($channel === self::CHANNEL_INBOUND) {
            return in_array($docType, self::INBOUND_TYPES, true);
        }

        return in_array($docType, self::OUTBOUND_TYPES, true);
    }

    public function isAllowedMime(string $channel, ?string $mime): bool
    {
        $allowed = $channel === self::CHANNEL_INBOUND ? self::INBOUND_MIMES : self::OUTBOUND_MIMES;

        return $mime && in_array(strtolower($mime), $allowed, true);
    }

    public function getAbsolutePath(object $document): string
    {
        return WRITEPATH . 'uploads/' . $document->file_path;
    }
}
