<?php
declare(strict_types=1);
namespace App\Models\Users;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class UserModel extends ShieldUserModel
{
    use \App\Traits\DataTableTrait;

    //protected $table = 'users';
    //protected $primaryKey = 'id';

    protected $datatableConfig = [
        'table' => 'users',
        'column_order' => ['users.username', 'users.first_name', 'users.last_name', 'auth_identities.secret'],
        'column_search' => ['users.username', 'users.first_name', 'users.last_name', 'auth_identities.secret'],
        'order' => ['users.id' => 'ASC'],
    ];

    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [
            ...$this->allowedFields,
            'first_name', //Added
            'last_name', //Added                        
            'avatar', //Added
        ];
    }

    //metodos adicionales asociados al modelo de usuario que generan funcionalidad adicional a la tabla "users", administrada por shield

    /**
     * _get_datatables_query()
     * -------------------------------------------------------------------
     * Esta función construye la base de la consulta SQL (Query Builder) requerida 
     * por DataTables. Su propósito principal es:
     * 1. Unir (JOIN) las tablas necesarias (users, auth_identities, auth_groups_users).
     * 2. Aplicar los filtros de búsqueda global generados por el cajón de "Buscar" del frontend.
     * 3. Aplicar el ordenamiento (ORDER BY) de las columnas si el usuario hace clic 
     *    en las flechas de ordenación de la cabecera de la tabla.
     */
    protected function _get_datatables_query($postData = [])
    {
        // 1. Construimos la consulta base uniendo información de Shield.
        $this->builder()
            ->select('users.*, auth_identities.secret as email, auth_groups_users.group as role')
            ->join('auth_identities', 'auth_identities.user_id = users.id AND auth_identities.type = \'email_password\'', 'left')
            ->join('auth_groups_users', 'auth_groups_users.user_id = users.id', 'left');

        // Filtros personalizados adicionales desde UI
        if (!empty($postData['role'])) {
            $this->builder()->where('auth_groups_users.group', $postData['role']);
        }
        if (!empty($postData['status'])) {
            if ($postData['status'] === 'active') {
                $this->builder()->where('users.deleted_at IS NULL');
            } else if ($postData['status'] === 'deleted') {
                $this->builder()->where('users.deleted_at IS NOT NULL');
            }
        }

        // 2. Aplicamos la lógica genérica de filtros y ordenamiento del Trait
        $this->_apply_datatables_filters($postData);
    }

    //obtiene todos los usuarios con filtros
    public function get_all_users($filtros = [])
    {

    }


}