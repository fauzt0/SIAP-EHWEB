<?php

namespace App\Database\Seeds\Usuarios;

use CodeIgniter\Database\Seeder;

class SeccionSeeder extends Seeder
{
    public function run()
    {
      //insertamos multiples registros con los datos, tipo, nombre_seccion, ruta, comando, comentarios

      $data = [
        [
          'tipo' => 'seccion',
          'nombre_seccion' => 'seccion',
          'comando' => '',
          'ruta' => '',
          'comentarios' => 'Sección del sistema para gestionar secciones, rutas y comandos',
        ],
        [
          'tipo' => 'seccion',
          'nombre_seccion' => 'usuario',
          'comando' => '',
          'ruta' => '',
          'comentarios' => 'Sección del sistema para gestionar usuarios',
        ],       
      ];      
      
      // Using Query Builder
      $this->db->table('seccion')->insertBatch($data);
    }
}
