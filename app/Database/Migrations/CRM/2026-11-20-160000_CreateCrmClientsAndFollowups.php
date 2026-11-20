<?php

namespace App\Database\Migrations\CRM;

use CodeIgniter\Database\Migration;

/**
 * CRM: clientes empresariales, contactos, perfiles fiscales y bitácora de
 * seguimiento versionada (árbol, mismo patrón que hr_contracts).
 */
class CreateCrmClientsAndFollowups extends Migration
{
    public function up(): void
    {
        $this->db->disableForeignKeyChecks();

        if (! $this->db->tableExists('crm_clients')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'client_code'     => ['type' => 'VARCHAR', 'constraint' => 32],
                'company_name'    => ['type' => 'VARCHAR', 'constraint' => 255],
                'trade_name'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'rfc_tax_id'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'website'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'email'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'phone'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'status'          => ['type' => 'ENUM', 'constraint' => ['lead', 'prospect', 'active', 'inactive', 'churned'], 'default' => 'lead'],
                'source'          => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'assigned_user_id'=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'branch_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'notes'           => ['type' => 'TEXT', 'null' => true],
                'plugin_ref'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'active'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('client_code');
            $this->forge->addKey('status');
            $this->forge->addKey('rfc_tax_id');
            $this->forge->createTable('crm_clients', true);
        }

        if (! $this->db->tableExists('crm_client_contacts')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'client_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'contact_type' => ['type' => 'ENUM', 'constraint' => ['buyer', 'technical', 'billing', 'admin'], 'default' => 'buyer'],
                'name'         => ['type' => 'VARCHAR', 'constraint' => 150],
                'email'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'phone'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'is_primary'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'notes'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('client_id');
            $this->forge->addForeignKey('client_id', 'crm_clients', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('crm_client_contacts', true);
        }

        if (! $this->db->tableExists('crm_billing_profiles')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'client_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'legal_name'   => ['type' => 'VARCHAR', 'constraint' => 255],
                'rfc_tax_id'   => ['type' => 'VARCHAR', 'constraint' => 20],
                'tax_regime'   => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
                'postal_code'  => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
                'address'      => ['type' => 'TEXT', 'null' => true],
                'cfdi_use'     => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
                'is_default'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('client_id');
            $this->forge->addForeignKey('client_id', 'crm_clients', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('crm_billing_profiles', true);
        }

        if (! $this->db->tableExists('crm_followups')) {
            $this->forge->addField([
                'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'client_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'parent_followup_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'version'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
                'change_source'       => ['type' => 'ENUM', 'constraint' => ['auto', 'manual', 'fork'], 'default' => 'manual'],
                'status'              => ['type' => 'ENUM', 'constraint' => ['active', 'archived', 'draft'], 'default' => 'active'],
                'followup_type'       => ['type' => 'ENUM', 'constraint' => ['comment', 'status_change', 'meeting', 'call', 'email', 'quote', 'order', 'service'], 'default' => 'comment'],
                'title'               => ['type' => 'VARCHAR', 'constraint' => 180],
                'comment'             => ['type' => 'TEXT', 'null' => true],
                'client_status_snapshot' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'sales_order_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'sales_quote_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_by'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'          => ['type' => 'DATETIME', 'null' => true],
                'updated_at'          => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['client_id', 'version']);
            $this->forge->addForeignKey('client_id', 'crm_clients', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('crm_followups', true);
            $this->db->query('ALTER TABLE crm_followups ADD CONSTRAINT fk_crm_followups_parent FOREIGN KEY (parent_followup_id) REFERENCES crm_followups(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        if (! $this->db->tableExists('crm_followup_services')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'followup_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'product_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'plan_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'service_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'notes'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('followup_id');
            $this->forge->addForeignKey('followup_id', 'crm_followups', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('crm_followup_services', true);
        }

        if (! $this->db->tableExists('crm_plugin_links')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'client_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'sales_order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'service_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'plugin_slug'    => ['type' => 'VARCHAR', 'constraint' => 80],
                'external_ref'   => ['type' => 'VARCHAR', 'constraint' => 120],
                'meta_json'      => ['type' => 'TEXT', 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['plugin_slug', 'external_ref']);
            $this->forge->addForeignKey('client_id', 'crm_clients', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('crm_plugin_links', true);
        }

        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('crm_plugin_links', true);
        $this->forge->dropTable('crm_followup_services', true);
        $this->forge->dropTable('crm_followups', true);
        $this->forge->dropTable('crm_billing_profiles', true);
        $this->forge->dropTable('crm_client_contacts', true);
        $this->forge->dropTable('crm_clients', true);
        $this->db->enableForeignKeyChecks();
    }
}
