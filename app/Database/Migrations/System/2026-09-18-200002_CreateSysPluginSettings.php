<?php

namespace App\Database\Migrations\System;

use CodeIgniter\Database\Migration;

class CreateSysPluginSettings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'plugin_slug' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'key'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'value'       => ['type' => 'TEXT', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['plugin_slug', 'key'], 'uq_plugin_setting');
        $this->forge->createTable('sys_plugin_settings', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sys_plugin_settings', true);
    }
}
