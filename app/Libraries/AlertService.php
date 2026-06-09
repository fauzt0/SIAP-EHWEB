<?php

namespace App\Libraries;

use App\Models\System\AlertModel;
use App\Models\System\AlertUserModel;
use App\Models\Users\UserActivityLogsModel;

/**
 * Servicio central del sistema de alertas y notificaciones.
 *
 * Proporciona métodos de alto nivel para:
 *   - Crear y despachar alertas a usuarios según permisos Shield.
 *   - Consultar alertas no leídas para el dropdown del topbar.
 *   - Marcar alertas como leídas.
 *   - Obtener historial paginado.
 *
 * Uso típico desde cualquier controlador:
 *   $alertService = new \App\Libraries\AlertService();
 *   $alertService->dispatch(
 *       title: 'Pago vencido',
 *       message: 'El proveedor X tiene un adeudo...',
 *       type: 'warning',
 *       icon: 'fa-exclamation-triangle',
 *       targetUrl: route_to('suppliers.show', $id),
 *       requiredPermission: 'purchasing.suppliers',
 *       module: 'suppliers',
 *       referenceType: 'supplier',
 *       referenceId: $id
 *   );
 */
class AlertService
{
    protected AlertModel $alertModel;
    protected AlertUserModel $alertUserModel;
    protected ?UserActivityLogsModel $logModel = null;

    public function __construct()
    {
        $this->alertModel     = new AlertModel();
        $this->alertUserModel = new AlertUserModel();
    }

    // ────────────────────────────────────────────────────────────────────────
    //  CREACIÓN DE ALERTAS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Crea un registro de alerta en sys_alerts.
     *
     * @param array $data Campos de la alerta.
     *                    - title:       string (requerido)
     *                    - message:     string|null
     *                    - type:        string (info|success|warning|danger)
     *                    - icon:        string (clase FontAwesome)
     *                    - target_url:  string|null
     *                    - required_permission: string|null
     *                    - module:      string|null
     *                    - reference_type: string|null
     *                    - reference_id: int|null
     * @return int ID de la alerta creada, o 0 si falló la validación.
     */
    public function createAlert(array $data): int
    {
        $alertData = [
            'title'               => $data['title'] ?? '',
            'message'             => $data['message'] ?? null,
            'type'                => $data['type'] ?? 'info',
            'icon'                => $data['icon'] ?? 'fa-bell',
            'target_url'          => $data['target_url'] ?? null,
            'required_permission' => $data['required_permission'] ?? null,
            'module'              => $data['module'] ?? null,
            'reference_type'      => $data['reference_type'] ?? null,
            'reference_id'        => $data['reference_id'] ?? null,
        ];

        if (!$this->alertModel->validate($alertData)) {
            log_message('error', 'AlertService::createAlert — validación fallida: '
                . json_encode($this->alertModel->errors()));
            return 0;
        }

        $alertId = $this->alertModel->insert($alertData, true);

        if ($alertId) {
            $this->logAudit('alert_create', 'Creó alerta: ' . ($alertData['title'] ?? '') . ' (ID: ' . $alertId . ')');
        }

        return (int) $alertId;
    }

    /**
     * Asigna una alerta existente a uno o más usuarios específicos.
     *
     * @param int   $alertId ID de la alerta en sys_alerts.
     * @param array $userIds Lista de IDs de usuarios.
     * @return bool True si al menos una asignación fue exitosa.
     */
    public function assignToUsers(int $alertId, array $userIds): bool
    {
        if (empty($alertId) || empty($userIds)) {
            return false;
        }

        $inserted = 0;
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if ($userId <= 0) {
                continue;
            }

            // Evitar duplicados (unique key lo maneja, pero evitamos el error)
            $exists = $this->alertUserModel
                ->where('alert_id', $alertId)
                ->where('user_id', $userId)
                ->withDeleted()
                ->first();

            if ($exists) {
                // Si existe pero está soft-deleted, lo restauramos
                if ($exists->deleted_at !== null) {
                    $this->alertUserModel->update($exists->id, ['deleted_at' => null]);
                    $inserted++;
                }
                continue;
            }

            $this->alertUserModel->insert([
                'alert_id' => $alertId,
                'user_id'  => $userId,
                'is_read'  => 0,
            ]);

            if ($this->alertUserModel->insertID()) {
                $inserted++;
            }
        }

