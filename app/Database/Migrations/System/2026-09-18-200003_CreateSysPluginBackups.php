<?php

namespace App\Database\Migrations\System;

use CodeIgniter\Database\Migration;

class CreateSysPluginBackups extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'plugin_slug'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'plugin_name'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'plugin_version'  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'class_dir'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'backup_file'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'archived_tables' => ['type' => 'JSON', 'null' => true],
            'backup_size_kb'  => ['type' => 'INT', 'null' => true],
            'uninstalled_at'  => ['type' => 'DATETIME', 'null' => false],
            'uninstalled_by'  => ['type' => 'INT', 'null' => false, 'default' => 0],
            'purged_at'       => ['type' => 'DATETIME', 'null' => true],
            'purged_by'       => ['type' => 'INT', 'null' => true],
            'restored_at'     => ['type' => 'DATETIME', 'null' => true],
            'restored_by'     => ['type' => 'INT', 'null' => true],
            'notes'           => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('plugin_slug');
        $this->forge->createTable('sys_plugin_backups', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sys_plugin_backups', true);
    }
}
