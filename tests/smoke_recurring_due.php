<?php

declare(strict_types=1);

/**
 * Próximo vencimiento de un servicio recurrente, sin base de datos.
 * php tests/smoke_recurring_due.php
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

use App\Models\Financial\FinRecurringExpenseModel as M;

$failed = 0;

function due_ok(bool $cond, string $message): void
{
    global $failed;
    if ($cond) {
        fwrite(STDOUT, "  [OK]    {$message}\n");

        return;
    }
    $failed++;
    fwrite(STDERR, "  [FAIL]  {$message}\n");
}

$due = M::computeNextDueDate([
    'frequency'   => 'monthly',
    'billing_day' => 15,
    'start_date'  => '2026-11-01',
], '2026-11-11');
due_ok($due === '2026-11-15', 'mensual, día 15 todavía no llega');

$due = M::computeNextDueDate([
    'frequency'   => 'monthly',
    'billing_day' => 1,
    'start_date'  => '2026-11-11',
], '2026-11-11');
due_ok($due === '2026-12-01', 'el día 1 de este mes ya pasó');

$due = M::computeNextDueDate([
    'frequency'       => 'weekly',
    'billing_weekday' => 3,
    'start_date'      => '2026-11-01',
], '2026-11-11');
due_ok($due === '2026-11-11', 'semanal en miércoles');

$due = M::computeNextDueDate([
    'frequency'   => 'monthly',
    'billing_day' => 20,
    'start_date'  => '2026-11-01',
    'end_date'    => '2026-11-10',
], '2026-11-11');
due_ok($due === null, 'no hay cobro si el fin es anterior');

due_ok(isset(M::SERVICE_TYPES['phone'], M::SERVICE_TYPES['internet'], M::SERVICE_TYPES['hosting']), 'telefonía, internet y hosting');

exit($failed === 0 ? 0 : 1);
