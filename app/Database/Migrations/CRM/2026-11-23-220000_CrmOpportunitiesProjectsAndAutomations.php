<?php

namespace App\Database\Migrations\CRM;

use CodeIgniter\Database\Migration;

/**
 * Oportunidades (embudos), motivos de pérdida, automatizaciones,
 * ingestión de leads y módulo de proyectos ligados a cliente/cotización.
 */
class CrmOpportunitiesProjectsAndAutomations extends Migration
{
    public function up(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->extendClients();
        $this->extendQuotes();
        $this->createPipelines();
        $this->createLostReasons();
        $this->createOpportunities();
        $this->createAutomations();
        $this->createLeadInbox();
        $this->createEmailTemplates();
        $this->createProjects();
        $this->seedCatalogs();
        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();
        foreach ([
            'prm_project_milestones',
            'prm_project_items',
            'prm_projects',
            'crm_email_templates',
            'crm_lead_inbox',
            'crm_automation_rules',
            'crm_opportunities',
            'crm_lost_reasons',
            'crm_pipeline_stages',
            'crm_pipelines',
        ] as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }
        $this->db->enableForeignKeyChecks();
    }

    private function extendClients(): void
    {
        if (! $this->db->tableExists('crm_clients')) {
            return;
        }
        $this->addColumnIfMissing('crm_clients', 'person_type', [
            'type'       => 'ENUM',
            'constraint' => ['company', 'person'],
            'default'    => 'company',
            'after'      => 'company_name',
        ]);
        $this->addColumnIfMissing('crm_clients', 'tags', [
            'type'       => 'VARCHAR',
            'constraint' => 255,
            'null'       => true,
            'after'      => 'source',
        ]);
        $this->addColumnIfMissing('crm_clients', 'custom_fields', [
            'type' => 'TEXT',
            'null' => true,
            'after'=> 'notes',
        ]);
        $this->addColumnIfMissing('crm_clients', 'lost_reason_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
        ]);
    }

    private function extendQuotes(): void
    {
        if (! $this->db->tableExists('sales_quotes')) {
            return;
        }
        $this->addColumnIfMissing('sales_quotes', 'approve_token', [
            'type'       => 'VARCHAR',
            'constraint' => 64,
            'null'       => true,
        ]);
        $this->addColumnIfMissing('sales_quotes', 'approved_at', [
            'type' => 'DATETIME',
            'null' => true,
        ]);
        $this->addColumnIfMissing('sales_quotes', 'opportunity_id', [
            'type'     => 'INT',
            'unsigned' => true,
            'null'     => true,
        ]);
        $this->addIndexIfMissing('sales_quotes', 'idx_quotes_approve_token', 'approve_token');
    }

    private function createPipelines(): void
    {
        if (! $this->db->tableExists('crm_pipelines')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'slug'       => ['type' => 'VARCHAR', 'constraint' => 40],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 80],
                'kind'       => ['type' => 'ENUM', 'constraint' => ['new_sales', 'renewal', 'upsell'], 'default' => 'new_sales'],
                'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'sort_order' => ['type' => 'TINYINT', 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
                'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('slug');
            $this->forge->createTable('crm_pipelines', true);
        }

        if (! $this->db->tableExists('crm_pipeline_stages')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'pipeline_id'     => ['type' => 'INT', 'unsigned' => true],
                'slug'            => ['type' => 'VARCHAR', 'constraint' => 40],
                'name'            => ['type' => 'VARCHAR', 'constraint' => 80],
                'win_probability' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
                'is_won'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'is_lost'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'sort_order'      => ['type' => 'TINYINT', 'default' => 0],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['pipeline_id', 'sort_order']);
            $this->forge->createTable('crm_pipeline_stages', true);
        }
    }

    private function createLostReasons(): void
    {
        if ($this->db->tableExists('crm_lost_reasons')) {
            return;
        }
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('crm_lost_reasons', true);
    }

    private function createOpportunities(): void
    {
        if ($this->db->tableExists('crm_opportunities')) {
            return;
        }
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'folio'            => ['type' => 'VARCHAR', 'constraint' => 32],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 180],
            'client_id'        => ['type' => 'INT', 'unsigned' => true],
            'pipeline_id'      => ['type' => 'INT', 'unsigned' => true],
            'stage_id'         => ['type' => 'INT', 'unsigned' => true],
            'assigned_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'quote_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'order_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'project_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'expected_value'   => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'probability'      => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'lost_reason_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'lost_notes'       => ['type' => 'TEXT', 'null' => true],
            'close_date'       => ['type' => 'DATE', 'null' => true],
            'next_followup_at' => ['type' => 'DATETIME', 'null' => true],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('folio');
        $this->forge->addKey(['pipeline_id', 'stage_id']);
        $this->forge->addKey('client_id');
        $this->forge->addKey('assigned_user_id');
        $this->forge->createTable('crm_opportunities', true);
    }

    private function createAutomations(): void
    {
        if ($this->db->tableExists('crm_automation_rules')) {
            return;
        }
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 120],
            'event'        => ['type' => 'VARCHAR', 'constraint' => 60],
            'action'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'payload_json' => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('crm_automation_rules', true);
    }

    private function createLeadInbox(): void
    {
        if ($this->db->tableExists('crm_lead_inbox')) {
            return;
        }
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'source'      => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'webhook'],
            'token'       => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'payload'     => ['type' => 'TEXT', 'null' => true],
            'client_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['received', 'imported', 'duplicate', 'error'], 'default' => 'received'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('crm_lead_inbox', true);
    }

    private function createEmailTemplates(): void
    {
        if ($this->db->tableExists('crm_email_templates')) {
            return;
        }
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'subject'    => ['type' => 'VARCHAR', 'constraint' => 180],
            'body'       => ['type' => 'TEXT'],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('crm_email_templates', true);
    }

    private function createProjects(): void
    {
        if (! $this->db->tableExists('prm_projects')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'code'             => ['type' => 'VARCHAR', 'constraint' => 32],
                'name'             => ['type' => 'VARCHAR', 'constraint' => 180],
                'client_id'        => ['type' => 'INT', 'unsigned' => true],
                'quote_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'order_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'opportunity_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'manager_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'status'           => ['type' => 'ENUM', 'constraint' => ['quoting', 'in_progress', 'delivery', 'completed', 'cancelled', 'on_hold'], 'default' => 'quoting'],
                'progress'         => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
                'start_date'       => ['type' => 'DATE', 'null' => true],
                'due_date'         => ['type' => 'DATE', 'null' => true],
                'delivered_at'     => ['type' => 'DATETIME', 'null' => true],
                'budget'           => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'notes'            => ['type' => 'TEXT', 'null' => true],
                'created_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->addKey(['client_id', 'status']);
            $this->forge->createTable('prm_projects', true);
        }

        if (! $this->db->tableExists('prm_project_items')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'project_id'    => ['type' => 'INT', 'unsigned' => true],
                'product_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'quote_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'order_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'item_name'     => ['type' => 'VARCHAR', 'constraint' => 255],
                'catalog_price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'unit_price'    => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'quantity'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 1],
                'billing_cycle' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'status'        => ['type' => 'ENUM', 'constraint' => ['pending', 'in_progress', 'delivered', 'cancelled'], 'default' => 'pending'],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('project_id');
            $this->forge->createTable('prm_project_items', true);
        }

        if (! $this->db->tableExists('prm_project_milestones')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'project_id'   => ['type' => 'INT', 'unsigned' => true],
                'name'         => ['type' => 'VARCHAR', 'constraint' => 180],
                'due_date'     => ['type' => 'DATE', 'null' => true],
                'status'       => ['type' => 'ENUM', 'constraint' => ['pending', 'completed'], 'default' => 'pending'],
                'sort_order'   => ['type' => 'TINYINT', 'default' => 0],
                'completed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('project_id');
            $this->forge->createTable('prm_project_milestones', true);
        }
    }

    private function seedCatalogs(): void
    {
        $now = date('Y-m-d H:i:s');
        if ($this->db->tableExists('crm_pipelines') && $this->db->table('crm_pipelines')->countAllResults() === 0) {
            $pipes = [
                ['slug' => 'ventas-nuevas', 'name' => 'Ventas nuevas', 'kind' => 'new_sales', 'is_default' => 1, 'sort_order' => 1],
                ['slug' => 'renovaciones', 'name' => 'Renovaciones', 'kind' => 'renewal', 'is_default' => 0, 'sort_order' => 2],
                ['slug' => 'upselling', 'name' => 'Upselling', 'kind' => 'upsell', 'is_default' => 0, 'sort_order' => 3],
            ];
            foreach ($pipes as $p) {
                $this->db->table('crm_pipelines')->insert($p + ['created_at' => $now, 'updated_at' => $now]);
            }
        }

        if ($this->db->tableExists('crm_pipeline_stages') && $this->db->table('crm_pipeline_stages')->countAllResults() === 0) {
            $pipes = $this->db->table('crm_pipelines')->get()->getResult();
            $stages = [
                ['slug' => 'new', 'name' => 'Nuevo lead', 'win_probability' => 10, 'sort_order' => 1],
                ['slug' => 'contacted', 'name' => 'Contactado', 'win_probability' => 20, 'sort_order' => 2],
                ['slug' => 'quoted', 'name' => 'Cotización enviada', 'win_probability' => 40, 'sort_order' => 3],
                ['slug' => 'negotiation', 'name' => 'Negociación', 'win_probability' => 60, 'sort_order' => 4],
                ['slug' => 'won', 'name' => 'Ganado', 'win_probability' => 100, 'sort_order' => 5, 'is_won' => 1],
                ['slug' => 'lost', 'name' => 'Perdido', 'win_probability' => 0, 'sort_order' => 6, 'is_lost' => 1],
            ];
            foreach ($pipes as $pipe) {
                foreach ($stages as $st) {
                    $this->db->table('crm_pipeline_stages')->insert($st + [
                        'pipeline_id' => (int) $pipe->id,
                        'is_won'      => $st['is_won'] ?? 0,
                        'is_lost'     => $st['is_lost'] ?? 0,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }
        }

        if ($this->db->tableExists('crm_lost_reasons') && $this->db->table('crm_lost_reasons')->countAllResults() === 0) {
            foreach ([
                ['slug' => 'precio', 'name' => 'Precio'],
                ['slug' => 'competencia', 'name' => 'Competencia'],
                ['slug' => 'tiempos', 'name' => 'Tiempos de entrega'],
                ['slug' => 'presupuesto', 'name' => 'Sin presupuesto'],
                ['slug' => 'no-respuesta', 'name' => 'Sin respuesta'],
            ] as $row) {
                $this->db->table('crm_lost_reasons')->insert($row + ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if ($this->db->tableExists('crm_automation_rules') && $this->db->table('crm_automation_rules')->countAllResults() === 0) {
            $this->db->table('crm_automation_rules')->insert([
                'name'         => 'Ganado crea proyecto y notifica',
                'event'        => 'opportunity.won',
                'action'       => 'create_project',
                'is_active'    => 1,
                'payload_json' => json_encode(['notify' => true], JSON_UNESCAPED_UNICODE),
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        if ($this->db->tableExists('crm_email_templates') && $this->db->table('crm_email_templates')->countAllResults() === 0) {
            $this->db->table('crm_email_templates')->insert([
                'slug'      => 'seguimiento-cotizacion',
                'name'      => 'Seguimiento de cotización',
                'subject'   => 'Cotización para {{empresa}}',
                'body'      => "Hola {{nombre_cliente}},\n\nLe compartimos la cotización para {{empresa}}.\n\nSaludos.",
                'is_active' => 1,
                'created_at'=> $now,
                'updated_at'=> $now,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function addColumnIfMissing(string $table, string $column, array $definition): void
    {
        if ($this->db->fieldExists($column, $table)) {
            return;
        }
        $this->forge->addColumn($table, [$column => $definition]);
    }

    private function addIndexIfMissing(string $table, string $indexName, string $column): void
    {
        $indexes = $this->db->getIndexData($table);
        foreach ($indexes as $idx) {
            if (strcasecmp((string) $idx->name, $indexName) === 0) {
                return;
            }
        }
        $this->db->query('ALTER TABLE `' . $table . '` ADD INDEX `' . $indexName . '` (`' . $column . '`)');
    }
}
