<?php
/**
 * Crea una nueva tabla llamada data_customer_users, la cual permite guardar los datos de  contacto de los usuarios que son clientes(en la tabla users en el campo type)
 */
namespace App\Database\Migrations\Users;
use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class DataCustomerUsers extends Migration
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

  /**
   *creamos la tabla data_customers_users, si no existe con los siguientes valores
   */  
  public function up()
  { //
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
      'origin_branch_id' => [ //se agrego para saber de que sucursal es el cliente, en caso de no tener sucursal se deja null y se atribuye a la tienda en linea
        'type' => 'INT',
        'constraint' => 5,
        'unsigned' => true, 
        'null' => true,
      ],
      'customer_type' => [
        'type' => 'ENUM', 
        'constraint' => ['customer','lead','reseller'], 
        'default' => 'customer',
        'null' => true,
      ],
      'company_branch' => [
        'type' => 'enum',
        'constraint' => ['especialistas hosting', 'especialistas web','mekonecta'],
        'default' => 'especialistas hosting',
        'null' => false,             
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
        'constraint' => 100,
        'null' => true,
      ],
      'comments' => [
        'type' => 'text',        
        'null' => true,
      ],
    ]);
    
    $this->forge->addKey('id', true);//pk
    $this->forge->addForeignKey('users_id', $this->tables['users'], 'id');//fk    
    //$this->forge->addKey('company_branch');//key    
    $this->forge->addForeignKey('origin_branch_id', 'org_branches', 'id', 'CASCADE', 'CASCADE');
    $this->forge->createTable('data_customers_users', true); //creamos la tabla    
    $this->db->enableForeignKeyChecks();
  }

  public function down()
  {
    $this->db->disableForeignKeyChecks();
    $this->forge->dropTable('data_customers_users', true);        
    $this->db->enableForeignKeyChecks();
  }
}
