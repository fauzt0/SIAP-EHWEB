<?php

namespace App\Commands;

use App\Models\HR\WorkerContractModel;
use App\Services\ContractService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class VerifyHrContracts extends BaseCommand
{
    protected $group       = 'HR';
    protected $name        = 'hr:verify-contracts';
    protected $description = 'Verificación one-off post-parche versionado contratos';

    public function run(array $params)
    {
        $db = db_connect();

        foreach (['version', 'change_source'] as $col) {
            $row = $db->query("SHOW COLUMNS FROM hr_contracts LIKE '$col'")->getRowArray();
            CLI::write("$col Null={$row['Null']} Default={$row['Default']}");
        }

        CLI::write('--- actives per profile ---');
        $actives = $db->query(
            "SELECT profile_id, SUM(status='active') AS actives, COUNT(*) AS total
             FROM hr_contracts WHERE deleted_at IS NULL GROUP BY profile_id"
        )->getResultArray();
        CLI::print(json_encode($actives, JSON_PRETTY_PRINT));

        CLI::write('--- profile 1 chain ---');
        $p1 = $db->query(
            "SELECT id, version, parent_contract_id, status FROM hr_contracts
             WHERE profile_id = 1 AND deleted_at IS NULL ORDER BY version"
        )->getResultArray();
        CLI::print(json_encode($p1, JSON_PRETTY_PRINT));

        $service = new ContractService();
        $oldEmp  = (object) [
            'current_salary' => '15750.00', 'daily_salary' => '500.00',
            'department_id' => 1, 'job_id' => 1, 'location_id' => null,
            'hiring_date' => '2024-01-01', 'payroll_type' => 'quincenal',
            'worker_type' => 'planta', 'corporate_email' => '',
        ];
        $newData = [
            'current_salary' => 15750.0, 'daily_salary' => 500.0,
            'department_id' => 1, 'job_id' => 1, 'location_id' => null,
            'hiring_date' => '2024-01-01', 'payroll_type' => 'quincenal',
            'worker_type' => 'planta', 'corporate_email' => '',
        ];
        $oldProfile = (object) array_fill_keys(ContractService::PROFILE_CONTRACT_FIELDS, '');
        $f1         = $service->hasContractRelevantChanges($oldProfile, $oldEmp, [], $newData);
        CLI::write('F1 same salary: ' . ($f1 ? 'FAIL' : 'PASS'));

        $model          = new WorkerContractModel();
        $activeBefore   = $model->getActiveContract(1);
        $activeIdBefore = $activeBefore ? $activeBefore->id : null;
        $failInsert     = $model->createVersion([
            'profile_id'       => 1,
            'content_snapshot' => '',
            'status'           => 'active',
            'change_source'    => 'manual',
        ]);
        $activeAfter   = $model->getActiveContract(1);
        $activeIdAfter = $activeAfter ? $activeAfter->id : null;
        CLI::write('F3 insert false: ' . ($failInsert === false ? 'PASS' : 'FAIL'));
        CLI::write('F3 active preserved: ' . ($activeIdBefore === $activeIdAfter ? 'PASS' : 'FAIL'));
    }
}
