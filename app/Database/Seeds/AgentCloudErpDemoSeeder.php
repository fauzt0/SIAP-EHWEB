<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Datos demo ERP para agentes en la nube (proveedores, catálogo, marcas).
 * Idempotente: solo inserta si las tablas objetivo están vacías o faltan filas clave.
 */
class AgentCloudErpDemoSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->db->table('catalog_brands')->countAllResults() === 0) {
            $this->call(BrandSeeder::class);
            echo "AgentCloudErpDemo: marcas insertadas.\n";
        } else {
            echo "AgentCloudErpDemo: catalog_brands ya tiene datos; se omite BrandSeeder.\n";
        }

        $hasBasicPlan = $this->db->table('catalog_products')
            ->where('sku', 'HOST-BASIC')
            ->countAllResults() > 0;

        if (! $hasBasicPlan) {
            $this->call(CatalogSeeder::class);
            echo "AgentCloudErpDemo: catálogo demo insertado.\n";
        } else {
            echo "AgentCloudErpDemo: catálogo HOST-BASIC ya existe; se omite CatalogSeeder.\n";
        }

        $hasInterserver = $this->db->table('fin_suppliers')
            ->where('commercial_name', 'Interserver')
            ->countAllResults() > 0;

        if (! $hasInterserver) {
            $this->call(SupplierSeeder::class);
            echo "AgentCloudErpDemo: proveedores demo insertados.\n";
        } else {
            echo "AgentCloudErpDemo: proveedor Interserver ya existe; se omite SupplierSeeder.\n";
        }
    }
}