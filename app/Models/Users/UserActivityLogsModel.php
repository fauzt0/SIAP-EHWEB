<?php

namespace App\Models\Users;

use CodeIgniter\Model;

class UserActivityLogsModel extends Model
{
    use \App\Traits\DataTableTrait;

    /**
     * §5/§16 — Columnas ordenables y buscables (DataTableTrait lee $datatableConfig;
     * estas propiedades se mantienen sincronizadas y se reconfiguran en modo global).
     */
    private $column_order = [
        'users_activity_logs.id',
        'type',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    private $column_search = [
        'type',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $datatableConfig = [
        'table'         => 'users_activity_logs',
        'column_order'  => ['users_activity_logs.id', 'type', 'description', 'ip_address', 'user_agent', 'created_at'],
        'column_search' => ['type', 'description', 'ip_address', 'user_agent', 'created_at'],
        'order'         => ['created_at' => 'DESC'],
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
     * Configuración DataTables para la bitácora de un usuario (vista existente).
     */
    private function _configureUserDatatable(): void
    {
        $this->column_order = [
            'users_activity_logs.id',
            'type',
            'description',
            'ip_address',
            'user_agent',
            'created_at',
        ];
        $this->column_search = [
            'type',
            'description',
            'ip_address',
            'user_agent',
            'created_at',
        ];
        $this->datatableConfig['column_order']  = $this->column_order;
        $this->datatableConfig['column_search'] = $this->column_search;
        $this->datatableConfig['order']         = ['created_at' => 'DESC'];
    }

    /**
     * Configuración DataTables para la bitácora global (JOIN users).
     * Columnas UI: ID, Usuario, Acción, Descripción, IP, Fecha.
     */
    private function _configureGlobalDatatable(): void
    {
        $this->column_order = [
            'users_activity_logs.id',
            'users.username',
            'users_activity_logs.type',
            'users_activity_logs.description',
            'users_activity_logs.ip_address',
            'users_activity_logs.created_at',
        ];
        $this->column_search = [
            'users.username',
            'users.first_name',
            'users.last_name',
            'users_activity_logs.type',
            'users_activity_logs.description',
            'users_activity_logs.ip_address',
        ];
        $this->datatableConfig['column_order']  = $this->column_order;
        $this->datatableConfig['column_search'] = $this->column_search;
        $this->datatableConfig['order']         = ['users_activity_logs.created_at' => 'DESC'];
    }

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

        $userAgent = 'CLI';
        if (method_exists($request, 'getUserAgent')) {
            $agent     = $request->getUserAgent();
            $userAgent = $agent ? $agent->getAgentString() : 'Unknown';
        }

        $data = [
            'users_id'    => $userId,
            'type'        => $type,
            'description' => $description,
            'ip_address'  => $request->getIPAddress(),
            'user_agent'  => $userAgent,
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
     * getGlobalActivity()
     * -------------------------------------------------------------------
     * Listado completo de bitácora con JOIN a users y filtros de
     * usuario / acción / rango de fechas. Pensado para exportaciones
     * y consultas de lectura (la DataTable server-side usa el Trait).
     *
     * Filtros aceptados: user_id|filter_user, action|filter_action,
     * date_from|filter_date_from, date_to|filter_date_to.
     */
    public function getGlobalActivity(array $filters = []): array
    {
        $query = $this->db->table('users_activity_logs ual')
            ->select('ual.id, ual.type AS action, ual.description, ual.ip_address, ual.created_at,
                      ual.users_id, u.username, u.first_name, u.last_name')
            ->join('users u', 'u.id = ual.users_id', 'left')
            ->orderBy('ual.created_at', 'DESC');

        $userId = $filters['user_id'] ?? $filters['filter_user'] ?? null;
        if (! empty($userId)) {
            $query->where('ual.users_id', $userId);
        }

        $action = $filters['action'] ?? $filters['filter_action'] ?? null;
        if (! empty($action)) {
            $query->like('ual.type', $action);
        }

        $dateFrom = $filters['date_from'] ?? $filters['filter_date_from'] ?? null;
        if (! empty($dateFrom)) {
            $query->where('ual.created_at >=', $dateFrom . ' 00:00:00');
        }

        $dateTo = $filters['date_to'] ?? $filters['filter_date_to'] ?? null;
        if (! empty($dateTo)) {
            $query->where('ual.created_at <=', $dateTo . ' 23:59:59');
        }

        return $query->get()->getResultArray();
    }

    /**
     * _get_datatables_query()
     * -------------------------------------------------------------------
     * Lógica personalizada para el Trait DataTable.
     * Modo por usuario: filtro users_id + tipo + fechas.
     * Modo global (scope=global): JOIN users + filtros usuario/acción/fecha.
     */
    protected function _get_datatables_query($postData = [])
    {
        $isGlobal = ! empty($postData['scope']) && $postData['scope'] === 'global';

        if ($isGlobal) {
            $this->_configureGlobalDatatable();

            $this->builder()
                ->select('users_activity_logs.id,
                          users_activity_logs.users_id,
                          users_activity_logs.type,
                          users_activity_logs.description,
                          users_activity_logs.ip_address,
                          users_activity_logs.created_at,
                          users.username,
                          users.first_name,
                          users.last_name')
                ->join('users', 'users.id = users_activity_logs.users_id', 'left');

            $userId = $postData['filter_user'] ?? $postData['user_id'] ?? $postData['users_id'] ?? null;
            if (! empty($userId)) {
                $this->builder()->where('users_activity_logs.users_id', $userId);
            }

            $action = $postData['filter_action'] ?? $postData['action'] ?? $postData['type'] ?? null;
            if (! empty($action)) {
                $this->builder()->like('users_activity_logs.type', $action);
            }

            $dateFrom = $postData['filter_date_from'] ?? $postData['date_from'] ?? $postData['start_date'] ?? null;
            if (! empty($dateFrom)) {
                $this->builder()->where('users_activity_logs.created_at >=', $dateFrom . ' 00:00:00');
            }

            $dateTo = $postData['filter_date_to'] ?? $postData['date_to'] ?? $postData['end_date'] ?? null;
            if (! empty($dateTo)) {
                $this->builder()->where('users_activity_logs.created_at <=', $dateTo . ' 23:59:59');
            }
        } else {
            $this->_configureUserDatatable();

            if (isset($postData['users_id'])) {
                $this->builder()->where('users_id', $postData['users_id']);
            }

            if (! empty($postData['type'])) {
                $this->builder()->where('type', $postData['type']);
            }

            if (! empty($postData['start_date']) && ! empty($postData['end_date'])) {
                $this->builder()->where('DATE(created_at) >=', $postData['start_date']);
                $this->builder()->where('DATE(created_at) <=', $postData['end_date']);
            }
        }

        $this->_apply_datatables_filters($postData);
    }
}
