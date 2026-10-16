<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\PluginSystem\PluginBackupService;
use App\Libraries\PluginSystem\PluginManager;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;
use Throwable;

class PluginDoctor extends BaseCommand
{
    protected $group = 'Plugin';

    protected $name = 'plugin:doctor';

    protected $description = 'Verifica tablas, manifiestos, permisos y migraciones del sistema de plugins.';

    protected $usage = 'plugin:doctor';

    public function run(array $params)
    {
        $errors = 0;
        $warnings = 0;

        CLI::write('Plugin System — doctor', 'yellow');

        try {
            $db = db_connect();
            $db->query('SELECT 1');
        } catch (Throwable $e) {
            CLI::write('  [FAIL]  Sin conexión a la base de datos.', 'red');
            CLI::write('  1 errores, 0 advertencias');

            return EXIT_ERROR;
        }

        $tables = [
            'sys_plugins'            => ['class_dir', 'table_prefix'],
            'sys_plugin_permissions' => ['permission'],
            'sys_plugin_settings'    => ['key'],
            'sys_plugin_backups'     => ['class_dir'],
        ];

        $missing = [];
        foreach ($tables as $table => $columns) {
            if (! $db->tableExists($table)) {
                $missing[] = $table;
                continue;
            }

            $fields = $db->getFieldNames($table);
            foreach ($columns as $column) {
                if (! in_array($column, $fields, true)) {
                    $missing[] = $table . '.' . $column;
                }
            }
        }

        if ($missing === []) {
            CLI::write('  [OK]    Tablas sys_plugins, sys_plugin_permissions, sys_plugin_settings, sys_plugin_backups', 'green');
        } else {
            $errors++;
            CLI::write('  [FAIL]  Faltan: ' . implode(', ', $missing), 'red');
        }

        $installed = $db->tableExists('sys_plugins') ? PluginManager::installedRows() : [];
        CLI::write('  [OK]    ' . count($installed) . ' plugins instalados', 'green');

        foreach ($installed as $row) {
            if (preg_match('/^[A-Z][A-Za-z0-9]+$/', (string) ($row['class_dir'] ?? '')) !== 1) {
                $errors++;
                CLI::write('  [FAIL]  ' . ($row['slug'] ?? '?') . ': class_dir no es una carpeta válida', 'red');
                continue;
            }

            $path = ROOTPATH . 'plugins/' . $row['class_dir'];
            $file = $path . '/plugin.json';
            if (! is_file($file)) {
                $errors++;
                CLI::write('  [FAIL]  ' . ($row['slug'] ?? '?') . ' no tiene plugin.json en class_dir', 'red');
                continue;
            }

            $manifest = json_decode((string) file_get_contents($file), true);
            if (! is_array($manifest) || (string) ($manifest['class'] ?? '') !== (string) ($row['main_class'] ?? '')) {
                $errors++;
                CLI::write('  [FAIL]  ' . ($row['slug'] ?? '?') . ': main_class no coincide con el manifiesto', 'red');
                continue;
            }

            $manifestErrors = PluginManager::manifestErrors($manifest, $path);
            if ($manifestErrors !== []) {
                $errors++;
                CLI::write('  [FAIL]  ' . ($row['slug'] ?? '?') . ': ' . $manifestErrors[0], 'red');
            }

            $prefix = (string) (($row['table_prefix'] ?? '') ?: ($manifest['table_prefix'] ?? ''));
            $overflow = PluginBackupService::archiveNameOverflow(
                PluginBackupService::liveTablesForPrefix($prefix)
            );
            if ($overflow !== []) {
                $errors++;
                CLI::write('  [FAIL]  ' . ($row['slug'] ?? '?') . ': tablas demasiado largas para archivar (' . implode(', ', $overflow) . ')', 'red');
            }
        }

        $onDisk = PluginManager::onDiskManifests();
        $invalidDisk = 0;
        foreach ($onDisk as $entry) {
            if (! $entry['readable']) {
                $invalidDisk++;
                $warnings++;
                CLI::write('  [WARN]  Manifiesto ilegible en ' . basename($entry['path']), 'yellow');
                continue;
            }

            $directory = (string) ($entry['manifest']['directory'] ?? '');
            if ($directory !== basename($entry['path'])) {
                $invalidDisk++;
                $warnings++;
                CLI::write('  [WARN]  directory no coincide con la carpeta ' . basename($entry['path']), 'yellow');
                continue;
            }

            $manifestErrors = PluginManager::manifestErrors($entry['manifest'], $entry['path']);
            if ($manifestErrors !== []) {
                $invalidDisk++;
                $warnings++;
                CLI::write('  [WARN]  ' . basename($entry['path']) . ': ' . $manifestErrors[0], 'yellow');
            }

            $hooks = $entry['manifest']['hooks'] ?? [];
            if (is_array($hooks)) {
                foreach ($hooks as $hook) {
                    if (! in_array($hook, ['alert.detect', 'layout.footer'], true)) {
                        $warnings++;
                        CLI::write('  [WARN]  Hook reservado o desconocido en ' . ($entry['manifest']['slug'] ?? basename($entry['path'])) . ': ' . $hook, 'yellow');
                    }
                }
            }
        }

        CLI::write('  [OK]    ' . count($onDisk) . ' manifiestos en disco', 'green');

        if ($db->tableExists('sys_plugin_permissions') && $db->tableExists('sys_plugins')) {
            $declared = [];
            foreach ($installed as $row) {
                if (preg_match('/^[A-Z][A-Za-z0-9]+$/', (string) ($row['class_dir'] ?? '')) !== 1) {
                    continue;
                }

                $file = ROOTPATH . 'plugins/' . $row['class_dir'] . '/plugin.json';
                if (! is_file($file)) {
                    continue;
                }
                $manifest = json_decode((string) file_get_contents($file), true);
                foreach ((is_array($manifest) ? ($manifest['permissions'] ?? []) : []) as $permission => $description) {
                    $declared[$row['slug'] . '|' . $permission] = true;
                }
            }

            $stored = [];
            foreach ($db->table('sys_plugin_permissions')->get()->getResultArray() as $permissionRow) {
                $stored[$permissionRow['plugin_slug'] . '|' . $permissionRow['permission']] = true;
            }

            $diff = array_merge(array_diff_key($declared, $stored), array_diff_key($stored, $declared));
            if ($diff === []) {
                CLI::write('  [OK]    Permisos registrados coherentes', 'green');
            } else {
                $errors++;
                CLI::write('  [FAIL]  Permisos de plugins desalineados con los manifiestos instalados (' . count($diff) . ')', 'red');
            }
        }

        CLI::write('  [OK]    Hooks v1 disponibles: alert.detect, layout.footer', 'green');

        foreach ($installed as $row) {
            if (preg_match('/^[A-Z][A-Za-z0-9]+$/', (string) ($row['class_dir'] ?? '')) !== 1) {
                continue;
            }

            $path = ROOTPATH . 'plugins/' . $row['class_dir'];
            $migrationsDir = $path . '/Database/Migrations';
            if (! is_dir($migrationsDir)) {
                continue;
            }

            try {
                $namespace = 'Plugins\\' . $row['class_dir'];
                Services::autoloader()->addNamespace($namespace, $path);
                $runner = Services::migrations(null, null, false);
                $runner->setNamespace($namespace);
                $found = $runner->findNamespaceMigrations($namespace);
                if ($found === []) {
                    $errors++;
                    CLI::write('  [FAIL]  ' . $row['slug'] . ': hay archivos de migración y no se descubren', 'red');
                    continue;
                }

                $history = $db->table('migrations')->where('namespace', $namespace)->get()->getResultArray();
                $applied = [];
                foreach ($history as $historyRow) {
                    $applied[(string) $historyRow['class']] = true;
                }

                $pending = 0;
                foreach ($found as $migration) {
                    if (! isset($applied[(string) $migration->class])) {
                        $pending++;
                    }
                }

                if ($pending > 0) {
                    $warnings++;
                    CLI::write('  [WARN]  ' . $row['slug'] . ': ' . $pending . ' migración(es) pendiente(s). Ejecuta plugin:action update.', 'yellow');
                }
            } catch (Throwable $e) {
                $errors++;
                CLI::write('  [FAIL]  migraciones de ' . $row['slug'] . ': ' . $e->getMessage(), 'red');
            }
        }

        CLI::write('  ' . $errors . ' errores, ' . $warnings . ' advertencias');

        return $errors > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
