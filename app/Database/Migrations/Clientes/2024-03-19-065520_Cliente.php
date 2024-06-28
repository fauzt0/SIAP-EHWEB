<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Cliente extends Migration
{
    public function up()
    {
      //creamos nuestra tabla cliente con los valores id_cliente,nombre,apellido_paterno,apellido_materno,email,password,telefono,telefono_secundario,movil,estatus,email_secundario,tipo,creado_en,actualizado_en,eliminado_en,empresa
      //pk id_cliente, key nombre, key apellido_paterno, key apellido_materno, unique key email, key telefono, key telefono_secundario
      $this->forge->addField([
        'id_cliente' => [
          'type' => 'INT',
          'constraint' => 11,
          'unsigned' => true,
          'auto_increment' => true,
          'null' => false,
        ],
        'nombre' => [
          'type' => 'VARCHAR',
          'constraint' => 50,
          'null' => false
        ],
        'apellido_paterno' => [
          'type' => 'VARCHAR',
          'constraint' => 50,
          'null' => true        
        ],
        'apellido_materno' => [
          'type' => 'VARCHAR',
          'constraint' => 50,
          'null' => true
        ],
        'email' => [
          'type' => 'VARCHAR',
          'constraint' => 100,
          'unique' => true,
          'null' => false
        ],
        'password' => [
          'type' => 'VARCHAR',
          'constraint' => 250,
          'null' => false
        ],
        'telefono' => [
          'type' => 'VARCHAR',
          'constraint' => 15,
          'null' => true,
        ],
        'telefono_secundario' => [
          'type' => 'VARCHAR',
          'constraint' => 15,
          'null' => true,
        ],
        'movil' => [
          'type' => 'VARCHAR',
          'constraint' => 15,
          'null' => true,
        ],
        'estatus' => [
          'type' => 'ENUM',
          'constraint' => ['activo','baja'],
          'default' => 'activo',
          'null' => false
        ],
        'email_secundario' => [
          'type' => 'VARCHAR',
          'constraint' => 100,
          'null' => true          
        ],
        'tipo' => [
          'type' => 'ENUM',
          'constraint' => ['cliente','reseller'],
          'default' => 'cliente',
        ],
        'creado_en' => [
          'type' => 'DATETIME',
          'null' => true,          
        ],
        'actualizado_en' => [
          'type' => 'DATETIME',
          'null' => true,
        ],
        'eliminado_en' => [
          'type' => 'DATETIME',
          'null' => true,
        ],
        'empresa' => [
          'type' => 'ENUM',
          'constraint' => ['hosting','web','mekonecta'],
          'default' => 'hosting',
        ],
      ]);

      $this->forge->addKey('id_cliente', true);//pk
      $this->forge->addKey('nombre');//key
      $this->forge->addKey('apellido_paterno');//key
      $this->forge->addKey('apellido_materno');//key
      //$this->forge->addKey('email');//key
      $this->forge->addKey('telefono');//key        
      $this->forge->createTable('cliente'); 

    }
    public function down()
    {
      $this->forge->dropTable('cliente');
    }
}
