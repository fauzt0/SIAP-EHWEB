<?php

namespace App\Database\Migrations\HR;

use CodeIgniter\Database\Migration;

/**
 * CreateHrCatalogs
 * ─────────────────────────────────────────────────────────────────────────────
 * Crea las tablas de catálogo del módulo de Recursos Humanos:
 *   - hr_cat_departments    → Departamentos
 *   - hr_cat_jobs           → Puestos / Cargos
 *   - hr_cat_contract_types → Tipos de contrato
 *   - hr_cat_document_types → Tipos de documento de expediente
 *
 * NOTA: Las ubicaciones/sucursales NO tienen catálogo propio en HR.
 *       Se utiliza org_branches como fuente única de verdad.
 */
class CreateHrCatalogs extends Migration
{
    private array $commonFields;

    public function up(): void
    {
        $this->commonFields = [
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ];

        // ── 1. Departamentos ──────────────────────────────────────────────────
        $this->forge->addField($this->commonFields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_departments');

        // ── 2. Puestos / Cargos ───────────────────────────────────────────────
        $this->forge->addField($this->commonFields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_jobs');

        // ── 3. Tipos de Contrato ──────────────────────────────────────────────
        $this->forge->addField($this->commonFields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_contract_types');

        // ── 4. Tipos de Documento de Expediente ───────────────────────────────
        $this->forge->addField($this->commonFields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hr_cat_document_types');
    }

    public function down(): void
    {
        $this->forge->dropTable('hr_cat_departments',    true);
        $this->forge->dropTable('hr_cat_jobs',           true);
        $this->forge->dropTable('hr_cat_contract_types', true);
        $this->forge->dropTable('hr_cat_document_types', true);
    }
}
