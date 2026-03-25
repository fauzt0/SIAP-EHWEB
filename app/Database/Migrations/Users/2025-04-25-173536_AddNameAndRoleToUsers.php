<?php
/**Edita la tabla "users" creada por shield con los valores nombre,apellido,role,avatar */
namespace App\Database\Migrations\Users;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class AddNameAndRoleToUsers extends Migration
{
    /**
    * @var string[]
    */
    private array $tables;    
    //constructor
    public function __construct(?Forge $forge = null)
    {
        parent::__construct($forge);

        /** @var \Config\Auth $authConfig */
        $authConfig   = config('Auth');
        $this->tables = $authConfig->tables;
    }

    public function up()
    {        
      
      $this->db->disableForeignKeyChecks();//desactivamos las claves foraneas
      $fields = ([      
        'first_name' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true,],
        'last_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true,],                
        'avatar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true,],
      ]);
      //agregarmos los fields a la tabla
      $this->forge->addColumn($this->tables['users'], $fields);

      //agregamos claves a la tabla      
      $this->forge->addKey('first_name');//key 
      $this->forge->addKey('last_name');//key                    
      $this->forge->processIndexes($this->tables['users']); //agregamos los indices */

      $this->db->enableForeignKeyChecks(); //reactivamos las claves foraneas     
    }


    public function down()
    {
        $this->db->disableForeignKeyChecks();
        //eliminamos las claves a las tablas
        //$this->forge->dropKey($this->tables['users'],'first_name', false);
        //$this->forge->dropKey($this->tables['users'],'last_name', false);                

        //array con los campos a borrar
        $fields = array('first_name','last_name','avatar'); 
        //deshacemos los cambios de las fields agregadas
        $this->forge->dropColumn($this->tables['users'], $fields);    
        $this->db->enableForeignKeyChecks();         
    }
}
