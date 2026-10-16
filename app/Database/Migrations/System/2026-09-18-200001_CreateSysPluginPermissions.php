<?php

namespace App\Database\Migrations\System;

use CodeIgniter\Database\Migration;

class CreateSysPluginPermissions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'plugin_slug' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'permission'  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['plugin_slug', 'permission']);
        $this->forge->createTable('sys_plugin_permissions', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sys_plugin_permissions', true);
    }
}
