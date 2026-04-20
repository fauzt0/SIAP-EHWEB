<?php

namespace App\Database\Migrations\Users;

use CodeIgniter\Database\Migration;

class AddMissingSoftDeletesToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('audit_logs', [
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'date'],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'updated_at'],
        ]);
        
        $this->forge->addColumn('data_billing_users', [
            'created_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'phone'],
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'created_at'],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'updated_at'],
        ]);
        
        $this->forge->addColumn('data_customers_users', [
            'created_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'comments'],
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'created_at'],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'updated_at'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('audit_logs', ['updated_at', 'deleted_at']);
        $this->forge->dropColumn('data_billing_users', ['created_at', 'updated_at', 'deleted_at']);
        $this->forge->dropColumn('data_customers_users', ['created_at', 'updated_at', 'deleted_at']);
    }
}
