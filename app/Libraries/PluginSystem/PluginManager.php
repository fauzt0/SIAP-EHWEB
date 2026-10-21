<?php

declare(strict_types=1);

namespace App\Libraries\PluginSystem;

use CodeIgniter\Shield\Entities\User;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * Registro, arranque y ciclo de vida de los plugins instalados.
 */
class PluginManager
{
    /** @var array<string, PluginInterface> */
    private static array $loadedPlugins = [];

    private static bool $booted = false;

    /** Intentos de leer sys_plugins en esta petición. Un fallo temprano no se queda pegado. */
    private static int $bootAttempts = 0;

    /** @var array<string, array<string, mixed>> */
    private static array $failedRows = [];

    private static bool $retriedFailures = false;

    /**
     * Catálogo de AuthGroups.php antes de fusionar permisos de plugins.
     * Si se lee después de la fusión, el permiso del propio plugin parece del ERP
     * y el boot lo rechaza: la ruta existe en spark y da 404 en la web.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $basePermissions = null;

    /** @var list<array{path: string, manifest: array, raw: mixed, readable: bool}>|null */
    private static ?array $onDiskCache = null;

    public static function bootActivePlugins(): void
    {
        if (self::$booted || self::$bootAttempts >= 2) {
            return;
        }

        self::$bootAttempts++;

        try {
            $db = db_connect();
            if (! $db->tableExists('sys_plugins')) {
                self::$booted = true;

                return;
            }

            $plugins = $db->table('sys_plugins')
                ->where('status', 'active')
                ->get()
                ->getResultArray();
        } catch (Throwable $e) {
            log_message('error', '[PluginSystem] boot abortado: {message}', [
                'message' => $e->getMessage(),
            ]);

            return;
        }

        self::$booted = true;
        self::bootRows($plugins);
    }

    /**
     * pre_system puede correr antes de que la BD responda. loadRoutes() reintenta
     * una vez; si no, el plugin queda activo en CLI (spark routes) y 404 en la web.
     */
    private static function ensureBooted(): void
    {
        self::bootActivePlugins();
        if (! self::$booted) {
            self::bootActivePlugins();
        }

        if (self::$retriedFailures || self::$failedRows === []) {
            return;
        }

        self::$retriedFailures = true;
        $rows = self::$failedRows;
        self::$failedRows = [];
        self::bootRows($rows);
    }

