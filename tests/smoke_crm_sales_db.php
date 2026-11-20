<?php

declare(strict_types=1);

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
define('FCPATH', realpath($root . '/public/') . DIRECTORY_SEPARATOR);
define('SUPPORTPATH', TESTPATH . '_support/');
define('COMPOSER_PATH', $root . '/vendor/autoload.php');
define('VENDORPATH', $root . '/vendor/');
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootTest($paths);

use App\Models\CRM\CrmClientModel;
use App\Models\CRM\CrmFollowupModel;
use App\Models\Sales\SalesOrderModel;
use App\Models\Sales\SalesQuoteModel;
use App\Services\ClientCommerceBridge;

$failed = 0;
function db_ok(bool $cond, string $message): void
{
    global $failed;
    if ($cond) {
        fwrite(STDOUT, "OK  {$message}\n");
        return;
    }
    $failed++;
    fwrite(STDERR, "FAIL {$message}\n");
}

$db = db_connect();
db_ok($db->tableExists('crm_clients'), 'tabla crm_clients');
db_ok($db->tableExists('crm_followups'), 'tabla crm_followups');
db_ok($db->tableExists('sales_quotes'), 'tabla sales_quotes');
db_ok($db->tableExists('sales_orders'), 'tabla sales_orders');
db_ok($db->fieldExists('client_id', 'sales_orders'), 'sales_orders.client_id');
db_ok($db->tableExists('crm_plugin_links'), 'tabla crm_plugin_links');

$clientModel = new CrmClientModel();
$clientId = $clientModel->createWithRelated([
    'company_name' => 'Cliente Demo CRM',
    'status'       => 'lead',
    'email'        => 'demo.crm@example.com',
    'active'       => 1,
], [
    ['contact_type' => 'buyer', 'name' => 'Ana Buyer', 'email' => 'ana@example.com'],
], [
    ['legal_name' => 'Cliente Demo SA', 'rfc_tax_id' => 'CDM010101XXX', 'tax_regime' => '601'],
]);
db_ok($clientId !== false && $clientId > 0, 'alta cliente con contactos y fiscal');

$fuId = (new CrmFollowupModel())->createVersion([
    'client_id'     => (int) $clientId,
    'title'         => 'Primer contacto',
    'comment'       => 'Llamada inicial',
    'followup_type' => 'call',
    'change_source' => 'manual',
    'status'        => 'active',
]);
db_ok($fuId !== false, 'bitácora de seguimiento v1');

$fu2 = (new CrmFollowupModel())->createVersion([
    'client_id'          => (int) $clientId,
    'parent_followup_id' => (int) $fuId,
    'title'              => 'Seguimiento comercial',
    'comment'            => 'Revisión en árbol',
    'followup_type'      => 'comment',
    'change_source'      => 'fork',
    'status'             => 'active',
    'client_status_snapshot' => 'prospect',
]);
db_ok($fu2 !== false && $fu2 !== $fuId, 'revisión fork v2');
$tree = (new CrmFollowupModel())->getTree((int) $clientId);
db_ok(count($tree) >= 2, 'árbol de seguimiento con 2 nodos');

$quoteId = (new SalesQuoteModel())->createWithItems([
    'client_id' => (int) $clientId,
    'status'    => 'draft',
    'notes'     => 'Demo',
], [
    ['item_name' => 'Hosting', 'unit_price' => 100, 'quantity' => 1, 'billing_cycle' => 'monthly'],
]);
db_ok($quoteId !== false, 'cotización con ciclo mensual');

$orderId = (new SalesOrderModel())->createWithItems([
    'client_id' => (int) $clientId,
    'status'    => 'confirmed',
    'channel'   => 'backend',
], [
    ['item_name' => 'Soporte anual', 'unit_price' => 1200, 'quantity' => 1, 'billing_cycle' => 'annual', 'period_start' => date('Y-m-d')],
]);
db_ok($orderId !== false, 'orden con servicio anual');

$tabs = (new ClientCommerceBridge())->collectClientTabs((int) $clientId);
db_ok(is_array($tabs), 'collectClientTabs no rompe sin plugins');

if ($failed > 0) {
    fwrite(STDERR, "{$failed} comprobaciones fallaron\n");
    exit(1);
}
fwrite(STDOUT, "CRM/Ventas: integración BD en verde\n");
exit(0);
