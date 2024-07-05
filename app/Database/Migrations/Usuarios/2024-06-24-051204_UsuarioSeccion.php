<?php

namespace App\Database\Migrations\Usuarios;

use CodeIgniter\Database\Migration;

class UsuarioSeccion extends Migration
{
    public function up()
    {
        //creamos la tabla "usuario_seccion" si no existe con los valores, id_permiso int, agregar boolean, editar boolean, consultar boolean,tipo_acceso varchar (10), id_usuario,id_permiso
        //pk id_permiso, fk id_usuario a tabla "usuario", fk id_seccion a tabla "seccion"
        $this->forge->addField([
            'id_permiso' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'null' => false             
            ],
            'agregar' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            ],
            'editar' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0                
            ],
            'consultar' => [
                'type' => 'TINYINT',
                'constraint' => 1, 
                'null' => false,
                'default' => 0
            ],
            'acceso' => [
                'type' => 'BOOLEAN',
                'null' => false,             
            ],
            'id_seccion' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,                
                'null' => true,         
            ],
            'id_usuario' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
        ]);

        $this->db->disableForeignKeyChecks();
        $this->forge->addKey('id_permiso', true);
        $this->forge->addForeignKey('id_seccion', 'seccion', 'id_seccion');
        $this->forge->addForeignKey('id_usuario', 'usuario', 'id_usuario');
        $this->forge->createTable('usuario_seccion');
        $this->db->enableForeignKeyChecks();        
    }

    public function down()
    {
        
        $this->db->disableForeignKeyChecks();
        //eliminamos la tabla
        $this->forge->dropTable('usuario_seccion');
        $this->db->enableForeignKeyChecks();

    }
}
