<?php

namespace App\Database\Migrations\Usuarios;

use CodeIgniter\Database\Migration;

class Seccion extends Migration
{
    public function up()
    {
      //creamos la tabla seccion si no existe, id_seccion int, tipo enum(seccion,ruta,comando),nombre_seccion varchar 50, comentarios varchar 255, ruta varchar 255, comando varchar 255
      //pk id_seccion

      $this->forge->addField([
        'id_seccion' => [
            'type' => 'INT',
            'constraint' => 11,
            'unsigned' => true, 
            'auto_increment' => true,      
            'null' => false,                                 
        ],
        'tipo' => [
            'type' => 'ENUM',
            'constraint' => ['seccion', 'ruta', 'comando'],
            'default' => 'seccion', 
            'null' => false,                                
        ],
        'nombre_seccion' => [
            'type' => 'VARCHAR',
            'constraint' => 50,
            'null' => true,
        ],
        'comando' => [
            'type' => 'VARCHAR',
            'constraint' => 255,
            'null' => true,
        ],
        'ruta' => [
            'type' => 'VARCHAR',
            'constraint' => 255,
            'null' => true,
        ],
        'comentarios' => [
            'type' => 'VARCHAR',
            'constraint' => 255,
            'null' => true,
        ],
      ]);
      $this->forge->addKey('id_seccion', true); //pk
      $this->forge->createTable('seccion');       
    }

    public function down()
    {
        //eliminamos la tabla
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('seccion');
        $this->db->enableForeignKeyChecks();

    }
}
