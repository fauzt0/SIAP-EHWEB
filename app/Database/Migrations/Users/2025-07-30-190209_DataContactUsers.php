<?php
/**
 * Crea la tabla data_contact_users en la base de datos, la cual permite guardar los datos de  contacto de los usuarios 
 */

namespace App\Database\Migrations\Users;
use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class DataContactUsers extends Migration
{
  /**
  * @var string[]
  */
  private array $tables;

  public function __construct(?Forge $forge = null)
  {
      parent::__construct($forge);
      /** @var \Config\Auth $authConfig */
      $authConfig   = config('Auth');
      $this->tables = $authConfig->tables;
  }
  /*creamos la tabla data_contact_users, si no existe, con los valores id,users_id,contact_source(enun), contact_value | pk id, fk users_id
      */
  public function up()
  {
    $this->db->disableForeignKeyChecks();
    $this->forge->addField([
      'id' => [
        'type' => 'INT',
        'constraint' => 5,
        'unsigned' => true, 
        'auto_increment' => true,                                       
        'null' => false,
      ],
      'users_id' => [
        'type' => 'INT',
        'constraint' => 5,
        'unsigned' => true, 
        'null' => false,
      ],
      'contact_source' => [
        'type' => 'enum',
        'constraint' => ['phone_number', 'mobile_number','email', 'facebook', 'twitter', 'instagram', 'whatsapp', 'skype', 'telegram', 'github','linkedin','other'],
        'default' => 'phone_number',
        'null' => false,             
      ],
      'contact_value' => [
        'type' => 'VARCHAR',
        'constraint' => 250,
        'null' => false,
      ],
    ]);
    
    $this->forge->addKey('id', true);//pk
    $this->forge->addForeignKey('users_id', $this->tables['users'], 'id'); //fk
    $this->forge->createTable('data_contact_users', true); //creamos la tabla
    $this->db->enableForeignKeyChecks();
  }

    public function down()
    {
      $this->db->disableForeignKeyChecks();
      $this->forge->dropTable('data_contact_users', true);        
      $this->db->enableForeignKeyChecks();
    }
}
