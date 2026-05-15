<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['name' => 'HP', 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'Dell', 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'Cisco', 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'Ubiquiti', 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'Genérico', 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'Especialistas Hosting', 'active' => 1, 'created_at' => date('Y-m-d H:i:s')],
        ];

        $this->db->table('catalog_brands')->insertBatch($data);
    }
}
