<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'company_id'  => 1,
                'name'        => 'Sucursal Matriz',
                'branch_code' => 'MAT01',
                'is_main'     => 1,
                'active'      => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'company_id'  => 1,
                'name'        => 'Sucursal Norte',
                'branch_code' => 'NOR02',
                'is_main'     => 0,
                'active'      => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'company_id'  => 1,
                'name'        => 'Bodega Central',
                'branch_code' => 'BOD03',
                'is_main'     => 0,
                'active'      => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        // Usando el query builder para insertar
        $this->db->table('org_branches')->insertBatch($data);
    }
}
