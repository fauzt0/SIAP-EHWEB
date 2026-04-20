<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

class UpdateSuppliersAndFolios extends Migration
{
    public function up()
    {
        // Campos nuevos para fin_suppliers basados en TODO.md
        // NOTA: Los campos created_at, updated_at, deleted_at para soft_delete 
        // de ambas tablas YA FUERON CREADOS en la migración original CreateFinancialModule.
        $this->forge->addColumn('fin_suppliers', [
            'legal_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'id'],
            'contact_person' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'tax_id'],
            'contact_phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'contact_email'],
            'website' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'contact_phone'],
            'address' => ['type' => 'TEXT', 'null' => true, 'after' => 'website'],
            'bank_details' => ['type' => 'TEXT', 'null' => true, 'after' => 'address'],
            'notes' => ['type' => 'TEXT', 'null' => true, 'after' => 'bank_details'],
        ]);

        // Campos nuevos para fin_payment_folios
        $this->forge->addColumn('fin_payment_folios', [
            'receipt_route' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'status'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('fin_suppliers', ['legal_name', 'contact_person', 'contact_phone', 'website', 'address', 'bank_details', 'notes']);
        $this->forge->dropColumn('fin_payment_folios', 'receipt_route');
    }
}
