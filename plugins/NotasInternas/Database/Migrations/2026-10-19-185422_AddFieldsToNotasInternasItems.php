<?php
declare(strict_types=1);

namespace Plugins\NotasInternas\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddFieldsToNotasInternasItems extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('plg_notasinternas_items', [
            'user_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
                'after'      => 'title',
            ],
            'body' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'user_id',
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'default'    => '#fff9c4',
                'after'      => 'body',
            ],
            'is_pinned' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'color',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'created_at',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE `plg_notasinternas_items` '
            . 'ADD INDEX `idx_plg_notasinternas_user_list` (`user_id`, `is_pinned`, `updated_at`)'
        );
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `plg_notasinternas_items` DROP INDEX `idx_plg_notasinternas_user_list`');

        $this->forge->dropColumn('plg_notasinternas_items', [
            'user_id',
            'body',
            'color',
            'is_pinned',
            'updated_at',
        ]);
    }
}