<?php

namespace App\Database\Migrations\Accounting;

use CodeIgniter\Database\Migration;

/**
 * Vincula cada orden de compra a la sucursal que recibe / factura el gasto.
 */
class AddOrgBranchIdToFinPurchaseOrders extends Migration
{
    public function up()
    {
        $this->forge->addColumn('fin_purchase_orders', [
            'org_branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'fin_supplier_id',
            ],
        ]);

        $mainBranch = $this->db->table('org_branches')
            ->select('id')
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('is_main', 'DESC')
            ->orderBy('id', 'ASC')
            ->get(1)
            ->getRow();

        if ($mainBranch) {
            $this->db->table('fin_purchase_orders')
                ->where('org_branch_id', null)
                ->update(['org_branch_id' => (int) $mainBranch->id]);
        }

        $this->db->query(
            'ALTER TABLE fin_purchase_orders MODIFY org_branch_id INT(11) UNSIGNED NOT NULL'
        );

        $this->db->query(
            'ALTER TABLE fin_purchase_orders ADD CONSTRAINT fk_fin_po_org_branch '
            . 'FOREIGN KEY (org_branch_id) REFERENCES org_branches(id) '
            . 'ON DELETE RESTRICT ON UPDATE CASCADE'
        );

        $this->forge->addKey('org_branch_id');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE fin_purchase_orders DROP FOREIGN KEY fk_fin_po_org_branch');
        $this->forge->dropColumn('fin_purchase_orders', 'org_branch_id');
    }
}
