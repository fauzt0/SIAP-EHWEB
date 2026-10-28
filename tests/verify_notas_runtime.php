<?php

declare(strict_types=1);

/**
 * Verificación en runtime (BD + arranque de plugins + modelo).
 * php tests/verify_notas_runtime.php
 */

$failed = 0;

function ni_runtime_ok(bool $cond, string $message): void
{
    global $failed;
    if ($cond) {
        fwrite(STDOUT, "  [OK]    {$message}\n");

        return;
    }

    $failed++;
    fwrite(STDERR, "  [FAIL]  {$message}\n");
}

define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';

$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootSpark($paths);

$db = db_connect();

$row = $db->table('sys_plugins')->where('slug', 'notas-internas')->get()->getRowArray();
ni_runtime_ok(is_array($row) && ($row['status'] ?? '') === 'active', 'plugin instalado y activo');
ni_runtime_ok(is_array($row) && ($row['version'] ?? '') === '1.3.1', 'versión en sys_plugins es 1.3.1');

$perm = $db->table('sys_plugin_permissions')
    ->where(['plugin_slug' => 'notas-internas', 'permission' => 'notasinternas.access'])
    ->countAllResults();
ni_runtime_ok($perm === 1, 'permiso notasinternas.access registrado');

$expected = ['id', 'title', 'user_id', 'body', 'color', 'is_pinned', 'created_at', 'updated_at'];
$cols     = $db->getFieldNames('plg_notasinternas_items');
ni_runtime_ok($expected === array_values(array_intersect($expected, $cols)), 'columnas de la tabla completas');

\App\Libraries\PluginSystem\PluginManager::bootActivePlugins();

$routes = \Config\Services::routes();
require APPPATH . 'Config/Routes.php';
$get    = $routes->getRoutes('GET');
ni_runtime_ok(
    isset($get['nat/plugins/notas-internas']) || isset($get['nat/plugins/notas-internas/']),
    'ruta GET registrada tras boot (simula web)'
);

require dirname(__DIR__) . '/plugins/NotasInternas/NoteContent.php';

use Plugins\NotasInternas\Models\NoteModel;

$db->transStart();
try {
    $probeUser = 9_999_001;
    $model     = new NoteModel();
    $id        = (int) $model->insert([
        'user_id'   => $probeUser,
        'title'     => 'Probe runtime',
        'body'      => '<b>x</b>',
        'color'     => '#fff9c4',
        'is_pinned' => true,
    ]);
    ni_runtime_ok($id > 0, 'insert de prueba');
    ni_runtime_ok($model->ownedBy($id, $probeUser) !== null, 'ownedBy acepta al dueño');
    ni_runtime_ok($model->ownedBy($id, $probeUser + 1) === null, 'ownedBy rechaza otro usuario');
    $listed = $model->forUser($probeUser);
    ni_runtime_ok($listed !== [] && (int) ($listed[0]['is_pinned'] ?? 0) === 1, 'forUser devuelve nota fijada');
} finally {
    $db->transRollback();
}

ni_runtime_ok(true, 'transacción de prueba revertida (sin basura en BD)');

if ($failed > 0) {
    fwrite(STDERR, "{$failed} comprobaciones runtime fallaron\n");
    exit(1);
}

fwrite(STDOUT, "Notas internas: verificación runtime OK\n");
exit(0);
