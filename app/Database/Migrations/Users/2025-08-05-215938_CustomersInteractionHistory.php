<?php

namespace App\Database\Migrations\Users;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CustomersInteractionHistory extends Migration
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
        'interaction_type' => [
          'type' => 'ENUM', //email, call, meeting, visit, whatsapp, telegram
          'constraint' => ['email', 'call', 'meeting', 'visit', 'whatsapp', 'telegram', 'skype','teams','zoom','ticket','other'],
          'default' => 'email',
          'null' => false,          
        ],            
        'interaction_status' => [
          'type' => 'ENUM', //pending, done, canceled
          'constraint' => ['pending', 'done', 'canceled','progress','scheduled','other'],  
          'default' => 'pending',
          'null' => false,          
        ],
        'interaction_date' => [
          'type' => 'DATETIME',          
          'null' => true,
        ],
        'created_at' => [
          'type' => 'DATETIME',
          'null' => false,
        ],
        'updated_at' => [
          'type' => 'DATETIME',
          'null' => false,
        ],
        'history' => [
          'type' => 'TEXT',
          'null' => true,
        ],
      ]);
      $this->forge->addKey('id', true);
      $this->forge->addForeignKey('users_id', $this->tables['users'], 'id');//fk        
      $this->forge->createTable('customers_interaction_history', true);
      
      //creamos la tabla de adjuntos
      $this->forge->addField([
        'id' => [
          'type' => 'INT',
          'constraint' => 5,
          'unsigned' => true, 
          'auto_increment' => true,                                       
          'null' => false,
        ],
        'interaction_id' => [
          'type' => 'INT',
          'constraint' => 5,
          'unsigned' => true, 
          'null' => false,
        ],
        'file_name' => [
          'type' => 'VARCHAR',
          'constraint' => 255,
          'null' => false,
        ],
        'file_type' => [
          'type' => 'VARCHAR',
          'constraint' => 50,
          'null' => false,
        ],
        'file_size' => [
          'type' => 'DECIMAL  ',
          'constraint' => '15,2',
          'null' => false,
        ],
        'file_path' => [
          'type' => 'VARCHAR',
          'constraint' => 255,
          'null' => false,
        ],
        'created_at' => [
          'type' => 'DATETIME',
          'null' => false,
        ],
        'updated_at' => [
          'type' => 'DATETIME',
          'null' => false,
        ],
        'deleted_at' => [
          'type' => 'DATETIME',
          'null' => true,
        ],
      ]);
       
      $this->forge->addKey('id', true);
      $this->forge->addForeignKey('interaction_id', 'customers_interaction_history', 'id');//fk        
      $this->forge->createTable('history_attachments', true);

      $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        //
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('customers_interaction_history', true);        
        $this->forge->dropTable('history_attachments', true);        
        $this->db->enableForeignKeyChecks();
    }
}
