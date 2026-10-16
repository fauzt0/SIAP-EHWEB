<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\PluginSystem\PluginBaseController;
use App\Libraries\Breadcrumb;
use App\Libraries\PluginSystem\PluginManager;
use App\Libraries\PluginSystem\PluginMarket;
use CodeIgniter\Exceptions\PageNotFoundException;

class PluginMarketController extends PluginBaseController
{
    public function index()
    {
        $this->assertPermission('admin.manage-plugins');
        $this->preparePage('Market de Plugins');

        $this->viewData['installed'] = PluginMarket::getInstalled();
        $this->viewData['available'] = PluginMarket::getAvailable();
        $this->viewData['backups']   = PluginManager::backupRows();
        $this->viewData['settings']  = null;

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Admin/plugins_market');
    }

    public function settings(string $slug)
    {
        $this->assertPermission('admin.manage-plugins');

        $settings = PluginManager::readSettings($slug);
        if ($settings === null) {
            throw PageNotFoundException::forPageNotFound('Plugin no instalado.');
        }

        $this->preparePage('Ajustes de ' . ($settings['manifest']['name'] ?? $slug));

        $this->viewData['installed'] = PluginMarket::getInstalled();
        $this->viewData['available'] = [];
        $this->viewData['backups']   = [];
        $this->viewData['settings']  = $settings;

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Admin/plugins_market');
    }

    public function install()
    {
        $this->assertAjaxPost();
        $this->assertPermission('admin.manage-plugins');

        $slug   = trim((string) $this->request->getPost('slug'));
        $result = PluginManager::install($slug);

        return $this->respond($result, 'plugin_install', "Instaló el plugin '{$slug}'.");
    }

    public function uninstall()
    {
        $this->assertAjaxPost();
        $this->assertPermission('admin.manage-plugins');

        if ((string) $this->request->getPost('confirm') !== '1') {
            return $this->respond(
                ['success' => false, 'message' => 'Confirma la desinstalación antes de continuar.'],
                '',
                ''
            );
        }

        $slug   = trim((string) $this->request->getPost('slug'));
        $result = PluginManager::uninstall($slug);

        return $this->respond($result, 'plugin_uninstall', "Desinstaló el plugin '{$slug}'. Datos archivados.");
    }

    public function activate()
    {
        $this->assertAjaxPost();
        $this->assertPermission('admin.manage-plugins');

        $slug = trim((string) $this->request->getPost('slug'));
        $ok   = PluginManager::activate($slug);

        return $this->respond(
            ['success' => $ok, 'message' => $ok ? 'Plugin activado.' : 'No se pudo activar el plugin.'],
            'plugin_activate',
            "Activó el plugin '{$slug}'."
        );
    }

    public function deactivate()
    {
        $this->assertAjaxPost();
        $this->assertPermission('admin.manage-plugins');

        $slug = trim((string) $this->request->getPost('slug'));
        $ok   = PluginManager::deactivate($slug);

        return $this->respond(
            ['success' => $ok, 'message' => $ok ? 'Plugin desactivado.' : 'No se pudo desactivar el plugin.'],
            'plugin_deactivate',
            "Desactivó el plugin '{$slug}'."
        );
    }

    public function restore()
    {
        $this->assertAjaxPost();
        $this->assertPermission('admin.manage-plugins');

        $backupId = (int) $this->request->getPost('backup_id');
        $result   = PluginManager::restore($backupId);

        return $this->respond($result, 'plugin_restore', "Restauró el respaldo de plugin #{$backupId}.");
    }

    public function purge()
    {
        $this->assertAjaxPost();
        $this->assertPermission('admin.manage-plugins');

        if ((string) $this->request->getPost('confirmation') !== 'ELIMINAR') {
            return $this->respond(
                ['success' => false, 'message' => 'Escribe ELIMINAR para confirmar la purga.'],
                '',
                ''
            );
        }

        $backupId = (int) $this->request->getPost('backup_id');
        $result   = PluginManager::purge($backupId);

        return $this->respond($result, 'plugin_purge', "Purgó definitivamente el respaldo de plugin #{$backupId}.");
    }

    public function update()
    {
        $this->assertAjaxPost();
        $this->assertPermission('admin.manage-plugins');

        $slug   = trim((string) $this->request->getPost('slug'));
        $result = PluginManager::update($slug);

        return $this->respond($result, 'plugin_update', "Actualizó el plugin '{$slug}'.");
    }

    public function settings_save(string $slug)
    {
        $this->assertAjaxPost();
        $this->assertPermission('admin.manage-plugins');

        $input  = $this->request->getPost('settings');
        $result = PluginManager::saveSettings($slug, is_array($input) ? $input : []);

        return $this->respond($result, 'plugin_settings', "Actualizó la configuración del plugin '{$slug}'.");
    }

    private function preparePage(string $title): void
    {
        $this->setViewSuccess($title);
        $this->setPageTittleAhead($title, 'Plugins');

        $breadcrumb = new Breadcrumb();
        $this->viewData['breadcrumb'] = $breadcrumb->getBreadCrumbHtml([
            'Inicio'        => route_to('dashboard.index'),
            'Administrador' => route_to('admin.plugins.index'),
            'Plugins'       => '',
        ]);
    }

    private function respond(array $result, string $logType, string $logMessage)
    {
        if (! empty($result['success'])) {
            if ($logType !== '') {
                $this->activityLogModel->logActivity($logType, $logMessage);
            }

            $payload = $this->setOutputSuccess((string) $result['message']);
        } else {
            $payload = $this->setOutputError((string) ($result['message'] ?? 'Operación no completada.'));
        }

        return $this->jsonWithCsrf(is_array($payload) ? $payload : []);
    }
}
