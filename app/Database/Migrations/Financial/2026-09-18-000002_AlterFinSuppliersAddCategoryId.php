<?php

namespace App\Database\Migrations\Financial;

use CodeIgniter\Database\Migration;

/**
 * Reemplaza fin_suppliers.supplier_type (ENUM) por category_id (FK).
 *
 * Orden estricto:
 * 1. Añadir category_id nullable
 * 2. Migrar datos string → FK
 * 3. Añadir FK
 * 4. Eliminar supplier_type
 *
 * down() revierte en orden inverso.
 */
class AlterFinSuppliersAddCategoryId extends Migration
{
    public function up()
    {
        // 1. Añadir columna category_id nullable
        $this->forge->addColumn('fin_suppliers', [
            'category_id' => ['type' => 'INT', 'null' => true, 'after' => 'supplier_type'],
        ]);

        // 2. Migrar datos: mapear supplier_type string → category_id
        $this->db->query("
            UPDATE fin_suppliers fs
            JOIN fin_supplier_categories fsc ON (
                (fs.supplier_type = 'infrastructure' AND fsc.name = 'Infraestructura') OR
                (fs.supplier_type = 'hardware'       AND fsc.name = 'Hardware')        OR
                (fs.supplier_type = 'services'       AND fsc.name = 'Servicios')       OR
                (fs.supplier_type = 'administrative' AND fsc.name = 'Administrativo')
            )
            SET fs.category_id = fsc.id
        ");

        // 3. Añadir FK (addForeignKey + processIndexes no aplica a tablas existentes)
        $this->db->query(
            'ALTER TABLE fin_suppliers
             ADD CONSTRAINT fk_fin_suppliers_category_id
             FOREIGN KEY (category_id) REFERENCES fin_supplier_categories(id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );

        // 4. Eliminar columna antigua
        $this->forge->dropColumn('fin_suppliers', 'supplier_type');
    }

    public function down()
    {
        // 1. Restaurar supplier_type ENUM
        $this->forge->addColumn('fin_suppliers', [
            'supplier_type' => [
                'type'       => 'ENUM',
                'constraint' => ['infrastructure', 'hardware', 'services', 'administrative'],
                'default'    => 'services',
                'after'      => 'id',
            ],
        ]);

        // 2. Mapear de regreso category_id → supplier_type
        $this->db->query("
            UPDATE fin_suppliers fs
            LEFT JOIN fin_supplier_categories fsc ON fsc.id = fs.category_id
            SET fs.supplier_type = CASE fsc.name
                WHEN 'Infraestructura' THEN 'infrastructure'
                WHEN 'Hardware'        THEN 'hardware'
                WHEN 'Servicios'       THEN 'services'
                WHEN 'Administrativo'  THEN 'administrative'
                ELSE 'services'
            END
        ");

        // 3. Eliminar FK
        $this->db->query('ALTER TABLE fin_suppliers DROP FOREIGN KEY fk_fin_suppliers_category_id');

        // 4. Eliminar columna category_id
        $this->forge->dropColumn('fin_suppliers', 'category_id');
    }
}
