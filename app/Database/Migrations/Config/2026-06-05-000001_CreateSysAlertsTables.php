<?php
/**
 * Migración para crear el sistema centralizado de alertas/notificaciones.
 *
 * Crea dos tablas:
 *   - sys_alerts:      definición única de cada alerta (un registro por evento)
 *   - sys_alert_user:  pivote que rastrea qué usuario ha recibido/leído cada alerta
 *
 * Reemplaza la tabla legacy sys_notifications (se eliminará en limpieza final).
 */

namespace App\Database\Migrations\Config;

use CodeIgniter\Database\Migration;

class CreateSysAlertsTables extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        // ────────────────────────────────────────────────────────────────────
        // 1. sys_alerts — Definición única de alertas
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'                => ['type' => 'VARCHAR', 'constraint' => 200],
            'message'              => ['type' => 'TEXT', 'null' => true],
            'type'                 => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'info'],
            'icon'                 => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'fa-bell'],
            'target_url'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'required_permission'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'module'               => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'reference_type'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'reference_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at');
        $this->forge->addKey('module');
        $this->forge->addKey('required_permission');
        $this->forge->createTable('sys_alerts', true);

        // ────────────────────────────────────────────────────────────────────
        // 2. sys_alert_user — Pivote: usuario + alerta + estado de lectura
        // ────────────────────────────────────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'alert_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'is_read'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'read_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['alert_id', 'user_id'], 'uq_alert_user');
        $this->forge->addKey(['user_id', 'is_read'], false, true, 'idx_user_read');
        $this->forge->addForeignKey('alert_id', 'sys_alerts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sys_alert_user', true);

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->dropTable('sys_alert_user', true);
        $this->forge->dropTable('sys_alerts', true);

        $this->db->enableForeignKeyChecks();
    }
}
