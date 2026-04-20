<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserActivityLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'users_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true, // Permite nulos para acciones del sistema (ej. crons)
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
            ],
            'description' => [
                'type' => 'TEXT',
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => '45', // Soporte IPv6
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        // LLave foránea opcional a tabla users si lo permite la BD
        $this->forge->addForeignKey('users_id', 'users', 'id', 'CASCADE', 'CASCADE');

        // Creación física de la tabla
        $this->forge->createTable('users_activity_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('users_activity_logs', true);
    }
}
