<?php

namespace App\Models\Users;

use CodeIgniter\Model;

class UserActivityLogsModel extends Model
{
    use \App\Traits\DataTableTrait;

    protected $datatableConfig = [
        'table' => 'users_activity_logs',
        'column_order' => ['users_activity_logs.id', 'type', 'description', 'ip_address', 'user_agent', 'created_at'], // Orden manual
        'column_search' => ['type', 'description', 'ip_address', 'user_agent', 'created_at'], // Columnas buscables globalmente
        'order' => ['created_at' => 'DESC'] // Default: más reciente
    ];

    protected $table            = 'users_activity_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'users_id',
        'type',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = '';

    /**
     * Registra rápida y estandarizadamente una acción en la bitácora
     * 
     * @param int|null $userId ID del usuario que generó la acción (si es nulo asume el usuario logueado en la request actual)
     * @param string $type Tipo de actividad (ej. 'login', 'create_user', 'update_order')
     * @param string $description Texto descriptivo detallado (ej. "El usuario editó el producto ID 15")
     */
    public function logActivity(string $type, string $description, ?int $userId = null)
    {
        $request = service('request');
        
        // Si no mandan ID y tenemos Shield disponible, usamos el logueado
        if ($userId === null && function_exists('user_id')) {
            $userId = user_id();
        }

        $data = [
            'users_id'    => $userId,
            'type'        => $type,
            'description' => $description,
            'ip_address'  => $request->getIPAddress(),
            'user_agent'  => $request->getUserAgent() ? $request->getUserAgent()->getAgentString() : 'Unknown',
        ];

        return $this->insert($data);
    }

    /**
     * getRecentByUser()
     * -------------------------------------------------------------------
     * Obtiene los últimos N registros de actividad de un usuario específico.
     * Se utiliza en el Offcanvas del listado de usuarios para mostrar
     * el timeline de "Actividad Reciente".
     *
     * @param int $userId ID del usuario a consultar
     * @param int $limit  Cantidad máxima de registros (por defecto 5)
     * @return array
     */
    public function getRecentByUser(int $userId, int $limit = 5): array
    {
        return $this->where('users_id', $userId)
                     ->orderBy('created_at', 'DESC')
                     ->limit($limit)
                     ->find();
    }

    /**
     * _get_datatables_query()
     * -------------------------------------------------------------------
     * Lógica personalizada para el Trait DataTable.
     * Implementa filtro para el users_id, rango de fechas y tipo.
     */
    protected function _get_datatables_query($postData = [])
    {
        // 1. Siempre filtramos por el usuario actual
        if (isset($postData['users_id'])) {
            $this->builder()->where('users_id', $postData['users_id']);
        }

        // 2. Filtro extendido: Tipo de Acción
        if (!empty($postData['type'])) {
            $this->builder()->where('type', $postData['type']);
        }

        // 3. Filtro extendido: Rango de fechas (DateRangePicker devuelve string ej. '01/01/2026 - 05/01/2026' o usamos 2 celdas)
        // Por consistencia y sencillez para el postData, asumiremos start_date y end_date enviados desde js.
        if (!empty($postData['start_date']) && !empty($postData['end_date'])) {
            $this->builder()->where('DATE(created_at) >=', $postData['start_date']);
            $this->builder()->where('DATE(created_at) <=', $postData['end_date']);
        }

        // 4. (Obligatorio) Llamar la lógica genérica del Trait para la búsqueda global y ordenación
        $this->_apply_datatables_filters($postData);
    }
}
