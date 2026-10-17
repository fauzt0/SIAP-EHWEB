<?php

declare(strict_types=1);

namespace Plugins\NotasInternas\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotasInternasItems extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('plg_notasinternas_items', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('plg_notasinternas_items', true);
    }
}
