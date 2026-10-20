<?php

declare(strict_types=1);

namespace Plugins\NotasInternas\Models;

use CodeIgniter\Model;

/**
 * Notas del plugin Notas internas (tabla plg_notasinternas_items).
 */
class NoteModel extends Model
{
    protected $table            = 'plg_notasinternas_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'title',
        'body',
        'color',
        'is_pinned',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $casts = [
        'id'        => 'integer',
        'user_id'   => 'integer',
        'is_pinned' => 'int-bool',
    ];

    /**
     * Notas de un usuario, orden Keep: fijadas primero, luego por última edición.
     *
     * @return list<array<string, mixed>>
     */
    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('is_pinned', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->findAll();
    }

    /**
     * Una nota solo si pertenece al usuario (defensa en profundidad en save/delete).
     */
    public function ownedBy(int $noteId, int $userId): ?array
    {
        $row = $this->where('id', $noteId)
            ->where('user_id', $userId)
            ->first();

        return is_array($row) ? $row : null;
    }
}
