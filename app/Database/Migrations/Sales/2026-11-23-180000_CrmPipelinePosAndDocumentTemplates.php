<?php

namespace App\Database\Migrations\Sales;

use CodeIgniter\Database\Migration;

/**
 * Pipeline CRM (valor / próximo seguimiento), sucursal en documentos comerciales
 * y plantillas PDF editables de cotización / venta / recibo.
 */
class CrmPipelinePosAndDocumentTemplates extends Migration
{
    public function up(): void
    {
        $this->db->disableForeignKeyChecks();

        if ($this->db->tableExists('crm_clients')) {
            if (! $this->db->fieldExists('expected_value', 'crm_clients')) {
                $this->forge->addColumn('crm_clients', [
                    'expected_value' => [
                        'type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00, 'after' => 'source',
                    ],
                ]);
            }
            if (! $this->db->fieldExists('next_followup_at', 'crm_clients')) {
                $this->forge->addColumn('crm_clients', [
                    'next_followup_at' => ['type' => 'DATE', 'null' => true, 'after' => 'expected_value'],
                ]);
            }
        }

        foreach (['sales_quotes', 'sales_orders', 'sales_receipts'] as $table) {
            if ($this->db->tableExists($table) && ! $this->db->fieldExists('branch_id', $table)) {
                $this->forge->addColumn($table, [
                    'branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                ]);
            }
        }

        if ($this->db->tableExists('sales_quotes') && ! $this->db->fieldExists('channel', 'sales_quotes')) {
            $this->forge->addColumn('sales_quotes', [
                'channel' => [
                    'type' => 'ENUM', 'constraint' => ['online', 'pos', 'backend'], 'default' => 'backend',
                ],
            ]);
        }

        if (! $this->db->tableExists('sales_document_templates')) {
            $this->forge->addField([
                'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'doc_kind'          => ['type' => 'ENUM', 'constraint' => ['quote', 'order', 'receipt'], 'default' => 'receipt'],
                'title'             => ['type' => 'VARCHAR', 'constraint' => 120],
                'legal_note'        => ['type' => 'TEXT', 'null' => true],
                'header_extra_html' => ['type' => 'TEXT', 'null' => true],
                'footer_extra_html' => ['type' => 'TEXT', 'null' => true],
                'watermark_text'    => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'paper'             => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'Letter'],
                'show_logo'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'default_branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'active'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at'        => ['type' => 'DATETIME', 'null' => true],
                'updated_at'        => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('doc_kind');
            $this->forge->createTable('sales_document_templates', true);

            $now = date('Y-m-d H:i:s');
            $this->db->table('sales_document_templates')->insertBatch([
                [
                    'doc_kind'   => 'quote',
                    'title'      => 'Cotización',
                    'legal_note' => 'Documento informativo. Precios vigentes hasta la fecha indicada. No constituye una factura fiscal.',
                    'paper'      => 'Letter',
                    'show_logo'  => 1,
                    'active'     => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'doc_kind'   => 'order',
                    'title'      => 'Comprobante de venta',
                    'legal_note' => 'Este documento es un comprobante interno de la orden de venta. La factura CFDI se emite por separado.',
                    'paper'      => 'Letter',
                    'show_logo'  => 1,
                    'active'     => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'doc_kind'   => 'receipt',
                    'title'      => 'Recibo / prefactura',
                    'legal_note' => 'Recibo interno de cobranza. El timbrado CFDI se realiza cuando el conector fiscal está activo.',
                    'paper'      => 'Letter',
                    'show_logo'  => 1,
                    'active'     => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->forge->dropTable('sales_document_templates', true);
    }
}
