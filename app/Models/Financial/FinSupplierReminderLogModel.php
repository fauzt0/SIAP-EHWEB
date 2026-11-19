<?php

namespace App\Models\Financial;

use CodeIgniter\Model;

class FinSupplierReminderLogModel extends Model
{
    protected $table            = 'fin_supplier_reminder_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'channel',
        'event_type',
        'status',
        'subject',
        'payload',
        'error_message',
        'reference_type',
        'reference_id',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
