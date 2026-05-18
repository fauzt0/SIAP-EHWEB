<?php

namespace App\Database\Migrations\Catalog; // Se puede dejar en Catalog o crear un folder Organization

use CodeIgniter\Database\Migration;

class AddLogoPathToOrgBranches extends Migration
{
    public function up()
    {
        $fields = [
            'logo_path' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'pos_printer_name', // Opcional, pero ayuda al orden visual
            ],
        ];

        $this->forge->addColumn('org_branches', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('org_branches', 'logo_path');
    }
}
