<?php

namespace App\Database\Migrations\System;

use CodeIgniter\Database\Migration;

/**
 * Identidad del prefijo de tablas: update() no puede cambiarlo y uninstall
 * archiva las tablas con el prefijo instalado, no el del manifiesto editado.
 */
class AddSysPluginsTablePrefix extends Migration
{
    public function up(): void
    {
        if ($this->db->fieldExists('table_prefix', 'sys_plugins')) {
            return;
        }

        $this->forge->addColumn('sys_plugins', [
            'table_prefix' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'after'      => 'class_dir',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('table_prefix', 'sys_plugins')) {
            $this->forge->dropColumn('sys_plugins', 'table_prefix');
        }
    }
}
