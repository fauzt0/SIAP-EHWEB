<?php

declare(strict_types=1);

namespace Plugins\NotasInternas;

use App\Libraries\PluginSystem\BasePlugin;
use App\Libraries\PluginSystem\PluginHookRegistry;
use CodeIgniter\Shield\Entities\User;

class NotasInternasPlugin extends BasePlugin
{
    public function registerHooks(): void
    {
        PluginHookRegistry::addAction('layout.footer', [$this, 'renderQuickAccess']);
    }

    /**
     * Icono del menú superior, modal de lectura y notas fijadas en /nat/.
     * layout.footer es el hook v1 que corre en el layout de usuario logueado.
     */
    public function renderQuickAccess(): void
    {
        $user = auth()->user();
        if ($user === null || ! $user->can('notasinternas.access')) {
            return;
        }

        $home = site_url('nat');
        $homePath = parse_url($home, PHP_URL_PATH);
        $views = $this->pluginPath . 'Views';

        echo service('renderer', $views, null, false)
            ->setData([
                'niListUrl'  => (string) route_to('notasinternas.list_ajax'),
                'niSaveUrl'  => (string) route_to('notasinternas.save'),
                'niIndexUrl' => (string) route_to('notasinternas.index'),
                'niHomePath' => is_string($homePath) && $homePath !== '' ? $homePath : '/nat',
                'niToken'    => csrf_token(),
                'niHeader'   => csrf_header(),
            ], 'raw')
            ->render('quick_access');
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
