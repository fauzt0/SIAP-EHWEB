<?php
/** 
 * Seeder de la base de datos de la seccion de usuarios. Al ejecutarse, crea un usuario principal con los permisos de todas las secciones de la base de datos, y un usuario de administrador. 
 * Ejecutar para crear al usuario principal:  php spark db:seed "App\Database\Seeds\Usuarios\DatamasterSeeder"
 * */    

 /*
 Debido a que se usa shield, ya no es necesario crear un usuario de administrador desde el seeder
 Se mantiene el archivo por motivos de consulta
  */
namespace App\Database\Seeds\Usuarios;
use CodeIgniter\Database\Seeder;

class DatamasterSeeder extends Seeder
{
    public function run()
    {
      //$this->call('App\Database\Seeds\Usuarios\UsuarioSeeder');
      //$this->call('App\Database\Seeds\Usuarios\SeccionSeeder');
      //$this->call('App\Database\Seeds\Usuarios\UsuarioSeccionSeeder');
    }
}
