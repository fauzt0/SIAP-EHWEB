<?php

namespace App\Models\Financial;

use CodeIgniter\Model;

/**
 * Historial de correos enviados desde una OC.
 */
class FinPurchaseOrderEmailLogModel extends Model
{
    protected $table            = 'fin_purchase_order_email_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fin_purchase_order_id',
        'email_kind',
        'to_emails',
        'cc_emails',
        'subject',
        'body_html',
        'attached_po_pdf',
        'smtp_status',
        'smtp_error',
        'sent_by',
        'sent_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'fin_purchase_order_id' => 'required|is_natural_no_zero',
        'email_kind'            => 'required|in_list[send_po,reply_with_attachments]',
        'to_emails'             => 'required',
        'subject'               => 'required|max_length[255]',
        'body_html'             => 'required',
        'smtp_status'           => 'required|in_list[sent,failed]',
    ];

    /**
     * Registra envío de correo y documentos outbound adjuntos en una transacción.
     *
     * @param array<int> $outboundDocumentIds Solo IDs de documentos outbound
     */
    public function logSend(array $logData, array $outboundDocumentIds = []): int|false
    {
        $this->db->transStart();

        $logId = $this->insert($logData);

        if ($logId && !empty($outboundDocumentIds)) {
            $pivot = $this->db->table('fin_po_email_log_documents');
            foreach ($outboundDocumentIds as $docId) {
                $pivot->insert([
                    'email_log_id' => $logId,
                    'document_id'  => (int) $docId,
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? (int) $logId : false;
    }

    public function getByOrder(int $poId): array
    {
        return $this->where('fin_purchase_order_id', $poId)
            ->orderBy('sent_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}
