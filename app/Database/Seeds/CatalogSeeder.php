<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\Catalog\CatalogProductModel;
use App\Models\Catalog\CatalogPlanModel;

class CatalogSeeder extends Seeder
{
    public function run()
    {
        $productModel = new CatalogProductModel();
        $planModel = new CatalogPlanModel();

        // Categoría ID: 1 (Hosting Compartido Básico)
        $categoryId = 1;

        $products = [
            [
                'product' => [
                    'category_id'       => $categoryId,
                    'sku'               => 'HOST-BASIC',
                    'commercial_name'   => 'Plan Básico',
                    'internal_name'     => 'Hosting Linux Básico',
                    'description_short' => 'Ideal para un sitio micro con necesidades básicas.',
                    'product_type'      => 'service',
                    'active'            => 1,
                ],
                'attributes' => [
                    ['attr_key' => 'Almacenamiento', 'attr_value' => '3 GB', 'is_highlight' => 1],
                    ['attr_key' => 'Dominio a alojar', 'attr_value' => '1', 'is_highlight' => 1],
                    ['attr_key' => 'Cuentas de correo', 'attr_value' => '10', 'is_highlight' => 1],
                    ['attr_key' => 'Transferencia mensual', 'attr_value' => '60 GB', 'is_highlight' => 1],
                    ['attr_key' => 'Soporte técnico', 'attr_value' => 'Online', 'is_highlight' => 0],
                    ['attr_key' => 'Panel de control', 'attr_value' => 'DirectAdmin', 'is_highlight' => 0],
                ],
                'plan' => [
                    'plan_name'     => 'Mensual',
                    'billing_cycle' => 'monthly',
                    'sale_price'    => 18.90,
                    'renewal_price' => 18.90,
                    'is_active'     => 1
                ]
            ],
            [
                'product' => [
                    'category_id'       => $categoryId,
                    'sku'               => 'HOST-PERSONAL',
                    'commercial_name'   => 'Plan Personal',
                    'internal_name'     => 'Hosting Linux Personal',
                    'description_short' => 'Ideal para Iniciar un Proyecto Personal o Idea de Negocio.',
                    'product_type'      => 'service',
                    'active'            => 1,
                ],
                'attributes' => [
                    ['attr_key' => 'Almacenamiento', 'attr_value' => '10 GB', 'is_highlight' => 1],
                    ['attr_key' => 'Dominio a alojar', 'attr_value' => '1', 'is_highlight' => 1],
                    ['attr_key' => 'Dominios Puntero', 'attr_value' => '6', 'is_highlight' => 0],
                    ['attr_key' => 'Subdominios', 'attr_value' => '20', 'is_highlight' => 0],
                    ['attr_key' => 'Cuentas de correo', 'attr_value' => '50', 'is_highlight' => 1],
                    ['attr_key' => 'Transferencia mensual', 'attr_value' => 'Ilimitada', 'is_highlight' => 1],
                    ['attr_key' => 'Soporte técnico', 'attr_value' => 'Online y Telefónico', 'is_highlight' => 0],
                    ['attr_key' => 'Constructor de sitios', 'attr_value' => 'Incluido', 'is_highlight' => 0],
                    ['attr_key' => 'Certificado SSL', 'attr_value' => 'Gratis', 'is_highlight' => 1],
                ],
                'plan' => [
                    'plan_name'     => 'Mensual',
                    'billing_cycle' => 'monthly',
                    'sale_price'    => 24.90,
                    'renewal_price' => 24.90,
                    'is_active'     => 1
                ]
            ],
            [
                'product' => [
                    'category_id'       => $categoryId,
                    'sku'               => 'HOST-EMPRESAS',
                    'commercial_name'   => 'Plan Empresas',
                    'internal_name'     => 'Hosting Linux Corporativo',
                    'description_short' => 'Ideal para Empresas que Buscan Estabilidad.',
                    'product_type'      => 'service',
                    'active'            => 1,
                ],
                'attributes' => [
                    ['attr_key' => 'Almacenamiento', 'attr_value' => '30 GB', 'is_highlight' => 1],
                    ['attr_key' => 'Dominio a alojar', 'attr_value' => '6', 'is_highlight' => 1],
                    ['attr_key' => 'Cuentas de correo', 'attr_value' => 'Ilimitadas', 'is_highlight' => 1],
                    ['attr_key' => 'Transferencia mensual', 'attr_value' => 'Ilimitada', 'is_highlight' => 1],
                    ['attr_key' => 'Dominios Puntero', 'attr_value' => '36', 'is_highlight' => 0],
                    ['attr_key' => 'Subdominios', 'attr_value' => '50', 'is_highlight' => 0],
                    ['attr_key' => 'Asesoría de marketing', 'attr_value' => 'Incluida', 'is_highlight' => 0],
                ],
                'plan' => [
                    'plan_name'     => 'Mensual',
                    'billing_cycle' => 'monthly',
                    'sale_price'    => 115.90,
                    'renewal_price' => 115.90,
                    'is_active'     => 1
                ]
            ]
        ];

        foreach ($products as $p) {
            // Usamos el método del modelo que ya maneja la transacción y atributos
            $productId = $productModel->createProductWithAttributes($p['product'], $p['attributes']);
            
            if ($productId) {
                // Insertar el plan
                $planData = $p['plan'];
                $planData['product_id'] = $productId;
                $planModel->insert($planData);
            }
        }
    }
}
