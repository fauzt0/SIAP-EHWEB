<?php

namespace App\Database\Migrations\Usuarios;

use CodeIgniter\Database\Migration;

class ContactoUsuario extends Migration
{
    public function up()
    {
      //creamos la tabla contacto_usuario si no existe con los valores id_contacto_usuario,id_usuario, medio_contacto, valor_contacto
      // pk id_contacto_usuario, fk id_usuario
      $this->db->disableForeignKeyChecks();
      $this->forge->addField([
        'id_contacto_usuario' => [
            'type' => 'INT',
            'constraint' => 11,
            'unsigned' => true, 
            'auto_increment' => true,                                       
        ],
        'id_usuario' => [
            'type' => 'INT',
            'constraint' => 11,
            'unsigned' => true, 
        ],
        'medio_contacto' => [
            'type' => 'VARCHAR',
            'constraint' => 50,
        ],
        'valor_contacto' => [
            'type' => 'VARCHAR',
            'constraint' => 50,
        ],
      ]);
      
      $this->forge->addKey('id_contacto_usuario', true);
      $this->forge->addForeignKey('id_usuario', 'usuario', 'id_usuario');
      $this->forge->createTable('contacto_usuario');
      $this->db->enableForeignKeyChecks();


    }

    public function down()
    {
        //eliminamos las tablas si existen
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('contacto_usuario', true);        
        $this->db->enableForeignKeyChecks();
    }
}
