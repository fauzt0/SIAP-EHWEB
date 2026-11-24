<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Users\UserModel;

/**
 * Asigna el siguiente vendedor con permiso crm.manage de forma rotativa.
 */
class CrmRoundRobinService
{
    public function nextUserId(): ?int
    {
        $ids = $this->sellerIds();
        if ($ids === []) {
            return auth()->id() ? (int) auth()->id() : null;
        }

        $cacheFile = WRITEPATH . 'crm_round_robin.json';
        $last      = 0;
        if (is_file($cacheFile)) {
            $raw  = json_decode((string) file_get_contents($cacheFile), true);
            $last = (int) ($raw['last_user_id'] ?? 0);
        }

        $next = $ids[0];
        $found = false;
        foreach ($ids as $id) {
            if ($found) {
                $next = $id;
                break;
            }
            if ($id === $last) {
                $found = true;
            }
        }
        if (! $found) {
            $next = $ids[0];
        } elseif ($last === $ids[array_key_last($ids)]) {
            $next = $ids[0];
        }

        @file_put_contents($cacheFile, json_encode(['last_user_id' => $next]));

        return $next;
    }

    /**
     * @return list<int>
     */
    private function sellerIds(): array
    {
        $users = (new UserModel())->getAssignableUsers();
        $ids   = [];
        foreach ($users as $user) {
            $id = (int) $user->id;
            if ($id < 1) {
                continue;
            }
            try {
                $entity = auth()->getProvider()->findById($id);
                if ($entity && ($entity->can('crm.manage') || $entity->can('sales.create'))) {
                    $ids[] = $id;
                }
            } catch (\Throwable $e) {
                $ids[] = $id;
            }
        }
        sort($ids);

        return array_values(array_unique($ids));
    }
}
