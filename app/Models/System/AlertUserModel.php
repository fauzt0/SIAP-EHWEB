<?php

namespace App\Models\System;

use CodeIgniter\Model;

/**
 * Modelo para la tabla pivote sys_alert_user.
 *
 * Registra qué usuarios tienen asignada cada alerta y el estado de lectura.
 * La combinación (alert_id, user_id) es única para evitar duplicados.
 *
 * @property int    $id
 * @property int    $alert_id
 * @property int    $user_id
 * @property int    $is_read   (0 = no leída, 1 = leída)
 * @property string|null $read_at
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
class AlertUserModel extends Model
{
    protected $table            = 'sys_alert_user';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'alert_id',
        'user_id',
        'is_read',
        'read_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'alert_id' => 'required|is_natural_no_zero',
        'user_id'  => 'required|is_natural_no_zero',
        'is_read'  => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'alert_id' => [
            'required'           => 'El ID de la alerta es obligatorio.',
            'is_natural_no_zero' => 'El ID de la alerta debe ser un número válido.',
        ],
        'user_id' => [
            'required'           => 'El ID del usuario es obligatorio.',
            'is_natural_no_zero' => 'El ID del usuario debe ser un número válido.',
        ],
    ];
}
