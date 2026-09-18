<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

/**
 * Expediente de archivos de OC (inbound_archive / outbound).
 */
class CreateFinPurchaseOrderDocuments extends Migration
{
    public function up()
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'fin_purchase_order_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'doc_channel' => [
                'type'       => 'ENUM',
                'constraint' => ['inbound_archive', 'outbound'],
            ],
            'doc_type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'supplier_invoice_pdf',
                    'supplier_invoice_xml',
                    'supplier_invoice_image',
                    'payment_proof',
                    'clarification',
                    'other',
                ],
            ],
            'original_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'stored_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'file_size' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'notes' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'uploaded_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('fin_purchase_order_id');
        $this->forge->addKey('doc_channel');
        $this->forge->addKey('doc_type');
        $this->forge->addForeignKey(
            'fin_purchase_order_id',
            'fin_purchase_orders',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->forge->addForeignKey(
            'uploaded_by',
            'users',
            'id',
            'SET NULL',
            'CASCADE'
        );
        $this->forge->createTable('fin_purchase_order_documents', true);

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('fin_purchase_order_documents', true);
        $this->db->enableForeignKeyChecks();
    }
}
