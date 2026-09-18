<?php

namespace App\Database\Migrations\HR;

use CodeIgniter\Database\Migration;

class HrContractVersioning extends Migration
{
    public function up(): void
    {
        // Versionado y árbol de contratos (fork)
        $this->forge->addColumn('hr_contracts', [
            'parent_contract_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'profile_id',
            ],
            'version' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
                'after'      => 'parent_contract_id',
            ],
            'change_source' => [
                'type'       => 'ENUM',
                'constraint' => ['auto', 'manual', 'fork'],
                'default'    => 'auto',
                'after'      => 'reason',
            ],
        ]);

        $this->db->query('ALTER TABLE hr_contracts ADD CONSTRAINT fk_hr_contracts_parent FOREIGN KEY (parent_contract_id) REFERENCES hr_contracts(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // Turnos base si el catálogo está vacío
        $count = $this->db->table('hr_cat_shifts')->countAllResults();
        if ($count === 0) {
            $now = date('Y-m-d H:i:s');
            $this->db->table('hr_cat_shifts')->insertBatch([
                [
                    'name'         => 'Matutino (L-V)',
                    'start_time'   => '08:00:00',
                    'end_time'     => '17:00:00',
                    'grace_period' => 10,
                    'work_days'    => '1,2,3,4,5',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'name'         => 'Vespertino (L-V)',
                    'start_time'   => '14:00:00',
                    'end_time'     => '22:00:00',
                    'grace_period' => 10,
                    'work_days'    => '1,2,3,4,5',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'name'         => 'Mixto (L-S)',
                    'start_time'   => '09:00:00',
                    'end_time'     => '18:00:00',
                    'grace_period' => 15,
                    'work_days'    => '1,2,3,4,5,6',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE hr_contracts DROP FOREIGN KEY fk_hr_contracts_parent');
        $this->forge->dropColumn('hr_contracts', ['parent_contract_id', 'version', 'change_source']);
    }
}
