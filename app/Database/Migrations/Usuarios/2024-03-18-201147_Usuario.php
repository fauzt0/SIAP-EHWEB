<?php
/* Creamos la tabla rol, permiso, seccion, usuario y contacto_usuario */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Usuario extends Migration
{
  public function up()
  {
    // Crear tabla usuario si no existe, con los valores id_usuario,nombre,apellido_paterno,apellido_materno,email,password,telefono,movil,estatus,creado_en,actualizado_en,avatar
    // pk id_usuario, key nombre, key apellido_paterno, key apellido_materno, key email, key telefono, key movil, fk id_rol
    $this->forge->addField([
      'id_usuario' => [
          'type' => 'INT',
          'constraint' => 11,
          'unsigned' => true, 
          'auto_increment' => true,   
          'null' => false,                                    
      ],
      'nombre' => [
          'type' => 'VARCHAR',
          'constraint' => 50,
          'null' => false,          
      ],
      'apellido_paterno' => [
          'type' => 'VARCHAR',
          'constraint' => 50,
          'null' => true,
      ],
      'apellido_materno' => [
          'type' => 'VARCHAR',
          'constraint' => 50,
          'null' => true,
      ],
      'email' => [
          'type' => 'VARCHAR',
          'constraint' => 100,  
          'null' => false,              
      ],
      'password' => [
          'type' => 'VARCHAR',
          'constraint' => 250,
          'null' => true,
      ],
      'telefono' => [
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
      ],
      'rol' => [
          'type' => 'VARCHAR',
          'constraint' => 50,
          'null' => true,
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
      'avatar' => [
          'type' => 'VARCHAR',
          'constraint' => 255,           
          'null' => true,
      ],
    ]);

    $this->forge->addKey('id_usuario', true);//pk   
    $this->forge->addKey('nombre');//key
    $this->forge->addKey('apellido_paterno');//key
    $this->forge->addKey('apellido_materno');//key
    $this->forge->addKey('email');//key
    $this->forge->addKey('telefono');//key
    $this->forge->addKey('movil');//key  
    $this->forge->createTable('usuario', true); 
  }


  public function down()
  {        
    //eliminamos las tablas si existen        
    $this->forge->dropTable('usuario', true);    
  }
}
