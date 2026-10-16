<?php

declare(strict_types=1);

namespace App\Libraries\PluginSystem;

use RuntimeException;

/**
 * Archivado, restauración y purga.
 * No instancia la clase del plugin: la purga funciona sin sus archivos.
 */
class PluginBackupService
{
    /**
     * @return array{backup_id: ?int, backup_file: ?string, archived_tables: array}
     */
    public static function archive(array $manifest, string $pluginPath): array
    {
        unset($pluginPath);

        $slug   = (string) ($manifest['slug'] ?? '');
        $prefix = (string) ($manifest['table_prefix'] ?? '');

        if (! self::isSafeSlug($slug) || ! self::isSafePrefix($prefix)) {
            throw new RuntimeException('Manifiesto inseguro: slug o table_prefix rechazados.');
        }

        $db     = db_connect();
        $stamp  = self::uniqueStamp($slug);
        $tables = self::tablesForPrefix($prefix);

        $backupDir = WRITEPATH . 'plugins/backups/';
        if (! is_dir($backupDir) && ! mkdir($backupDir, 0750, true) && ! is_dir($backupDir)) {
            throw new RuntimeException('No se pudo crear el directorio de respaldos.');
        }

        $backupName = $slug . '_' . $stamp . '.sql';
        $backupFile = $backupDir . $backupName;
        $relative   = 'plugins/backups/' . $backupName;
        $archived   = [];

        foreach ($tables as $table) {
            $archived[] = '_bak_' . $table . '_' . $stamp;
        }

        $db->table('sys_plugin_backups')->insert([
            'plugin_slug'     => $slug,
            'plugin_name'     => mb_substr((string) ($manifest['name'] ?? $slug), 0, 150),
            'plugin_version'  => mb_substr((string) ($manifest['version'] ?? '0.0.0'), 0, 20),
            'class_dir'       => (string) $manifest['directory'],
            'backup_file'     => $tables === [] ? null : $relative,
            'archived_tables' => json_encode($archived, JSON_UNESCAPED_UNICODE),
            'backup_size_kb'  => 0,
            'uninstalled_at'  => date('Y-m-d H:i:s'),
            'uninstalled_by'  => self::actorId(),
        ]);
        $backupId = (int) $db->insertID();

        try {
            if ($tables !== []) {
                file_put_contents($backupFile, self::dumpSql($db, $tables, $manifest));
                chmod($backupFile, 0640);
                $db->table('sys_plugin_backups')->where('id', $backupId)->update([
                    'backup_size_kb' => (int) max(1, (int) ceil(filesize($backupFile) / 1024)),
                ]);

                $parts = [];
                foreach ($tables as $i => $table) {
                    $parts[] = '`' . $table . '` TO `' . $archived[$i] . '`';
                }
                $db->query('RENAME TABLE ' . implode(', ', $parts));
            }
        } catch (\Throwable $e) {
            $db->table('sys_plugin_backups')->where('id', $backupId)->delete();
            if (is_file($backupFile)) {
                unlink($backupFile);
            }

            throw $e;
        }

        return [
            'backup_id'       => $backupId,
            'backup_file'     => $tables === [] ? null : $backupFile,
            'archived_tables' => $archived,
        ];
    }

