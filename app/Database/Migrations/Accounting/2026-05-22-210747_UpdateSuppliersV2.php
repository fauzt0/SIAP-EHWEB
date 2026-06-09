<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

class UpdateSuppliersV2 extends Migration
{
    public function up()
    {
        //agregamos el nuevo campo "supplier_type" a la tabla "fin_suppliers" para poder catalogar al tipo de proveedor que es.
        $this->forge->addColumn('fin_suppliers', [
            'supplier_type' => [
                'type' => 'ENUM',
                'after' => 'id',//loagregamos despues de la columna "id"
                'constraint' => ['infrastructure', 'hardware', 'services', 'administrative'],
                'default' => 'services',
            ],
        ]);        
    }

    public function down()
    {
        //eliminamos la columna "supplier_type" de la tabla "fin_suppliers"
        $this->forge->dropColumn('fin_suppliers', 'supplier_type');        

    }
}
