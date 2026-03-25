<?php
/**
 * Agregamos la tabla de audit_logs para registrar los cambios en la base de datos, con una relacion a la tabla users
 */
namespace App\Database\Migrations\Users;

use CodeIgniter\Database\Migration;

class AuditLogs extends Migration
{
    public function up()
    {
        //deshabilitamos la verificacion de llaves foraneas
        $this->db->disableForeignKeyChecks();
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'table_name'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'record_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'action'           => ['type' => 'ENUM', 'constraint' => ['create', 'update', 'delete'], 'default' => 'create'],            
            'date'             => ['type' => 'DATETIME', 'null' => true],            
        ]);
        $this->forge->addKey('id', true);
        //llave foranea a la tabla users
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');   
        $this->forge->createTable('audit_logs', true);
        //habilitamos la verificacion de llaves foraneas
        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        //deshabilitamos la verificacion de llaves foraneas
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('audit_logs');
        //habilitamos la verificacion de llaves foraneas
        $this->db->enableForeignKeyChecks();
    }
}
