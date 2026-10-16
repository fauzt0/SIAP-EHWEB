<?php

declare(strict_types=1);

namespace App\Libraries\PluginSystem;

use CodeIgniter\Shield\Entities\User;

/**
 * Contrato v1 del núcleo de plugins.
 * La firma de uninstall() es array (C-1). No existe una variante bool.
 */
interface PluginInterface
{
    /** Corre migraciones y registra permisos. Una sola vez por instalación. */
    public function install(): bool;

    /**
     * Archiva datos, retira permisos y limpia el historial de migraciones.
     * El plugin tiene que estar inactive. Nunca hace DROP TABLE.
     *
     * @return array{success: bool, backup_id: ?int, backup_file: ?string, archived_tables: array}
     */
    public function uninstall(): array;

    public function activate(): void;

    public function deactivate(): void;

    /** Se llama en cada request mientras el plugin está active. */
    public function registerHooks(): void;

    public function getSidebarItems(User $user): array;

    /**
     * @param \CodeIgniter\Router\RouteCollection $routes
     */
    public function loadRoutes($routes): void;

    public function getConfig(): array;

    public function setConfig(array $config): bool;

    public function getManifest(): array;
}