    /**
     * @param list<array<string, mixed>> $plugins
     */
    private static function bootRows(array $plugins): void
    {
        foreach ($plugins as $row) {
            $slug = (string) ($row['slug'] ?? '');

            try {
                if (! self::isSlug($slug)) {
                    throw new RuntimeException('Slug registrado no válido.');
                }

                $instance = self::instantiateRow($row);
                $instance->registerHooks();
                self::$loadedPlugins[$slug] = $instance;
                unset(self::$failedRows[$slug]);
            } catch (Throwable $e) {
                if ($slug !== '') {
                    self::$failedRows[$slug] = $row;
                }

                log_message('error', '[PluginSystem] no se cargó {slug}: {message}', [
                    'slug'    => $slug !== '' ? $slug : '?',
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param \CodeIgniter\Router\RouteCollection $routes
     */
    public static function loadRoutes($routes): void
    {
        self::ensureBooted();

        foreach (self::$loadedPlugins as $slug => $plugin) {
            try {
                $plugin->loadRoutes($routes);
            } catch (Throwable $e) {
                log_message('error', '[PluginSystem] rutas de {slug} fallaron: {message}', [
                    'slug'    => $slug,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    public static function renderSidebar(?User $user): string
    {
        if ($user === null) {
            return '';
        }

        self::ensureBooted();

        $html = '';
        foreach (self::$loadedPlugins as $slug => $plugin) {
            try {
                $items = $plugin->getSidebarItems($user);
                if ($items === []) {
                    continue;
                }

                $html .= view('PluginSystem/sidebar_section', ['items' => $items]);
            } catch (Throwable $e) {
                log_message('error', '[PluginSystem] sidebar de {slug} falló: {message}', [
                    'slug'    => $slug,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $html;
    }

    public static function renderAssets(): string
    {
        self::ensureBooted();

        $html = '';
        foreach (self::$loadedPlugins as $slug => $plugin) {
            try {
                $manifest = $plugin->getManifest();
                if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $slug) !== 1) {
                    continue;
                }

                $version  = preg_replace('/[^A-Za-z0-9._-]/', '', (string) ($manifest['version'] ?? '1')) ?: '1';
                $assetDir = realpath(FCPATH . 'plugins' . DIRECTORY_SEPARATOR . $slug);

                foreach (['css' => 'css/plugin.css', 'js' => 'js/plugin.js'] as $kind => $relative) {
                    $public = 'plugins/' . $slug . '/' . $relative;
                    $file   = realpath(FCPATH . $public);
                    if ($assetDir === false || $file === false || ! is_file($file)) {
                        continue;
                    }

                    $assetPrefix = rtrim($assetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                    if (! str_starts_with($file, $assetPrefix)) {
                        continue;
                    }

                    $url = esc(base_url($public) . '?v=' . rawurlencode($version), 'attr');
                    if ($kind === 'css') {
                        $html .= '<link rel="stylesheet" href="' . $url . '">' . "\n";
                    } else {
                        $html .= '<script src="' . $url . '"></script>' . "\n";
                    }
                }
            } catch (Throwable $e) {
                log_message('error', '[PluginSystem] assets de {slug} fallaron: {message}', [
                    'slug'    => $slug,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $html;
    }

    public static function runAlertDetectors(User $user): array
    {
        self::ensureBooted();

        $collected = PluginHookRegistry::doActionCollect('alert.detect', $user);
        $alerts    = [];

        foreach ($collected as $item) {
            if ($item instanceof \stdClass) {
                $alerts[] = $item;
                continue;
            }

            if (is_object($item) && ! $item instanceof \Throwable) {
                $alerts[] = $item;
                continue;
            }

            if (is_array($item) && (isset($item['title']) || isset($item['message']))) {
                $alerts[] = (object) $item;
            }
        }

        return $alerts;
    }

    /**
     * Catálogo que consume la UI de usuarios: ERP + plugins instalados.
     *
     * @return array<string, string>
     */
    public static function permissionCatalog(): array
    {
        $permissions = setting('AuthGroups.permissions');
        if (! is_array($permissions)) {
            $permissions = [];
        }

        try {
            $db = db_connect();
            if (! $db->tableExists('sys_plugin_permissions')) {
                return $permissions;
            }

            foreach ($db->table('sys_plugin_permissions')->get()->getResultArray() as $row) {
                $name = (string) ($row['permission'] ?? '');
                if ($name === '' || array_key_exists($name, $permissions)) {
                    continue;
                }

                $permissions[$name] = '[Plugin] ' . (string) ($row['description'] ?? $name);
            }
        } catch (Throwable $e) {
            log_message('error', '[PluginSystem] catálogo de permisos: {message}', [
                'message' => $e->getMessage(),
            ]);
        }

        return $permissions;
    }

    /**
     * @return array{success: bool, message: string, backup_id?: int}
     */
    public static function install(string $slug): array
    {
        $matches = [];
        foreach (self::onDiskManifests() as $entry) {
            if (($entry['manifest']['slug'] ?? '') === $slug) {
                $matches[] = $entry;
            }
        }
        if (count($matches) > 1) {
            return ['success' => false, 'message' => "Hay más de una carpeta con el slug '{$slug}'. Deja una sola."];
        }

        $found = $matches[0] ?? null;
        if ($found === null) {
            return ['success' => false, 'message' => 'No hay un plugin con ese slug en disco.'];
        }

        $errors = self::manifestErrors($found['manifest'], $found['path']);
        if ($errors !== []) {
            return ['success' => false, 'message' => $errors[0]];
        }

        $db = db_connect();
        if ($db->table('sys_plugins')->where('slug', $slug)->countAllResults()) {
            return ['success' => false, 'message' => 'Ese plugin ya está instalado.'];
        }

        $pending = $db->table('sys_plugin_backups')
            ->where('plugin_slug', $slug)
            ->where('purged_at', null)
            ->where('restored_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if ($pending) {
            return [
                'success'   => false,
                'message'   => 'Hay un respaldo pendiente. Restaura esos datos en lugar de instalar de cero.',
                'backup_id' => (int) $pending['id'],
            ];
        }

        $requires = $found['manifest']['requires'] ?? [];
        if (is_array($requires)) {
            foreach ($requires as $required) {
                $required = (string) $required;
                $active   = $db->table('sys_plugins')
                    ->where(['slug' => $required, 'status' => 'active'])
                    ->countAllResults();
                if ($active === 0) {
                    return ['success' => false, 'message' => "Falta el plugin requerido '{$required}' activo."];
                }
            }
        }

        try {
            $instance = self::instantiateManifest($found['manifest'], $found['path']);
            if (! $instance->install()) {
                return ['success' => false, 'message' => 'La instalación no pudo crear las tablas del plugin.'];
            }

            $manifest = $found['manifest'];
            $overflow = PluginBackupService::archiveNameOverflow(
                PluginBackupService::liveTablesForPrefix((string) ($manifest['table_prefix'] ?? ''))
            );
            if ($overflow !== []) {
                self::removePermissions($slug);

                return [
                    'success' => false,
                    'message' => 'Las tablas creadas superan ' . PluginBackupService::MAX_LIVE_TABLE_NAME . ' caracteres y no se podrían desinstalar: ' . implode(', ', $overflow),
                ];
            }

            $db->table('sys_plugins')->insert([
                'slug'         => $manifest['slug'],
                'name'         => $manifest['name'],
                'version'      => $manifest['version'],
                'main_class'   => $manifest['class'],
                'class_dir'    => $manifest['directory'],
                'table_prefix' => $manifest['table_prefix'] ?? null,
                'description'  => $manifest['description'] ?? '',
                'icon'         => self::safeIcon((string) ($manifest['icon'] ?? '')),
                'status'       => 'active',
                'installed_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            try {
                self::removePermissions($slug);
            } catch (Throwable) {
            }

            log_message('error', '[PluginManager::install] {message}', ['message' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Error al instalar el plugin. Revisa el log del sistema.'];
        }

        return ['success' => true, 'message' => "Plugin '{$found['manifest']['name']}' instalado."];
    }

    /**
     * @return array{success: bool, message: string, backup?: array}
     */
    public static function uninstall(string $slug): array
    {
        if (! self::isSlug($slug)) {
            return ['success' => false, 'message' => 'Slug no válido.'];
        }

        $db  = db_connect();
        $row = $db->table('sys_plugins')->where('slug', $slug)->get()->getRowArray();
        if (! $row) {
            return ['success' => false, 'message' => 'Plugin no encontrado.'];
        }

        if ($row['status'] === 'active') {
            return ['success' => false, 'message' => 'Debes desactivar el plugin antes de desinstalarlo.'];
        }

        $dependents = self::activeDependents($slug);
        if ($dependents !== []) {
            return ['success' => false, 'message' => 'Otros plugins activos lo requieren: ' . implode(', ', $dependents)];
        }

        try {
            $instance = self::instantiateRow($row);
            $manifest = $instance->getManifest();
            $storedPrefix = (string) ($row['table_prefix'] ?? '');
            if ($storedPrefix !== '' && preg_match('/^plg_[a-z0-9]+_$/', $storedPrefix) === 1) {
                $manifest['table_prefix'] = $storedPrefix;
            }

            $backup = PluginBackupService::archive($manifest, $instance->getPluginPath());
            $backup['success'] = true;

            $db->table('sys_plugins')->where('slug', $slug)->delete();

            try {
                self::removePermissions($slug);
                $instance->forgetMigrationHistory();
            } catch (Throwable $cleanup) {
                log_message('error', '[PluginManager::uninstall] limpieza: {message}', [
                    'message' => $cleanup->getMessage(),
                ]);
            }
        } catch (Throwable $e) {
            log_message('error', '[PluginManager::uninstall] {message}', ['message' => $e->getMessage()]);

            $detail = $e->getMessage();
            if (str_contains($detail, 'demasiado largas')) {
                return ['success' => false, 'message' => $detail];
            }

            return ['success' => false, 'message' => 'Error al desinstalar el plugin. Revisa el log del sistema.'];
        }

        $archived = $backup['archived_tables'] ?? [];
        if ($archived === []) {
            $message = "Plugin '{$row['name']}' desinstalado. No había tablas que archivar.";
        } else {
            $message = "Plugin '{$row['name']}' desinstalado. Datos respaldados.";
            if (! empty($backup['backup_file'])) {
                $message .= ' Backup: ' . basename((string) $backup['backup_file']);
            }
        }

        return ['success' => true, 'message' => $message, 'backup' => $backup];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function restore(int $backupId): array
    {
        try {
            return PluginBackupService::restore($backupId);
        } catch (Throwable $e) {
            log_message('error', '[PluginManager::restore] {message}', ['message' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Error al restaurar el respaldo. Revisa el log del sistema.'];
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function purge(int $backupId): array
    {
        try {
            return PluginBackupService::purge($backupId);
        } catch (Throwable $e) {
            log_message('error', '[PluginManager::purge] {message}', ['message' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Error al purgar el respaldo. Revisa el log del sistema.'];
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function activate(string $slug): array
    {
        $row = self::installedRow($slug);
        if ($row === null) {
            return ['success' => false, 'message' => 'Plugin no instalado.'];
        }

        try {
            $instance = self::instantiateRow($row);
            $instance->registerHooks();
        } catch (Throwable $e) {
            log_message('error', '[PluginManager::activate] {slug}: {message}', [
                'slug'    => $slug,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'El plugin no se puede activar: el manifiesto o registerHooks() fallan.'];
        }

        if (! self::setStatus($slug, 'active')) {
            return ['success' => false, 'message' => 'No se pudo activar el plugin.'];
        }

        return ['success' => true, 'message' => 'Plugin activado.'];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function deactivate(string $slug): array
    {
        $dependents = self::activeDependents($slug);
        if ($dependents !== []) {
            return ['success' => false, 'message' => 'Otros plugins activos lo requieren: ' . implode(', ', $dependents)];
        }

        if (! self::setStatus($slug, 'inactive')) {
            return ['success' => false, 'message' => 'No se pudo desactivar el plugin.'];
        }

        return ['success' => true, 'message' => 'Plugin desactivado.'];
    }

    /**
     * Corre las migraciones nuevas y actualiza la versión registrada.
     *
     * @return array{success: bool, message: string}
     */
    public static function update(string $slug): array
    {
        if (! self::isSlug($slug)) {
            return ['success' => false, 'message' => 'Slug no válido.'];
        }

        $db  = db_connect();
        $row = $db->table('sys_plugins')->where('slug', $slug)->get()->getRowArray();
        if (! $row) {
            return ['success' => false, 'message' => 'Plugin no instalado.'];
        }

        $path = self::directoryPath((string) $row['class_dir']);
        if ($path === null) {
            return ['success' => false, 'message' => 'No están los archivos del plugin en disco.'];
        }

        $manifestFile = $path . DIRECTORY_SEPARATOR . 'plugin.json';
        if (! is_file($manifestFile)) {
            return ['success' => false, 'message' => 'No están los archivos del plugin en disco.'];
        }

        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        $errors   = self::manifestErrors(is_array($manifest) ? $manifest : [], $path);
        if ($errors !== [] || ! is_array($manifest)) {
            return ['success' => false, 'message' => $errors[0] ?? 'Manifiesto inválido.'];
        }

        if ($manifest['slug'] !== $slug || $manifest['directory'] !== $row['class_dir']) {
            return ['success' => false, 'message' => 'El slug o la carpeta del manifiesto no coinciden con el plugin instalado.'];
        }

        $storedPrefix = (string) ($row['table_prefix'] ?? '');
        $newPrefix    = (string) ($manifest['table_prefix'] ?? '');
        if ($storedPrefix !== '' && $storedPrefix !== $newPrefix) {
            return ['success' => false, 'message' => 'No se puede cambiar table_prefix de un plugin instalado. Las tablas vivas quedarían sin respaldo.'];
        }

        try {
            $instance = self::instantiateManifest($manifest, $path);
            if (! $instance->install()) {
                return ['success' => false, 'message' => 'Las migraciones nuevas no se aplicaron.'];
            }

            $prefixToCheck = $storedPrefix !== '' ? $storedPrefix : $newPrefix;
            $overflow      = PluginBackupService::archiveNameOverflow(
                PluginBackupService::liveTablesForPrefix($prefixToCheck)
            );
            if ($overflow !== []) {
                return [
                    'success' => false,
                    'message' => 'Las tablas superan ' . PluginBackupService::MAX_LIVE_TABLE_NAME . ' caracteres y no se podrían desinstalar: ' . implode(', ', $overflow),
                ];
            }

            $db->table('sys_plugins')->where('slug', $slug)->update([
                'name'         => $manifest['name'],
                'version'      => $manifest['version'],
                'main_class'   => $manifest['class'],
                'table_prefix' => $prefixToCheck !== '' ? $prefixToCheck : null,
                'description'  => $manifest['description'] ?? '',
                'icon'         => self::safeIcon((string) ($manifest['icon'] ?? '')),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', '[PluginManager::update] {message}', ['message' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Error al actualizar el plugin. Revisa el log del sistema.'];
        }

        return ['success' => true, 'message' => "Plugin '{$manifest['name']}' actualizado a v{$manifest['version']}."];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function saveSettings(string $slug, array $input): array
    {
        $row = self::installedRow($slug);
        if ($row === null) {
            return ['success' => false, 'message' => 'Plugin no instalado.'];
        }

        try {
            $instance = self::instantiateRow($row);
            $manifest = $instance->getManifest();
            $defs     = is_array($manifest['settings'] ?? null) ? $manifest['settings'] : [];
            $current  = $instance->getConfig();
            $clean    = [];

            if ($input === []) {
                return ['success' => false, 'message' => 'No se envió ningún ajuste.'];
            }

            foreach ($input as $incoming => $unused) {
                unset($unused);
                if (! is_string($incoming) || $incoming === '') {
                    return ['success' => false, 'message' => 'Clave de ajuste vacía o no válida.'];
                }
                if (! array_key_exists($incoming, $defs)) {
                    return ['success' => false, 'message' => "El ajuste '{$incoming}' no está declarado en el plugin."];
                }
            }

            foreach ($defs as $key => $definition) {
                if (! is_string($key) || ! is_array($definition)) {
                    continue;
                }

                if (! array_key_exists($key, $input)) {
                    continue;
                }

                $type  = (string) ($definition['type'] ?? 'text');
                $value = $input[$key];

                if ($type === 'password' && ($value === null || $value === '')) {
                    continue;
                }

                if ($type === 'number') {
                    if ($value === '' || $value === null) {
                        $clean[$key] = $definition['default'] ?? null;
                        continue;
                    }
                    if (! is_numeric($value)) {
                        return ['success' => false, 'message' => "El ajuste '{$key}' debe ser numérico."];
                    }
                    $clean[$key] = (string) $value;
                    continue;
                }

                if ($type === 'select') {
                    $allowed = self::settingOptionValues($definition);
                    if (! in_array((string) $value, $allowed, true)) {
                        return ['success' => false, 'message' => "El ajuste '{$key}' no coincide con las opciones declaradas."];
                    }
                    $clean[$key] = (string) $value;
                    continue;
                }

                if (is_array($value)) {
                    return ['success' => false, 'message' => "El ajuste '{$key}' no admite listas."];
                }

                $clean[$key] = $value === null ? null : (string) $value;
            }

            unset($current);

            if ($clean !== [] && ! $instance->setConfig($clean)) {
                return ['success' => false, 'message' => 'No se pudo guardar la configuración. Si hay un ajuste password, revisa encryption.key.'];
            }
        } catch (Throwable $e) {
            log_message('error', '[PluginManager::saveSettings] {message}', ['message' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Error al guardar la configuración.'];
        }

        return ['success' => true, 'message' => 'Configuración guardada.'];
    }

    /**
     * @return array{row: array, manifest: array, values: array}|null
     */
    public static function readSettings(string $slug): ?array
    {
        $row = self::installedRow($slug);
        if ($row === null) {
            return null;
        }

        $instance = self::instantiateRow($row);
        $manifest = $instance->getManifest();
        $stored   = $instance->getConfig();
        $values   = $stored;
        $secrets  = [];

        foreach (($manifest['settings'] ?? []) as $key => $definition) {
            if (! is_array($definition) || ($definition['type'] ?? '') !== 'password') {
                continue;
            }

            $secrets[$key] = ($stored[$key] ?? null) !== null && (string) ($stored[$key] ?? '') !== '';
            $values[$key]  = '';
        }

        return [
            'row'      => $row,
            'manifest' => $manifest,
            'values'   => $values,
            'secrets'  => $secrets,
        ];
    }

    public static function registerPermissions(array $manifest): void
    {
        $errors = self::permissionErrors($manifest);
        if ($errors !== []) {
            throw new RuntimeException($errors[0]);
        }

        $db       = db_connect();
        $slug     = (string) $manifest['slug'];
        $declared = is_array($manifest['permissions'] ?? null) ? $manifest['permissions'] : [];
        $keep     = [];

        foreach ($declared as $permission => $description) {
            $permission  = (string) $permission;
            $description = mb_substr((string) $description, 0, 255);
            $keep[]      = $permission;

            $exists = $db->table('sys_plugin_permissions')
                ->where(['plugin_slug' => $slug, 'permission' => $permission])
                ->countAllResults() > 0;

            if ($exists) {
                $db->table('sys_plugin_permissions')
                    ->where(['plugin_slug' => $slug, 'permission' => $permission])
                    ->update(['description' => $description]);
            } else {
                $db->table('sys_plugin_permissions')->insert([
                    'plugin_slug' => $slug,
                    'permission'  => $permission,
                    'description' => $description,
                ]);
            }
        }

        $builder = $db->table('sys_plugin_permissions')->where('plugin_slug', $slug);
        if ($keep !== []) {
            $builder->whereNotIn('permission', $keep);
        }
        $builder->delete();
    }

    public static function removePermissions(string $slug): void
    {
        if (! self::isSlug($slug)) {
            throw new RuntimeException('Slug no válido al retirar permisos.');
        }

        db_connect()->table('sys_plugin_permissions')->where('plugin_slug', $slug)->delete();
    }

    /**
     * @return array<string, PluginInterface>
     */
    public static function getLoaded(): array
    {
        return self::$loadedPlugins;
    }

    public static function isActive(string $slug): bool
    {
        return isset(self::$loadedPlugins[$slug]);
    }

    /**
     * @return list<array>
     */
    public static function installedRows(): array
    {
        $db = db_connect();
        if (! $db->tableExists('sys_plugins')) {
            return [];
        }

        return $db->table('sys_plugins')->orderBy('name', 'ASC')->get()->getResultArray();
    }

    /**
     * @return list<array>
     */
    public static function backupRows(): array
    {
        $db = db_connect();
        if (! $db->tableExists('sys_plugin_backups')) {
            return [];
        }

        return $db->table('sys_plugin_backups')
            ->where('purged_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<string>
     */
    public static function manifestErrors(array $manifest, string $pluginPath, bool $checkNeighbors = true): array
    {
        $errors = [];

        $slug = (string) ($manifest['slug'] ?? '');
        if (! self::isSlug($slug)) {
            $errors[] = 'El slug del manifiesto no es válido.';
        }

        $directory = (string) ($manifest['directory'] ?? '');
        if (preg_match('/^[A-Z][A-Za-z0-9]+$/', $directory) !== 1 || strlen($directory) > 100) {
            $errors[] = 'El campo directory debe ser PascalCase, sin rutas.';
        }

        $real = realpath($pluginPath);
        $root = realpath(ROOTPATH . 'plugins');
        if ($real === false || $root === false || dirname($real) !== $root || basename($real) !== $directory) {
            $errors[] = 'La carpeta del plugin no está dentro de plugins/.';
        }

        $class = (string) ($manifest['class'] ?? '');
        $classPattern = '/^Plugins\\\\' . preg_quote($directory, '/') . '\\\\[A-Z][A-Za-z0-9]+$/';
        if ($directory !== '' && preg_match($classPattern, $class) !== 1) {
            $errors[] = 'La clase principal debe ser Plugins\\{Directory}\\Nombre y vivir en la raíz del plugin.';
        }

        if (strlen($class) > 255) {
            $errors[] = 'La clase principal excede 255 caracteres.';
        }

        $name = (string) ($manifest['name'] ?? '');
        if ($name === '' || strlen($name) > 150) {
            $errors[] = 'El nombre del plugin es obligatorio y de máximo 150 caracteres.';
        }

        $description = $manifest['description'] ?? '';
        $description = is_string($description) ? trim($description) : '';
        if ($description === '' || mb_strlen($description) < 40 || mb_strlen($description) > 1000) {
            $errors[] = 'description es obligatoria: explica cómo funciona el plugin, entre 40 y 1000 caracteres.';
        }

        $version = (string) ($manifest['version'] ?? '');
        if (preg_match('/^\d+\.\d+\.\d+$/', $version) !== 1 || strlen($version) > 20) {
            $errors[] = 'La versión debe ser SemVer (MAJOR.MINOR.PATCH).';
        }

        $prefix = (string) ($manifest['table_prefix'] ?? '');
        if (preg_match('/^plg_[a-z0-9]+_$/', $prefix) !== 1 || strlen($prefix) > 32) {
            $errors[] = 'table_prefix debe tener la forma plg_{identificador}_.';
        }

        if ($checkNeighbors) {
            $selfPath = realpath($pluginPath);
            foreach (self::onDiskManifests() as $other) {
                $otherPath = realpath($other['path']);
                if ($selfPath !== false && $otherPath === $selfPath) {
                    continue;
                }

                $otherSlug   = (string) ($other['manifest']['slug'] ?? '');
                $otherPrefix = (string) ($other['manifest']['table_prefix'] ?? '');
                if ($slug !== '' && $otherSlug === $slug) {
                    $errors[] = "El slug '{$slug}' ya está declarado en otra carpeta (" . basename((string) $other['path']) . ').';
                    break;
                }
                if ($prefix !== '' && $otherPrefix === $prefix) {
                    $errors[] = 'table_prefix ya lo usa otro plugin en disco.';
                    break;
                }
            }
        }

        $routePrefix = (string) ($manifest['route_prefix'] ?? '');
        if ($routePrefix !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $routePrefix) !== 1) {
            $errors[] = 'route_prefix no es válido.';
        }

        $errors = array_merge($errors, self::permissionErrors($manifest, $checkNeighbors));

        $settings = $manifest['settings'] ?? [];
        if ($settings !== [] && ! is_array($settings)) {
            $errors[] = 'settings debe ser un objeto.';
        } elseif (is_array($settings)) {
            foreach ($settings as $key => $definition) {
                if (preg_match('/^[a-z][a-z0-9_]{0,63}$/', (string) $key) !== 1 || ! is_array($definition)) {
                    $errors[] = 'Hay un ajuste con clave o definición inválida.';
                    break;
                }
            }
        }

        if (isset($manifest['settings_defaults'])) {
            $errors[] = 'Usa la clave settings. settings_defaults no es válido.';
        }

        if ($errors !== [] || $real === false) {
            return $errors;
        }

        try {
            if (! class_exists($class)) {
                return array_merge($errors, ['La clase principal no se puede cargar.']);
            }

            $reflection = new ReflectionClass($class);
            if (! $reflection->isInstantiable() || ! $reflection->isSubclassOf(BasePlugin::class)) {
                return array_merge($errors, ['La clase principal debe extender BasePlugin y poder instanciarse.']);
            }

            $file = $reflection->getFileName();
            $fileReal = is_string($file) ? realpath($file) : false;
            $prefixPath = rtrim($real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if ($fileReal === false || ! str_starts_with($fileReal, $prefixPath)) {
                return array_merge($errors, ['La clase principal no está dentro de la carpeta del plugin.']);
            }
        } catch (Throwable $e) {
            log_message('error', '[PluginSystem] clase de {slug}: {message}', [
                'slug'    => $slug,
                'message' => $e->getMessage(),
            ]);

            return array_merge($errors, ['No se pudo inspeccionar la clase principal.']);
        }

        return $errors;
    }

    public static function captureBasePermissions(): void
    {
        if (self::$basePermissions !== null) {
            return;
        }

        $config = config('AuthGroups');
        $perms  = (is_object($config) && isset($config->permissions) && is_array($config->permissions))
            ? $config->permissions
            : [];

        self::$basePermissions = $perms;
    }

    /**
     * @return array<string, mixed>
     */
    private static function basePermissions(): array
    {
        if (self::$basePermissions === null) {
            self::captureBasePermissions();
        }

        return self::$basePermissions ?? [];
    }

    /**
     * @return list<string>
     */
    public static function permissionErrors(array $manifest, bool $checkNeighbors = true): array
    {
        $slug = (string) ($manifest['slug'] ?? '');

        $permissions = $manifest['permissions'] ?? [];
        if (! is_array($permissions)) {
            return ['permissions debe ser un objeto permiso => descripción.'];
        }

        $catalog = self::basePermissions();

        $reserved = [];
        $matrix   = setting('AuthGroups.matrix');
        if (is_array($matrix)) {
            foreach ($matrix as $grants) {
                if (! is_array($grants)) {
                    continue;
                }
                foreach ($grants as $grant) {
                    if (is_string($grant) && str_ends_with($grant, '.*')) {
                        $reserved[] = substr($grant, 0, -2);
                    }
                }
            }
        }
        $reserved = array_values(array_unique($reserved));

        $errors = [];
        foreach ($permissions as $permission => $description) {
            $permission = (string) $permission;
            if (preg_match('/^[a-z][a-z0-9]*(\.[a-z0-9][a-z0-9_-]*)+$/', $permission) !== 1 || strlen($permission) > 150) {
                $errors[] = "Permiso '{$permission}' con formato no permitido.";
                continue;
            }

            $prefix = strstr($permission, '.', true);
            if ($prefix !== false && in_array($prefix, $reserved, true)) {
                $errors[] = "El permiso '{$permission}' usa un prefijo que el ERP ya concede por comodín.";
                continue;
            }

            if (array_key_exists($permission, $catalog)) {
                $errors[] = "El permiso '{$permission}' ya existe en el ERP.";
                continue;
            }

            $owner = self::permissionOwner($permission, $slug, $checkNeighbors);
            if ($owner !== null) {
                $errors[] = "El permiso '{$permission}' ya lo declara el plugin '{$owner}'.";
                continue;
            }

            if (! is_string($description) || $description === '' || strlen($description) > 255) {
                $errors[] = "La descripción de '{$permission}' es obligatoria y de máximo 255 caracteres.";
            }
        }

        return $errors;
    }

    /**
     * @return array{path: string, manifest: array}|null
     */
    public static function findOnDisk(string $slug): ?array
    {
        if (! self::isSlug($slug)) {
            return null;
        }

        foreach (self::onDiskManifests() as $entry) {
            if (($entry['manifest']['slug'] ?? '') === $slug) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return list<array{path: string, manifest: array, raw: mixed, readable: bool}>
     */
    public static function onDiskManifests(): array
    {
        if (self::$onDiskCache !== null) {
            return self::$onDiskCache;
        }

        $entries = [];
        $root    = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'plugins';

        foreach (glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            $file = $dir . DIRECTORY_SEPARATOR . 'plugin.json';
            if (! is_file($file)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($file), true);
            $entries[] = [
                'path'     => $dir,
                'manifest' => is_array($decoded) ? $decoded : [],
                'raw'      => $decoded,
                'readable' => is_array($decoded),
            ];
        }

        return self::$onDiskCache = $entries;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function instantiateRow(array $row): PluginInterface
    {
        $path = self::directoryPath((string) ($row['class_dir'] ?? ''));
        if ($path === null || ! is_file($path . DIRECTORY_SEPARATOR . 'plugin.json')) {
            throw new RuntimeException('Carpeta del plugin no disponible.');
        }

        $manifest = json_decode((string) file_get_contents($path . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if (! is_array($manifest)) {
            throw new RuntimeException('plugin.json ilegible.');
        }

        if (($manifest['class'] ?? '') !== ($row['main_class'] ?? null)) {
            throw new RuntimeException('main_class no coincide con el manifiesto.');
        }

        $errors = self::manifestErrors($manifest, $path, false);
        if ($errors !== []) {
            throw new RuntimeException($errors[0]);
        }

        return self::instantiateManifest($manifest, $path);
    }

    private static function instantiateManifest(array $manifest, string $path): PluginInterface
    {
        $class = (string) $manifest['class'];
        $reflection = new ReflectionClass($class);

        return $reflection->newInstance($path);
    }

    private static function installedRow(string $slug): ?array
    {
        if (! self::isSlug($slug)) {
            return null;
        }

        $row = db_connect()->table('sys_plugins')->where('slug', $slug)->get()->getRowArray();

        return $row ?: null;
    }

    private static function setStatus(string $slug, string $status): bool
    {
        if (! self::isSlug($slug)) {
            return false;
        }

        $db  = db_connect();
        $row = $db->table('sys_plugins')->where('slug', $slug)->get()->getRowArray();
        if (! $row) {
            return false;
        }

        return $db->table('sys_plugins')->where('slug', $slug)->update([
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return list<string>
     */
    private static function activeDependents(string $slug): array
    {
        $names = [];
        foreach (self::installedRows() as $row) {
            if ((string) ($row['slug'] ?? '') === $slug || ($row['status'] ?? '') !== 'active') {
                continue;
            }

            $path = self::directoryPath((string) ($row['class_dir'] ?? ''));
            if ($path === null || ! is_file($path . DIRECTORY_SEPARATOR . 'plugin.json')) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($path . DIRECTORY_SEPARATOR . 'plugin.json'), true);
            $requires = is_array($manifest) ? ($manifest['requires'] ?? []) : [];
            if (is_array($requires) && in_array($slug, $requires, true)) {
                $names[] = (string) ($row['name'] ?? $row['slug']);
            }
        }

        return $names;
    }

    private static function permissionOwner(string $permission, string $exceptSlug, bool $checkDisk): ?string
    {
        if ($checkDisk) {
            foreach (self::onDiskManifests() as $entry) {
                $otherSlug = (string) ($entry['manifest']['slug'] ?? '');
                if ($otherSlug === '' || $otherSlug === $exceptSlug || ! $entry['readable']) {
                    continue;
                }

                $declared = $entry['manifest']['permissions'] ?? [];
                if (is_array($declared) && array_key_exists($permission, $declared)) {
                    return $otherSlug;
                }
            }
        }

        try {
            $db = db_connect();
            if (! $db->tableExists('sys_plugin_permissions')) {
                return null;
            }

            $row = $db->table('sys_plugin_permissions')
                ->where('permission', $permission)
                ->where('plugin_slug !=', $exceptSlug)
                ->get()
                ->getRowArray();

            if ($row) {
                return (string) $row['plugin_slug'];
            }
        } catch (Throwable) {
        }

        return null;
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return list<string>
     */
    private static function settingOptionValues(array $definition): array
    {
        $options = $definition['options'] ?? [];
        if (! is_array($options)) {
            return [];
        }

        if (array_is_list($options)) {
            return array_map('strval', array_values($options));
        }

        return array_map('strval', array_keys($options));
    }

    private static function directoryPath(string $directory): ?string
    {
        if (preg_match('/^[A-Z][A-Za-z0-9]+$/', $directory) !== 1) {
            return null;
        }

        $root = realpath(ROOTPATH . 'plugins');
        $path = realpath(ROOTPATH . 'plugins' . DIRECTORY_SEPARATOR . $directory);
        if ($root === false || $path === false || ! is_dir($path) || dirname($path) !== $root) {
            return null;
        }

        return $path;
    }

    private static function isSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1 && strlen($slug) <= 100;
    }

    private static function safeIcon(string $icon): string
    {
        return preg_match('/^fa-[a-z0-9-]+$/', $icon) === 1 ? $icon : 'fa-plug';
    }
}