        if ($inserted > 0) {
            $this->logAudit('alert_assign', 'Asignó alerta #' . $alertId . ' a ' . $inserted . ' usuarios');
        }

        return $inserted > 0;
    }

    /**
     * Busca todos los usuarios que poseen un permiso específico (vía grupo o directo)
     * y les asigna la alerta.
     *
     * @param int    $alertId    ID de la alerta.
     * @param string $permission Permiso Shield (ej. 'purchasing.suppliers').
     * @return bool
     */
    public function assignByPermission(int $alertId, string $permission): bool
    {
        if (empty($alertId) || empty($permission)) {
            return false;
        }

        $db = \Config\Database::connect();

        // Consulta unificada: usuarios con permiso vía grupo O vía asignación directa
        $sql = "
            SELECT DISTINCT u.id
            FROM users u
            LEFT JOIN auth_groups_users agu ON agu.user_id = u.id
            LEFT JOIN auth_groups_permissions agp ON agp.group_id = agu.group_id AND agp.permission = ?
            LEFT JOIN auth_users_permissions aup ON aup.user_id = u.id AND aup.permission = ?
            WHERE (agp.permission IS NOT NULL OR aup.permission IS NOT NULL)
              AND u.deleted_at IS NULL
        ";

        $rows = $db->query($sql, [$permission, $permission])->getResultArray();
        $userIds = array_column($rows, 'id');

        if (empty($userIds)) {
            log_message('info', 'AlertService::assignByPermission — Sin usuarios con permiso: ' . $permission);
            return false;
        }

        return $this->assignToUsers($alertId, $userIds);
    }

    /**
     * Método de alto nivel: crea la alerta y la asigna automáticamente a todos
     * los usuarios que posean el permiso especificado.
     *
     * Es el método recomendado para uso desde controladores de módulos.
     *
     * @param string      $title               Título de la alerta.
     * @param string|null $message             Mensaje descriptivo.
     * @param string      $type                Tipo: info, success, warning, danger.
     * @param string      $icon                Clase FontAwesome.
     * @param string|null $targetUrl           URL de acceso directo.
     * @param string|null $requiredPermission  Permiso Shield requerido.
     * @param string|null $module              Módulo que origina la alerta.
     * @param string|null $referenceType       Tipo de referencia.
     * @param int|null    $referenceId         ID del registro referenciado.
     * @return int ID de la alerta creada, 0 si falló.
     */
    public function dispatch(
        string $title,
        ?string $message = null,
        string $type = 'info',
        string $icon = 'fa-bell',
        ?string $targetUrl = null,
        ?string $requiredPermission = null,
        ?string $module = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): int {
        $alertId = $this->createAlert([
            'title'               => $title,
            'message'             => $message,
            'type'                => $type,
            'icon'                => $icon,
            'target_url'          => $targetUrl,
            'required_permission' => $requiredPermission,
            'module'              => $module,
            'reference_type'      => $referenceType,
            'reference_id'        => $referenceId,
        ]);

        if ($alertId > 0 && !empty($requiredPermission)) {
            $this->assignByPermission($alertId, $requiredPermission);
        }

        return $alertId;
    }

    // ────────────────────────────────────────────────────────────────────────
    //  CONSULTAS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene las alertas no leídas para un usuario, filtrando por permisos Shield.
     *
     * @param int $userId ID del usuario.
     * @param int $limit  Máximo de alertas a retornar.
     * @return array
     */
    public function getUnreadByUser(int $userId, int $limit = 5): array
    {
        $user = auth()->getProvider()->findById($userId);
        if (!$user) {
            return [];
        }

        $alerts = $this->alertUserModel
            ->select('
                sys_alert_user.id AS pivot_id,
                sys_alert_user.is_read,
                sys_alert_user.read_at,
                sys_alerts.id,
                sys_alerts.title,
                sys_alerts.message,
                sys_alerts.type,
                sys_alerts.icon,
                sys_alerts.target_url,
                sys_alerts.required_permission,
                sys_alerts.module,
                sys_alerts.reference_type,
                sys_alerts.reference_id,
                sys_alerts.created_at
            ')
            ->join('sys_alerts', 'sys_alerts.id = sys_alert_user.alert_id')
            ->where('sys_alert_user.user_id', $userId)
            ->where('sys_alert_user.is_read', 0)
            ->where('sys_alerts.deleted_at', null)
            ->orderBy('sys_alerts.created_at', 'DESC')
            ->limit($limit)
            ->findAll();

        // Filtrar por permisos Shield: solo mostrar alertas cuyo permiso el usuario posea
        return array_values(array_filter($alerts, function ($alert) use ($user) {
            if (empty($alert->required_permission)) {
                return true;
            }
            return $user->can($alert->required_permission);
        }));
    }

    /**
     * Obtiene todas las alertas de un usuario (leídas y no leídas) con paginación.
     *
     * @param int $userId  ID del usuario.
     * @param int $page    Número de página.
     * @param int $perPage Registros por página.
     * @return array Con claves: data, total, page, perPage.
     */
    public function getAllByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $builder = $this->alertUserModel
            ->select('
                sys_alert_user.id AS pivot_id,
                sys_alert_user.is_read,
                sys_alert_user.read_at,
                sys_alerts.id,
                sys_alerts.title,
                sys_alerts.message,
                sys_alerts.type,
                sys_alerts.icon,
                sys_alerts.target_url,
                sys_alerts.required_permission,
                sys_alerts.module,
                sys_alerts.reference_type,
                sys_alerts.reference_id,
                sys_alerts.created_at
            ')
            ->join('sys_alerts', 'sys_alerts.id = sys_alert_user.alert_id')
            ->where('sys_alert_user.user_id', $userId)
            ->where('sys_alerts.deleted_at', null);

        $total = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('sys_alerts.created_at', 'DESC')
            ->limit($perPage, $offset)
            ->findAll();

        return [
            'data'    => $data,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Cuenta las alertas no leídas de un usuario.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->alertUserModel
            ->join('sys_alerts', 'sys_alerts.id = sys_alert_user.alert_id')
            ->where('sys_alert_user.user_id', $userId)
            ->where('sys_alert_user.is_read', 0)
            ->where('sys_alerts.deleted_at', null)
            ->countAllResults();
    }

    // ────────────────────────────────────────────────────────────────────────
    //  MARCADO DE LECTURA
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Marca una alerta específica como leída para un usuario.
     *
     * @param int $alertId ID de la alerta.
     * @param int $userId  ID del usuario.
     * @return bool
     */
    public function markAsRead(int $alertId, int $userId): bool
    {
        $pivot = $this->alertUserModel
            ->where('alert_id', $alertId)
            ->where('user_id', $userId)
            ->first();

        if (!$pivot) {
            return false;
        }

        return $this->alertUserModel->update($pivot->id, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Marca todas las alertas no leídas de un usuario como leídas.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        $db = \Config\Database::connect();

        $sql = "
            UPDATE sys_alert_user
            SET is_read = 1,
                read_at = NOW(),
                updated_at = NOW()
            WHERE user_id = ?
              AND is_read = 0
              AND deleted_at IS NULL
        ";

        return $db->query($sql, [$userId]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  UTILIDADES
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene el modelo de alertas para consultas personalizadas.
     *
     * @return AlertModel
     */
    public function getAlertModel(): AlertModel
    {
        return $this->alertModel;
    }

    /**
     * Obtiene el modelo pivote para consultas personalizadas.
     *
     * @return AlertUserModel
     */
    public function getAlertUserModel(): AlertUserModel
    {
        return $this->alertUserModel;
    }

    // ────────────────────────────────────────────────────────────────────────
    //  AUDITORÍA
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Registra en la bitácora de auditoría las operaciones sobre alertas.
     *
     * @param string $action  Acción realizada (alert_create, alert_assign, etc.).
     * @param string $details Descripción detallada.
     */
    protected function logAudit(string $action, string $details): void
    {
        if ($this->logModel === null) {
            $this->logModel = new UserActivityLogsModel();
        }

        $this->logModel->logActivity($action, $details);
    }
}
