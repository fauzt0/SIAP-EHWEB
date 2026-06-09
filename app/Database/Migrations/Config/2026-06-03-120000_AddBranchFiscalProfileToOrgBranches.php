<?php

namespace App\Database\Migrations\Config;

use CodeIgniter\Database\Migration;

/**
 * Perfil fiscal/comercial por sucursal (emisión de facturas, OC, contabilidad futura).
 */
class AddBranchFiscalProfileToOrgBranches extends Migration
{
    public function up()
    {
        $this->forge->addColumn('org_branches', [
            'commercial_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'name',
            ],
            'legal_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'commercial_name',
            ],
            'tax_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'legal_name',
            ],
            'website_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'email',
            ],
        ]);

        // Nombre comercial = nombre operativo donde falte
        $this->db->query(
            'UPDATE org_branches SET commercial_name = name '
            . 'WHERE commercial_name IS NULL AND deleted_at IS NULL'
        );

        // Sucursal principal: copiar datos del perfil corporativo si existen
        $company = $this->db->table('org_company_profile')
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->getRow();

        if ($company) {
            $this->db->table('org_branches')
                ->where('is_main', 1)
                ->where('deleted_at IS NULL', null, false)
                ->update([
                    'commercial_name' => $company->commercial_name,
                    'legal_name'      => $company->legal_name,
                    'tax_id'          => $company->tax_id,
                    'phone'           => $company->primary_phone ?? null,
                    'email'           => $company->primary_email ?? null,
                    'website_url'     => $company->website_url,
                ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('org_branches', ['commercial_name', 'legal_name', 'tax_id', 'website_url']);
    }
}
