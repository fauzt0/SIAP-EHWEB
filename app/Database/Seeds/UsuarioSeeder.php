<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
      $data = [        
        'nombre' => 'admin',
        'apellido_paterno' => 'admin',
        'apellido_materno' => 'admin',            
        'email' => 'soporte2@especialistashosting.com',
        'password' => password_hash('Prueba1', PASSWORD_DEFAULT),
        'telefono' => '1234567890',
        'estatus' => 'activo',
        'creado_en' => date('Y-m-d H:i:s'),          
      ];
      
      // Using Query Builder
      $this->db->table('usuario')->insert($data);
    }
}
