<?php

namespace App\Database\Migrations\Config;

use CodeIgniter\Database\Migration;

/**
 * Documentos legales por sucursal: CSF, comprobante de domicilio y acta constitutiva.
 */
class AddLegalDocumentPathsToOrgBranches extends Migration
{
    public function up()
    {
        $this->forge->addColumn('org_branches', [
            'csf_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'logo_path',
            ],
            'address_proof_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'csf_path',
            ],
            'incorporation_act_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'address_proof_path',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('org_branches', [
            'csf_path',
            'address_proof_path',
            'incorporation_act_path',
        ]);
    }
}
