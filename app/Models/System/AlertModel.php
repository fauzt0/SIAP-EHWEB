<?php

namespace App\Models\System;

use CodeIgniter\Model;

/**
 * Modelo para la tabla sys_alerts (definición única de alertas).
 *
 * Cada registro representa un evento de alerta que puede ser asignado
 * a múltiples usuarios según su permiso en el sistema Shield.
 *
 * @property int    $id
 * @property string $title
 * @property string|null $message
 * @property string $type                  (info, success, warning, danger)
 * @property string $icon                  Clase FontAwesome (ej. fa-bell)
 * @property string|null $target_url       Ruta de acceso directo
 * @property string|null $required_permission Permiso Shield requerido
 * @property string|null $module           Módulo que originó la alerta
 * @property string|null $reference_type   Tipo de referencia (supplier, worker, etc.)
 * @property int|null    $reference_id     ID del registro referenciado
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
class AlertModel extends Model
{
    protected $table            = 'sys_alerts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'title',
        'message',
        'type',
        'icon',
        'target_url',
        'required_permission',
        'module',
        'reference_type',
        'reference_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'title'              => 'required|max_length[200]',
        'type'               => 'required|in_list[info,success,warning,danger]',
        'icon'               => 'required|max_length[60]',
        'target_url'         => 'permit_empty|max_length[500]',
        'required_permission'=> 'permit_empty|max_length[100]',
        'module'             => 'permit_empty|max_length[50]',
        'reference_type'     => 'permit_empty|max_length[50]',
        'reference_id'       => 'permit_empty|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'title' => [
            'required'   => 'El título de la alerta es obligatorio.',
            'max_length' => 'El título no debe exceder 200 caracteres.',
        ],
        'type' => [
            'required' => 'El tipo de alerta es obligatorio.',
            'in_list'  => 'El tipo debe ser: info, success, warning o danger.',
        ],
        'icon' => [
            'required'   => 'El icono de la alerta es obligatorio.',
            'max_length' => 'El icono no debe exceder 60 caracteres.',
        ],
    ];
}
