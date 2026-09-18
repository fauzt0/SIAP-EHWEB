<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

/**
 * Historial de correos enviados desde una OC + pivot con documentos outbound.
 */
class CreateFinPurchaseOrderEmailLogs extends Migration
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
            'email_kind' => [
                'type'       => 'ENUM',
                'constraint' => ['send_po', 'reply_with_attachments'],
            ],
            'to_emails' => [
                'type' => 'TEXT',
            ],
            'cc_emails' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'body_html' => [
                'type' => 'TEXT',
            ],
            'attached_po_pdf' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'smtp_status' => [
                'type'       => 'ENUM',
                'constraint' => ['sent', 'failed'],
                'default'    => 'sent',
            ],
            'smtp_error' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'sent_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey('email_kind');
        $this->forge->addForeignKey(
            'fin_purchase_order_id',
            'fin_purchase_orders',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->forge->addForeignKey(
            'sent_by',
            'users',
            'id',
            'SET NULL',
            'CASCADE'
        );
        $this->forge->createTable('fin_purchase_order_email_logs', true);

        // Pivot: documentos outbound adjuntos al correo
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'email_log_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'document_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('document_id');
        $this->forge->addUniqueKey(['email_log_id', 'document_id']);
        $this->forge->addForeignKey(
            'email_log_id',
            'fin_purchase_order_email_logs',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->forge->addForeignKey(
            'document_id',
            'fin_purchase_order_documents',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->forge->createTable('fin_po_email_log_documents', true);

        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('fin_po_email_log_documents', true);
        $this->forge->dropTable('fin_purchase_order_email_logs', true);
        $this->db->enableForeignKeyChecks();
    }
}