    /**
     * Devuelve las tablas al nombre original y deja el plugin inactive.
     *
     * @return array{success: bool, message: string}
     */
    public static function restore(int $backupId): array
    {
        $db     = db_connect();
        $backup = $db->table('sys_plugin_backups')->where('id', $backupId)->get()->getRowArray();

        if (! $backup) {
            return ['success' => false, 'message' => 'Respaldo no encontrado.'];
        }

        if (! empty($backup['purged_at'])) {
            return ['success' => false, 'message' => 'Este respaldo ya fue purgado.'];
        }

        if (! empty($backup['restored_at'])) {
            return ['success' => false, 'message' => 'Este respaldo ya fue restaurado.'];
        }

        if ($db->table('sys_plugins')->where('slug', $backup['plugin_slug'])->countAllResults()) {
            return ['success' => false, 'message' => 'El plugin ya está instalado. No se restaura encima.'];
        }

        $classDir = (string) ($backup['class_dir'] ?? '');
        if (! preg_match('/^[A-Z][A-Za-z0-9]+$/', $classDir)) {
            return ['success' => false, 'message' => 'class_dir del respaldo no es válido.'];
        }

        $pluginPath   = self::pluginDirectory($classDir);
        $manifestFile = $pluginPath . DIRECTORY_SEPARATOR . 'plugin.json';

        if ($pluginPath === null || ! is_file($manifestFile)) {
            return ['success' => false, 'message' => 'Faltan los archivos del plugin. Súbelos antes de restaurar.'];
        }

        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        $errors   = PluginManager::manifestErrors(is_array($manifest) ? $manifest : [], $pluginPath);

        if ($errors !== [] || ! is_array($manifest)) {
            return ['success' => false, 'message' => 'El manifiesto del plugin no pasa la validación.'];
        }

        if (($manifest['slug'] ?? '') !== $backup['plugin_slug']) {
            return ['success' => false, 'message' => 'El slug del manifiesto no coincide con el respaldo.'];
        }

        $prefix   = (string) $manifest['table_prefix'];
        $archived = json_decode((string) ($backup['archived_tables'] ?? '[]'), true) ?: [];
        $parts    = [];
        $back     = [];

        foreach ($archived as $name) {
            if (! is_string($name) || ! self::isArchiveName($name, $prefix)) {
                return ['success' => false, 'message' => 'El respaldo contiene un nombre de tabla no permitido.'];
            }

            if (preg_match('/^_bak_(.+)_\d{8}_\d{6}$/', $name, $matches) !== 1) {
                return ['success' => false, 'message' => 'No se pudo leer el nombre original de una tabla archivada.'];
            }

            $original = $matches[1];
            if (! str_starts_with($original, $prefix) || ! self::isIdentifier($original)) {
                return ['success' => false, 'message' => 'Tabla original fuera del prefijo del plugin.'];
            }

            $parts[]         = '`' . $name . '` TO `' . $original . '`';
            $back[$original] = $name;
        }

        if ($parts !== []) {
            $db->query('RENAME TABLE ' . implode(', ', $parts));
        }

        try {
            $db->table('sys_plugins')->insert([
                'slug'         => $manifest['slug'],
                'name'         => $manifest['name'],
                'version'      => $manifest['version'],
                'main_class'   => $manifest['class'],
                'class_dir'    => $classDir,
                'description'  => $manifest['description'] ?? '',
                'icon'         => self::safeIcon((string) ($manifest['icon'] ?? '')),
                'status'       => 'inactive',
                'installed_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            if ($back !== []) {
                $revert = [];
                foreach ($back as $original => $archiveName) {
                    $revert[] = '`' . $original . '` TO `' . $archiveName . '`';
                }
                $db->query('RENAME TABLE ' . implode(', ', $revert));
            }

            log_message('error', '[PluginSystem] restore rollback: {message}', ['message' => $e->getMessage()]);

            return ['success' => false, 'message' => 'No se pudo registrar el plugin restaurado. Las tablas siguen archivadas.'];
        }

        PluginManager::registerPermissions($manifest);
        self::markMigrationsApplied('Plugins\\' . $manifest['directory'], $pluginPath);

        $db->table('sys_plugin_backups')->where('id', $backupId)->update([
            'restored_at' => date('Y-m-d H:i:s'),
            'restored_by' => self::actorId(),
        ]);

        return ['success' => true, 'message' => "Plugin '{$manifest['name']}' restaurado. Actívalo para usarlo."];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function purge(int $backupId): array
    {
        $db     = db_connect();
        $backup = $db->table('sys_plugin_backups')->where('id', $backupId)->get()->getRowArray();

        if (! $backup) {
            return ['success' => false, 'message' => 'Respaldo no encontrado.'];
        }

        if (! empty($backup['purged_at'])) {
            return ['success' => false, 'message' => 'Este respaldo ya fue purgado.'];
        }

        if ($db->table('sys_plugins')->where('slug', $backup['plugin_slug'])->countAllResults()) {
            return ['success' => false, 'message' => 'No puedes purgar un plugin que está instalado.'];
        }

        $prefix = self::prefixFromBackup($backup);
        if ($prefix === null) {
            return ['success' => false, 'message' => 'No se pudo confirmar el prefijo de tablas del respaldo.'];
        }

        foreach (json_decode((string) ($backup['archived_tables'] ?? '[]'), true) ?: [] as $table) {
            if (! is_string($table) || ! self::isArchiveName($table, $prefix)) {
                return ['success' => false, 'message' => 'El respaldo contiene un nombre de tabla no permitido.'];
            }

            $db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }

        self::deleteBackupFile($backup['backup_file'] ?? null);

        if (self::isSafeSlug((string) $backup['plugin_slug'])) {
            $others = $db->table('sys_plugin_backups')
                ->where('plugin_slug', $backup['plugin_slug'])
                ->where('id !=', $backupId)
                ->where('purged_at', null)
                ->countAllResults();

            if ($others === 0) {
                $db->table('sys_plugin_settings')->where('plugin_slug', $backup['plugin_slug'])->delete();
                $db->table('sys_plugin_permissions')->where('plugin_slug', $backup['plugin_slug'])->delete();
            }
        }

        $db->table('sys_plugin_backups')->where('id', $backupId)->update([
            'purged_at' => date('Y-m-d H:i:s'),
            'purged_by' => self::actorId(),
            'notes'     => trim((string) ($backup['notes'] ?? '') . "\nDatos purgados definitivamente."),
        ]);

        return ['success' => true, 'message' => "Datos del plugin '{$backup['plugin_name']}' eliminados definitivamente."];
    }

    /**
     * @return list<string>
     */
    private static function tablesForPrefix(string $prefix): array
    {
        $others = [];
        foreach (self::knownPrefixes() as $other) {
            if ($other !== $prefix) {
                $others[] = $other;
            }
        }

        $tables = [];
        foreach (db_connect()->listTables() as $table) {
            if (! is_string($table) || ! self::isIdentifier($table) || ! str_starts_with($table, $prefix)) {
                continue;
            }

            $ownedByLonger = false;
            foreach ($others as $other) {
                if (strlen($other) > strlen($prefix) && str_starts_with($table, $other)) {
                    $ownedByLonger = true;
                    break;
                }
            }

            if (! $ownedByLonger) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * @return list<string>
     */
    private static function knownPrefixes(): array
    {
        $prefixes = [];
        $root     = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'plugins';

        foreach (glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            $file = $dir . DIRECTORY_SEPARATOR . 'plugin.json';
            if (! is_file($file)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($file), true);
            $prefix   = is_array($manifest) ? (string) ($manifest['table_prefix'] ?? '') : '';
            if (self::isSafePrefix($prefix)) {
                $prefixes[] = $prefix;
            }
        }

        return array_values(array_unique($prefixes));
    }

    private static function prefixFromBackup(array $backup): ?string
    {
        $classDir = (string) ($backup['class_dir'] ?? '');
        $path     = self::pluginDirectory($classDir);

        if ($path !== null) {
            $manifest = json_decode((string) file_get_contents($path . DIRECTORY_SEPARATOR . 'plugin.json'), true);
            $prefix   = is_array($manifest) ? (string) ($manifest['table_prefix'] ?? '') : '';
            if (self::isSafePrefix($prefix)) {
                return $prefix;
            }
        }

        $archived = json_decode((string) ($backup['archived_tables'] ?? '[]'), true) ?: [];
        foreach ($archived as $name) {
            if (is_string($name) && preg_match('/^_bak_(plg_[a-z0-9]+_).+_\d{8}_\d{6}$/', $name, $matches) === 1) {
                return $matches[1];
            }
        }

        if ($archived === []) {
            return 'plg_none_';
        }

        return null;
    }

    /**
     * @param list<string> $tables
     */
    private static function dumpSql($db, array $tables, array $manifest): string
    {
        $sql  = '-- Plugin backup: ' . $manifest['slug'] . ' | ' . date('Y-m-d H:i:s') . "\n";
        $sql .= '-- Plugin version: ' . ($manifest['version'] ?? '') . "\n\n";

        foreach ($tables as $table) {
            $create = $db->query('SHOW CREATE TABLE `' . $table . '`')->getRowArray();
            $sql   .= (string) array_values($create)[1] . ";\n\n";

            $rows = $db->query('SELECT * FROM `' . $table . '`')->getResultArray();
            if ($rows === []) {
                continue;
            }

            $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
            $values  = [];
            foreach ($rows as $row) {
                $cells = [];
                foreach ($row as $column => $value) {
                    if (! self::isIdentifier((string) $column)) {
                        throw new RuntimeException('Columna con nombre no permitido en el respaldo.');
                    }
                    $cells[] = $value === null ? 'NULL' : $db->escape($value);
                }
                $values[] = '(' . implode(', ', $cells) . ')';
            }

            $sql .= 'INSERT INTO `' . $table . '` (' . $columns . ") VALUES\n" . implode(",\n", $values) . ";\n\n";
        }

        return $sql;
    }

    private static function deleteBackupFile(mixed $relative): void
    {
        if (! is_string($relative) || $relative === '') {
            return;
        }

        if (preg_match('#^plugins/backups/[a-z0-9]+(?:-[a-z0-9]+)*_\d{8}_\d{6}\.sql$#', $relative) !== 1) {
            log_message('error', '[PluginSystem] backup_file rechazado: {file}', ['file' => $relative]);

            return;
        }

        $path = WRITEPATH . $relative;
        $root = realpath(WRITEPATH . 'plugins/backups');
        $real = realpath($path);

        if ($root === false || $real === false || ! is_file($real)) {
            return;
        }

        if (! str_starts_with($real, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            return;
        }

        unlink($real);
    }

    private static function pluginDirectory(string $directory): ?string
    {
        if (preg_match('/^[A-Z][A-Za-z0-9]+$/', $directory) !== 1) {
            return null;
        }

        $root = realpath(ROOTPATH . 'plugins');
        $path = realpath(ROOTPATH . 'plugins' . DIRECTORY_SEPARATOR . $directory);

        if ($root === false || $path === false || ! is_dir($path)) {
            return null;
        }

        $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (! str_starts_with($path, $prefix) || dirname($path) !== $root) {
            return null;
        }

        return $path;
    }

    private static function isSafeSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1 && strlen($slug) <= 100;
    }

    private static function isSafePrefix(string $prefix): bool
    {
        return preg_match('/^plg_[a-z0-9]+_$/', $prefix) === 1 && strlen($prefix) <= 32;
    }

    private static function isIdentifier(string $name): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $name) === 1 && strlen($name) <= 64;
    }

    private static function isArchiveName(string $name, string $prefix): bool
    {
        if ($prefix === 'plg_none_') {
            return false;
        }

        if (! self::isIdentifier($name) || ! self::isSafePrefix($prefix)) {
            return false;
        }

        return preg_match('/^_bak_' . preg_quote($prefix, '/') . '[A-Za-z0-9_]*_\d{8}_\d{6}$/', $name) === 1;
    }

    /**
     * Tras restaurar, las tablas ya existen. El historial se borró al desinstalar;
     * sin reponerlo, la siguiente actualización intentaría crearlas de nuevo.
     */
    private static function markMigrationsApplied(string $namespace, string $pluginPath): void
    {
        if (! is_dir($pluginPath . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations')) {
            return;
        }

        \Config\Services::autoloader()->addNamespace($namespace, $pluginPath);

        $runner = \Config\Services::migrations(null, null, false);
        $runner->setNamespace($namespace);
        $found = $runner->findNamespaceMigrations($namespace);
        if ($found === []) {
            return;
        }

        $db    = db_connect();
        $batch = ((int) ($db->table('migrations')->selectMax('batch', 'batch')->get()->getRow()->batch ?? 0)) + 1;

        foreach ($found as $migration) {
            $exists = $db->table('migrations')->where([
                'namespace' => $namespace,
                'class'     => $migration->class,
                'version'   => $migration->version,
            ])->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $db->table('migrations')->insert([
                'version'   => $migration->version,
                'class'     => $migration->class,
                'group'     => 'default',
                'namespace' => $namespace,
                'time'      => time(),
                'batch'     => $batch,
            ]);
        }
    }

    private static function uniqueStamp(string $slug): string
    {
        $dir = WRITEPATH . 'plugins/backups/';

        do {
            $stamp = date('Ymd_His');
            $taken = is_file($dir . $slug . '_' . $stamp . '.sql');
            if ($taken) {
                sleep(1);
            }
        } while ($taken);

        return $stamp;
    }

    private static function safeIcon(string $icon): string
    {
        return preg_match('/^fa-[a-z0-9-]+$/', $icon) === 1 ? $icon : 'fa-plug';
    }

    private static function actorId(): int
    {
        try {
            if (! function_exists('auth')) {
                return 0;
            }

            $id = auth()->id();

            return is_numeric($id) ? (int) $id : 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}
