<?php

namespace App\Database\Migrations\HR;

use CodeIgniter\Database\Migration;

class HrContractVersioningFix extends Migration
{
    public function up(): void
    {
        $db = $this->db;

        // 1. Backfill NULLs antes de NOT NULL
        $db->query("UPDATE hr_contracts SET version = 1 WHERE version IS NULL");
        $db->query("UPDATE hr_contracts SET change_source = 'auto' WHERE change_source IS NULL");

        // 2. Por profile_id: recalcular version (1..N) y cadena parent_id por created_at ASC
        $profiles = $db->query(
            "SELECT DISTINCT profile_id FROM hr_contracts WHERE deleted_at IS NULL"
        )->getResultArray();

        foreach ($profiles as $row) {
            $profileId = (int) $row['profile_id'];

            $contracts = $db->query(
                "SELECT id FROM hr_contracts
                 WHERE profile_id = ? AND deleted_at IS NULL
                 ORDER BY created_at ASC, id ASC",
                [$profileId]
            )->getResultArray();

            $prevId = null;
            foreach ($contracts as $i => $c) {
                $version  = $i + 1;
                $parentId = $prevId;
                $db->query(
                    "UPDATE hr_contracts SET version = ?, parent_contract_id = ? WHERE id = ?",
                    [$version, $parentId, $c['id']]
                );
                $prevId = (int) $c['id'];
            }

            // 3. Un solo active: el de mayor created_at (desempate id DESC)
            $active = $db->query(
                "SELECT id FROM hr_contracts
                 WHERE profile_id = ? AND deleted_at IS NULL
                 ORDER BY created_at DESC, id DESC LIMIT 1",
                [$profileId]
            )->getRow();

            if ($active) {
                $db->query(
                    "UPDATE hr_contracts SET status = 'archived'
                     WHERE profile_id = ? AND deleted_at IS NULL AND id != ?",
                    [$profileId, $active->id]
                );
                $db->query(
                    "UPDATE hr_contracts SET status = 'active' WHERE id = ?",
                    [$active->id]
                );
            }
        }

        // 4. NOT NULL en version y change_source
        $db->query(
            "ALTER TABLE hr_contracts
             MODIFY COLUMN version INT(11) UNSIGNED NOT NULL DEFAULT 1"
        );
        $db->query(
            "ALTER TABLE hr_contracts
             MODIFY COLUMN change_source ENUM('auto','manual','fork') NOT NULL DEFAULT 'auto'"
        );

        // 5. Índice compuesto (parent ya cubierto por FK fk_hr_contracts_parent)
        $indexExists = $db->query("SHOW INDEX FROM hr_contracts WHERE Key_name = 'idx_hr_contracts_profile_version'")->getResult();
        if (empty($indexExists)) {
            $db->query('CREATE INDEX idx_hr_contracts_profile_version ON hr_contracts (profile_id, version)');
        }
    }

    public function down(): void
    {
        $db = $this->db;

        $indexExists = $db->query("SHOW INDEX FROM hr_contracts WHERE Key_name = 'idx_hr_contracts_profile_version'")->getResult();
        if (! empty($indexExists)) {
            $db->query('DROP INDEX idx_hr_contracts_profile_version ON hr_contracts');
        }

        $db->query(
            "ALTER TABLE hr_contracts
             MODIFY COLUMN version INT(11) UNSIGNED NULL DEFAULT 1"
        );
        $db->query(
            "ALTER TABLE hr_contracts
             MODIFY COLUMN change_source ENUM('auto','manual','fork') NULL DEFAULT 'auto'"
        );
    }
}
