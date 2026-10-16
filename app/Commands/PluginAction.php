<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\PluginSystem\PluginManager;
use App\Models\Users\UserActivityLogsModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Ciclo de vida por CLI. El sandbox responde 401 antes del login de Shield.
 */
class PluginAction extends BaseCommand
{
    protected $group = 'Plugin';

    protected $name = 'plugin:action';

    protected $description = 'install, activate, deactivate, uninstall, restore, purge o update de un plugin.';

    protected $usage = 'plugin:action <accion> <slug|backup_id> [ELIMINAR]';

    protected $arguments = [
        'accion' => 'install | activate | deactivate | uninstall | restore | purge | update',
        'objetivo' => 'slug del plugin, o id del respaldo en restore/purge',
    ];

    public function run(array $params)
    {
        $action = (string) ($params[0] ?? '');
        $target = (string) ($params[1] ?? '');
        $extra  = (string) ($params[2] ?? '');

        $result = match ($action) {
            'install'    => PluginManager::install($target),
            'activate'   => $this->statusResult(PluginManager::activate($target), 'Plugin activado.', 'No se pudo activar.'),
            'deactivate' => $this->statusResult(PluginManager::deactivate($target), 'Plugin desactivado.', 'No se pudo desactivar.'),
            'uninstall'  => PluginManager::uninstall($target),
            'update'     => PluginManager::update($target),
            'restore'    => PluginManager::restore((int) $target),
            'purge'      => $extra === 'ELIMINAR'
                ? PluginManager::purge((int) $target)
                : ['success' => false, 'message' => 'La purga exige el argumento ELIMINAR.'],
            'settings'   => PluginManager::saveSettings($target, [$extra => ($params[3] ?? '')]),
            default      => ['success' => false, 'message' => 'Acción no reconocida. Usa install, activate, deactivate, uninstall, restore, purge, update o settings.'],
        };

        $ok = ! empty($result['success']);
        CLI::write((string) ($result['message'] ?? ($ok ? 'Listo.' : 'Falló.')), $ok ? 'green' : 'red');

        if ($ok) {
            $labels = [
                'install'    => 'plugin_install',
                'activate'   => 'plugin_activate',
                'deactivate' => 'plugin_deactivate',
                'uninstall'  => 'plugin_uninstall',
                'update'     => 'plugin_update',
                'restore'    => 'plugin_restore',
                'purge'      => 'plugin_purge',
                'settings'   => 'plugin_settings',
            ];
            if (isset($labels[$action])) {
                (new UserActivityLogsModel())->logActivity(
                    $labels[$action],
                    'CLI ' . $action . ' sobre plugin/respaldo ' . $target . '. ' . ($result['message'] ?? ''),
                    null
                );
            }
        }

        return $ok ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function statusResult(bool $ok, string $success, string $failure): array
    {
        return ['success' => $ok, 'message' => $ok ? $success : $failure];
    }
}
