<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function () {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        Services::toolbar()->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            Services::routes()->get('__hot-reload', static function () {
                (new HotReloader())->run();
            });
        }
    }
});

/*
 * --------------------------------------------------------------------
 * Bitácora de Actividad / Shield Events
 * --------------------------------------------------------------------
 * Se interceptan los inicios y cierres de sesión para grabarlos
 * en la base de datos inmediatamente.
 */
Events::on('login', static function ($user) {
    if ($user && isset($user->id)) {
        $logModel = new \App\Models\Users\UserActivityLogsModel();
        // Usamos la app para identificar si fue un success login de email/pwd, magic link, etc (opcional)
        $logModel->logActivity('login', 'El usuario inició sesión exitosamente en el sistema.', $user->id);
    }
});

Events::on('logout', static function ($user) {
    if ($user && isset($user->id)) {
        $logModel = new \App\Models\Users\UserActivityLogsModel();
        $logModel->logActivity('logout', 'El usuario cerró su sesión.', $user->id);
    }
});

Events::on('failedLogin', static function ($credentials) {
    $logModel = new \App\Models\Users\UserActivityLogsModel();
    
    // Extraemos el correo intentado
    $emailAttempt = $credentials['email'] ?? 'Desconocido';
    $userId = null;
    
    // Intentamos buscar si ese correo pertenece a un usuario real en la base de datos
    // para enlazar este "intento fallido" al perfil del usuario
    if ($emailAttempt !== 'Desconocido') {
        $usersProvider = auth()->getProvider();
        $user = $usersProvider->findByCredentials(['email' => $emailAttempt]);
        if ($user) {
            $userId = $user->id;
        }
    }

    $logModel->logActivity(
        'failed_login', 
        "Intento fallido de inicio de sesión. Correo usado: {$emailAttempt}.", 
        $userId
    );
});

// Plugin System: carga de plugins activos en cada request web.
// php spark no dispara pre_system; loadRoutes() arranca los plugins en CLI.
Events::on('pre_system', static function () {
    if (! class_exists(\App\Libraries\PluginSystem\PluginManager::class)) {
        return;
    }

    // La foto del catálogo tiene que tomarse ANTES de fusionar: si no, boot
    // cree que notasinternas.access ya es un permiso del ERP y no carga el plugin.
    \App\Libraries\PluginSystem\PluginManager::captureBasePermissions();

    // Shield syncPermissions()/addPermission() solo aceptan claves de setting('AuthGroups.permissions').
    // La UI usa permissionCatalog() (ERP + sys_plugin_permissions); hay que alinear la config en runtime.
    try {
        $authGroups = config('AuthGroups');
        if ($authGroups !== null) {
            $authGroups->permissions = \App\Libraries\PluginSystem\PluginManager::permissionCatalog();
        }
    } catch (\Throwable $e) {
        log_message('error', '[PluginSystem] No se fusionaron permisos de plugins en AuthGroups: {message}', [
            'message' => $e->getMessage(),
        ]);
    }

    \App\Libraries\PluginSystem\PluginManager::bootActivePlugins();
});
