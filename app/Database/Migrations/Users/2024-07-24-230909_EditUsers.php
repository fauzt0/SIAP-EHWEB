<?php
/**Editar la tabla usuario creada por shield con los valores nombre,apellido,email,,telefono,movil,avatar */
namespace App\Database\Migrations\Users;
use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class EditUsers extends Migration
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
      // Editar la tabla usuario si no existe, con los valores nombre,apellido,email,,telefono,movil,avatar
      // pk id_usuario, key nombre, key apellido_paterno, key apellido_materno, key email, key telefono, key movil, fk id_rol
      //$this->forge->addField([
      $this->db->disableForeignKeyChecks();
        $fields = ([      
          'first_name' => [
              'type' => 'VARCHAR',
              'constraint' => 50,
              'null' => false,          
          ],
          'last_name' => [
              'type' => 'VARCHAR',
              'constraint' => 100,
              'null' => true,
          ],                      
          'phone_number' => [
              'type' => 'VARCHAR',
              'constraint' => 15,
              'null' => true,
          ],
          'mobile_number' => [
              'type' => 'VARCHAR',
              'constraint' => 15,
              'null' => true,
          ],      
          'role' => [
              'type' => 'VARCHAR',
              'constraint' => 50,
              'null' => true,
          ],      
          'avatar' => [
              'type' => 'VARCHAR',
              'constraint' => 255,           
              'null' => true,
          ],
        ]);

        //agregarmos los fields a la tabla
        $this->forge->addColumn($this->tables['users'], $fields);

        //agregamos claves a la tabla
        $this->forge->addKey('first_name');//key 
        $this->forge->addKey('last_name');//key            
        $this->forge->addKey('phone_number');//key
        $this->forge->addKey('mobile_number');//key  
        $this->forge->processIndexes($this->tables['users']); //agregamos los indices */
      $this->db->enableForeignKeyChecks();      
    }

    public function down()
    {
      $this->db->disableForeignKeyChecks();
        //eliminamos las claves a las tablas
        $this->forge->dropKey($this->tables['users'],'first_name', false);
        $this->forge->dropKey($this->tables['users'],'last_name', false);        
        $this->forge->dropKey($this->tables['users'],'phone_number', false);
        $this->forge->dropKey($this->tables['users'],'mobile_number', false);

        //array con los campos a borrar
        $fields = array('first_name', 'last_name', 'phone_number', 'mobile_number', 'role', 'avatar'); 
        //deshacemos los cambios de las fields agregadas
        $this->forge->dropColumn($this->tables['users'], $fields);    
      $this->db->enableForeignKeyChecks();     
    }
}
