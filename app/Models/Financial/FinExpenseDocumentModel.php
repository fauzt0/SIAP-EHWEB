<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Comprobantes de vencimientos recurrentes.
 */
class FinExpenseDocumentModel extends Model
{
    protected $table            = 'fin_expense_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fin_expense_id',
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
        'fin_expense_id' => 'required|is_natural_no_zero',
        'doc_type'       => 'required|in_list[payment_proof,invoice,other]',
        'original_name'  => 'required|max_length[255]',
        'stored_name'    => 'required|max_length[255]',
        'file_path'      => 'required|max_length[255]',
    ];

    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    public function getByExpense(int $expenseId): array
    {
        return $this->where('fin_expense_id', $expenseId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function storeDocument(int $expenseId, UploadedFile $file, array $meta): int|false
    {
        if (!$file->isValid() || $file->hasMoved()) {
            return false;
        }

        $mime       = $file->getMimeType();
        $size       = $file->getSize();
        $clientName = $file->getClientName();

        if (!$mime || !in_array(strtolower($mime), self::ALLOWED_MIMES, true)) {
            return false;
        }

        if ($size > 10 * 1024 * 1024) {
            return false;
        }

        $uploadDir = WRITEPATH . 'uploads/financial/expenses/' . $expenseId . '/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return false;
        }

        $storedName   = $file->getRandomName();
        $relativePath = 'financial/expenses/' . $expenseId . '/' . $storedName;
        $absolutePath = $uploadDir . $storedName;

        $file->move($uploadDir, $storedName);

        $this->db->transStart();

        $newId = $this->insert([
            'fin_expense_id' => $expenseId,
            'doc_type'       => $meta['doc_type'] ?? 'payment_proof',
            'original_name'  => $clientName,
            'stored_name'    => $storedName,
            'file_path'      => $relativePath,
            'mime_type'      => $mime,
            'file_size'      => $size,
            'notes'          => $meta['notes'] ?? null,
            'uploaded_by'    => $meta['uploaded_by'] ?? null,
        ]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            return false;
        }

        return (int) $newId;
    }

    public function getAbsolutePath(object $document): string
    {
        return WRITEPATH . 'uploads/' . $document->file_path;
    }

    public function softDeleteDocument(int $id): bool
    {
        return (bool) $this->delete($id);
    }
}
