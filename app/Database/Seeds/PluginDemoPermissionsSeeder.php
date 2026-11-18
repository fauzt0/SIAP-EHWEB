<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Libraries\PluginSystem\PluginManager;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

/**
 * Permisos de plugins de referencia tras `plugin:action install notas-internas`.
 * Idempotente: addPermission es acumulativo en Shield.
 */
class PluginDemoPermissionsSeeder extends Seeder
{
    private const DEMO_PERMISSION = 'notasinternas.access';

    /** @var list<string> */
    private array $emails = [
        'superadmin@especialistashosting.com',
        'admin@especialistashosting.com',
    ];

    public function run(): void
    {
        $active = $this->db->table('sys_plugins')
            ->where('slug', 'notas-internas')
            ->where('status', 'active')
            ->countAllResults();

        if ($active === 0) {
            echo "PluginDemoPermissions: notas-internas no está activo; instala el plugin antes.\n";

            return;
        }

        // En CLI, pre_system no fusiona permisos de plugins; alinear con la UI (Events.php).
        $authGroups = config('AuthGroups');
        if ($authGroups !== null) {
            $authGroups->permissions = PluginManager::permissionCatalog();
        }

        $provider = auth()->getProvider();

        foreach ($this->emails as $email) {
            /** @var User|null $user */
            $user = $provider->findByCredentials(['email' => $email]);

            if ($user === null) {
                echo "PluginDemoPermissions: usuario {$email} no encontrado; se omite.\n";

                continue;
            }

            $user->addPermission(self::DEMO_PERMISSION);
            echo "PluginDemoPermissions: " . self::DEMO_PERMISSION . " → {$email}\n";
        }
    }
}