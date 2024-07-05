<?php
/** 
 * Seeder para la tabla usuario_seccion, relacion de usuario con secciones
 */
namespace App\Database\Seeds\Usuarios;

use CodeIgniter\Database\Seeder;

class UsuarioSeccionSeeder extends Seeder
{
    public function run()
    {
      //get usuario and seccion ids
      $usuarioIDs = $this->db->table('usuario')->select('id_usuario')->get()->getResultArray();
      $seccionIDs = $this->db->table('seccion')->select('id_seccion')->get()->getResultArray();

      $data = [];
      
      foreach ($seccionIDs as $seccionID) {
        foreach ($usuarioIDs as $usuarioID) {
          $data[] = [
            'agregar' => 1,
            'editar'  => 1,
            'consultar' => 1,
            'acceso' => 1,
            'id_seccion' => $seccionID['id_seccion'],
            'id_usuario' => $usuarioID['id_usuario'],
          ];
        }
      }
      
      $this->db->table('usuario_seccion')->insertBatch($data);
    }
}
