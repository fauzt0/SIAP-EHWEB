<?php

namespace App\Database\Migrations\System;

use CodeIgniter\Database\Migration;

class CreateSysPlugins extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'version'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'main_class'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'class_dir'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'table_prefix' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'description'  => ['type' => 'TEXT', 'null' => true],
            'icon'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'fa-plug'],
            'status'       => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'installing', 'error'], 'default' => 'inactive'],
            'installed_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('sys_plugins', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sys_plugins', true);
    }
}
