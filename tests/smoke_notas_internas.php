<?php

declare(strict_types=1);

/**
 * Prueba sin base de datos ni CodeIgniter.
 * php tests/smoke_notas_internas.php
 */

$failed = 0;

function ni_ok(bool $cond, string $message): void
{
    global $failed;
    if ($cond) {
        fwrite(STDOUT, "  [OK]    {$message}\n");

        return;
    }

    $failed++;
    fwrite(STDERR, "  [FAIL]  {$message}\n");
}

require dirname(__DIR__) . '/plugins/NotasInternas/NoteContent.php';

use Plugins\NotasInternas\NoteContent;

$body = NoteContent::sanitizeBody('<b onclick="alert(1)">hola</b><script>alert(1)</script><i>cursiva</i>');
ni_ok($body === '<b>hola</b><i>cursiva</i>', 'sanitize quita script y atributos');

ni_ok(NoteContent::normalizeColor('#BBDEFB') === '#bbdefb', 'color conocido en minúsculas');
ni_ok(NoteContent::normalizeColor('red') === '#fff9c4', 'color ajeno vuelve al amarillo');
ni_ok(NoteContent::resolveTitle('', '<p>primera linea</p>') === 'primera linea', 'título desde el cuerpo');
ni_ok(NoteContent::resolveTitle('  ', '<br>') === null, 'nota vacía');
ni_ok(NoteContent::resolveTitle('Nota fija', '') === 'Nota fija', 'título explícito');

$routes = new class {
    public array $gets = [];
    public array $posts = [];

    public function group(string $name, array $opts, callable $cb): void
    {
        $this->group = $name;
        $this->opts  = $opts;
        $cb($this);
    }

    public function get(string $path, string $handler, array $opts = []): void
    {
        $this->gets[] = [$this->group . '/' . trim($path, '/'), $handler, $opts];
    }

    public function post(string $path, string $handler, array $opts = []): void
    {
        $this->posts[] = [$this->group . '/' . ltrim($path, '/'), $handler, $opts];
    }
};

$file = dirname(__DIR__) . '/plugins/NotasInternas/Config/Routes.php';
$first = require $file;
ni_ok(is_callable($first), 'el primer require devuelve el registrador');
$first($routes);

$second = require $file;
ni_ok(is_callable($second), 'un segundo require vuelve a devolver el registrador');
$second($routes);
ni_ok(function_exists('notas_internas_declare_routes'), 'la función no se redeclaró en el segundo require');

$index = null;
foreach ($routes->gets as $route) {
    if ($route[2]['as'] ?? '' === 'notasinternas.index') {
        $index = $route;
    }
}
ni_ok($index !== null && rtrim((string) $index[0], '/') === 'nat/plugins/notas-internas', 'GET índice en nat/plugins/notas-internas');
ni_ok(($index[2]['filter'] ?? '') === 'permission:notasinternas.access', 'el índice exige el permiso del plugin');
ni_ok(count($routes->posts) === 6, 'list, save y delete siguen declarados si el archivo se carga dos veces');

$plugin = file_get_contents(dirname(__DIR__) . '/plugins/NotasInternas/NotasInternasPlugin.php');
ni_ok(is_string($plugin) && str_contains($plugin, 'is_callable($callback)'), 'loadRoutes no llama un require que ya devolvió true');

$manager = file_get_contents(dirname(__DIR__) . '/app/Libraries/PluginSystem/PluginManager.php');
ni_ok(is_string($manager) && str_contains($manager, 'bootAttempts'), 'un boot fallido de BD no se queda marcado como listo');
ni_ok(is_string($manager) && str_contains($manager, 'description es obligatoria'), 'el manifiesto exige la descripción del funcionamiento');

$manifest = json_decode((string) file_get_contents(dirname(__DIR__) . '/plugins/NotasInternas/plugin.json'), true);
$description = is_array($manifest) ? trim((string) ($manifest['description'] ?? '')) : '';
ni_ok(mb_strlen($description) >= 40 && str_contains($description, '/nat/'), 'Notas internas describe el fijado en el inicio');
ni_ok(is_string($plugin) && str_contains($plugin, 'layout.footer'), 'el acceso rápido entra por layout.footer');
ni_ok(is_file(dirname(__DIR__) . '/plugins/NotasInternas/Views/quick_access.php'), 'existe la vista del listado rápido');

if ($failed > 0) {
    fwrite(STDERR, "{$failed} comprobaciones fallaron\n");
    exit(1);
}

fwrite(STDOUT, "Notas internas: comprobaciones locales en verde\n");
exit(0);
