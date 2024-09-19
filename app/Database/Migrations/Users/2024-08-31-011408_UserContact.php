<?php
namespace App\Database\Migrations\Users;
use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class UserContact extends Migration
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
    
    public function up()
    {
      //creamos la tabla user_contact si no existe con los valores id,user_id,contact_source, contact_value
      // pk id, fk user_id
      $this->db->disableForeignKeyChecks();
      $this->forge->addField([
        'id' => [
            'type' => 'INT',
            'constraint' => 11,
            'unsigned' => true, 
            'auto_increment' => true,                                       
            'null' => false,
        ],
        'user_id' => [
            'type' => 'INT',
            'constraint' => 11,
            'unsigned' => true, 
            'null' => false,
        ],
        'contact_source' => [
            'type' => 'enum',
            'constraint' => ['phone_number', 'mobile_number','email', 'facebook', 'twitter', 'instagram', 'whatsapp', 'skype', 'telegram', 'github','linkedin','other'],
            'default' => 'phone_number',
            'null' => true,             
        ],
        'contact_value' => [
            'type' => 'VARCHAR',
            'constraint' => 250,
        ],
      ]);
      
      $this->forge->addKey('id', true);//pk
      $this->forge->addForeignKey('user_id', $this->tables['users'], 'id');
      $this->forge->createTable('user_contact', true);
      $this->db->enableForeignKeyChecks();
    }

    public function down()
    {      
      $this->db->disableForeignKeyChecks();
      $this->forge->dropTable('user_contact', true);        
      $this->db->enableForeignKeyChecks();
    }
}
