<?php

declare(strict_types=1);

/**
 * Prueba de integración (BD local): OC, plantilla recurrente y scheduler.
 * php tests/smoke_suppliers_integration.php
 */

$root = dirname(__DIR__);
chdir($root);
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('CI_DEBUG', true);
define('HOMEPATH', $root . DIRECTORY_SEPARATOR);
define('CONFIGPATH', $root . '/app/Config/');
define('PUBLICPATH', $root . '/public/');
require CONFIGPATH . 'Paths.php';
$paths = new Config\Paths();
define('APPPATH', realpath(rtrim($paths->appDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath(rtrim($paths->systemDirectory, '\\/')) . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath(rtrim($paths->writableDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
define('TESTPATH', $root . '/tests/');
define('CIPATH', realpath(SYSTEMPATH . '../') . DIRECTORY_SEPARATOR);
define('FCPATH', realpath(PUBLICPATH) . DIRECTORY_SEPARATOR);
define('SUPPORTPATH', TESTPATH . '_support/');
define('COMPOSER_PATH', $root . '/vendor/autoload.php');
define('VENDORPATH', $root . '/vendor/');
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootTest($paths);

use App\Models\Financial\FinPurchaseOrderModel;
use App\Models\Financial\FinRecurringExpenseModel;
use App\Services\RecurringExpenseScheduler;
use App\Services\SupplierRecurringBridge;
$failed = 0;

function int_ok(bool $cond, string $message): void
{
    global $failed;
    if ($cond) {
        fwrite(STDOUT, "  [OK]    {$message}\n");

        return;
    }

    $failed++;
    fwrite(STDERR, "  [FAIL]  {$message}\n");
}

$db = \Config\Database::connect();

$tables = [
    'fin_suppliers',
    'fin_purchase_orders',
    'fin_purchase_order_items',
    'fin_purchase_order_documents',
    'fin_recurring_expenses',
    'fin_expenses',
    'fin_expense_documents',
    'fin_supplier_categories',
];
foreach ($tables as $t) {
    int_ok($db->tableExists($t), "tabla {$t}");
}

$supplier = $db->table('fin_suppliers')->where('status', 1)->get(1)->getRow();
int_ok($supplier !== null, 'hay al menos un proveedor activo');

$poModel = new FinPurchaseOrderModel();
$poNumber = 'SMK-' . date('Ymd-His');
$poId = $poModel->createPurchaseOrderWithItems([
    'fin_supplier_id' => (int) $supplier->id,
    'org_branch_id'   => 1,
    'po_number'       => $poNumber,
    'status'          => 'draft',
    'issue_date'      => date('Y-m-d'),
    'currency'        => 'MXN',
    'total_amount'    => 1500,
    'notes'           => 'Smoke integración',
], [[
    'description'  => 'Servicio de prueba',
    'quantity'     => 1,
    'unit_price'   => 1500,
    'total_price'  => 1500,
]]);

int_ok(is_int($poId) && $poId > 0, 'crear OC con líneas');

$itemCount = $db->table('fin_purchase_order_items')->where('fin_purchase_order_id', $poId)->countAllResults();
int_ok($itemCount === 1, 'línea de OC persistida');

$recModel = new FinRecurringExpenseModel();
$tplId = $recModel->createForSupplier((int) $supplier->id, [
    'description'   => 'Internet smoke',
    'service_type'  => 'internet',
    'amount'        => 899,
    'currency'      => 'MXN',
    'frequency'     => 'monthly',
    'billing_day'   => 5,
    'start_date'    => date('Y-m-01'),
    'is_active'     => 1,
    'org_branch_id' => 1,
]);

int_ok(is_int($tplId) && $tplId > 0, 'plantilla recurrente creada');

$tpl = $recModel->find($tplId);
int_ok($tpl && ! empty($tpl->next_due_date), 'next_due_date en plantilla');

$sched = new RecurringExpenseScheduler();
$result = $sched->generate(date('Y-m-t', strtotime('+2 months')));
int_ok($result['created'] > 0, 'scheduler genera vencimientos');

$expCount = $db->table('fin_expenses')->where('recurring_expense_id', $tplId)->countAllResults();
int_ok($expCount > 0, 'fin_expenses con recurring_expense_id');

$hosting = SupplierRecurringBridge::hostingPayloadFromServer([
    'name'          => 'VPS test',
    'monthly_cost'  => 12.5,
    'currency'      => 'USD',
    'renewal_date'  => date('Y-m-15', strtotime('+1 month')),
], 'Interserver');
$bridgeResult = (new SupplierRecurringBridge())->syncTemplate(
    (int) $supplier->id,
    $hosting,
    null,
    true
);
int_ok($bridgeResult['success'] ?? false, 'SupplierRecurringBridge crea plantilla hosting');
int_ok(($bridgeResult['template_id'] ?? 0) > 0, 'template_id para plugin recurring_expense_id');

$crmControllers = is_dir(APPPATH . 'Controllers/CRM');
int_ok(! $crmControllers, 'CRM dedicado aún no existe (esperado)');

$catalog = $db->table('information_schema.tables')
    ->where('table_schema', $db->getDatabase())
    ->like('table_name', 'crm_', 'after')
    ->countAllResults();
int_ok($catalog === 0, 'sin tablas crm_* (módulo CRM no implementado)');

if ($failed > 0) {
    fwrite(STDERR, "{$failed} comprobaciones fallaron\n");
    exit(1);
}

fwrite(STDOUT, "Proveedores: integración local en verde\n");
exit(0);