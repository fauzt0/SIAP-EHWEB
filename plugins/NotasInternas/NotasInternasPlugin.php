<?php

declare(strict_types=1);

namespace Plugins\NotasInternas;

use App\Libraries\PluginSystem\BasePlugin;
use CodeIgniter\Shield\Entities\User;

class NotasInternasPlugin extends BasePlugin
{
    public function registerHooks(): void
    {
    }

    public function getSidebarItems(User $user): array
    {
        if (! $user->can('notasinternas.access')) {
            return [];
        }

        return [
            [
                'section'       => 'Herramientas',
                'slug'          => 'notas-internas',
                'label'         => 'Notas internas',
                'icon'          => 'fa-sticky-note',
                'permission'    => 'notasinternas.access',
                'route'         => 'notasinternas.index',
                'route_pattern' => 'nat/plugins/notas-internas*',
            ],
        ];
    }

    public function loadRoutes($routes): void
    {
        $file = $this->pluginPath . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php';
        if (! is_file($file)) {
            return;
        }

        $callback = require $file;
        if (is_callable($callback)) {
            $callback($routes);

            return;
        }

        if (function_exists('notas_internas_declare_routes')) {
            notas_internas_declare_routes($routes);
        }
    }
}
