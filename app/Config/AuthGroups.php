<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    public string $defaultGroup = 'user';
    

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * An associative array of the available groups in the system, where the keys
     * are the group names and the values are arrays of the group info.
     *
     * Whatever value you assign as the key will be used to refer to the group
     * when using functions such as:
     *      $user->addGroup('superadmin');
     *
     * @var array<string, array<string, string>>
     *
     * @see https://codeigniter4.github.io/shield/quick_start_guide/using_authorization/#change-available-groups for more info
     */
    /*
    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Complete control of the site.',
        ],
        'admin' => [
            'title'       => 'Admin',
            'description' => 'Day to day administrators of the site.',
        ],
        'developer' => [
            'title'       => 'Developer',
            'description' => 'Site programmers.',
        ],
        'user' => [
            'title'       => 'User',
            'description' => 'General users of the site. Often customers.',
        ],
        'beta' => [
            'title'       => 'Beta User',
            'description' => 'Has access to beta-level features.',
        ],
    ];*/

    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Administrador',
            'description' => 'Control completo del sistema ERP, modelos, tienda en linea y sitio web',
        ],
        'admin' => [
            'title'       => 'Administrador',
            'description' => 'Administradores del sistema ERP con acceso a todas las funciones excepto configuración avanzada',
        ],
        'editor' => [
            'title'       => 'Editor',
            'description' => 'Personal con permisos para editar contenido, catálogo y publicaciones',
        ],
        'seller' => [
            'title'       => 'Vendedor',
            'description' => 'Agente de ventas con acceso a gestión de clientes y prospectos',
        ],
        'customer' => [
            'title'       => 'Cliente',
            'description' => 'Usuarios finales, clientes con acceso al portal de servicios',
        ],
        'lead' => [
            'title'       => 'Prospecto',
            'description' => 'Usuarios potenciales registrados desde campañas o formularios externos',
        ],
        'developer' => [
            'title'       => 'Desarrollador',
            'description' => 'Programadores del sistema ERP con acceso a herramientas de desarrollo',
        ],
        'user' => [
            'title'       => 'Usuario',
            'description' => 'Usuarios generales del sistema con acceso limitado',
        ],
        'finance' => [
            'title'       => 'Finanzas',
            'description' => 'Personal de finanzas con acceso a módulos contables y financieros',
        ],
        'inventory' => [
            'title'       => 'Inventario',
            'description' => 'Personal de almacén con acceso a gestión de inventario',
        ],
        'sales' => [
            'title'       => 'Módulo de Ventas',
            'description' => 'Grupo heredado para personal general de ventas',
        ],
        'purchasing' => [
            'title'       => 'Compras',
            'description' => 'Personal de compras con acceso a módulos de compras y proveedores',
        ],
        'hr' => [
            'title'       => 'Recursos Humanos',
            'description' => 'Personal de RRHH con acceso a módulos de personal y nómina',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * The available permissions in the system.
     *
     * If a permission is not listed here it cannot be used.
     */
    public array $permissions = [
        // Admin permissions
        'superadmin.access'   => 'Can access the super admin area',
        'superadmin.settings' => 'Can access the main site settings superadmin',
        'superadmin.manage-admins' => 'Can manage other admins',
        'admin.access'        => 'Can access the admin dashboard',
        'admin.settings'      => 'Can access the main ERP settings',
        'admin.manage-users'  => 'Can manage users',
        
        // User management
        'users.create'        => 'Can create new users',
        'users.edit'          => 'Can edit existing users',
        'users.delete'        => 'Can delete existing users',
        'users.view'          => 'Can view user details',
        
        // Finance module
        'finance.access'      => 'Can access finance module',
        'finance.transactions' => 'Can manage financial transactions',
        'finance.reports'     => 'Can generate financial reports',
        'finance.settings'    => 'Can configure finance settings',
        
        // Inventory module
        'inventory.access'    => 'Can access inventory module',
        'inventory.items'     => 'Can manage inventory items',
        'inventory.movements' => 'Can record inventory movements',
        'inventory.reports'   => 'Can generate inventory reports',
        
        // Sales module
        'sales.access'        => 'Can access sales module',
        'sales.create'        => 'Can create sales orders',
        'sales.edit'          => 'Can edit sales orders',
        'sales.reports'       => 'Can generate sales reports',
        'sales.customers'     => 'Can manage customers',
        
        // Purchasing module
        'purchasing.access'   => 'Can access purchasing module',
        'purchasing.create'   => 'Can create purchase orders',
        'purchasing.edit'     => 'Can edit purchase orders',
        'purchasing.reports'  => 'Can generate purchasing reports',
        'purchasing.suppliers' => 'Can manage suppliers',
        
        // HR module
        'hr.access'           => 'Can access HR module',
        'hr.employees'        => 'Can manage employee records',
        'hr.view'             => 'Can view worker list and profiles',
        'hr.create'           => 'Can create new worker records',
        'hr.edit'             => 'Can edit worker records',
        'hr.delete'           => 'Can delete worker records (soft delete)',
        'hr.payroll'          => 'Can manage payroll',
        'hr.reports'          => 'Can generate HR reports',

        // Catalog module
        'catalog.access'      => 'Can access catalog module',
        'catalog.manage'      => 'Can manage products and categories',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
    public array $matrix = [
        'superadmin' => [
            'superadmin.*',
            'admin.*',
            'users.*',
            'finance.*',
            'inventory.*',
            'sales.*',
            'purchasing.*',
            'hr.*',
            'catalog.*',
        ],
        'admin' => [
            'admin.*',
            'users.*',
            'finance.*',
            'inventory.*',
            'sales.*',
            'purchasing.*',
            'hr.*',
            'catalog.*',
        ],
        'editor' => [
            'admin.access',
            // Puedes añadir permisos de edición aquí en el futuro
        ],
        'seller' => [
            'admin.access',
            'sales.*',
            'users.view',
        ],
        'customer' => [
            // Permisos base de portal CRM
        ],
        'lead' => [
            // Sin acceso administrativo
        ],
        'developer' => [
            'admin.access',
            'admin.settings',
            'users.view',
        ],
        'user' => [
            'users.view',
        ],
        'finance' => [
            'admin.access',
            'finance.*',
            'users.view',
        ],
        'inventory' => [
            'admin.access',
            'inventory.*',
            'catalog.*',
            'users.view',
        ],
        'sales' => [
            'admin.access',
            'sales.*',
            'users.view',
        ],
        'purchasing' => [
            'admin.access',
            'purchasing.*',
            'users.view',
        ],
        'hr' => [
            'admin.access',
            'hr.*',
            'users.view',
        ],
    ];
}