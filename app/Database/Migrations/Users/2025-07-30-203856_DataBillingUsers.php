<?php
/**
 * Crea una nueva tabla llamada data_billing_users, la cual permite guardar los datos de pago o facturacion  de los usuarios que son clientes(en la tabla users en el campo type como customers)
 */
namespace App\Database\Migrations\Users;
use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class DataBillingUsers extends Migration
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
  
  //creamos la tabla data_billing_customers, si no existe con los siguientes valores;
  public function up()
  {//
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
      'tax_id' => [ //RFC
        'type' => 'VARCHAR',
        'constraint' => 50,
        'null' => false,                
      ],
      'trade_name' => [ //Razon social
        'type' => 'VARCHAR',
        'constraint' => 250,
        'null' => true,                
      ],
      'company_name' => [ //Nombre de la empresa o comercial
        'type' => 'VARCHAR',
        'constraint' => 250,
        'null' => true,
      ],
      'address' => [
        'type' => 'VARCHAR',
        'constraint' => 250,
        'null' => true,
      ],
      'zip' => [
        'type' => 'VARCHAR',
        'constraint' => 15,
        'null' => true,
      ],
      'state' => [
        'type' => 'VARCHAR',
        'constraint' => 250,
        'null' => true,
      ],
      'city' => [
        'type' => 'VARCHAR',
        'constraint' => 250,
        'null' => true,
      ],
      'municipality' => [
        'type' => 'VARCHAR',
        'constraint' => 250,
        'null' => true,
      ],
      'country' => [
        'type' => 'VARCHAR',
        'constraint' => 250,
        'null' => true,
      ],
      'email' => [
        'type' => 'VARCHAR',
        'constraint' => 150,
        'null' => true,
      ],
      'phone' => [
        'type' => 'VARCHAR',
        'constraint' => 50,
        'null' => true,
      ],      
    ]);

    $this->forge->addKey('id', true);//pk
    $this->forge->addForeignKey('users_id', $this->tables['users'], 'id');//fk        
    $this->forge->addKey('tax_id',false,true);//uk    
    $this->forge->addKey('trade_name');//key    
    $this->forge->addKey('company_name');//key    
    $this->forge->createTable('data_billing_users', true); //creamos la tabla    
    $this->db->enableForeignKeyChecks();
  }

  public function down()
  {
    $this->db->disableForeignKeyChecks();
    $this->forge->dropTable('data_billing_users', true);        
    $this->db->enableForeignKeyChecks();
  }
}
