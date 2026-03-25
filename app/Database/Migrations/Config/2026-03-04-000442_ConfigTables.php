<?php
/**
 * Tablas para la configuración de la empresa, sucursal matriz y sucursales secundarias
 * Tabla para enlazar usuarios a las sucursales
 */

namespace App\Database\Migrations\Config;

use CodeIgniter\Database\Migration;

class ConfigTables extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        // 1. Perfil de la Empresa (Identidad de Especialistas Web)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'commercial_name'=> ['type' => 'VARCHAR', 'constraint' => 255],
            'legal_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'tax_id'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true], // RFC en México
            'logo_path'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'favicon_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'primary_email'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'primary_phone'  => ['type' => 'VARCHAR', 'constraint' => 20],
            'website_url'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('org_company_profile', true);

        // 2. Sucursales (Base para el POS y almacén)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'branch_code'    => ['type' => 'VARCHAR', 'constraint' => 10, 'unique' => true], // Ej: 'CENTRO', 'NTE'
            'is_main'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'address'        => ['type' => 'TEXT', 'null' => true],
            'phone'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pos_printer_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true], // Para configuración de POS
            'active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('company_id', 'org_company_profile', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('org_branches', true);

        // 3. Relación Sucursales <-> Usuarios (Vendedores/Gerentes)
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'branch_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'is_manager'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'assigned_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('branch_id', 'org_branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('org_branch_users', true);


        // 4. Ajustes Globales del Sistema (Key-Value)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'key'            => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'value'          => ['type' => 'TEXT', 'null' => true],
            'group'          => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general'], // smtp, appearance, billing
            'description'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('app_settings', true);

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->forge->dropTable('org_company_profile');
        $this->forge->dropTable('org_branches');
        $this->forge->dropTable('org_branch_users');
        $this->forge->dropTable('app_settings');
    }
}
