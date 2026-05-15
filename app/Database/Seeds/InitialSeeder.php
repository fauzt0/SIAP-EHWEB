<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run()
    {
        // 1. Empresa
        $this->db->table('org_company_profile')->insert([
            'commercial_name' => 'Especialistas Hosting',
            'primary_email'   => 'admin@especialistashosting.com',
            'primary_phone'   => '5555555555',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $companyId = $this->db->insertID();

        // 2. Sucursales
        $branches = [
            [
                'company_id'  => $companyId,
                'name'        => 'Sucursal Matriz',
                'branch_code' => 'MAT01',
                'is_main'     => 1,
                'active'      => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'company_id'  => $companyId,
                'name'        => 'Sucursal Norte',
                'branch_code' => 'NOR02',
                'is_main'     => 0,
                'active'      => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
        ];
        $this->db->table('org_branches')->insertBatch($branches);
    }
}
