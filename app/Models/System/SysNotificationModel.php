<?php

namespace App\Models\System;

use CodeIgniter\Model;

class SysNotificationModel extends Model
{
    protected $table            = 'sys_notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
        'target_url'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // No updated_at en esta tabla según plan
}
