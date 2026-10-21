<?php

declare(strict_types=1);

namespace App\Libraries\PluginSystem;

use CodeIgniter\Shield\Entities\User;
use RuntimeException;

/**
 * Ciclo de vida por defecto. La configuración vive solo en sys_plugin_settings.
 */
abstract class BasePlugin implements PluginInterface
{
    protected array $manifest = [];

    protected ?array $config = null;

    protected string $pluginPath;

    public function __construct(string $pluginPath)
    {
        $this->pluginPath = rtrim($pluginPath, '/\\') . DIRECTORY_SEPARATOR;
        $manifestFile     = $this->pluginPath . 'plugin.json';

        if (! is_file($manifestFile)) {
            throw new RuntimeException('plugin.json no encontrado en el directorio del plugin.');
        }

        $manifest = json_decode((string) file_get_contents($manifestFile), true);

        if (! is_array($manifest) || empty($manifest['slug']) || empty($manifest['class']) || empty($manifest['directory'])) {
            throw new RuntimeException('plugin.json inválido: faltan slug, directory o class.');
        }

        $this->manifest = $manifest;
    }

    public function getManifest(): array
    {
        return $this->manifest;
    }

    public function getSlug(): string
    {
        return (string) $this->manifest['slug'];
    }

    public function getDirectory(): string
    {
        return (string) $this->manifest['directory'];
    }

    public function getNamespace(): string
    {
        return 'Plugins\\' . $this->getDirectory();
    }

    public function getPluginPath(): string
    {
        return $this->pluginPath;
    }

    public function getConfig(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $settings = [];
        foreach (($this->manifest['settings'] ?? []) as $key => $definition) {
            if (! is_array($definition)) {
                continue;
            }
            $settings[$key] = $definition['default'] ?? null;
        }

        $db = db_connect();
        if ($db->tableExists('sys_plugin_settings')) {
            $rows = $db->table('sys_plugin_settings')
                ->where('plugin_slug', $this->getSlug())
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $key = (string) ($row['key'] ?? '');
                if ($row['value'] === null) {
                    $settings[$key] = null;
                    continue;
                }

                $type = (string) (($this->manifest['settings'][$key]['type'] ?? 'text'));
                if ($type === 'password') {
                    $settings[$key] = PluginSecret::open((string) $row['value']);
                    continue;
                }
                if (in_array($type, ['text', 'number', 'select'], true)) {
                    $settings[$key] = $row['value'];
                    continue;
                }

                $decoded = json_decode((string) $row['value'], true);
                $settings[$key] = is_array($decoded) ? $decoded : $row['value'];
            }
        }

        return $this->config = $settings;
    }

    public function setConfig(array $config): bool
    {
        $allowed = $this->manifest['settings'] ?? [];
        if (! is_array($allowed)) {
            return false;
        }

        foreach (array_keys($config) as $key) {
            if (! is_string($key) || ! array_key_exists($key, $allowed)) {
                return false;
            }
        }

        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        foreach ($config as $key => $value) {
            $type = (string) (($allowed[$key]['type'] ?? 'text'));
            if ($type === 'password') {
                if (! is_scalar($value)) {
                    return false;
                }
                $stored = PluginSecret::seal((string) $value);
                if ($stored === null) {
                    log_message('error', '[PluginSystem] {slug}: ajuste password rechazado, falta encryption.key.', [
                        'slug' => $this->getSlug(),
                    ]);

                    return false;
                }
            } elseif (is_array($value)) {
                $stored = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif ($value === null) {
                $stored = null;
            } elseif (is_scalar($value)) {
                $stored = (string) $value;
            } else {
                return false;
            }

            if (is_string($stored) && strlen($stored) > 65000) {
                return false;
            }

            $exists = $db->table('sys_plugin_settings')
                ->where(['plugin_slug' => $this->getSlug(), 'key' => $key])
                ->countAllResults() > 0;

            if ($exists) {
                $db->table('sys_plugin_settings')
                    ->where(['plugin_slug' => $this->getSlug(), 'key' => $key])
                    ->update(['value' => $stored, 'updated_at' => $now]);
            } else {
                $db->table('sys_plugin_settings')->insert([
                    'plugin_slug' => $this->getSlug(),
                    'key'         => $key,
                    'value'       => $stored,
                    'updated_at'  => $now,
                ]);
            }
        }

        $this->config = null;

        return true;
    }

    /**
     * Descubre y corre solo las migraciones de este plugin.
     * El runner no es el servicio compartido (V12-2).
     */
    protected function runMigrations(): bool
    {
        $migrationsPath = $this->pluginPath . 'Database/Migrations';

        if (! is_dir($migrationsPath)) {
            return true;
        }

        $namespace = $this->getNamespace();

        if (! preg_match('/^Plugins\\\\[A-Z][A-Za-z0-9]+$/', $namespace)) {
            log_message('error', '[PluginSystem] {slug}: namespace de migraciones rechazado.', [
                'slug' => $this->getSlug(),
            ]);

            return false;
        }

        \Config\Services::autoloader()->addNamespace($namespace, $this->pluginPath);

        $runner = \Config\Services::migrations(null, null, false);
        $runner->setNamespace($namespace);

        if ($runner->findNamespaceMigrations($namespace) === []) {
            log_message('error', '[PluginSystem] {slug}: migraciones en disco no descubiertas.', [
                'slug' => $this->getSlug(),
            ]);

            return false;
        }

        return $runner->latest() !== false;
    }

    protected function removeMigrationHistory(): void
    {
        $this->forgetMigrationHistory();
    }

    public function forgetMigrationHistory(): void
    {
        $namespace = $this->getNamespace();

        if (! preg_match('/^Plugins\\\\[A-Z][A-Za-z0-9]+$/', $namespace)) {
            throw new RuntimeException('Namespace de plugin inseguro; no se limpia el historial.');
        }

        db_connect()->table('migrations')
            ->where('namespace', $namespace)
            ->delete();
    }

    public function install(): bool
    {
        if (! $this->runMigrations()) {
            return false;
        }

        PluginManager::registerPermissions($this->manifest);

        return true;
    }

    public function uninstall(): array
    {
        $backup = PluginBackupService::archive($this->manifest, $this->pluginPath);
        PluginManager::removePermissions($this->getSlug());
        $this->forgetMigrationHistory();

        return ['success' => true] + $backup;
    }

    public function activate(): void
    {
        $this->registerHooks();
    }

    public function deactivate(): void
    {
        // El siguiente request no lo arranca si status = inactive.
    }

    public function getSidebarItems(User $user): array
    {
        return [];
    }

    public function loadRoutes($routes): void
    {
        // Cada plugin carga su Config/Routes.php.
    }
}
