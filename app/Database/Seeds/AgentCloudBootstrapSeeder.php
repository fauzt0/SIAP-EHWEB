<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Orquesta datos mínimos para un agente Cloud (vía C del runbook).
 *
 * Orden:
 * 1. Empresa + sucursales (InitialSeeder)
 * 2. Usuarios Shield de prueba (UserSeeder)
 * 3. Demo ERP opcional pero recomendado para proveedores/catálogo (AgentCloudErpDemoSeeder)
 * 4. Permisos del plugin Notas internas (PluginDemoPermissionsSeeder; requiere plugin activo)
 *
 * Uso:
 *   php spark migrate
 *   php spark plugin:action install notas-internas
 *   php spark db:seed App\\Database\\Seeds\\AgentCloudBootstrapSeeder
 */
class AgentCloudBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->db->table('org_company_profile')->countAllResults() === 0) {
            $this->call(InitialSeeder::class);
        } else {
            echo "AgentCloudBootstrap: org_company_profile ya poblado; se omite InitialSeeder.\n";
        }

        $this->call(UserSeeder::class);
        $this->call(AgentCloudErpDemoSeeder::class);
        $this->call(PluginDemoPermissionsSeeder::class);

        echo "AgentCloudBootstrap: terminado.\n";
    }
}